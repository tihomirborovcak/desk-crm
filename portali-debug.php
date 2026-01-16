<?php
/**
 * Debug - analiza HTML strukture portala
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$portal = $_GET['portal'] ?? 'index';

$urls = [
    'index' => 'https://www.index.hr',
    '24sata' => 'https://www.24sata.hr',
    'jutarnji' => 'https://www.jutarnji.hr',
    'vecernji' => 'https://www.vecernji.hr'
];

$rssUrls = [
    'index' => 'https://www.index.hr/rss',
    '24sata' => 'https://www.24sata.hr/feeds/aktualno.xml',
    'jutarnji' => 'https://www.jutarnji.hr/feed',
    'vecernji' => 'https://www.vecernji.hr/feeds/latest'
];

$url = $urls[$portal] ?? $urls['index'];
$rssUrl = $rssUrls[$portal] ?? $rssUrls['index'];

// Dohvati HTML
$ctx = stream_context_create([
    'http' => [
        'timeout' => 15,
        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\nAccept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\nAccept-Language: hr,en;q=0.5\r\n"
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$html = @file_get_contents($url, false, $ctx);

define('PAGE_TITLE', 'Debug portala');
include 'includes/header.php';
?>

<div class="page-header">
    <h1>Debug - <?= e($portal) ?></h1>
</div>

<div style="margin-bottom: 1rem;">
    <a href="?portal=index" class="btn <?= $portal === 'index' ? 'btn-primary' : 'btn-outline' ?>">Index</a>
    <a href="?portal=24sata" class="btn <?= $portal === '24sata' ? 'btn-primary' : 'btn-outline' ?>">24sata</a>
    <a href="?portal=jutarnji" class="btn <?= $portal === 'jutarnji' ? 'btn-primary' : 'btn-outline' ?>">Jutarnji</a>
    <a href="?portal=vecernji" class="btn <?= $portal === 'vecernji' ? 'btn-primary' : 'btn-outline' ?>">Večernji</a>
</div>

<?php if ($html): ?>
<div style="background: #d1fae5; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
    HTML dohvaćen: <?= number_format(strlen($html)) ?> znakova
</div>

<?php
// Traži najčitanije sekcije
$dom = new DOMDocument();
libxml_use_internal_errors(true);
@$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
libxml_clear_errors();
$xpath = new DOMXPath($dom);

// Različiti selectori za pronalazak
$selectors = [
    'most-read' => "//div[contains(@class, 'most-read')]",
    'najcitanije' => "//*[contains(@class, 'najcitanije')]",
    'popular' => "//*[contains(@class, 'popular')]",
    'trending' => "//*[contains(@class, 'trending')]",
    'top-news' => "//*[contains(@class, 'top-news')]",
    'sidebar article' => "//aside//article",
    'widget' => "//*[contains(@class, 'widget')]",
];

echo '<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">';
echo '<h3>Pronađeni elementi:</h3>';

foreach ($selectors as $name => $selector) {
    $nodes = $xpath->query($selector);
    if ($nodes->length > 0) {
        echo "<div style='margin: 0.5rem 0; padding: 0.5rem; background: #f0fdf4; border-radius: 4px;'>";
        echo "<strong>$name:</strong> {$nodes->length} elemenata<br>";

        // Prikaži prvi element
        $first = $nodes->item(0);
        $class = $first->getAttribute('class');
        echo "<small>Klasa: " . e($class) . "</small>";
        echo "</div>";
    }
}

echo '</div>';

// Traži sve linkove s "najčitanije" ili "popular" u roditeljima
echo '<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">';
echo '<h3>Svi linkovi u aside/widget sekcijama:</h3>';

$links = $xpath->query("//aside//a | //*[contains(@class, 'widget')]//a | //*[contains(@class, 'sidebar')]//a");
echo "<p>Pronađeno: {$links->length} linkova</p>";

$shown = 0;
$seen = [];
foreach ($links as $link) {
    $href = $link->getAttribute('href');
    $text = trim($link->textContent);

    if (strlen($text) > 20 && strlen($text) < 200 && !isset($seen[$text])) {
        $seen[$text] = true;
        echo "<div style='padding: 0.5rem; border-bottom: 1px solid #e5e7eb; font-size: 0.875rem;'>";
        echo e(substr($text, 0, 100)) . "...";
        echo "</div>";
        $shown++;
        if ($shown >= 15) break;
    }
}

echo '</div>';

// Traži specifične strukture za svaki portal
echo '<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem;">';
echo '<h3>Specifična pretraga za ' . e($portal) . ':</h3>';

$specificResults = [];

if ($portal === 'index') {
    // Index.hr - traži box s brojevima 1-10
    $items = $xpath->query("//*[contains(@class, 'vijesti-box')]//a | //*[contains(@class, 'side')]//a | //ol//li//a");
    foreach ($items as $item) {
        $text = trim($item->textContent);
        $href = $item->getAttribute('href');
        if (strlen($text) > 15 && strlen($text) < 300 && strpos($href, 'index.hr') !== false) {
            $specificResults[] = ['text' => $text, 'href' => $href];
        }
    }
}

if ($portal === '24sata') {
    // 24sata - traži u sidebar ili aside
    $items = $xpath->query("//aside//a | //*[contains(@class, 'card')]//a");
    foreach ($items as $item) {
        $text = trim($item->textContent);
        $href = $item->getAttribute('href');
        if (strlen($text) > 15 && strlen($text) < 300) {
            $specificResults[] = ['text' => $text, 'href' => $href];
        }
    }
}

if ($portal === 'jutarnji') {
    $items = $xpath->query("//aside//a | //*[contains(@class, 'article')]//a");
    foreach ($items as $item) {
        $text = trim($item->textContent);
        $href = $item->getAttribute('href');
        if (strlen($text) > 15 && strlen($text) < 300) {
            $specificResults[] = ['text' => $text, 'href' => $href];
        }
    }
}

if ($portal === 'vecernji') {
    $items = $xpath->query("//aside//a | //*[contains(@class, 'article')]//a | //*[contains(@class, 'card')]//a");
    foreach ($items as $item) {
        $text = trim($item->textContent);
        $href = $item->getAttribute('href');
        if (strlen($text) > 15 && strlen($text) < 300) {
            $specificResults[] = ['text' => $text, 'href' => $href];
        }
    }
}

echo "<p>Pronađeno: " . count($specificResults) . " kandidata</p>";

$shown = 0;
$seenTexts = [];
foreach ($specificResults as $r) {
    if (isset($seenTexts[$r['text']])) continue;
    $seenTexts[$r['text']] = true;

    echo "<div style='padding: 0.5rem; border-bottom: 1px solid #e5e7eb; font-size: 0.875rem;'>";
    echo "<strong>" . e(substr($r['text'], 0, 80)) . "</strong><br>";
    echo "<small style='color: #6b7280;'>" . e(substr($r['href'], 0, 60)) . "</small>";
    echo "</div>";
    $shown++;
    if ($shown >= 20) break;
}

echo '</div>';

// RSS Feed Test
echo '<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">';
echo '<h3>RSS Feed Test:</h3>';
echo '<p><strong>URL:</strong> ' . e($rssUrl) . '</p>';

$rssContent = @file_get_contents($rssUrl, false, $ctx);
if ($rssContent) {
    echo '<div style="background: #d1fae5; padding: 0.5rem; border-radius: 4px; margin: 0.5rem 0;">RSS dohvaćen: ' . number_format(strlen($rssContent)) . ' znakova</div>';

    libxml_use_internal_errors(true);
    $feed = @simplexml_load_string($rssContent);
    libxml_clear_errors();

    if ($feed) {
        echo '<div style="background: #d1fae5; padding: 0.5rem; border-radius: 4px; margin: 0.5rem 0;">XML parsiran uspješno</div>';

        $items = [];
        if (isset($feed->channel->item)) {
            $items = $feed->channel->item;
            echo '<p>Format: RSS 2.0, ' . count($feed->channel->item) . ' članaka</p>';
        } elseif (isset($feed->entry)) {
            $items = $feed->entry;
            echo '<p>Format: Atom, ' . count($feed->entry) . ' članaka</p>';
        }

        echo '<div style="max-height: 300px; overflow-y: auto;">';
        $count = 0;
        foreach ($items as $item) {
            $title = (string)($item->title ?? '');
            $link = (string)($item->link ?? '');
            if (empty($link) && isset($item->link['href'])) {
                $link = (string)$item->link['href'];
            }

            // Dohvati datum objave
            $pubDate = '';
            if (isset($item->pubDate)) {
                $pubDate = date('d.m.Y H:i', strtotime((string)$item->pubDate));
            } elseif (isset($item->published)) {
                $pubDate = date('d.m.Y H:i', strtotime((string)$item->published));
            } elseif (isset($item->updated)) {
                $pubDate = date('d.m.Y H:i', strtotime((string)$item->updated));
            }

            echo '<div style="padding: 0.5rem; border-bottom: 1px solid #e5e7eb; font-size: 0.875rem;">';
            echo '<strong>' . e($title) . '</strong><br>';
            if ($pubDate) {
                echo '<small style="color: #059669; margin-right: 0.5rem;">' . $pubDate . '</small>';
            }
            echo '<small style="color: #6b7280;">' . e(substr($link, 0, 60)) . '</small>';
            echo '</div>';

            $count++;
            if ($count >= 10) break;
        }
        echo '</div>';
    } else {
        echo '<div style="background: #fee2e2; padding: 0.5rem; border-radius: 4px;">Greška pri parsiranju XML-a</div>';
    }
} else {
    echo '<div style="background: #fee2e2; padding: 0.5rem; border-radius: 4px;">Nije moguće dohvatiti RSS feed</div>';
}
echo '</div>';

// Prikaži raw HTML excerpt za analizu
echo '<details style="margin-top: 1rem;">';
echo '<summary style="cursor: pointer; padding: 1rem; background: #f3f4f6; border-radius: 8px;">Prikaži sirovi HTML (prvih 50000 znakova)</summary>';
echo '<pre style="background: #1e1e1e; color: #d4d4d4; padding: 1rem; overflow-x: auto; font-size: 0.75rem; max-height: 500px; overflow-y: auto;">';
echo e(substr($html, 0, 50000));
echo '</pre>';
echo '</details>';

?>

<?php else: ?>
<div style="background: #fee2e2; padding: 1rem; border-radius: 8px;">
    Greška: Nije moguće dohvatiti HTML sa <?= e($url) ?>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
