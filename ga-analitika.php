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
function getGA4Report($startDate, $endDate, $dimensions, $metrics, $limit = 50, $dimensionFilter = null, $orderBy = null) {
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
        'limit' => $limit
    ];

    if ($orderBy) {
        $requestBody['orderBys'] = $orderBy;
    } else {
        $requestBody['orderBys'] = [
            ['metric' => ['metricName' => $metrics[0]], 'desc' => true]
        ];
    }

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

/**
 * Dohvati real-time podatke
 */
function getGA4Realtime() {
    if (empty(GA4_PROPERTY_ID)) {
        return ['error' => 'GA4 Property ID nije postavljen'];
    }

    $auth = getGoogleAccessToken();
    if (isset($auth['error'])) {
        return $auth;
    }

    $url = 'https://analyticsdata.googleapis.com/v1beta/properties/' . GA4_PROPERTY_ID . ':runRealtimeReport';

    $requestBody = [
        'dimensions' => [
            ['name' => 'unifiedScreenName']
        ],
        'metrics' => [
            ['name' => 'activeUsers']
        ],
        'limit' => 15
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
$reportType = $_GET['report'] ?? 'overview';
$pagePath = $_GET['page'] ?? '';

switch ($period) {
    case 'today':
        $startDate = 'today';
        $endDate = 'today';
        $compareStart = 'yesterday';
        $compareEnd = 'yesterday';
        $periodLabel = 'Danas';
        break;
    case 'yesterday':
        $startDate = 'yesterday';
        $endDate = 'yesterday';
        $compareStart = '2daysAgo';
        $compareEnd = '2daysAgo';
        $periodLabel = 'Jučer';
        break;
    case '7days':
        $startDate = '7daysAgo';
        $endDate = 'today';
        $compareStart = '14daysAgo';
        $compareEnd = '8daysAgo';
        $periodLabel = 'Zadnjih 7 dana';
        break;
    case '30days':
        $startDate = '30daysAgo';
        $endDate = 'today';
        $compareStart = '60daysAgo';
        $compareEnd = '31daysAgo';
        $periodLabel = 'Zadnjih 30 dana';
        break;
    case 'year':
        $startDate = '365daysAgo';
        $endDate = 'today';
        $compareStart = '730daysAgo';
        $compareEnd = '366daysAgo';
        $periodLabel = 'Zadnjih 365 dana';
        break;
    case 'month':
        // Ovaj mjesec do danas
        $startDate = date('Y-m-01'); // Prvi dan ovog mjeseca
        $endDate = 'today';
        // Prošli mjesec za usporedbu (isti broj dana)
        $daysIntoMonth = date('j'); // Koji je danas dan u mjesecu
        $lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
        $lastMonthEnd = date('Y-m-d', strtotime($lastMonthStart . ' + ' . ($daysIntoMonth - 1) . ' days'));
        $compareStart = $lastMonthStart;
        $compareEnd = $lastMonthEnd;
        $periodLabel = 'Ovaj mjesec';
        break;
    default:
        // Provjeri je li odabran specifičan mjesec (format: month_YYYY_MM)
        if (preg_match('/^month_(\d{4})_(\d{2})$/', $period, $matches)) {
            $year = $matches[1];
            $month = $matches[2];
            $startDate = "$year-$month-01";
            $endDate = date('Y-m-t', strtotime($startDate)); // Zadnji dan mjeseca

            // Usporedba s istim mjesecom prošle godine
            $prevYear = $year - 1;
            $compareStart = "$prevYear-$month-01";
            $compareEnd = date('Y-m-t', strtotime($compareStart));

            $periodLabel = strftime('%B %Y', strtotime($startDate));
            break;
        }
        $startDate = '7daysAgo';
        $endDate = 'today';
        $compareStart = '14daysAgo';
        $compareEnd = '8daysAgo';
        $periodLabel = 'Zadnjih 7 dana';
}

$error = null;

// Dohvati podatke ovisno o tipu reporta
$reportData = null;
$compareData = null;
$realtimeData = null;

if (!empty(GA4_PROPERTY_ID)) {
    switch ($reportType) {
        case 'overview':
            // Osnovne metrike
            $reportData = getGA4Report($startDate, $endDate, [],
                ['screenPageViews', 'totalUsers', 'sessions', 'averageSessionDuration', 'bounceRate', 'newUsers'], 1);
            // Prethodni period za usporedbu
            $compareData = getGA4Report($compareStart, $compareEnd, [],
                ['screenPageViews', 'totalUsers', 'sessions', 'averageSessionDuration', 'bounceRate', 'newUsers'], 1);
            // Real-time
            $realtimeData = getGA4Realtime();
            break;

        case 'published':
            // Dohvati RSS feed za zagorje.com
            $rssUrl = 'https://www.zagorje.com/rss';
            $rssData = @file_get_contents($rssUrl);
            $rssArticles = [];

            if ($rssData) {
                $xml = @simplexml_load_string($rssData);
                if ($xml && isset($xml->channel->item)) {
                    foreach ($xml->channel->item as $item) {
                        $pubDate = isset($item->pubDate) ? strtotime((string)$item->pubDate) : null;
                        $rssArticles[] = [
                            'title' => (string)$item->title,
                            'link' => (string)$item->link,
                            'pubDate' => $pubDate,
                            'description' => (string)$item->description
                        ];
                    }
                }
            }

            // Dohvati GA4 podatke za članke (zadnjih 30 dana za bolji matching)
            $gaData = getGA4Report('30daysAgo', 'today',
                ['pageTitle', 'pagePath'],
                ['screenPageViews'], 500);

            // Napravi mapu naslova -> pregledi
            $viewsByTitle = [];
            if ($gaData && isset($gaData['rows'])) {
                foreach ($gaData['rows'] as $row) {
                    $title = trim($row['dimensionValues'][0]['value'] ?? '');
                    $views = (int)($row['metricValues'][0]['value'] ?? 0);
                    // Ukloni " - Zagorje.com" suffix ako postoji
                    $cleanTitle = preg_replace('/\s*[-–|]\s*(Zagorje\.com|zagorje\.com)$/i', '', $title);
                    if (!isset($viewsByTitle[$cleanTitle])) {
                        $viewsByTitle[$cleanTitle] = 0;
                    }
                    $viewsByTitle[$cleanTitle] += $views;
                }
            }

            $reportData = ['articles' => $rssArticles, 'viewsByTitle' => $viewsByTitle];
            break;

        case 'pages':
            // Članci s engagement metrikom
            $reportData = getGA4Report($startDate, $endDate,
                ['pageTitle', 'pagePath'],
                ['screenPageViews', 'averageSessionDuration', 'bounceRate'], 100);
            break;

        case 'trending':
            // Članci iz ovog perioda
            $currentData = getGA4Report($startDate, $endDate, ['pageTitle', 'pagePath'], ['screenPageViews'], 100);
            // Članci iz prethodnog perioda
            $previousData = getGA4Report($compareStart, $compareEnd, ['pageTitle', 'pagePath'], ['screenPageViews'], 100);
            $reportData = ['current' => $currentData, 'previous' => $previousData];
            break;

        case 'sources':
            $reportData = getGA4Report($startDate, $endDate,
                ['sessionSource', 'sessionMedium'],
                ['screenPageViews', 'totalUsers', 'sessions'], 30);
            break;

        case 'daily':
            $reportData = getGA4Report($startDate, $endDate,
                ['date'],
                ['screenPageViews', 'totalUsers', 'sessions', 'averageSessionDuration'], 31,
                null,
                [['dimension' => ['dimensionName' => 'date'], 'desc' => true]]);
            break;

        case 'hourly':
            $reportData = getGA4Report($startDate, $endDate,
                ['hour'],
                ['screenPageViews', 'totalUsers'], 24,
                null,
                [['dimension' => ['dimensionName' => 'hour'], 'desc' => false]]);
            break;

        case 'geography':
            $reportData = getGA4Report($startDate, $endDate,
                ['country', 'city'],
                ['screenPageViews', 'totalUsers'], 50);
            break;

        case 'devices':
            $reportData = getGA4Report($startDate, $endDate,
                ['deviceCategory'],
                ['screenPageViews', 'totalUsers', 'sessions'], 10);
            break;

        case 'landing':
            $reportData = getGA4Report($startDate, $endDate,
                ['landingPagePlusQueryString'],
                ['sessions', 'totalUsers', 'bounceRate'], 50);
            break;

        case 'search':
            // Google search keywords
            $reportData = getGA4Report($startDate, $endDate,
                ['sessionGoogleAdsQuery'],
                ['sessions', 'totalUsers'], 50);
            // Fallback to source/medium
            if (empty($reportData['rows'])) {
                $reportData = getGA4Report($startDate, $endDate,
                    ['sessionDefaultChannelGroup'],
                    ['sessions', 'totalUsers'], 20);
            }
            break;

        case 'article':
            if ($pagePath) {
                $filter = [
                    'filter' => [
                        'fieldName' => 'pagePath',
                        'stringFilter' => [
                            'matchType' => 'EXACT',
                            'value' => $pagePath
                        ]
                    ]
                ];
                // Izvori prometa za članak
                $sourcesData = getGA4Report($startDate, $endDate,
                    ['sessionSource', 'sessionMedium'],
                    ['screenPageViews', 'totalUsers'], 20, $filter);
                // Dnevna statistika članka
                $dailyData = getGA4Report($startDate, $endDate,
                    ['date'],
                    ['screenPageViews'], 31, $filter,
                    [['dimension' => ['dimensionName' => 'date'], 'desc' => false]]);
                // Naslov
                $titleData = getGA4Report($startDate, $endDate,
                    ['pageTitle'],
                    ['screenPageViews', 'averageSessionDuration', 'bounceRate'], 1, $filter);
                $reportData = [
                    'sources' => $sourcesData,
                    'daily' => $dailyData,
                    'title' => $titleData
                ];
            }
            break;

        case 'revenue':
            // Samo za admine
            if (isAdmin()) {
                // Ovaj mjesec do danas
                $revenueStartDate = date('Y-m-01');
                $revenueEndDate = 'today';

                // Isti period prošlog mjeseca (1. do istog dana u mjesecu)
                $revenueCompareStart = date('Y-m-01', strtotime('first day of last month'));
                $revenueCompareEnd = date('Y-m-d', strtotime('-1 month'));

                // Zarada po danima (ovaj mjesec) - koristimo za ukupan zbroj
                $dailyRevenue = getGA4Report($revenueStartDate, $revenueEndDate,
                    ['date'],
                    ['totalAdRevenue'], 31,
                    null,
                    [['dimension' => ['dimensionName' => 'date'], 'desc' => true]]);

                // Zarada po danima prošle godine (za usporedbu)
                $compareDailyRevenue = getGA4Report($revenueCompareStart, $revenueCompareEnd,
                    ['date'],
                    ['totalAdRevenue'], 31,
                    null,
                    [['dimension' => ['dimensionName' => 'date'], 'desc' => true]]);

                // Top članci po zaradi
                $topRevenue = getGA4Report($revenueStartDate, $revenueEndDate,
                    ['pageTitle', 'pagePath'],
                    ['totalAdRevenue', 'screenPageViews'], 50);

                // Zarada po izvorima
                $sourceRevenue = getGA4Report($revenueStartDate, $revenueEndDate,
                    ['sessionSource'],
                    ['totalAdRevenue', 'screenPageViews'], 20);

                $reportData = [
                    'daily' => $dailyRevenue,
                    'compareDaily' => $compareDailyRevenue,
                    'top' => $topRevenue,
                    'sources' => $sourceRevenue
                ];
            }
            break;
    }

    if (isset($reportData['error'])) {
        $error = $reportData['error'];
        $reportData = null;
    }
} else {
    $error = 'GA4 Property ID nije postavljen.';
}

// Helper za formatiranje trajanja
function formatDuration($seconds) {
    $seconds = round($seconds);
    if ($seconds < 60) return $seconds . 's';
    $minutes = floor($seconds / 60);
    $secs = $seconds % 60;
    return $minutes . 'm ' . $secs . 's';
}

// Helper za izračun promjene
function calcChange($current, $previous) {
    if ($previous == 0) return $current > 0 ? 100 : 0;
    return round(($current - $previous) / $previous * 100, 1);
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>GA4 Analitika</h1>
    <p style="color: #6b7280; margin: 0.25rem 0 0 0;">Statistike posjećenosti portala zagorje.com</p>
</div>

<?php if ($error): ?>
<div class="alert alert-danger">
    <?= e($error) ?>
</div>
<?php else: ?>

<!-- Period filter -->
<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body" style="padding: 0.75rem;">
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
            <div style="display: flex; gap: 0; border: 1px solid #d1d5db; border-radius: 6px; overflow: hidden;">
                <?php foreach (['today' => 'Danas', 'yesterday' => 'Jučer', '7days' => '7 dana', '30days' => '30 dana', 'year' => 'Godina'] as $p => $label): ?>
                <a href="?period=<?= $p ?>&report=<?= $reportType ?>"
                   class="btn btn-sm <?= $period === $p ? 'btn-primary' : 'btn-outline' ?>"
                   style="border-radius: 0; border: none; <?= $p !== 'today' ? 'border-left: 1px solid #d1d5db;' : '' ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </div>

            <!-- Odabir mjeseca -->
            <select onchange="if(this.value) window.location='?period='+this.value+'&report=<?= $reportType ?>'"
                    class="form-control" style="width: auto; padding: 0.4rem 0.75rem; font-size: 0.875rem;">
                <option value="">-- Odaberi mjesec --</option>
                <?php
                for ($i = 0; $i < 12; $i++) {
                    $monthDate = strtotime("-$i months");
                    $monthKey = 'month_' . date('Y_m', $monthDate);
                    $monthLabel = strftime('%B %Y', $monthDate);
                    $selected = ($period === $monthKey) ? 'selected' : '';
                    echo "<option value=\"$monthKey\" $selected>$monthLabel</option>";
                }
                ?>
            </select>

            <span style="color: #6b7280; margin-left: auto; font-size: 0.875rem;"><?= $periodLabel ?></span>
        </div>
    </div>
</div>

<!-- Report tabs -->
<div style="display: flex; gap: 0.25rem; flex-wrap: wrap; margin-bottom: 1rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem;">
    <?php
    $tabs = [
        'overview' => 'Pregled',
        'published' => 'Objavljeno',
        'pages' => 'Članci',
        'trending' => 'Trending',
        'sources' => 'Izvori',
        'daily' => 'Po danima',
        'hourly' => 'Po satima',
        'geography' => 'Geografija',
        'devices' => 'Uređaji',
        'landing' => 'Landing',
    ];
    // Zarada samo za admine
    if (isAdmin()) {
        $tabs['revenue'] = '💰 Zarada';
    }
    foreach ($tabs as $t => $label):
    ?>
    <a href="?period=<?= $period ?>&report=<?= $t ?>"
       style="padding: 0.5rem 1rem; text-decoration: none; border-radius: 6px 6px 0 0; font-size: 0.875rem;
              <?= $reportType === $t ? 'background: #2563eb; color: white; font-weight: 500;' : 'color: #6b7280;' ?>
              <?= $t === 'revenue' ? 'background: ' . ($reportType === 'revenue' ? '#16a34a' : 'transparent') . '; color: ' . ($reportType === 'revenue' ? 'white' : '#16a34a') . ';' : '' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if ($reportType === 'overview'): ?>
<!-- PREGLED - Overview cards -->
<?php
$metrics = [
    'views' => ['label' => 'Pregledi', 'icon' => '👁️', 'current' => 0, 'previous' => 0],
    'users' => ['label' => 'Korisnici', 'icon' => '👥', 'current' => 0, 'previous' => 0],
    'sessions' => ['label' => 'Sesije', 'icon' => '📊', 'current' => 0, 'previous' => 0],
    'duration' => ['label' => 'Pros. vrijeme', 'icon' => '⏱️', 'current' => 0, 'previous' => 0],
    'bounce' => ['label' => 'Bounce rate', 'icon' => '↩️', 'current' => 0, 'previous' => 0],
    'newUsers' => ['label' => 'Novi korisnici', 'icon' => '🆕', 'current' => 0, 'previous' => 0],
];

if ($reportData && isset($reportData['rows'][0])) {
    $row = $reportData['rows'][0]['metricValues'];
    $metrics['views']['current'] = (int)($row[0]['value'] ?? 0);
    $metrics['users']['current'] = (int)($row[1]['value'] ?? 0);
    $metrics['sessions']['current'] = (int)($row[2]['value'] ?? 0);
    $metrics['duration']['current'] = (float)($row[3]['value'] ?? 0);
    $metrics['bounce']['current'] = (float)($row[4]['value'] ?? 0) * 100;
    $metrics['newUsers']['current'] = (int)($row[5]['value'] ?? 0);
}

if ($compareData && isset($compareData['rows'][0])) {
    $row = $compareData['rows'][0]['metricValues'];
    $metrics['views']['previous'] = (int)($row[0]['value'] ?? 0);
    $metrics['users']['previous'] = (int)($row[1]['value'] ?? 0);
    $metrics['sessions']['previous'] = (int)($row[2]['value'] ?? 0);
    $metrics['duration']['previous'] = (float)($row[3]['value'] ?? 0);
    $metrics['bounce']['previous'] = (float)($row[4]['value'] ?? 0) * 100;
    $metrics['newUsers']['previous'] = (int)($row[5]['value'] ?? 0);
}

$realtimeUsers = 0;
$realtimePages = [];
if ($realtimeData && isset($realtimeData['rows'])) {
    foreach ($realtimeData['rows'] as $row) {
        $realtimeUsers += (int)($row['metricValues'][0]['value'] ?? 0);
        $realtimePages[] = [
            'page' => $row['dimensionValues'][0]['value'] ?? '',
            'users' => (int)($row['metricValues'][0]['value'] ?? 0)
        ];
    }
}
?>

<!-- Real-time card -->
<div class="card" style="margin-bottom: 1rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
    <div class="card-body">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="font-size: 2.5rem;">🟢</div>
            <div>
                <div style="font-size: 2rem; font-weight: 700;"><?= $realtimeUsers ?></div>
                <div style="opacity: 0.9;">Aktivnih korisnika upravo sada</div>
            </div>
        </div>
        <?php if (!empty($realtimePages)): ?>
        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.2);">
            <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.5rem;">Trenutno čitaju:</div>
            <?php foreach (array_slice($realtimePages, 0, 10) as $rp): ?>
            <div style="font-size: 0.8rem; opacity: 0.85; margin-bottom: 0.35rem; line-height: 1.3;">
                • <?= e($rp['page']) ?> <strong>(<?= $rp['users'] ?>)</strong>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Period info -->
<div style="background: #f3f4f6; padding: 0.5rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.875rem; color: #6b7280;">
    <?php if ($period === 'month'): ?>
        📅 <strong>1. - <?= date('j') ?>. <?= date('F Y') ?></strong> vs 1. - <?= date('j') ?>. <?= date('F Y', strtotime('first day of last month')) ?>
    <?php elseif ($period === '7days'): ?>
        📅 <strong>Zadnjih 7 dana</strong> vs prethodnih 7 dana
    <?php elseif ($period === '30days'): ?>
        📅 <strong>Zadnjih 30 dana</strong> vs prethodnih 30 dana
    <?php else: ?>
        📅 <strong><?= $periodLabel ?></strong> — usporedba s prethodnim periodom
    <?php endif; ?>
</div>

<!-- Metric cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
    <?php foreach ($metrics as $key => $m):
        $change = calcChange($m['current'], $m['previous']);
        $isPositive = $key === 'bounce' ? $change < 0 : $change > 0;
        $displayValue = $key === 'duration' ? formatDuration($m['current']) :
                       ($key === 'bounce' ? round($m['current'], 1) . '%' : number_format($m['current']));
    ?>
    <div class="card">
        <div class="card-body" style="padding: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="font-size: 1.5rem;"><?= $m['icon'] ?></div>
                <?php if ($change != 0): ?>
                <div style="font-size: 0.75rem; padding: 2px 6px; border-radius: 4px;
                            background: <?= $isPositive ? '#dcfce7' : '#fee2e2' ?>;
                            color: <?= $isPositive ? '#16a34a' : '#dc2626' ?>;">
                    <?= $change > 0 ? '+' : '' ?><?= $change ?>%
                </div>
                <?php endif; ?>
            </div>
            <div style="font-size: 1.5rem; font-weight: 700; margin: 0.5rem 0;"><?= $displayValue ?></div>
            <div style="font-size: 0.875rem; color: #6b7280;"><?= $m['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Novi vs povratni korisnici -->
<?php
$newUsersPercent = $metrics['users']['current'] > 0 ? round($metrics['newUsers']['current'] / $metrics['users']['current'] * 100) : 0;
$returningPercent = 100 - $newUsersPercent;
?>
<div class="card" style="margin-bottom: 1rem;">
    <div class="card-header"><h3 class="card-title">Novi vs povratni korisnici</h3></div>
    <div class="card-body">
        <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>🆕 Novi korisnici</span>
                    <strong><?= $newUsersPercent ?>%</strong>
                </div>
                <div style="height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
                    <div style="width: <?= $newUsersPercent ?>%; height: 100%; background: #3b82f6;"></div>
                </div>
            </div>
            <div style="flex: 1; min-width: 200px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>🔄 Povratni korisnici</span>
                    <strong><?= $returningPercent ?>%</strong>
                </div>
                <div style="height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
                    <div style="width: <?= $returningPercent ?>%; height: 100%; background: #10b981;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($reportType === 'published' && $reportData && isset($reportData['articles'])): ?>
<!-- OBJAVLJENO - RSS ČLANCI -->
<?php
$articles = $reportData['articles'];
$viewsByTitle = $reportData['viewsByTitle'] ?? [];
$now = time();
$todayStart = strtotime('today');
$weekStart = strtotime('-7 days');
$monthStart = strtotime('-30 days');

// Brojači
$countToday = 0;
$countWeek = 0;
$countMonth = 0;
$totalViews = 0;

foreach ($articles as $article) {
    if ($article['pubDate']) {
        if ($article['pubDate'] >= $todayStart) $countToday++;
        if ($article['pubDate'] >= $weekStart) $countWeek++;
        if ($article['pubDate'] >= $monthStart) $countMonth++;
    }
    // Zbroji preglede
    $cleanTitle = trim($article['title']);
    if (isset($viewsByTitle[$cleanTitle])) {
        $totalViews += $viewsByTitle[$cleanTitle];
    }
}
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="card" style="text-align: center; padding: 1.25rem;">
        <div style="font-size: 2rem; font-weight: 700; color: #3b82f6;"><?= $countToday ?></div>
        <div style="font-size: 0.875rem; color: #6b7280;">Danas</div>
    </div>
    <div class="card" style="text-align: center; padding: 1.25rem;">
        <div style="font-size: 2rem; font-weight: 700; color: #10b981;"><?= $countWeek ?></div>
        <div style="font-size: 0.875rem; color: #6b7280;">7 dana</div>
    </div>
    <div class="card" style="text-align: center; padding: 1.25rem;">
        <div style="font-size: 2rem; font-weight: 700; color: #8b5cf6;"><?= $countMonth ?></div>
        <div style="font-size: 0.875rem; color: #6b7280;">30 dana</div>
    </div>
    <div class="card" style="text-align: center; padding: 1.25rem;">
        <div style="font-size: 2rem; font-weight: 700; color: #f59e0b;"><?= count($articles) ?></div>
        <div style="font-size: 0.875rem; color: #6b7280;">U RSS-u</div>
    </div>
    <div class="card" style="text-align: center; padding: 1.25rem;">
        <div style="font-size: 2rem; font-weight: 700; color: #dc2626;"><?= number_format($totalViews, 0, ',', '.') ?></div>
        <div style="font-size: 0.875rem; color: #6b7280;">Pregledi (30d)</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Objavljeni članci - zagorje.com</h2>
    </div>
    <?php if (empty($articles)): ?>
        <div class="card-body">
            <p style="color: #6b7280; text-align: center;">RSS feed nije dostupan ili nema članaka.</p>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 140px;">Objavljeno</th>
                    <th>Naslov</th>
                    <th style="width: 100px; text-align: right;">Pregledi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($articles as $article):
                    $pubDateFormatted = $article['pubDate'] ? date('d.m.Y H:i', $article['pubDate']) : '-';
                    $isToday = $article['pubDate'] && $article['pubDate'] >= $todayStart;
                    $isRecent = $article['pubDate'] && $article['pubDate'] >= strtotime('-2 hours');
                    $cleanTitle = trim($article['title']);
                    $articleViews = $viewsByTitle[$cleanTitle] ?? 0;
                ?>
                <tr>
                    <td>
                        <span style="<?= $isRecent ? 'color: #dc2626; font-weight: 600;' : ($isToday ? 'color: #059669; font-weight: 500;' : 'color: #6b7280;') ?>">
                            <?= $pubDateFormatted ?>
                        </span>
                        <?php if ($isRecent): ?>
                            <span style="background: #fee2e2; color: #dc2626; font-size: 0.625rem; padding: 2px 4px; border-radius: 3px; margin-left: 4px;">NOVO</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= e($article['link']) ?>" target="_blank" style="text-decoration: none; color: inherit;">
                            <div style="font-weight: 500;"><?= e($article['title']) ?></div>
                        </a>
                    </td>
                    <td style="text-align: right;">
                        <?php if ($articleViews > 0): ?>
                            <span style="font-weight: 600; color: <?= $articleViews > 1000 ? '#059669' : ($articleViews > 100 ? '#2563eb' : '#6b7280') ?>;">
                                <?= number_format($articleViews, 0, ',', '.') ?>
                            </span>
                        <?php else: ?>
                            <span style="color: #d1d5db;">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php elseif ($reportType === 'pages' && $reportData && isset($reportData['rows'])): ?>
<!-- ČLANCI -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Najčitaniji članci</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Naslov</th>
                    <th style="text-align: right;">Pregledi</th>
                    <th style="text-align: right;">Vrijeme</th>
                    <th style="text-align: right;">Bounce</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 0;
                foreach ($reportData['rows'] as $row):
                    $title = $row['dimensionValues'][0]['value'] ?? '-';
                    $path = $row['dimensionValues'][1]['value'] ?? '';
                    $views = (int)($row['metricValues'][0]['value'] ?? 0);
                    $duration = (float)($row['metricValues'][1]['value'] ?? 0);
                    $bounce = (float)($row['metricValues'][2]['value'] ?? 0) * 100;

                    if ($path === '/' || strpos($path, '/admin') === 0 || $title === '(not set)') continue;
                    $i++;
                    if ($i > 50) break;
                ?>
                <tr>
                    <td style="color: #9ca3af;"><?= $i ?></td>
                    <td>
                        <a href="?period=<?= $period ?>&report=article&page=<?= urlencode($path) ?>" style="text-decoration: none; color: inherit;">
                            <div style="font-weight: 500;"><?= e(truncate($title, 60)) ?></div>
                            <div style="font-size: 0.75rem; color: #9ca3af;"><?= e(truncate($path, 50)) ?></div>
                        </a>
                    </td>
                    <td style="text-align: right; font-weight: 600;"><?= number_format($views) ?></td>
                    <td style="text-align: right; color: #6b7280;"><?= formatDuration($duration) ?></td>
                    <td style="text-align: right;">
                        <span style="padding: 2px 6px; border-radius: 4px; font-size: 0.75rem;
                                     background: <?= $bounce > 70 ? '#fee2e2' : ($bounce > 50 ? '#fef3c7' : '#dcfce7') ?>;
                                     color: <?= $bounce > 70 ? '#dc2626' : ($bounce > 50 ? '#d97706' : '#16a34a') ?>;">
                            <?= round($bounce) ?>%
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($reportType === 'trending'): ?>
<!-- TRENDING -->
<?php
$trending = [];
$currentArticles = [];
$previousArticles = [];

if (isset($reportData['current']['rows'])) {
    foreach ($reportData['current']['rows'] as $row) {
        $path = $row['dimensionValues'][1]['value'] ?? '';
        $title = $row['dimensionValues'][0]['value'] ?? '';
        $views = (int)($row['metricValues'][0]['value'] ?? 0);
        if ($path === '/' || strpos($path, '/admin') === 0 || $title === '(not set)') continue;
        $currentArticles[$path] = ['title' => $title, 'views' => $views];
    }
}

if (isset($reportData['previous']['rows'])) {
    foreach ($reportData['previous']['rows'] as $row) {
        $path = $row['dimensionValues'][1]['value'] ?? '';
        $views = (int)($row['metricValues'][0]['value'] ?? 0);
        $previousArticles[$path] = $views;
    }
}

foreach ($currentArticles as $path => $data) {
    $prevViews = $previousArticles[$path] ?? 0;
    $change = calcChange($data['views'], $prevViews);
    $trending[$path] = [
        'title' => $data['title'],
        'path' => $path,
        'current' => $data['views'],
        'previous' => $prevViews,
        'change' => $change
    ];
}

// Sortiraj po promjeni (najviši rast)
uasort($trending, function($a, $b) {
    return $b['change'] - $a['change'];
});

$trending = array_slice($trending, 0, 30, true);
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">🔥 Trending članci (najveći rast)</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Naslov</th>
                    <th style="text-align: right;">Sada</th>
                    <th style="text-align: right;">Prije</th>
                    <th style="text-align: right;">Promjena</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 0; foreach ($trending as $article): $i++; ?>
                <tr>
                    <td style="color: #9ca3af;"><?= $i ?></td>
                    <td>
                        <a href="?period=<?= $period ?>&report=article&page=<?= urlencode($article['path']) ?>" style="text-decoration: none; color: inherit;">
                            <?= e(truncate($article['title'], 60)) ?>
                        </a>
                    </td>
                    <td style="text-align: right; font-weight: 600;"><?= number_format($article['current']) ?></td>
                    <td style="text-align: right; color: #9ca3af;"><?= number_format($article['previous']) ?></td>
                    <td style="text-align: right;">
                        <?php if ($article['change'] > 0): ?>
                        <span style="background: #dcfce7; color: #16a34a; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                            ↑ +<?= $article['change'] ?>%
                        </span>
                        <?php elseif ($article['change'] < 0): ?>
                        <span style="background: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 4px;">
                            ↓ <?= $article['change'] ?>%
                        </span>
                        <?php else: ?>
                        <span style="color: #9ca3af;">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($reportType === 'sources' && $reportData && isset($reportData['rows'])): ?>
<!-- IZVORI -->
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

                    if (strpos($source, 'google') !== false) $color = '#ea4335';
                    elseif (strpos($source, 'facebook') !== false || strpos($source, 'fb') !== false) $color = '#1877f2';
                    elseif ($source === '(direct)') $color = '#10b981';
                    else $color = '#6b7280';
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
<!-- PO DANIMA -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Statistika po danima</h2>
    </div>
    <div class="card-body" style="padding: 1rem;">
        <!-- Mini chart -->
        <?php
        $chartData = [];
        $maxViews = 0;
        foreach ($reportData['rows'] as $row) {
            $views = (int)($row['metricValues'][0]['value'] ?? 0);
            $chartData[] = $views;
            if ($views > $maxViews) $maxViews = $views;
        }
        $chartData = array_reverse($chartData);
        ?>
        <div style="display: flex; align-items: flex-end; gap: 4px; height: 100px; margin-bottom: 1rem;">
            <?php foreach ($chartData as $views):
                $height = $maxViews > 0 ? ($views / $maxViews * 100) : 0;
            ?>
            <div style="flex: 1; background: #3b82f6; border-radius: 2px 2px 0 0; height: <?= $height ?>%; min-height: 2px;"
                 title="<?= number_format($views) ?> pregleda"></div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th style="text-align: right;">Pregledi</th>
                    <th style="text-align: right;">Korisnici</th>
                    <th style="text-align: right;">Sesije</th>
                    <th style="text-align: right;">Pros. vrijeme</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData['rows'] as $row):
                    $dateStr = $row['dimensionValues'][0]['value'] ?? '';
                    $date = DateTime::createFromFormat('Ymd', $dateStr);
                    $formattedDate = $date ? $date->format('d.m.Y.') : $dateStr;
                    $dayName = $date ? ['Ned', 'Pon', 'Uto', 'Sri', 'Čet', 'Pet', 'Sub'][$date->format('w')] : '';

                    $views = $row['metricValues'][0]['value'] ?? 0;
                    $users = $row['metricValues'][1]['value'] ?? 0;
                    $sessions = $row['metricValues'][2]['value'] ?? 0;
                    $duration = (float)($row['metricValues'][3]['value'] ?? 0);
                ?>
                <tr>
                    <td>
                        <span style="font-weight: 500;"><?= $formattedDate ?></span>
                        <span style="color: #9ca3af; margin-left: 0.5rem;"><?= $dayName ?></span>
                    </td>
                    <td style="text-align: right; font-weight: 600; color: #1e3a5f;"><?= number_format($views) ?></td>
                    <td style="text-align: right;"><?= number_format($users) ?></td>
                    <td style="text-align: right;"><?= number_format($sessions) ?></td>
                    <td style="text-align: right; color: #6b7280;"><?= formatDuration($duration) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($reportType === 'hourly' && $reportData && isset($reportData['rows'])): ?>
<!-- PO SATIMA - Heatmapa -->
<?php
$hourlyData = array_fill(0, 24, ['views' => 0, 'users' => 0]);
$maxHourViews = 0;
foreach ($reportData['rows'] as $row) {
    $hour = (int)($row['dimensionValues'][0]['value'] ?? 0);
    $views = (int)($row['metricValues'][0]['value'] ?? 0);
    $users = (int)($row['metricValues'][1]['value'] ?? 0);
    $hourlyData[$hour] = ['views' => $views, 'users' => $users];
    if ($views > $maxHourViews) $maxHourViews = $views;
}
?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">🕐 Aktivnost po satima</h2>
    </div>
    <div class="card-body">
        <p style="color: #6b7280; margin-bottom: 1rem;">Kada su korisnici najaktivniji? Tamnije = više prometa.</p>

        <div style="display: grid; grid-template-columns: repeat(12, 1fr); gap: 4px; margin-bottom: 1.5rem;">
            <?php for ($h = 0; $h < 24; $h++):
                $intensity = $maxHourViews > 0 ? ($hourlyData[$h]['views'] / $maxHourViews) : 0;
                $bgColor = "rgba(37, 99, 235, " . (0.1 + $intensity * 0.9) . ")";
                $textColor = $intensity > 0.5 ? 'white' : '#1e3a5f';
            ?>
            <div style="background: <?= $bgColor ?>; padding: 0.75rem 0.5rem; border-radius: 4px; text-align: center;"
                 title="<?= $h ?>:00 - <?= number_format($hourlyData[$h]['views']) ?> pregleda">
                <div style="font-size: 0.7rem; color: <?= $textColor ?>; opacity: 0.8;"><?= $h ?>h</div>
                <div style="font-size: 0.9rem; font-weight: 600; color: <?= $textColor ?>;"><?= number_format($hourlyData[$h]['views']) ?></div>
            </div>
            <?php endfor; ?>
        </div>

        <!-- Legenda -->
        <div style="display: flex; align-items: center; gap: 1rem; justify-content: center;">
            <span style="font-size: 0.75rem; color: #6b7280;">Manje</span>
            <div style="display: flex; gap: 2px;">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <div style="width: 20px; height: 12px; background: rgba(37, 99, 235, <?= $i * 0.2 ?>); border-radius: 2px;"></div>
                <?php endfor; ?>
            </div>
            <span style="font-size: 0.75rem; color: #6b7280;">Više</span>
        </div>
    </div>
</div>

<?php elseif ($reportType === 'geography' && $reportData && isset($reportData['rows'])): ?>
<!-- GEOGRAFIJA -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">🌍 Geografija posjetitelja</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Lokacija</th>
                    <th style="text-align: right;">Pregledi</th>
                    <th style="text-align: right;">Korisnici</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 0; foreach ($reportData['rows'] as $row): $i++;
                    $country = $row['dimensionValues'][0]['value'] ?? '-';
                    $city = $row['dimensionValues'][1]['value'] ?? '-';
                    $views = $row['metricValues'][0]['value'] ?? 0;
                    $users = $row['metricValues'][1]['value'] ?? 0;

                    // Emoji zastave za poznate zemlje
                    $flags = ['Croatia' => '🇭🇷', 'Germany' => '🇩🇪', 'Austria' => '🇦🇹', 'Slovenia' => '🇸🇮',
                              'United States' => '🇺🇸', 'United Kingdom' => '🇬🇧', 'Serbia' => '🇷🇸',
                              'Bosnia & Herzegovina' => '🇧🇦', 'Switzerland' => '🇨🇭', 'Italy' => '🇮🇹'];
                    $flag = $flags[$country] ?? '🌐';
                ?>
                <tr>
                    <td style="color: #9ca3af;"><?= $i ?></td>
                    <td>
                        <span style="margin-right: 0.5rem;"><?= $flag ?></span>
                        <strong><?= e($city !== '(not set)' ? $city : $country) ?></strong>
                        <?php if ($city !== '(not set)'): ?>
                        <span style="color: #9ca3af; margin-left: 0.5rem;"><?= e($country) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right; font-weight: 600;"><?= number_format($views) ?></td>
                    <td style="text-align: right;"><?= number_format($users) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($reportType === 'devices' && $reportData && isset($reportData['rows'])): ?>
<!-- UREĐAJI -->
<?php
$devices = ['desktop' => ['label' => 'Desktop', 'icon' => '🖥️', 'color' => '#3b82f6'],
            'mobile' => ['label' => 'Mobitel', 'icon' => '📱', 'color' => '#10b981'],
            'tablet' => ['label' => 'Tablet', 'icon' => '📲', 'color' => '#f59e0b']];
$deviceData = [];
$totalSessions = 0;

foreach ($reportData['rows'] as $row) {
    $device = strtolower($row['dimensionValues'][0]['value'] ?? 'other');
    $sessions = (int)($row['metricValues'][2]['value'] ?? 0);
    $deviceData[$device] = $sessions;
    $totalSessions += $sessions;
}
?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📱 Uređaji</h2>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 2rem; flex-wrap: wrap; justify-content: center; margin-bottom: 2rem;">
            <?php foreach ($devices as $key => $d):
                $sessions = $deviceData[$key] ?? 0;
                $percent = $totalSessions > 0 ? round($sessions / $totalSessions * 100) : 0;
            ?>
            <div style="text-align: center; min-width: 120px;">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;"><?= $d['icon'] ?></div>
                <div style="font-size: 2rem; font-weight: 700; color: <?= $d['color'] ?>;"><?= $percent ?>%</div>
                <div style="color: #6b7280;"><?= $d['label'] ?></div>
                <div style="font-size: 0.875rem; color: #9ca3af;"><?= number_format($sessions) ?> sesija</div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Progress bars -->
        <div style="max-width: 400px; margin: 0 auto;">
            <?php foreach ($devices as $key => $d):
                $sessions = $deviceData[$key] ?? 0;
                $percent = $totalSessions > 0 ? round($sessions / $totalSessions * 100) : 0;
            ?>
            <div style="margin-bottom: 0.75rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                    <span><?= $d['icon'] ?> <?= $d['label'] ?></span>
                    <span style="font-weight: 600;"><?= $percent ?>%</span>
                </div>
                <div style="height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
                    <div style="width: <?= $percent ?>%; height: 100%; background: <?= $d['color'] ?>;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php elseif ($reportType === 'landing' && $reportData && isset($reportData['rows'])): ?>
<!-- LANDING PAGES -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">🚪 Landing stranice</h2>
        <p style="color: #6b7280; margin: 0.25rem 0 0 0; font-size: 0.875rem;">Na koje stranice korisnici prvo dolaze</p>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Stranica</th>
                    <th style="text-align: right;">Sesije</th>
                    <th style="text-align: right;">Korisnici</th>
                    <th style="text-align: right;">Bounce</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 0; foreach ($reportData['rows'] as $row): $i++;
                    $path = $row['dimensionValues'][0]['value'] ?? '-';
                    $sessions = $row['metricValues'][0]['value'] ?? 0;
                    $users = $row['metricValues'][1]['value'] ?? 0;
                    $bounce = (float)($row['metricValues'][2]['value'] ?? 0) * 100;
                    if ($i > 30) break;
                ?>
                <tr>
                    <td style="color: #9ca3af;"><?= $i ?></td>
                    <td>
                        <a href="?period=<?= $period ?>&report=article&page=<?= urlencode(parse_url($path, PHP_URL_PATH) ?: $path) ?>"
                           style="text-decoration: none; color: inherit;">
                            <?= e(truncate($path, 70)) ?>
                        </a>
                    </td>
                    <td style="text-align: right; font-weight: 600;"><?= number_format($sessions) ?></td>
                    <td style="text-align: right;"><?= number_format($users) ?></td>
                    <td style="text-align: right;">
                        <span style="padding: 2px 6px; border-radius: 4px; font-size: 0.75rem;
                                     background: <?= $bounce > 70 ? '#fee2e2' : ($bounce > 50 ? '#fef3c7' : '#dcfce7') ?>;
                                     color: <?= $bounce > 70 ? '#dc2626' : ($bounce > 50 ? '#d97706' : '#16a34a') ?>;">
                            <?= round($bounce) ?>%
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($reportType === 'article' && $pagePath && $reportData): ?>
<!-- DETALJI ČLANKA -->
<?php
$pageTitle = '';
$totalViews = 0;
$avgDuration = 0;
$bounceRate = 0;

if (isset($reportData['title']['rows'][0])) {
    $pageTitle = $reportData['title']['rows'][0]['dimensionValues'][0]['value'] ?? '';
    $totalViews = (int)($reportData['title']['rows'][0]['metricValues'][0]['value'] ?? 0);
    $avgDuration = (float)($reportData['title']['rows'][0]['metricValues'][1]['value'] ?? 0);
    $bounceRate = (float)($reportData['title']['rows'][0]['metricValues'][2]['value'] ?? 0) * 100;
}
?>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-header">
        <a href="?period=<?= $period ?>&report=pages" class="btn btn-sm btn-outline">← Natrag</a>
    </div>
    <div class="card-body">
        <h2 style="margin: 0 0 0.5rem 0;"><?= e($pageTitle ?: $pagePath) ?></h2>
        <p style="color: #6b7280; margin: 0; font-size: 0.875rem;"><?= e($pagePath) ?></p>

        <div style="display: flex; gap: 2rem; margin-top: 1rem; flex-wrap: wrap;">
            <div>
                <div style="font-size: 1.5rem; font-weight: 700;"><?= number_format($totalViews) ?></div>
                <div style="color: #6b7280; font-size: 0.875rem;">Pregledi</div>
            </div>
            <div>
                <div style="font-size: 1.5rem; font-weight: 700;"><?= formatDuration($avgDuration) ?></div>
                <div style="color: #6b7280; font-size: 0.875rem;">Pros. vrijeme</div>
            </div>
            <div>
                <div style="font-size: 1.5rem; font-weight: 700;"><?= round($bounceRate) ?>%</div>
                <div style="color: #6b7280; font-size: 0.875rem;">Bounce rate</div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($reportData['daily']['rows'])): ?>
<!-- Mini graf po danima -->
<div class="card" style="margin-bottom: 1rem;">
    <div class="card-header"><h3 class="card-title">📈 Pregledi po danima</h3></div>
    <div class="card-body">
        <?php
        $dailyViews = [];
        $maxDaily = 0;
        foreach ($reportData['daily']['rows'] as $row) {
            $dateStr = $row['dimensionValues'][0]['value'] ?? '';
            $views = (int)($row['metricValues'][0]['value'] ?? 0);
            $dailyViews[$dateStr] = $views;
            if ($views > $maxDaily) $maxDaily = $views;
        }
        ?>
        <div style="display: flex; align-items: flex-end; gap: 4px; height: 80px;">
            <?php foreach ($dailyViews as $date => $views):
                $height = $maxDaily > 0 ? ($views / $maxDaily * 100) : 0;
                $d = DateTime::createFromFormat('Ymd', $date);
                $label = $d ? $d->format('d.m.') : $date;
            ?>
            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px;">
                <div style="width: 100%; background: #3b82f6; border-radius: 2px 2px 0 0; height: <?= max($height, 2) ?>px;"
                     title="<?= $label ?>: <?= number_format($views) ?>"></div>
                <div style="font-size: 0.6rem; color: #9ca3af; transform: rotate(-45deg); white-space: nowrap;"><?= $label ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (isset($reportData['sources']['rows'])): ?>
<!-- Izvori prometa -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">🔗 Izvori prometa za ovaj članak</h3>
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
                <?php foreach ($reportData['sources']['rows'] as $row):
                    $source = $row['dimensionValues'][0]['value'] ?? '(direct)';
                    $medium = $row['dimensionValues'][1]['value'] ?? '(none)';
                    $views = $row['metricValues'][0]['value'] ?? 0;
                    $users = $row['metricValues'][1]['value'] ?? 0;

                    if (strpos($source, 'google') !== false) $color = '#ea4335';
                    elseif (strpos($source, 'facebook') !== false || strpos($source, 'fb') !== false) $color = '#1877f2';
                    elseif ($source === '(direct)') $color = '#10b981';
                    else $color = '#6b7280';
                ?>
                <tr>
                    <td>
                        <span style="color: <?= $color ?>;">●</span>
                        <strong style="margin-left: 0.5rem;"><?= e($source) ?></strong>
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

<?php elseif ($reportType === 'revenue' && isAdmin() && $reportData): ?>
<!-- ZARADA - Samo za admine -->
<?php
$currentRevenue = 0;
$previousRevenue = 0;

// Ovaj mjesec do danas
$monthStart = date('Y-m-01');
$today = date('Y-m-d');
$currentMonthName = date('F Y');
$dayOfMonth = date('j');

// Isti period prošlog mjeseca
$lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
$lastMonthEnd = date('Y-m-d', strtotime('-1 month'));
$lastMonthName = date('F Y', strtotime('first day of last month'));

// Izračunaj ukupno iz dnevnih podataka (jer total query vraća 0)
if (isset($reportData['daily']['rows'])) {
    foreach ($reportData['daily']['rows'] as $row) {
        $currentRevenue += (float)($row['metricValues'][0]['value'] ?? 0);
    }
}

// Za usporedbu s prošlom godinom - zbroji dnevne podatke
if (isset($reportData['compareDaily']['rows'])) {
    foreach ($reportData['compareDaily']['rows'] as $row) {
        $previousRevenue += (float)($row['metricValues'][0]['value'] ?? 0);
    }
}
$revenueChange = calcChange($currentRevenue, $previousRevenue);
?>

<!-- Ukupna zarada kartica -->
<div class="card" style="margin-bottom: 1rem; background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); color: white;">
    <div class="card-body">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.25rem;">
                    <?= strftime('%B %Y', strtotime($monthStart)) ?> (1. - <?= $dayOfMonth ?>.)
                </div>
                <div style="font-size: 2.5rem; font-weight: 700;"><?= number_format($currentRevenue, 2) ?> €</div>
            </div>
            <div style="text-align: right;">
                <?php if ($revenueChange != 0): ?>
                <div style="font-size: 1.5rem; font-weight: 600;">
                    <?= $revenueChange > 0 ? '↑' : '↓' ?> <?= $revenueChange > 0 ? '+' : '' ?><?= $revenueChange ?>%
                </div>
                <?php endif; ?>
                <div style="font-size: 0.875rem; opacity: 0.8;">vs <?= strftime('%B %Y', strtotime($lastMonthStart)) ?></div>
                <div style="font-size: 0.875rem; opacity: 0.7;">(<?= number_format($previousRevenue, 2) ?> €)</div>
            </div>
        </div>
    </div>
</div>

<!-- Zarada po danima -->
<?php if (isset($reportData['daily']['rows']) && !empty($reportData['daily']['rows'])): ?>
<div class="card" style="margin-bottom: 1rem;">
    <div class="card-header">
        <h2 class="card-title">📅 Zarada po danima</h2>
    </div>
    <div class="card-body" style="padding: 1rem;">
        <?php
        $dailyData = [];
        $maxRev = 0;
        foreach ($reportData['daily']['rows'] as $row) {
            $rev = (float)($row['metricValues'][0]['value'] ?? 0);
            $dailyData[] = $rev;
            if ($rev > $maxRev) $maxRev = $rev;
        }
        $dailyData = array_reverse($dailyData);
        ?>
        <div style="display: flex; align-items: flex-end; gap: 4px; height: 100px; margin-bottom: 1rem;">
            <?php foreach ($dailyData as $rev):
                $height = $maxRev > 0 ? ($rev / $maxRev * 100) : 0;
            ?>
            <div style="flex: 1; background: #16a34a; border-radius: 2px 2px 0 0; height: <?= max($height, 2) ?>%; min-height: 2px;"
                 title="<?= number_format($rev, 2) ?> €"></div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th style="text-align: right;">Zarada</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData['daily']['rows'] as $row):
                    $dateStr = $row['dimensionValues'][0]['value'] ?? '';
                    $date = DateTime::createFromFormat('Ymd', $dateStr);
                    $formattedDate = $date ? $date->format('d.m.Y.') : $dateStr;
                    $dayName = $date ? ['Ned', 'Pon', 'Uto', 'Sri', 'Čet', 'Pet', 'Sub'][$date->format('w')] : '';
                    $rev = (float)($row['metricValues'][0]['value'] ?? 0);
                ?>
                <tr>
                    <td>
                        <span style="font-weight: 500;"><?= $formattedDate ?></span>
                        <span style="color: #9ca3af; margin-left: 0.5rem;"><?= $dayName ?></span>
                    </td>
                    <td style="text-align: right; font-weight: 600; color: #16a34a;"><?= number_format($rev, 2) ?> €</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Top članci po zaradi -->
<?php if (isset($reportData['top']['rows']) && !empty($reportData['top']['rows'])): ?>
<div class="card" style="margin-bottom: 1rem;">
    <div class="card-header">
        <h2 class="card-title">🏆 Top članci po zaradi</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Članak</th>
                    <th style="text-align: right;">Pregledi</th>
                    <th style="text-align: right;">Zarada</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 0;
                foreach ($reportData['top']['rows'] as $row):
                    $title = $row['dimensionValues'][0]['value'] ?? '-';
                    $path = $row['dimensionValues'][1]['value'] ?? '';
                    $rev = (float)($row['metricValues'][0]['value'] ?? 0);
                    $views = (int)($row['metricValues'][1]['value'] ?? 0);

                    if ($rev <= 0 || $path === '/' || strpos($path, '/admin') === 0 || $title === '(not set)') continue;
                    $i++;
                    if ($i > 30) break;
                ?>
                <tr>
                    <td style="color: #9ca3af;"><?= $i ?></td>
                    <td>
                        <a href="?period=<?= $period ?>&report=article&page=<?= urlencode($path) ?>" style="text-decoration: none; color: inherit;">
                            <div style="font-weight: 500;"><?= e(truncate($title, 50)) ?></div>
                        </a>
                    </td>
                    <td style="text-align: right; color: #6b7280;"><?= number_format($views) ?></td>
                    <td style="text-align: right; font-weight: 600; color: #16a34a;"><?= number_format($rev, 2) ?> €</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Zarada po izvorima -->
<?php if (isset($reportData['sources']['rows']) && !empty($reportData['sources']['rows'])): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">🔗 Zarada po izvorima prometa</h2>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Izvor</th>
                    <th style="text-align: right;">Pregledi</th>
                    <th style="text-align: right;">Zarada</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalSourceRev = 0;
                foreach ($reportData['sources']['rows'] as $row) {
                    $totalSourceRev += (float)($row['metricValues'][0]['value'] ?? 0);
                }

                foreach ($reportData['sources']['rows'] as $row):
                    $source = $row['dimensionValues'][0]['value'] ?? '(direct)';
                    $rev = (float)($row['metricValues'][0]['value'] ?? 0);
                    $views = (int)($row['metricValues'][1]['value'] ?? 0);

                    if ($rev <= 0) continue;

                    if (strpos($source, 'google') !== false) $color = '#ea4335';
                    elseif (strpos($source, 'facebook') !== false || strpos($source, 'fb') !== false) $color = '#1877f2';
                    elseif ($source === '(direct)') $color = '#10b981';
                    else $color = '#6b7280';

                    $percent = $totalSourceRev > 0 ? round($rev / $totalSourceRev * 100) : 0;
                ?>
                <tr>
                    <td>
                        <span style="color: <?= $color ?>;">●</span>
                        <strong style="margin-left: 0.5rem;"><?= e($source) ?></strong>
                        <span style="color: #9ca3af; font-size: 0.75rem; margin-left: 0.5rem;">(<?= $percent ?>%)</span>
                    </td>
                    <td style="text-align: right; color: #6b7280;"><?= number_format($views) ?></td>
                    <td style="text-align: right; font-weight: 600; color: #16a34a;"><?= number_format($rev, 2) ?> €</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<div class="card">
    <div class="empty-state">
        <p>Nema podataka za odabrano razdoblje.</p>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
