<?php
/**
 * Cron za sinkronizaciju Gmail poruka u lokalnu bazu
 * Pokreni jednom za inicijalni sync, zatim svakih 5 min za nove poruke
 *
 * php cron/sync-gmail.php        - dohvati nove (zadnja 3 dana)
 * php cron/sync-gmail.php full   - dohvati više (zadnjih 30 dana)
 */

if (php_sapi_name() !== 'cli' && !defined('CRON_RUN')) {
    die('Samo za CLI/cron');
}

require_once __DIR__ . '/../GmailClient.php';
require_once __DIR__ . '/../config/database.php';

$db = getDB();
$gmail = new GmailClient();

if (!$gmail->isAuthorized()) {
    die("Gmail nije autoriziran. Idi na gmail-auth.php\n");
}

// Provjeri/kreiraj tablice
$db->exec("
    CREATE TABLE IF NOT EXISTS gmail_messages_cache (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id VARCHAR(100) NOT NULL UNIQUE,
        thread_id VARCHAR(100),
        from_email VARCHAR(255),
        from_name VARCHAR(255),
        to_email VARCHAR(255),
        subject VARCHAR(500),
        snippet TEXT,
        body_text MEDIUMTEXT,
        labels VARCHAR(255),
        is_unread TINYINT(1) DEFAULT 0,
        has_attachments TINYINT(1) DEFAULT 0,
        received_at DATETIME,
        synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_received (received_at),
        INDEX idx_from (from_email),
        INDEX idx_unread (is_unread)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$db->exec("
    CREATE TABLE IF NOT EXISTS gmail_attachments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id VARCHAR(100) NOT NULL,
        attachment_id VARCHAR(255) NOT NULL,
        filename VARCHAR(255),
        mime_type VARCHAR(100),
        size INT,
        downloaded TINYINT(1) DEFAULT 0,
        local_path VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_message (message_id),
        UNIQUE KEY unique_att (message_id, attachment_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Odredi koliko dana unatrag
$mode = $argv[1] ?? 'recent';
if ($mode === 'full') {
    $days = 30;
    $maxMessages = 500;
} else {
    $days = 3;
    $maxMessages = 100;
}

$afterDate = date('Y/m/d', strtotime("-{$days} days"));
echo "Dohvaćam poruke od {$afterDate}...\n";

try {
    $params = [
        'maxResults' => $maxMessages,
        'q' => "after:{$afterDate}"
    ];

    $list = $gmail->listMessages($params);
    $total = count($list['messages'] ?? []);
    echo "Pronađeno {$total} poruka\n";

    $new = 0;
    $updated = 0;

    foreach ($list['messages'] ?? [] as $i => $msgRef) {
        $messageId = $msgRef['id'];

        // Provjeri postoji li već
        $stmt = $db->prepare("SELECT id, is_unread FROM gmail_messages_cache WHERE message_id = ?");
        $stmt->execute([$messageId]);
        $existing = $stmt->fetch();

        // Dohvati poruku
        $msg = $gmail->getMessage($messageId, 'full');
        $parsed = $gmail->parseMessage($msg);

        // Parsiraj from
        $fromEmail = '';
        $fromName = '';
        if (preg_match('/<([^>]+)>/', $parsed['from'], $m)) {
            $fromEmail = strtolower($m[1]);
            $fromName = trim(str_replace($m[0], '', $parsed['from']), ' "');
        } else {
            $fromEmail = strtolower(trim($parsed['from']));
            $fromName = $fromEmail;
        }

        $receivedAt = $parsed['timestamp'] ? date('Y-m-d H:i:s', $parsed['timestamp']) : null;
        $labels = implode(',', $parsed['labels'] ?? []);
        $isUnread = $parsed['isUnread'] ? 1 : 0;

        // Pronađi attachmente
        $attachments = [];
        $hasAttachments = 0;
        if (isset($msg['payload']['parts'])) {
            foreach ($msg['payload']['parts'] as $part) {
                if (!empty($part['filename']) && !empty($part['body']['attachmentId'])) {
                    $attachments[] = [
                        'id' => $part['body']['attachmentId'],
                        'filename' => $part['filename'],
                        'mimeType' => $part['mimeType'] ?? 'application/octet-stream',
                        'size' => $part['body']['size'] ?? 0
                    ];
                    $hasAttachments = 1;
                }
                // Provjeri i nested parts (za multipart poruke)
                if (isset($part['parts'])) {
                    foreach ($part['parts'] as $subpart) {
                        if (!empty($subpart['filename']) && !empty($subpart['body']['attachmentId'])) {
                            $attachments[] = [
                                'id' => $subpart['body']['attachmentId'],
                                'filename' => $subpart['filename'],
                                'mimeType' => $subpart['mimeType'] ?? 'application/octet-stream',
                                'size' => $subpart['body']['size'] ?? 0
                            ];
                            $hasAttachments = 1;
                        }
                    }
                }
            }
        }

        if ($existing) {
            // Update samo ako se promijenio unread status
            if ($existing['is_unread'] != $isUnread) {
                $db->prepare("UPDATE gmail_messages_cache SET is_unread = ?, labels = ? WHERE message_id = ?")
                   ->execute([$isUnread, $labels, $messageId]);
                $updated++;
            }
        } else {
            // Nova poruka
            // Izvuci HTML body ako postoji
            $bodyHtml = '';
            if (isset($msg['payload']['parts'])) {
                foreach ($msg['payload']['parts'] as $part) {
                    if ($part['mimeType'] === 'text/html' && !empty($part['body']['data'])) {
                        $bodyHtml = base64_decode(strtr($part['body']['data'], '-_', '+/'));
                        break;
                    }
                    if (isset($part['parts'])) {
                        foreach ($part['parts'] as $subpart) {
                            if ($subpart['mimeType'] === 'text/html' && !empty($subpart['body']['data'])) {
                                $bodyHtml = base64_decode(strtr($subpart['body']['data'], '-_', '+/'));
                                break 2;
                            }
                        }
                    }
                }
            } elseif (($msg['payload']['mimeType'] ?? '') === 'text/html' && !empty($msg['payload']['body']['data'])) {
                $bodyHtml = base64_decode(strtr($msg['payload']['body']['data'], '-_', '+/'));
            }

            $stmt = $db->prepare("
                INSERT INTO gmail_messages_cache
                (message_id, thread_id, from_email, from_name, to_email, subject, snippet, body_text, body_html, labels, is_unread, has_attachments, received_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $messageId,
                $parsed['threadId'],
                $fromEmail,
                $fromName,
                $parsed['to'],
                $parsed['subject'],
                $parsed['snippet'],
                $parsed['body'],
                $bodyHtml,
                $labels,
                $isUnread,
                $hasAttachments,
                $receivedAt
            ]);

            // Spremi info o attachmentima
            foreach ($attachments as $att) {
                $db->prepare("
                    INSERT IGNORE INTO gmail_attachments (message_id, attachment_id, filename, mime_type, size)
                    VALUES (?, ?, ?, ?, ?)
                ")->execute([$messageId, $att['id'], $att['filename'], $att['mimeType'], $att['size']]);
            }

            $new++;
        }

        // Progress
        $progress = round(($i + 1) / $total * 100);
        echo "\r[{$progress}%] Obrađeno " . ($i + 1) . "/{$total}...";
    }

    echo "\n\nGotovo! Novih: {$new}, Ažuriranih: {$updated}\n";

} catch (Exception $e) {
    echo "Greška: " . $e->getMessage() . "\n";
    exit(1);
}
