<?php
/**
 * RSS čitač - Feedly Trending za Zagorje International i Radio Stubica
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

define('PAGE_TITLE', 'RSS Vijesti');

// Feedly API konfiguracija
define('FEEDLY_TOKEN', 'REDACTED_FEEDLY_TOKEN');
define('FEEDLY_USER_ID', '6e135c2a-75fe-4109-8be8-6b52fa6866e6');
define('FEEDLY_API_URL', 'https://cloud.feedly.com/v3');

// Stream ID-evi za naše izvore (Zagorje International i Radio Stubica folder u Feedly)
// Ovo treba prilagoditi tvom Feedly accountu
$localStreams = [
    'zagorje' => 'feed/https://www.zagorje-international.hr/index.php/feed/',
    'stubica' => 'feed/https://radio-stubica.hr/feed/'
];

/**
 * Feedly API poziv
 */
function feedlyRequest($endpoint, $params = []) {
    $url = FEEDLY_API_URL . $endpoint;
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . FEEDLY_TOKEN,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['error' => "HTTP $httpCode"];
    }
    
    return json_decode($response, true);
}

/**
 * Dohvati članke iz Feedly streama
 */
function getFeedlyArticles($streamId, $count = 50, $ranked = 'engagement') {
    $params = [
        'streamId' => $streamId,
        'count' => $count,
        'ranked' => $ranked
    ];
    
    return feedlyRequest('/streams/contents', $params);
}

/**
 * Dohvati sve kolekcije/foldere
 */
function getFeedlyCollections() {
    return feedlyRequest('/collections');
}

// Dohvati filter
$source = $_GET['source'] ?? 'all';
$hours = intval($_GET['hours'] ?? 24);

// Dohvati kolekcije za sidebar
$collections = getFeedlyCollections();
if (isset($collections['error'])) {
    $collections = [];
}

// Pronađi Zagorski list folder
$zagorskiStreamId = null;
foreach ($collections as $col) {
    if (stripos($col['label'], 'zagorski') !== false || stripos($col['label'], 'lokalno') !== false) {
        $zagorskiStreamId = $col['id'];
        break;
    }
}

// Dohvati članke
$articles = [];
$debugData = [];

if ($source === 'zagorje') {
    $data = getFeedlyArticles($localStreams['zagorje'], 50, 'engagement');
    $debugData['stream'] = $localStreams['zagorje'];
} elseif ($source === 'stubica') {
    $data = getFeedlyArticles($localStreams['stubica'], 50, 'engagement');
    $debugData['stream'] = $localStreams['stubica'];
} elseif ($zagorskiStreamId) {
    $data = getFeedlyArticles($zagorskiStreamId, 100, 'engagement');
    $debugData['stream'] = $zagorskiStreamId;
} else {
    // Fallback - dohvati oba feeda
    $data1 = getFeedlyArticles($localStreams['zagorje'], 50, 'engagement');
    $data2 = getFeedlyArticles($localStreams['stubica'], 50, 'engagement');
    
    $debugData['data1'] = $data1;
    $debugData['data2'] = $data2;
    
    $articles = array_merge(
        $data1['items'] ?? [],
        $data2['items'] ?? []
    );
    
    // Sortiraj po engagementu
    usort($articles, function($a, $b) {
        return ($b['engagement'] ?? 0) - ($a['engagement'] ?? 0);
    });
    
    $data = ['items' => $articles];
}

$debugData['response'] = $data;
$articles = $data['items'] ?? [];
$debugData['articles_count'] = count($articles);

// DEBUG - prikaži što dolazi
if (isset($_GET['debug'])) {
    echo '<pre style="background:#fff;padding:20px;margin:20px;">';
    print_r($debugData);
    echo '</pre>';
    exit;
}

// Filter po vremenu
if ($hours > 0) {
    $cutoff = (time() - ($hours * 3600)) * 1000;
    $articles = array_filter($articles, function($a) use ($cutoff) {
        return ($a['published'] ?? 0) >= $cutoff;
    });
}

include 'includes/header.php';
?>

<div class="d-flex" style="justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <h1>📰 Lokalne vijesti</h1>
    <a href="?refresh=1" class="btn btn-primary">🔄 Osvježi</a>
</div>

<!-- Filteri -->
<div class="card mt-2">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-1">
            <a href="rss.php" class="btn <?= $source === 'all' ? 'btn-primary' : 'btn-outline' ?>">Sve</a>
            <a href="rss.php?source=zagorje" class="btn <?= $source === 'zagorje' ? 'btn-primary' : 'btn-outline' ?>">
                🌍 Zagorje International
            </a>
            <a href="rss.php?source=stubica" class="btn <?= $source === 'stubica' ? 'btn-primary' : 'btn-outline' ?>">
                📻 Radio Stubica
            </a>
            
            <span style="margin-left: auto;"></span>
            
            <select onchange="location.href='rss.php?source=<?= $source ?>&hours='+this.value" class="form-control" style="width: auto;">
                <option value="6" <?= $hours == 6 ? 'selected' : '' ?>>Zadnjih 6h</option>
                <option value="12" <?= $hours == 12 ? 'selected' : '' ?>>Zadnjih 12h</option>
                <option value="24" <?= $hours == 24 ? 'selected' : '' ?>>Zadnja 24h</option>
                <option value="48" <?= $hours == 48 ? 'selected' : '' ?>>Zadnja 2 dana</option>
                <option value="168" <?= $hours == 168 ? 'selected' : '' ?>>Zadnjih 7 dana</option>
                <option value="0" <?= $hours == 0 ? 'selected' : '' ?>>Sve</option>
            </select>
        </div>
    </div>
</div>

<!-- Vijesti -->
<?php if (empty($articles)): ?>
<div class="card mt-2">
    <div class="empty-state">
        <h3>Nema vijesti</h3>
        <p>Nema članaka za odabrani period</p>
    </div>
</div>
<?php else: ?>

<?php 
$maxEngagement = max(array_map(function($a) { return $a['engagement'] ?? 0; }, $articles));
?>

<div class="rss-list mt-2">
    <?php foreach ($articles as $index => $article): 
        $engagement = $article['engagement'] ?? 0;
        $engClass = '';
        if ($maxEngagement > 0) {
            if ($engagement > $maxEngagement * 0.7) $engClass = 'high-engagement';
            elseif ($engagement > $maxEngagement * 0.3) $engClass = 'medium-engagement';
            else $engClass = 'low-engagement';
        }
        
        $sourceName = $article['origin']['title'] ?? 'Nepoznato';
        $sourceIcon = $article['origin']['iconUrl'] ?? '';
        $link = $article['alternate'][0]['href'] ?? $article['originId'] ?? '#';
        $title = $article['title'] ?? 'Bez naslova';
        $summary = isset($article['summary']['content']) ? strip_tags($article['summary']['content']) : '';
        $published = $article['published'] ?? $article['crawled'] ?? 0;
        $thumbnail = $article['visual']['url'] ?? '';
        
        // Format engagement
        $engFormatted = $engagement;
        if ($engagement >= 1000000) $engFormatted = round($engagement/1000000, 1) . 'M';
        elseif ($engagement >= 1000) $engFormatted = round($engagement/1000, 1) . 'K';
    ?>
    <div class="rss-item <?= $engClass ?>">
        <div class="rss-rank"><?= $index + 1 ?></div>
        <div class="rss-content">
            <div class="rss-item-header">
                <span class="rss-source">
                    <?php if ($sourceIcon): ?><img src="<?= e($sourceIcon) ?>" alt=""><?php endif; ?>
                    <?= e($sourceName) ?>
                </span>
                <span class="rss-time"><?= $published ? timeAgo(date('Y-m-d H:i:s', $published/1000)) : '' ?></span>
            </div>
            <h3 class="rss-item-title">
                <a href="<?= e($link) ?>" target="_blank"><?= e($title) ?></a>
            </h3>
            <?php if ($summary): ?>
            <p class="rss-item-desc"><?= e(mb_substr($summary, 0, 200)) ?>...</p>
            <?php endif; ?>
        </div>
        <?php if ($engagement > 0): ?>
        <div class="rss-engagement <?= $engClass ?>">
            <div class="rss-engagement-number"><?= $engFormatted ?></div>
            <div class="rss-engagement-label">🔥</div>
        </div>
        <?php endif; ?>
        <?php if ($thumbnail && strpos($thumbnail, 'http') === 0): ?>
        <img class="rss-thumbnail" src="<?= e($thumbnail) ?>" alt="">
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<style>
.rss-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.rss-item {
    background: var(--white);
    border-radius: var(--radius);
    padding: 1rem;
    box-shadow: var(--shadow);
    display: grid;
    grid-template-columns: 40px 1fr auto auto;
    gap: 1rem;
    align-items: start;
    border-left: 4px solid var(--gray-300);
    transition: all 0.2s;
}
.rss-item:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}
.rss-item.high-engagement {
    border-left-color: #e74c3c;
    background: linear-gradient(90deg, rgba(231,76,60,0.05) 0%, transparent 100%);
}
.rss-item.medium-engagement {
    border-left-color: #f39c12;
}
.rss-item.low-engagement {
    border-left-color: #3498db;
}
.rss-rank {
    width: 32px;
    height: 32px;
    background: var(--gray-100);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: var(--gray-600);
    font-size: 0.85rem;
}
.rss-item.high-engagement .rss-rank {
    background: #e74c3c;
    color: white;
}
.rss-content {
    min-width: 0;
}
.rss-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.rss-source {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: var(--primary);
    font-weight: 500;
}
.rss-source img {
    width: 16px;
    height: 16px;
    border-radius: 4px;
}
.rss-time {
    font-size: 0.75rem;
    color: var(--gray-500);
}
.rss-item-title {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 0.5rem;
    line-height: 1.4;
}
.rss-item-title a {
    color: var(--dark);
    text-decoration: none;
}
.rss-item-title a:hover {
    color: var(--primary);
}
.rss-item-desc {
    font-size: 0.875rem;
    color: var(--gray-600);
    margin: 0;
    line-height: 1.5;
}
.rss-engagement {
    text-align: center;
    padding: 0.5rem;
    min-width: 60px;
}
.rss-engagement-number {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--gray-700);
}
.rss-engagement.high-engagement .rss-engagement-number {
    color: #e74c3c;
}
.rss-engagement.medium-engagement .rss-engagement-number {
    color: #f39c12;
}
.rss-engagement-label {
    font-size: 0.7rem;
    color: var(--gray-500);
}
.rss-thumbnail {
    width: 100px;
    height: 70px;
    object-fit: cover;
    border-radius: var(--radius);
}
@media (max-width: 768px) {
    .rss-item {
        grid-template-columns: 30px 1fr auto;
    }
    .rss-thumbnail {
        display: none;
    }
    .rss-engagement {
        min-width: 50px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
