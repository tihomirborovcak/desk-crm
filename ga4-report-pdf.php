<?php
// GA4 PDF Report
require_once 'includes/auth.php';
requireLogin();

define('GA4_PROPERTY_ID', '279956882');

// Parametri iz GET-a
$startDate = $_GET['start_date'] ?? date('Y-m-01', strtotime('-2 months'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$groupBy = $_GET['group_by'] ?? 'month';

function getGoogleAccessToken() {
    $credentialsFile = __DIR__ . '/google-credentials.json';
    if (!file_exists($credentialsFile)) return ['error' => 'Credentials not found'];
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

// Generiraj periode ovisno o groupBy
$periods = [];
$start = new DateTime($startDate);
$end = new DateTime($endDate);
$monthNames = ['', 'Siječanj', 'Veljača', 'Ožujak', 'Travanj', 'Svibanj', 'Lipanj',
               'Srpanj', 'Kolovoz', 'Rujan', 'Listopad', 'Studeni', 'Prosinac'];

if ($groupBy === 'month') {
    $current = clone $start;
    $current->modify('first day of this month');
    while ($current <= $end) {
        $periodStart = $current->format('Y-m-d');
        $current->modify('last day of this month');
        $periodEnd = min($current->format('Y-m-d'), $end->format('Y-m-d'));
        $periods[] = [
            'name' => $monthNames[(int)$current->format('n')] . ' ' . $current->format('Y'),
            'start' => $periodStart,
            'end' => $periodEnd
        ];
        $current->modify('first day of next month');
    }
} elseif ($groupBy === 'week') {
    $current = clone $start;
    $weekNum = 1;
    while ($current <= $end) {
        $periodStart = $current->format('Y-m-d');
        $current->modify('+6 days');
        $periodEnd = min($current->format('Y-m-d'), $end->format('Y-m-d'));
        $periods[] = [
            'name' => 'Tjedan ' . $weekNum . ' (' . (new DateTime($periodStart))->format('d.m.') . ' - ' . (new DateTime($periodEnd))->format('d.m.') . ')',
            'start' => $periodStart,
            'end' => $periodEnd
        ];
        $current->modify('+1 day');
        $weekNum++;
    }
} else { // day
    $current = clone $start;
    $dayNames = ['Ned', 'Pon', 'Uto', 'Sri', 'Čet', 'Pet', 'Sub'];
    while ($current <= $end) {
        $periods[] = [
            'name' => $dayNames[(int)$current->format('w')] . ', ' . $current->format('d.m.Y'),
            'start' => $current->format('Y-m-d'),
            'end' => $current->format('Y-m-d')
        ];
        $current->modify('+1 day');
    }
}

$results = [];
$totalViews = 0;
$totalUsers = 0;
$totalSessions = 0;

foreach ($periods as $period) {
    $data = getGA4Report($period['start'], $period['end']);
    $row = $data['rows'][0]['metricValues'] ?? null;
    if ($row) {
        $views = (int)$row[0]['value'];
        $users = (int)$row[1]['value'];
        $sessions = (int)$row[2]['value'];
        $results[] = [
            'name' => $period['name'],
            'views' => $views,
            'users' => $users,
            'sessions' => $sessions
        ];
        $totalViews += $views;
        $totalUsers += $users;
        $totalSessions += $sessions;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Analytics - <?= date('d.m.Y') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none; }
            @page {
                margin: 1cm;
                margin-top: 0;
                margin-bottom: 0;
            }
        }
        @page {
            size: A4;
            margin: 15mm;
        }
        @page :first { margin-top: 15mm; }
        @page :left { margin-left: 15mm; }
        @page :right { margin-right: 15mm; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Roboto', 'Google Sans', -apple-system, sans-serif;
            max-width: 900px;
            margin: 0 auto;
            padding: 24px;
            background: #fff;
            color: #202124;
            font-size: 14px;
            line-height: 1.5;
        }
        .ga-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e8eaed;
            margin-bottom: 24px;
        }
        .ga-logo {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .ga-logo svg { width: 32px; height: 32px; }
        .ga-logo-text {
            font-family: 'Google Sans', sans-serif;
            font-size: 22px;
            color: #5f6368;
            font-weight: 400;
        }
        .ga-logo-text span { color: #202124; }
        .ga-property {
            margin-left: auto;
            text-align: right;
        }
        .ga-property-name {
            font-family: 'Google Sans', sans-serif;
            font-size: 16px;
            font-weight: 500;
            color: #202124;
        }
        .ga-property-id {
            font-size: 12px;
            color: #5f6368;
        }
        .ga-date-range {
            background: #f8f9fa;
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .ga-date-range svg { width: 20px; height: 20px; color: #5f6368; }
        .ga-date-range-text {
            font-weight: 500;
            color: #202124;
        }
        .ga-section-title {
            font-family: 'Google Sans', sans-serif;
            font-size: 16px;
            font-weight: 500;
            color: #202124;
            margin: 24px 0 16px 0;
        }
        .ga-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .ga-card {
            background: #fff;
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 16px;
        }
        .ga-card-label {
            font-size: 12px;
            color: #5f6368;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .ga-card-value {
            font-family: 'Google Sans', sans-serif;
            font-size: 28px;
            font-weight: 500;
            color: #202124;
        }
        .ga-card-value.blue { color: #1a73e8; }
        .ga-card-value.green { color: #137333; }
        .ga-card-value.orange { color: #e37400; }
        .ga-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
        }
        .ga-table th {
            text-align: left;
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 500;
            color: #5f6368;
            border-bottom: 1px solid #e8eaed;
            background: #f8f9fa;
        }
        .ga-table th:not(:first-child) { text-align: right; }
        .ga-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #e8eaed;
            color: #202124;
        }
        .ga-table td:not(:first-child) {
            text-align: right;
            font-family: 'Google Sans', sans-serif;
        }
        .ga-table tr:hover { background: #f8f9fa; }
        .ga-table .total-row {
            background: #e8f0fe !important;
            font-weight: 500;
        }
        .ga-table .total-row td { color: #1a73e8; }
        .ga-footer {
            margin-top: 32px;
            padding-top: 16px;
            border-top: 1px solid #e8eaed;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #5f6368;
            font-size: 12px;
        }
        .print-btn {
            background: #1a73e8;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            font-family: 'Google Sans', sans-serif;
        }
        .print-btn:hover { background: #1557b0; }
    </style>
</head>
<body>
    <div class="ga-header">
        <div class="ga-logo">
            <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                <g fill="none" fill-rule="evenodd">
                    <path d="M10 42c0 3.3 2.7 6 6 6s6-2.7 6-6V10c0-3.3-2.7-6-6-6s-6 2.7-6 6v32z" fill="#F9AB00"/>
                    <path d="M26 42c0 3.3 2.7 6 6 6s6-2.7 6-6V26c0-3.3-2.7-6-6-6s-6 2.7-6 6v16z" fill="#E37400"/>
                    <circle cx="48" cy="42" r="6" fill="#F9AB00"/>
                </g>
            </svg>
            <span class="ga-logo-text"><span>Google</span> Analytics</span>
        </div>
        <div class="ga-property">
            <div class="ga-property-name">zagorje.com</div>
            <div class="ga-property-id">Property ID: <?= GA4_PROPERTY_ID ?></div>
        </div>
    </div>

    <div class="ga-date-range">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span class="ga-date-range-text"><?= date('d. M Y.', strtotime($startDate)) ?> – <?= date('d. M Y.', strtotime($endDate)) ?></span>
    </div>

    <div class="ga-section-title">Pregled ključnih metrika</div>
    <div class="ga-cards">
        <div class="ga-card">
            <div class="ga-card-label">Pregledi stranica</div>
            <div class="ga-card-value blue"><?= number_format($totalViews, 0, ',', '.') ?></div>
        </div>
        <div class="ga-card">
            <div class="ga-card-label">Korisnici</div>
            <div class="ga-card-value green"><?= number_format($totalUsers, 0, ',', '.') ?></div>
        </div>
        <div class="ga-card">
            <div class="ga-card-label">Sesije</div>
            <div class="ga-card-value orange"><?= number_format($totalSessions, 0, ',', '.') ?></div>
        </div>
    </div>

    <div class="ga-section-title">Detaljna analiza po razdobljima</div>
    <table class="ga-table">
        <thead>
            <tr>
                <th>Razdoblje</th>
                <th>Pregledi stranica</th>
                <th>Korisnici</th>
                <th>Sesije</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $r): ?>
            <tr>
                <td><?= $r['name'] ?></td>
                <td><?= number_format($r['views'], 0, ',', '.') ?></td>
                <td><?= number_format($r['users'], 0, ',', '.') ?></td>
                <td><?= number_format($r['sessions'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td>Ukupno</td>
                <td><?= number_format($totalViews, 0, ',', '.') ?></td>
                <td><?= number_format($totalUsers, 0, ',', '.') ?></td>
                <td><?= number_format($totalSessions, 0, ',', '.') ?></td>
            </tr>
        </tbody>
    </table>

    <div class="ga-footer">
        <div>
            <span>Generirano: <?= date('d.m.Y. H:i') ?></span>
        </div>
        <button class="print-btn no-print" onclick="window.print()">
            Spremi kao PDF
        </button>
        <div>
            <span>© <?= date('Y') ?> Google Analytics</span>
        </div>
    </div>
</body>
</html>
