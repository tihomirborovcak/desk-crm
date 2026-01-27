<?php
/**
 * Google Analytics 4 - Analitika članaka
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

// Samo urednici i admini
if (!isEditor()) {
    header('Location: dashboard.php');
    exit;
}

define('PAGE_TITLE', 'GA4 Analitika');

// GA4 Property ID
define('GA4_PROPERTY_ID', '279956882');

/**
 * Dohvati Google Access Token
 */
function getGoogleAccessToken() {
    $credentialsFile = __DIR__ . '/google-credentials.json';

    if (!file_exists($credentialsFile)) {
        return ['error' => 'Google credentials file not found'];
    }

    $credentials = json_decode(file_get_contents($credentialsFile), true);

    // Kreiraj JWT
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

    // Razmijeni JWT za access token
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

    if (isset($tokenData['access_token'])) {
        return ['token' => $tokenData['access_token']];
    }

    return ['error' => $tokenData['error_description'] ?? 'Token error'];
}

/**
 * Dohvati GA4 podatke
 */
function getGA4Report($startDate, $endDate, $dimensions, $metrics, $limit = 50) {
    if (empty(GA4_PROPERTY_ID)) {
        return ['error' => 'GA4 Property ID nije postavljen'];
    }

    $auth = getGoogleAccessToken();
    if (isset($auth['error'])) {
        return $auth;
    }

    $url = 'https://analyticsdata.googleapis.com/v1beta/properties/' . GA4_PROPERTY_ID . ':runReport';

    $requestBody = [
        'dateRanges' => [
            ['startDate' => $startDate, 'endDate' => $endDate]
        ],
        'dimensions' => array_map(function($d) { return ['name' => $d]; }, $dimensions),
        'metrics' => array_map(function($m) { return ['name' => $m]; }, $metrics),
        'limit' => $limit,
        'orderBys' => [
            ['metric' => ['metricName' => $metrics[0]], 'desc' => true]
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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        return ['error' => $data['error']['message'] ?? 'API error: ' . $httpCode];
    }

    return $data;
}

// Parametri
$period = $_GET['period'] ?? '7days';
$reportType = $_GET['report'] ?? 'pages';

switch ($period) {
    case 'today':
        $startDate = 'today';
        $endDate = 'today';
        $periodLabel = 'Danas';
        break;
    case 'yesterday':
        $startDate = 'yesterday';
        $endDate = 'yesterday';
        $periodLabel = 'Jučer';
        break;
    case '7days':
        $startDate = '7daysAgo';
        $endDate = 'today';
        $periodLabel = 'Zadnjih 7 dana';
        break;
    case '30days':
        $startDate = '30daysAgo';
        $endDate = 'today';
        $periodLabel = 'Zadnjih 30 dana';
        break;
    default:
        $startDate = '7daysAgo';
        $endDate = 'today';
        $periodLabel = 'Zadnjih 7 dana';
}

// Dohvati podatke
$reportData = null;
$error = null;

if (!empty(GA4_PROPERTY_ID)) {
    if ($reportType === 'pages') {
        $reportData = getGA4Report($startDate, $endDate, ['pageTitle', 'pagePath'], ['screenPageViews', 'averageSessionDuration'], 50);
    } elseif ($reportType === 'daily') {
        $reportData = getGA4Report($startDate, $endDate, ['date'], ['screenPageViews', 'totalUsers', 'sessions'], 31);
    }

    if (isset($reportData['error'])) {
        $error = $reportData['error'];
        $reportData = null;
    }
} else {
    $error = 'GA4 Property ID nije postavljen. Kontaktirajte administratora.';
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>GA4 Analitika</h1>
    <p style="color: #6b7280; margin: 0.25rem 0 0 0;">Statistike posjećenosti za zagorje.com</p>
</div>

<?php if ($error): ?>
<div class="alert alert-danger">
    <?= e($error) ?>
</div>

<div class="card">
    <div class="card-body">
        <h3>Upute za postavljanje</h3>
        <ol style="line-height: 2;">
            <li>Otvori <a href="https://analytics.google.com" target="_blank">Google Analytics</a></li>
            <li>Idi na Admin > Property Settings</li>
            <li>Kopiraj <strong>Property ID</strong> (broj)</li>
            <li>U GA4 Admin > Property Access Management dodaj email:<br>
                <code style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px;">vertex-express@robotic-flash-428407-f0.iam.gserviceaccount.com</code><br>
                s ulogom <strong>Viewer</strong>
            </li>
            <li>Javi Property ID administratoru da ga postavi u sustav</li>
        </ol>
    </div>
</div>
<?php else: ?>

<!-- Filteri -->
<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
            <div style="display: flex; gap: 0; border: 1px solid #d1d5db; border-radius: 6px; overflow: hidden;">
                <a href="?period=today&report=<?= $reportType ?>"
                   class="btn btn-sm <?= $period === 'today' ? 'btn-primary' : 'btn-outline' ?>"
                   style="border-radius: 0; border: none;">Danas</a>
                <a href="?period=yesterday&report=<?= $reportType ?>"
                   class="btn btn-sm <?= $period === 'yesterday' ? 'btn-primary' : 'btn-outline' ?>"
                   style="border-radius: 0; border: none; border-left: 1px solid #d1d5db;">Jučer</a>
                <a href="?period=7days&report=<?= $reportType ?>"
                   class="btn btn-sm <?= $period === '7days' ? 'btn-primary' : 'btn-outline' ?>"
                   style="border-radius: 0; border: none; border-left: 1px solid #d1d5db;">7 dana</a>
                <a href="?period=30days&report=<?= $reportType ?>"
                   class="btn btn-sm <?= $period === '30days' ? 'btn-primary' : 'btn-outline' ?>"
                   style="border-radius: 0; border: none; border-left: 1px solid #d1d5db;">30 dana</a>
            </div>

            <div style="display: flex; gap: 0; border: 1px solid #d1d5db; border-radius: 6px; overflow: hidden;">
                <a href="?period=<?= $period ?>&report=pages"
                   class="btn btn-sm <?= $reportType === 'pages' ? 'btn-primary' : 'btn-outline' ?>"
                   style="border-radius: 0; border: none;">Članci</a>
                <a href="?period=<?= $period ?>&report=daily"
                   class="btn btn-sm <?= $reportType === 'daily' ? 'btn-primary' : 'btn-outline' ?>"
                   style="border-radius: 0; border: none; border-left: 1px solid #d1d5db;">Po danima</a>
            </div>

            <span style="color: #6b7280; margin-left: auto;"><?= $periodLabel ?></span>
        </div>
    </div>
</div>

<?php if ($reportType === 'pages' && $reportData && isset($reportData['rows'])): ?>
<!-- Najčitaniji članci -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Najčitaniji članci</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Naslov članka</th>
                    <th style="width: 120px; text-align: right;">Pregledi</th>
                    <th style="width: 120px; text-align: right;">Avg. vrijeme</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData['rows'] as $i => $row):
                    $title = $row['dimensionValues'][0]['value'] ?? '-';
                    $path = $row['dimensionValues'][1]['value'] ?? '';
                    $views = $row['metricValues'][0]['value'] ?? 0;
                    $duration = round(($row['metricValues'][1]['value'] ?? 0));
                    $durationFormatted = gmdate("i:s", $duration);

                    // Preskoči homepage i admin stranice
                    if ($path === '/' || strpos($path, '/admin') === 0 || $title === '(not set)') continue;
                ?>
                <tr>
                    <td style="color: #9ca3af;"><?= $i + 1 ?></td>
                    <td>
                        <div style="font-weight: 500;"><?= e(truncate($title, 80)) ?></div>
                        <div style="font-size: 0.75rem; color: #9ca3af;"><?= e(truncate($path, 60)) ?></div>
                    </td>
                    <td style="text-align: right; font-weight: 600; color: #1e3a5f;"><?= number_format($views) ?></td>
                    <td style="text-align: right; color: #6b7280;"><?= $durationFormatted ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($reportType === 'daily' && $reportData && isset($reportData['rows'])): ?>
<!-- Po danima -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Statistika po danima</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th style="text-align: right;">Pregledi</th>
                    <th style="text-align: right;">Korisnici</th>
                    <th style="text-align: right;">Sesije</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Sortiraj po datumu silazno
                $rows = $reportData['rows'];
                usort($rows, function($a, $b) {
                    return strcmp($b['dimensionValues'][0]['value'], $a['dimensionValues'][0]['value']);
                });

                foreach ($rows as $row):
                    $dateStr = $row['dimensionValues'][0]['value'] ?? '';
                    $date = DateTime::createFromFormat('Ymd', $dateStr);
                    $formattedDate = $date ? $date->format('d.m.Y.') : $dateStr;
                    $dayName = $date ? ['Ned', 'Pon', 'Uto', 'Sri', 'Čet', 'Pet', 'Sub'][$date->format('w')] : '';

                    $views = $row['metricValues'][0]['value'] ?? 0;
                    $users = $row['metricValues'][1]['value'] ?? 0;
                    $sessions = $row['metricValues'][2]['value'] ?? 0;
                ?>
                <tr>
                    <td>
                        <span style="font-weight: 500;"><?= $formattedDate ?></span>
                        <span style="color: #9ca3af; margin-left: 0.5rem;"><?= $dayName ?></span>
                    </td>
                    <td style="text-align: right; font-weight: 600; color: #1e3a5f;"><?= number_format($views) ?></td>
                    <td style="text-align: right;"><?= number_format($users) ?></td>
                    <td style="text-align: right;"><?= number_format($sessions) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="empty-state">
        <p>Nema podataka za odabrano razdoblje.</p>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
