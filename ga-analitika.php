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
function getGA4Report($startDate, $endDate, $dimensions, $metrics, $limit = 50, $dimensionFilter = null) {
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

    if ($dimensionFilter) {
        $requestBody['dimensionFilter'] = $dimensionFilter;
    }

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
$pagePath = $_GET['page'] ?? '';

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
$sourcesData = null;
$pageTitle = '';
$error = null;

if (!empty(GA4_PROPERTY_ID)) {
    if ($reportType === 'pages') {
        // Članci s izvorima prometa
        $reportData = getGA4Report($startDate, $endDate, ['pageTitle', 'pagePath', 'sessionSource'], ['screenPageViews'], 200);
    } elseif ($reportType === 'daily') {
        $reportData = getGA4Report($startDate, $endDate, ['date'], ['screenPageViews', 'totalUsers', 'sessions'], 31);
    } elseif ($reportType === 'sources') {
        // Top izvori prometa
        $reportData = getGA4Report($startDate, $endDate, ['sessionSource', 'sessionMedium'], ['screenPageViews', 'totalUsers'], 30);
    } elseif ($reportType === 'article' && $pagePath) {
        // Detalji članka - izvori prometa
        $filter = [
            'filter' => [
                'fieldName' => 'pagePath',
                'stringFilter' => [
                    'matchType' => 'EXACT',
                    'value' => $pagePath
                ]
            ]
        ];
        $sourcesData = getGA4Report($startDate, $endDate, ['sessionSource', 'sessionMedium'], ['screenPageViews', 'totalUsers'], 20, $filter);

        // Dohvati naslov članka
        $titleData = getGA4Report($startDate, $endDate, ['pageTitle'], ['screenPageViews'], 1, $filter);
        if (isset($titleData['rows'][0])) {
            $pageTitle = $titleData['rows'][0]['dimensionValues'][0]['value'] ?? '';
        }
    }

    if (isset($reportData['error'])) {
        $error = $reportData['error'];
        $reportData = null;
    }
} else {
    $error = 'GA4 Property ID nije postavljen. Kontaktirajte administratora.';
}

// Grupiraj članke s izvorima
$articlesWithSources = [];
if ($reportType === 'pages' && $reportData && isset($reportData['rows'])) {
    foreach ($reportData['rows'] as $row) {
        $title = $row['dimensionValues'][0]['value'] ?? '-';
        $path = $row['dimensionValues'][1]['value'] ?? '';
        $source = $row['dimensionValues'][2]['value'] ?? '(direct)';
        $views = (int)($row['metricValues'][0]['value'] ?? 0);

        // Preskoči homepage i admin stranice
        if ($path === '/' || strpos($path, '/admin') === 0 || $title === '(not set)') continue;

        if (!isset($articlesWithSources[$path])) {
            $articlesWithSources[$path] = [
                'title' => $title,
                'path' => $path,
                'totalViews' => 0,
                'sources' => []
            ];
        }
        $articlesWithSources[$path]['totalViews'] += $views;
        $articlesWithSources[$path]['sources'][$source] = ($articlesWithSources[$path]['sources'][$source] ?? 0) + $views;
    }

    // Sortiraj po ukupnim pregledima
    uasort($articlesWithSources, function($a, $b) {
        return $b['totalViews'] - $a['totalViews'];
    });

    // Uzmi top 50
    $articlesWithSources = array_slice($articlesWithSources, 0, 50, true);

    // Sortiraj izvore za svaki članak
    foreach ($articlesWithSources as &$article) {
        arsort($article['sources']);
        $article['sources'] = array_slice($article['sources'], 0, 5, true);
    }
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
                <a href="?period=<?= $period ?>&report=sources"
                   class="btn btn-sm <?= $reportType === 'sources' ? 'btn-primary' : 'btn-outline' ?>"
                   style="border-radius: 0; border: none; border-left: 1px solid #d1d5db;">Izvori</a>
                <a href="?period=<?= $period ?>&report=daily"
                   class="btn btn-sm <?= $reportType === 'daily' ? 'btn-primary' : 'btn-outline' ?>"
                   style="border-radius: 0; border: none; border-left: 1px solid #d1d5db;">Po danima</a>
            </div>

            <span style="color: #6b7280; margin-left: auto;"><?= $periodLabel ?></span>
        </div>
    </div>
</div>

<?php if ($reportType === 'article' && $pagePath): ?>
<!-- Detalji članka -->
<div class="card" style="margin-bottom: 1rem;">
    <div class="card-header">
        <a href="?period=<?= $period ?>&report=pages" class="btn btn-sm btn-outline">← Natrag</a>
    </div>
    <div class="card-body">
        <h2 style="margin: 0 0 0.5rem 0;"><?= e($pageTitle ?: $pagePath) ?></h2>
        <p style="color: #6b7280; margin: 0; font-size: 0.875rem;"><?= e($pagePath) ?></p>
    </div>
</div>

<?php if ($sourcesData && isset($sourcesData['rows'])): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Izvori prometa za ovaj članak</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Izvor</th>
                    <th>Medium</th>
                    <th style="text-align: right;">Pregledi</th>
                    <th style="text-align: right;">Korisnici</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sourcesData['rows'] as $row):
                    $source = $row['dimensionValues'][0]['value'] ?? '(direct)';
                    $medium = $row['dimensionValues'][1]['value'] ?? '(none)';
                    $views = $row['metricValues'][0]['value'] ?? 0;
                    $users = $row['metricValues'][1]['value'] ?? 0;
                ?>
                <tr>
                    <td>
                        <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                            <?php if (strpos($source, 'google') !== false): ?>
                                <span style="color: #ea4335;">●</span>
                            <?php elseif (strpos($source, 'facebook') !== false || strpos($source, 'fb') !== false): ?>
                                <span style="color: #1877f2;">●</span>
                            <?php elseif ($source === '(direct)'): ?>
                                <span style="color: #10b981;">●</span>
                            <?php else: ?>
                                <span style="color: #6b7280;">●</span>
                            <?php endif; ?>
                            <strong><?= e($source) ?></strong>
                        </span>
                    </td>
                    <td style="color: #6b7280;"><?= e($medium) ?></td>
                    <td style="text-align: right; font-weight: 600;"><?= number_format($views) ?></td>
                    <td style="text-align: right;"><?= number_format($users) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php elseif ($reportType === 'pages' && !empty($articlesWithSources)): ?>
<!-- Najčitaniji članci s izvorima -->
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
                    <th style="width: 250px;">Top izvori</th>
                    <th style="width: 100px; text-align: right;">Pregledi</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 0; foreach ($articlesWithSources as $path => $article): $i++; ?>
                <tr>
                    <td style="color: #9ca3af;"><?= $i ?></td>
                    <td>
                        <a href="?period=<?= $period ?>&report=article&page=<?= urlencode($path) ?>" style="text-decoration: none; color: inherit;">
                            <div style="font-weight: 500;"><?= e(truncate($article['title'], 70)) ?></div>
                            <div style="font-size: 0.75rem; color: #9ca3af;"><?= e(truncate($path, 50)) ?></div>
                        </a>
                    </td>
                    <td>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                            <?php foreach ($article['sources'] as $source => $views):
                                $percent = round($views / $article['totalViews'] * 100);
                                if ($percent < 5) continue;

                                // Boja za izvor
                                if (strpos($source, 'google') !== false) {
                                    $color = '#ea4335';
                                    $bg = '#fef2f2';
                                } elseif (strpos($source, 'facebook') !== false || strpos($source, 'fb') !== false) {
                                    $color = '#1877f2';
                                    $bg = '#eff6ff';
                                } elseif ($source === '(direct)') {
                                    $color = '#10b981';
                                    $bg = '#ecfdf5';
                                } else {
                                    $color = '#6b7280';
                                    $bg = '#f3f4f6';
                                }
                            ?>
                            <span style="background: <?= $bg ?>; color: <?= $color ?>; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: 500;">
                                <?= e($source) ?> <?= $percent ?>%
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td style="text-align: right; font-weight: 600; color: #1e3a5f;"><?= number_format($article['totalViews']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($reportType === 'sources' && $reportData && isset($reportData['rows'])): ?>
<!-- Top izvori prometa -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Izvori prometa</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Izvor</th>
                    <th>Medium</th>
                    <th style="text-align: right;">Pregledi</th>
                    <th style="text-align: right;">Korisnici</th>
                    <th style="width: 200px;">Udio</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalViews = 0;
                foreach ($reportData['rows'] as $row) {
                    $totalViews += (int)($row['metricValues'][0]['value'] ?? 0);
                }

                foreach ($reportData['rows'] as $row):
                    $source = $row['dimensionValues'][0]['value'] ?? '(direct)';
                    $medium = $row['dimensionValues'][1]['value'] ?? '(none)';
                    $views = (int)($row['metricValues'][0]['value'] ?? 0);
                    $users = $row['metricValues'][1]['value'] ?? 0;
                    $percent = $totalViews > 0 ? round($views / $totalViews * 100, 1) : 0;

                    // Boja za izvor
                    if (strpos($source, 'google') !== false) {
                        $color = '#ea4335';
                    } elseif (strpos($source, 'facebook') !== false || strpos($source, 'fb') !== false) {
                        $color = '#1877f2';
                    } elseif ($source === '(direct)') {
                        $color = '#10b981';
                    } else {
                        $color = '#6b7280';
                    }
                ?>
                <tr>
                    <td>
                        <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                            <span style="color: <?= $color ?>;">●</span>
                            <strong><?= e($source) ?></strong>
                        </span>
                    </td>
                    <td style="color: #6b7280;"><?= e($medium) ?></td>
                    <td style="text-align: right; font-weight: 600;"><?= number_format($views) ?></td>
                    <td style="text-align: right;"><?= number_format($users) ?></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="flex: 1; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
                                <div style="width: <?= $percent ?>%; height: 100%; background: <?= $color ?>;"></div>
                            </div>
                            <span style="font-size: 0.75rem; color: #6b7280; width: 40px;"><?= $percent ?>%</span>
                        </div>
                    </td>
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
