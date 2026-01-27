<?php
/**
 * Cron skripta za dohvat Facebook i Instagram poruka
 * Pokrenuti svakih 5 minuta: 0,5,10,15,20,25,30,35,40,45,50,55 * * * * php /path/to/fetch-facebook-messages.php
 */

// Ako se pokreće iz CLI
if (php_sapi_name() !== 'cli' && !defined('CRON_RUN')) {
    die('Ova skripta je samo za cron.');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/facebook.php';
require_once __DIR__ . '/../config/email.php';

$db = getDB();
$newMessages = [];

// Dodaj platform kolonu ako ne postoji
try {
    $db->query("SELECT platform FROM facebook_conversations LIMIT 1");
} catch (PDOException $e) {
    $db->exec("ALTER TABLE facebook_conversations ADD COLUMN platform VARCHAR(20) DEFAULT 'facebook'");
}

/**
 * Dohvati i obradi poruke za platformu
 */
function fetchMessages($platform, $accountId, $token, $db, &$newMessages) {
    // Facebook koristi /conversations, Instagram koristi /conversations isto ali preko IG accounta
    if ($platform === 'instagram') {
        $conversationsUrl = "https://graph.facebook.com/v24.0/{$accountId}/conversations?platform=instagram&fields=id,participants,updated_time,messages.limit(10){id,message,from,created_time}&access_token={$token}";
    } else {
        $conversationsUrl = "https://graph.facebook.com/v24.0/{$accountId}/conversations?fields=id,participants,updated_time,messages.limit(10){id,message,from,created_time,attachments{image_data,file_url,mime_type}}&access_token={$token}";
    }

    $ch = curl_init($conversationsUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("$platform API error: $response");
        return;
    }

    $data = json_decode($response, true);

    if (!isset($data['data'])) {
        return;
    }

    foreach ($data['data'] as $conversation) {
        $conversationId = $conversation['id'];

        // Nađi participanta koji nije stranica/account
        $participantName = 'Nepoznato';
        $participantId = '';
        if (isset($conversation['participants']['data'])) {
            foreach ($conversation['participants']['data'] as $participant) {
                if ($participant['id'] !== $accountId) {
                    $participantName = $participant['name'] ?? $participant['username'] ?? 'Korisnik';
                    $participantId = $participant['id'];
                    break;
                }
            }
        }

        // Provjeri je li konverzacija obrisana
        $stmt = $db->prepare("SELECT deleted FROM facebook_conversations WHERE conversation_id = ?");
        $stmt->execute([$conversationId]);
        $existing = $stmt->fetch();

        if ($existing && $existing['deleted'] == 1) {
            // Preskoči obrisane konverzacije
            continue;
        }

        // Spremi/ažuriraj konverzaciju
        $stmt = $db->prepare("
            INSERT INTO facebook_conversations (conversation_id, participant_id, participant_name, last_message_at, platform)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                participant_name = VALUES(participant_name),
                last_message_at = VALUES(last_message_at),
                platform = VALUES(platform)
        ");
        $stmt->execute([
            $conversationId,
            $participantId,
            $participantName,
            date('Y-m-d H:i:s', strtotime($conversation['updated_time'])),
            $platform
        ]);

        // Obradi poruke
        if (isset($conversation['messages']['data'])) {
            foreach ($conversation['messages']['data'] as $msg) {
                $messageId = $msg['id'];
                $messageText = $msg['message'] ?? '';
                $senderId = $msg['from']['id'] ?? '';
                $senderName = $msg['from']['name'] ?? $msg['from']['username'] ?? 'Nepoznato';
                $sentAt = date('Y-m-d H:i:s', strtotime($msg['created_time']));
                $isFromPage = ($senderId === $accountId) ? 1 : 0;

                // Provjeri za attachment (slika)
                $attachmentUrl = null;
                if (isset($msg['attachments']['data'][0])) {
                    $att = $msg['attachments']['data'][0];
                    if (isset($att['image_data']['url'])) {
                        $attachmentUrl = $att['image_data']['url'];
                    } elseif (isset($att['file_url'])) {
                        $attachmentUrl = $att['file_url'];
                    }
                }

                // Provjeri postoji li već
                $stmt = $db->prepare("SELECT id FROM facebook_messages WHERE message_id = ?");
                $stmt->execute([$messageId]);

                if (!$stmt->fetch()) {
                    // Nova poruka - spremi
                    $stmt = $db->prepare("
                        INSERT INTO facebook_messages
                        (conversation_id, message_id, sender_id, sender_name, message_text, sent_at, is_from_page, attachment_url)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $conversationId,
                        $messageId,
                        $senderId,
                        $senderName,
                        $messageText,
                        $sentAt,
                        $isFromPage,
                        $attachmentUrl
                    ]);

                    // Ako nije od stranice, dodaj u nove poruke za email
                    if (!$isFromPage && !empty($messageText)) {
                        $platformLabel = $platform === 'instagram' ? 'IG' : 'FB';
                        $newMessages[] = [
                            'sender' => $senderName,
                            'message' => $messageText,
                            'time' => $sentAt,
                            'platform' => $platformLabel
                        ];
                    }

                    // Ažuriraj broj nepročitanih
                    if (!$isFromPage) {
                        $db->prepare("
                            UPDATE facebook_conversations
                            SET unread_count = unread_count + 1
                            WHERE conversation_id = ?
                        ")->execute([$conversationId]);
                    }
                }
            }
        }
    }
}

// Dohvati Facebook poruke
fetchMessages('facebook', FB_PAGE_ID, FB_PAGE_ACCESS_TOKEN, $db, $newMessages);

// Dohvati Instagram poruke (ako je konfiguriran)
if (defined('INSTAGRAM_ACCOUNT_ID') && INSTAGRAM_ACCOUNT_ID) {
    fetchMessages('instagram', INSTAGRAM_ACCOUNT_ID, FB_PAGE_ACCESS_TOKEN, $db, $newMessages);
}

// Pošalji email ako ima novih poruka
if (!empty($newMessages)) {
    $subject = 'Nova poruka (' . count($newMessages) . ')';

    $body = '<html><body style="font-family: Arial, sans-serif;">';
    $body .= '<h2 style="color: #1877f2;">Nove poruke</h2>';

    foreach ($newMessages as $msg) {
        $platformColor = $msg['platform'] === 'IG' ? '#E1306C' : '#1877f2';
        $body .= '<div style="background: #f0f2f5; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid ' . $platformColor . ';">';
        $body .= '<span style="background: ' . $platformColor . '; color: white; padding: 2px 6px; border-radius: 4px; font-size: 11px;">' . $msg['platform'] . '</span> ';
        $body .= '<strong>' . htmlspecialchars($msg['sender']) . '</strong>';
        $body .= '<span style="color: #65676b; font-size: 12px; margin-left: 10px;">' . $msg['time'] . '</span>';
        $body .= '<p style="margin: 10px 0 0 0;">' . htmlspecialchars($msg['message']) . '</p>';
        $body .= '</div>';
    }

    $body .= '<p><a href="https://desk.zagorje.com/facebook-messages.php" style="color: #1877f2;">Otvori u CMS-u</a></p>';
    $body .= '</body></html>';

    $sent = sendEmail(NOTIFICATION_EMAILS, $subject, $body);

    if ($sent) {
        // Označi da je email poslan
        $db->exec("UPDATE facebook_messages SET email_sent = 1 WHERE email_sent = 0 AND is_from_page = 0");
        echo "Email poslan za " . count($newMessages) . " novih poruka\n";
    } else {
        echo "Greška pri slanju emaila\n";
    }
} else {
    echo "Nema novih poruka\n";
}
