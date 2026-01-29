<?php
/**
 * Download Gmail attachment
 */

require_once 'includes/auth.php';
require_once 'GmailClient.php';

requireLogin();

if (!isEditor()) {
    die('Pristup odbijen');
}

$messageId = $_GET['message'] ?? '';
$attachmentId = $_GET['id'] ?? '';
$filename = $_GET['name'] ?? 'attachment';

if (!$messageId || !$attachmentId) {
    die('Nedostaju parametri');
}

try {
    $gmail = new GmailClient();

    if (!$gmail->isAuthorized()) {
        die('Gmail nije autoriziran');
    }

    $token = $gmail->getAccessToken();
    if (!$token) {
        die('Token nije dostupan');
    }

    // Dohvati poruku da dobijemo svjež attachment ID
    $msg = $gmail->getMessage($messageId, 'full');

    // Pronađi attachment po imenu datoteke
    $freshAttachmentId = null;
    $parts = $msg['payload']['parts'] ?? [];
    foreach ($parts as $part) {
        if (($part['filename'] ?? '') === $filename && !empty($part['body']['attachmentId'])) {
            $freshAttachmentId = $part['body']['attachmentId'];
            break;
        }
        // Provjeri nested parts
        foreach ($part['parts'] ?? [] as $subpart) {
            if (($subpart['filename'] ?? '') === $filename && !empty($subpart['body']['attachmentId'])) {
                $freshAttachmentId = $subpart['body']['attachmentId'];
                break 2;
            }
        }
    }

    if (!$freshAttachmentId) {
        die('Attachment nije pronađen u poruci');
    }

    // Dohvati attachment preko API-ja
    $url = "https://gmail.googleapis.com/gmail/v1/users/me/messages/{$messageId}/attachments/{$freshAttachmentId}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        $error = json_decode($response, true);
        die('Greška (' . $httpCode . '): ' . ($error['error']['message'] ?? $response));
    }

    $data = json_decode($response, true);

    if (!isset($data['data'])) {
        die('Nema podataka');
    }

    // Dekodiraj base64url
    $content = base64_decode(strtr($data['data'], '-_', '+/'));

    // Odredi MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($content) ?: 'application/octet-stream';

    // Očisti output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Pošalji datoteku
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Content-Length: ' . strlen($content));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');

    echo $content;
    flush();
    exit;

} catch (Exception $e) {
    die('Greška: ' . $e->getMessage());
}
