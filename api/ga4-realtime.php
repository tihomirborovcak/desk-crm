<?php
/**
 * API endpoint za GA4 real-time podatke
 */
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

define('GA4_PROPERTY_ID', '279956882');

function getGoogleAccessToken() {
    $credentialsFile = dirname(__DIR__) . '/google-credentials.json';
    if (!file_exists($credentialsFile)) {
        return ['error' => 'Credentials not found'];
    }

    $credentials = json_decode(file_get_contents($credentialsFile), true);
    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $now = time();
    $payload = json_encode([
        'iss' => $credentials['client_email'],
        'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600
    ]);

    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    $signatureInput = $base64Header . '.' . $base64Payload;

    $privateKey = openssl_pkey_get_private($credentials['private_key']);
    openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    $jwt = $signatureInput . '.' . $base64Signature;

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ])
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $tokenData = json_decode($response, true);

    if (isset($tokenData['access_token'])) {
        return ['token' => $tokenData['access_token']];
    }
    return ['error' => $tokenData['error_description'] ?? 'Token error'];
}

$auth = getGoogleAccessToken();
if (isset($auth['error'])) {
    echo json_encode(['success' => false, 'error' => $auth['error']]);
    exit;
}

$url = 'https://analyticsdata.googleapis.com/v1beta/properties/' . GA4_PROPERTY_ID . ':runRealtimeReport';
$headers = [
    'Authorization: Bearer ' . $auth['token'],
    'Content-Type: application/json'
];

// Upit 1: ukupan broj korisnika (bez dimenzija = jedan red s totalom)
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POSTFIELDS => json_encode([
        'metrics' => [['name' => 'activeUsers']]
    ])
]);
$totalResponse = curl_exec($ch);
curl_close($ch);
$totalData = json_decode($totalResponse, true);
$totalUsers = (int)($totalData['rows'][0]['metricValues'][0]['value'] ?? 0);

// Upit 2: top stranice
$ch2 = curl_init($url);
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POSTFIELDS => json_encode([
        'dimensions' => [['name' => 'unifiedScreenName']],
        'metrics' => [['name' => 'activeUsers']],
        'limit' => 10
    ])
]);
$pagesResponse = curl_exec($ch2);
$httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);
$pagesData = json_decode($pagesResponse, true);

if ($httpCode !== 200 || !isset($pagesData['rows'])) {
    echo json_encode(['success' => false, 'error' => 'GA4 API error']);
    exit;
}

$pages = [];
foreach ($pagesData['rows'] as $row) {
    $pages[] = [
        'title' => $row['dimensionValues'][0]['value'] ?? '',
        'users' => (int)($row['metricValues'][0]['value'] ?? 0)
    ];
}

echo json_encode([
    'success' => true,
    'totalUsers' => $totalUsers,
    'pages' => $pages
]);
