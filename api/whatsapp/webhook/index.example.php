<?php
/**
 * WhatsApp Webhook za Meta/Facebook
 *
 * UPUTE: Kopirajte ovu datoteku u index.php i unesite pravi verify_token
 * Callback URL: https://www.zagorje-promocija.com/desk-crm/api/whatsapp/webhook
 */

// Verify token - isti kao u Meta konfiguraciji
$verify_token = 'YOUR_VERIFY_TOKEN';

// Verifikacija webhoka (GET request od Mete)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';

    if ($mode === 'subscribe' && $token === $verify_token) {
        echo $challenge;
        exit;
    } else {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

// Primanje poruka (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Spremi u log
    $logFile = __DIR__ . '/webhook_log.txt';
    file_put_contents($logFile, date('Y-m-d H:i:s') . "\n" . $input . "\n\n", FILE_APPEND);

    // Obradi poruke
    if (isset($data['entry'])) {
        foreach ($data['entry'] as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                if ($change['field'] === 'messages') {
                    $value = $change['value'] ?? [];
                    $messages = $value['messages'] ?? [];

                    foreach ($messages as $message) {
                        // Spremi poruku u bazu (TODO)
                        $from = $message['from'] ?? '';
                        $type = $message['type'] ?? '';
                        $timestamp = $message['timestamp'] ?? '';
                        $text = $message['text']['body'] ?? '';

                        // Log pojedinačne poruke
                        $msgLog = "FROM: {$from}, TYPE: {$type}, TEXT: {$text}\n";
                        file_put_contents($logFile, $msgLog, FILE_APPEND);
                    }
                }
            }
        }
    }

    // Meta očekuje 200 OK
    http_response_code(200);
    echo 'OK';
    exit;
}

// Ostali requestovi
http_response_code(405);
echo 'Method not allowed';
