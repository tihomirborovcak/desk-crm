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
    <title>GA4 Izvještaj - <?= date('d.m.Y') ?></title>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none; }
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 40px;
            background: #fff;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #1e40af;
            margin: 0;
            font-size: 28px;
        }
        .header .subtitle {
            color: #6b7280;
            margin-top: 8px;
        }
        .header .date {
            color: #9ca3af;
            font-size: 14px;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 15px 20px;
            text-align: right;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #2563eb;
            color: white;
            font-weight: 600;
        }
        th:first-child, td:first-child {
            text-align: left;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        tr:hover {
            background: #f3f4f6;
        }
        .total-row {
            background: #1e40af !important;
            color: white;
            font-weight: bold;
        }
        .total-row:hover {
            background: #1e40af !important;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 30px;
        }
        .summary-card {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
        }
        .summary-card .value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .summary-card .label {
            opacity: 0.9;
            font-size: 14px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
        }
        .print-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin: 20px auto;
            display: block;
        }
        .print-btn:hover {
            background: #1e40af;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Google Analytics 4 - Izvještaj</h1>
        <div class="subtitle">Zagorje Promocija - Analitika web prometa</div>
        <div class="date">Generirano: <?= date('d.m.Y H:i') ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Mjesec</th>
                <th>Pregledi</th>
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
                <td>UKUPNO</td>
                <td><?= number_format($totalViews, 0, ',', '.') ?></td>
                <td><?= number_format($totalUsers, 0, ',', '.') ?></td>
                <td><?= number_format($totalSessions, 0, ',', '.') ?></td>
            </tr>
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-card">
            <div class="value"><?= number_format($totalViews, 0, ',', '.') ?></div>
            <div class="label">Ukupno pregleda</div>
        </div>
        <div class="summary-card">
            <div class="value"><?= number_format($totalUsers, 0, ',', '.') ?></div>
            <div class="label">Ukupno korisnika</div>
        </div>
        <div class="summary-card">
            <div class="value"><?= number_format($totalSessions, 0, ',', '.') ?></div>
            <div class="label">Ukupno sesija</div>
        </div>
    </div>

    <button class="print-btn no-print" onclick="window.print()">
        🖨️ Spremi kao PDF
    </button>

    <div class="footer">
        GA4 Property ID: <?= GA4_PROPERTY_ID ?> | Razdoblje: <?= date('d.m.Y', strtotime($startDate)) ?> - <?= date('d.m.Y', strtotime($endDate)) ?>
    </div>
</body>
</html>
