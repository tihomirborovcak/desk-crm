<?php
/**
 * Cron skripta za dohvat Gmail poruka
 * Pokrenuti svakih 5 minuta
 * Windows Task Scheduler: php C:\xampp\htdocs\desk-crm\cron\fetch-gmail-messages.php
 */

// Ako se pokrece iz CLI
if (php_sapi_name() !== 'cli' && !defined('CRON_RUN')) {
    die('Ova skripta je samo za cron.');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/gmail.php';
require_once __DIR__ . '/../config/email.php';

$db = getDB();
$newMessages = [];

/**
 * Parsiraj email adresu iz headera
 */
function parseEmailAddress($header) {
    if (preg_match('/<([^>]+)>/', $header, $matches)) {
        return strtolower(trim($matches[1]));
    }
    return strtolower(trim($header));
}

/**
 * Parsiraj ime posiljatelja iz headera
 */
function parseSenderName($header) {
    if (preg_match('/^([^<]+)</', $header, $matches)) {
        return trim($matches[1], ' "\'');
    }
    return parseEmailAddress($header);
}

/**
 * Dohvati ili kreiraj partnera po emailu
 */
function getOrCreatePartner($db, $email, $name) {
    $email = strtolower(trim($email));

    $stmt = $db->prepare("SELECT id FROM partners WHERE email = ?");
    $stmt->execute([$email]);
    $partner = $stmt->fetch();

    if ($partner) {
        return $partner['id'];
    }

    // Kreiraj novog partnera
    $displayName = $name ?: $email;
    // Izvuci ime iz email adrese ako nema imena
    if ($displayName === $email && strpos($email, '@') !== false) {
        $displayName = ucwords(str_replace(['.', '_', '-'], ' ', explode('@', $email)[0]));
    }

    $stmt = $db->prepare("
        INSERT INTO partners (name, email, source) VALUES (?, ?, 'gmail')
    ");
    $stmt->execute([$displayName, $email]);

    return $db->lastInsertId();
}

/**
 * Dekodiraj base64url
 */
function base64UrlDecode($data) {
    return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
}

/**
 * Dohvati tijelo poruke iz dijelova
 */
function getMessageBody($payload) {
    $body = ['text' => '', 'html' => ''];

    if (isset($payload['body']['data'])) {
        $decoded = base64UrlDecode($payload['body']['data']);
        if (isset($payload['mimeType'])) {
            if ($payload['mimeType'] === 'text/plain') {
                $body['text'] = $decoded;
            } elseif ($payload['mimeType'] === 'text/html') {
                $body['html'] = $decoded;
            }
        }
    }

    if (isset($payload['parts'])) {
        foreach ($payload['parts'] as $part) {
            $partBody = getMessageBody($part);
            if (!empty($partBody['text']) && empty($body['text'])) {
                $body['text'] = $partBody['text'];
            }
            if (!empty($partBody['html']) && empty($body['html'])) {
                $body['html'] = $partBody['html'];
            }
        }
    }

    return $body;
}

/**
 * Dohvati poruke za Gmail racun
 */
function fetchGmailMessages($db, $account, &$newMessages) {
    $auth = getValidGmailToken($db, $account['id']);

    if (isset($auth['error'])) {
        error_log("Gmail token error za {$account['email']}: " . $auth['error']);
        return;
    }

    $accessToken = $auth['token'];
    $ourEmail = strtolower($account['email']);

    // Dohvati nedavne poruke (zadnjih 50, u INBOX)
    $listUrl = "https://www.googleapis.com/gmail/v1/users/me/messages?maxResults=" . GMAIL_FETCH_LIMIT . "&q=in:inbox";

    $ch = curl_init($listUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken]
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Gmail API error: $response");
        return;
    }

    $data = json_decode($response, true);

    if (!isset($data['messages'])) {
        return;
    }

    foreach ($data['messages'] as $msgRef) {
        $messageId = $msgRef['id'];

        // Provjeri postoji li vec
        $stmt = $db->prepare("SELECT id FROM gmail_messages WHERE message_id = ?");
        $stmt->execute([$messageId]);
        if ($stmt->fetch()) {
            continue;
        }

        // Dohvati punu poruku
        $msgUrl = "https://www.googleapis.com/gmail/v1/users/me/messages/{$messageId}?format=full";
        $ch = curl_init($msgUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken]
        ]);
        $msgResponse = curl_exec($ch);
        curl_close($ch);

        $msg = json_decode($msgResponse, true);

        if (!isset($msg['id'])) continue;

        // Parsiraj headere
        $headers = [];
        foreach ($msg['payload']['headers'] ?? [] as $h) {
            $headers[strtolower($h['name'])] = $h['value'];
        }

        $fromHeader = $headers['from'] ?? '';
        $fromEmail = parseEmailAddress($fromHeader);
        $fromName = parseSenderName($fromHeader);
        $toEmail = parseEmailAddress($headers['to'] ?? '');
        $subject = $headers['subject'] ?? '(bez naslova)';
        $threadId = $msg['threadId'];
        $internalDate = isset($msg['internalDate'])
            ? date('Y-m-d H:i:s', $msg['internalDate'] / 1000)
            : date('Y-m-d H:i:s');

        // Odredi je li dolazna (nama) ili odlazna (od nas)
        $isInbound = (stripos($fromEmail, $ourEmail) === false);

        // Dohvati tijelo
        $body = getMessageBody($msg['payload']);

        // Provjeri ima li attachmenta
        $hasAttachments = 0;
        if (isset($msg['payload']['parts'])) {
            foreach ($msg['payload']['parts'] as $part) {
                if (!empty($part['filename'])) {
                    $hasAttachments = 1;
                    break;
                }
            }
        }

        // Labels
        $labels = isset($msg['labelIds']) ? implode(',', $msg['labelIds']) : '';

        // Odredi email partnera
        $partnerEmail = $isInbound ? $fromEmail : $toEmail;
        $partnerName = $isInbound ? $fromName : $toEmail;

        // Preskoci sistemske emailove
        if (preg_match('/(noreply|no-reply|mailer-daemon|postmaster|notifications?|alert|donotreply|bounce)/i', $partnerEmail)) {
            continue;
        }

        $partnerId = getOrCreatePartner($db, $partnerEmail, $partnerName);

        // Provjeri postoji li thread, azuriraj partner link
        $stmt = $db->prepare("SELECT id FROM gmail_threads WHERE thread_id = ?");
        $stmt->execute([$threadId]);
        $existingThread = $stmt->fetch();

        if (!$existingThread) {
            $stmt = $db->prepare("
                INSERT INTO gmail_threads
                (thread_id, gmail_account_id, partner_id, subject, snippet, last_message_at, unread_count)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $threadId,
                $account['id'],
                $partnerId,
                $subject,
                $msg['snippet'] ?? '',
                $internalDate,
                $isInbound ? 1 : 0
            ]);
        } else {
            $stmt = $db->prepare("
                UPDATE gmail_threads
                SET last_message_at = GREATEST(last_message_at, ?),
                    snippet = ?,
                    partner_id = COALESCE(partner_id, ?),
                    unread_count = unread_count + ?
                WHERE thread_id = ?
            ");
            $stmt->execute([
                $internalDate,
                $msg['snippet'] ?? '',
                $partnerId,
                $isInbound ? 1 : 0,
                $threadId
            ]);
        }

        // Umetni poruku
        $stmt = $db->prepare("
            INSERT INTO gmail_messages
            (thread_id, message_id, gmail_account_id, from_email, from_name, to_email,
             subject, body_text, body_html, sent_at, is_inbound, has_attachments, labels)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $threadId,
            $messageId,
            $account['id'],
            $fromEmail,
            $fromName,
            $toEmail,
            $subject,
            $body['text'],
            $body['html'],
            $internalDate,
            $isInbound ? 1 : 0,
            $hasAttachments,
            $labels
        ]);

        // Dodaj u nove poruke za notifikaciju
        if ($isInbound) {
            $newMessages[] = [
                'sender' => $fromName,
                'subject' => $subject,
                'snippet' => $msg['snippet'] ?? '',
                'time' => $internalDate,
                'email' => $account['email']
            ];
        }
    }
}

// Obradi sve povezane Gmail racune
try {
    $accounts = $db->query("SELECT * FROM gmail_oauth_tokens")->fetchAll();
} catch (PDOException $e) {
    echo "Tablica gmail_oauth_tokens ne postoji. Pokreni gmail-setup.php prvo.\n";
    exit(1);
}

foreach ($accounts as $account) {
    fetchGmailMessages($db, $account, $newMessages);
}

// Posalji email notifikaciju ako ima novih poruka
if (!empty($newMessages)) {
    $subject = 'Nova Gmail poruka (' . count($newMessages) . ')';

    $body = '<html><body style="font-family: Arial, sans-serif;">';
    $body .= '<h2 style="color: #ea4335;">Nove Gmail poruke</h2>';

    foreach ($newMessages as $msg) {
        $body .= '<div style="background: #f0f2f5; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #ea4335;">';
        $body .= '<strong>' . htmlspecialchars($msg['sender']) . '</strong>';
        $body .= '<span style="color: #65676b; font-size: 12px; margin-left: 10px;">' . $msg['time'] . '</span>';
        $body .= '<p style="margin: 5px 0 0 0; font-weight: 500;">' . htmlspecialchars($msg['subject']) . '</p>';
        $body .= '<p style="margin: 5px 0 0 0; color: #65676b;">' . htmlspecialchars(mb_substr($msg['snippet'], 0, 150)) . '</p>';
        $body .= '</div>';
    }

    $body .= '<p><a href="https://desk.zagorje.com/gmail-messages.php" style="color: #ea4335;">Otvori u CMS-u</a></p>';
    $body .= '</body></html>';

    if (defined('NOTIFICATION_EMAILS') && NOTIFICATION_EMAILS) {
        $sent = sendEmail(NOTIFICATION_EMAILS, $subject, $body);

        if ($sent) {
            echo "Email poslan za " . count($newMessages) . " novih poruka\n";
        } else {
            echo "Greska pri slanju emaila\n";
        }
    } else {
        echo "Dohvaceno " . count($newMessages) . " novih poruka (email notifikacije nisu konfigurirane)\n";
    }
} else {
    echo "Nema novih poruka\n";
}
