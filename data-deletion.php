<?php
/**
 * Data Deletion Callback for Meta/Facebook
 * This endpoint handles user data deletion requests
 */

// GET request - show info page
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Data Deletion - Zagorjenews</title>
        <style>
            body { font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
            h1 { color: #333; }
        </style>
    </head>
    <body>
        <h1>Data Deletion Request</h1>
        <p>To request deletion of your data, please contact us at:</p>
        <p><a href="mailto:tihomir.borovcak@gmail.com">tihomir.borovcak@gmail.com</a></p>
        <p>Or use the Facebook app settings to initiate a data deletion request.</p>
    </body>
    </html>
    <?php
    exit;
}

header('Content-Type: application/json');

// Get signed request from Meta (POST request)
$signed_request = $_POST['signed_request'] ?? '';

if (empty($signed_request)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing signed_request']);
    exit;
}

// Parse signed request (simplified - in production verify signature)
$parts = explode('.', $signed_request);
if (count($parts) !== 2) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signed_request format']);
    exit;
}

$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
$user_id = $payload['user_id'] ?? null;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_id']);
    exit;
}

// Generate confirmation code
$confirmation_code = 'DEL_' . bin2hex(random_bytes(8));

// Log deletion request
$logFile = __DIR__ . '/storage/deletion_requests.log';
$logEntry = date('Y-m-d H:i:s') . " | User: {$user_id} | Code: {$confirmation_code}\n";
@file_put_contents($logFile, $logEntry, FILE_APPEND);

// TODO: Actually delete user data from database here
// DELETE FROM partners WHERE facebook_id = $user_id
// DELETE FROM gmail_messages_cache WHERE ...
// etc.

// Return response as required by Meta
$response = [
    'url' => 'https://www.zagorje-promocija.com/desk-crm/deletion-status.php?code=' . $confirmation_code,
    'confirmation_code' => $confirmation_code
];

echo json_encode($response);
