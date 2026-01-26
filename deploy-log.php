<?php
// GitHub Webhook Deploy Script with logging
$secret = 'Signal2026WebhookSecret';
$logFile = __DIR__ . '/deploy.log';

function logMsg($msg) {
    global $logFile;
    $time = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$time] $msg\n", FILE_APPEND);
}

logMsg("Request received: " . $_SERVER['REQUEST_METHOD']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logMsg("Invalid method");
    die('Invalid request');
}

$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$payload = file_get_contents('php://input');
$hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);

logMsg("Signature check: " . ($hash === $signature ? 'OK' : 'FAIL'));

if (!hash_equals($hash, $signature)) {
    logMsg("Forbidden - signature mismatch");
    http_response_code(403);
    die('Forbidden');
}

$data = json_decode($payload, true);
$repo = $data['repository']['name'] ?? '';

logMsg("Repository: $repo");

$repos = [
    'desk-crm' => '/home/zagorje-promocija/htdocs/www.zagorje-promocija.com/desk-crm',
    'crm' => '/home/zagorje-promocija/htdocs/www.zagorje-promocija.com/crm',
];

if (isset($repos[$repo])) {
    $path = $repos[$repo];
    logMsg("Deploying to: $path");
    $output = shell_exec("cd $path && git pull 2>&1");
    logMsg("Output: $output");
    echo "Deployed $repo:\n$output";
} else {
    logMsg("Unknown repo: $repo");
    echo "Unknown repo: $repo";
}
