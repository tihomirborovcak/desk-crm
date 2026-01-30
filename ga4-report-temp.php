<?php
// GA4 Report - zadnja 3 mjeseca
define('GA4_PROPERTY_ID', '279956882');

function getGoogleAccessToken() {
    $credentialsFile = __DIR__ . '/google-credentials.json';
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
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ])
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $tokenData = json_decode($response, true);
    return isset($tokenData['access_token']) ? ['token' => $tokenData['access_token']] : ['error' => $tokenData['error_description'] ?? 'Error'];
}

function getGA4Report($startDate, $endDate) {
    $auth = getGoogleAccessToken();
    if (isset($auth['error'])) return $auth;

    $url = 'https://analyticsdata.googleapis.com/v1beta/properties/' . GA4_PROPERTY_ID . ':runReport';

    $requestBody = [
        'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
        'metrics' => [
            ['name' => 'screenPageViews'],
            ['name' => 'totalUsers'],
            ['name' => 'sessions']
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $auth['token'],
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($requestBody)
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// Dohvati podatke za svaki mjesec
$months = [
    ['name' => 'Studeni 2025', 'start' => '2025-11-01', 'end' => '2025-11-30'],
    ['name' => 'Prosinac 2025', 'start' => '2025-12-01', 'end' => '2025-12-31'],
    ['name' => 'Sijecanj 2026', 'start' => '2026-01-01', 'end' => '2026-01-29']
];

echo "=== GA4 IZVJESTAJ ===\n\n";

foreach ($months as $month) {
    $data = getGA4Report($month['start'], $month['end']);

    if (isset($data['error'])) {
        echo $month['name'] . ": ERROR - " . print_r($data, true) . "\n";
        continue;
    }

    $row = $data['rows'][0]['metricValues'] ?? null;
    if ($row) {
        $views = number_format((int)$row[0]['value'], 0, ',', '.');
        $users = number_format((int)$row[1]['value'], 0, ',', '.');
        $sessions = number_format((int)$row[2]['value'], 0, ',', '.');
        echo $month['name'] . ":\n";
        echo "  Pregledi:   $views\n";
        echo "  Korisnici:  $users\n";
        echo "  Sesije:     $sessions\n\n";
    } else {
        echo $month['name'] . ": Nema podataka\n\n";
    }
}
