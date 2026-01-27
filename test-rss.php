<?php
/**
 * Test RSS sadržaja
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$rssUrls = [
    'index' => 'https://www.index.hr/rss',
    '24sata' => 'https://www.24sata.hr/feeds/aktualno.xml',
    'jutarnji' => 'https://www.jutarnji.hr/feed',
    'vecernji' => 'https://www.vecernji.hr/feeds/latest'
];

$portal = $_GET['portal'] ?? 'index';
$rssUrl = $rssUrls[$portal] ?? $rssUrls['index'];

$ctx = stream_context_create([
    'http' => [
        'timeout' => 15,
        'header' => "User-Agent: Mozilla/5.0\r\n"
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$xml = @file_get_contents($rssUrl, false, $ctx);

define('PAGE_TITLE', 'Test RSS');
include 'includes/header.php';
?>

<div class="page-header">
    <h1>Test RSS - <?= e($portal) ?></h1>
</div>

<div style="margin-bottom: 1rem;">
    <a href="?portal=index" class="btn <?= $portal === 'index' ? 'btn-primary' : 'btn-outline' ?>">Index</a>
    <a href="?portal=24sata" class="btn <?= $portal === '24sata' ? 'btn-primary' : 'btn-outline' ?>">24sata</a>
    <a href="?portal=jutarnji" class="btn <?= $portal === 'jutarnji' ? 'btn-primary' : 'btn-outline' ?>">Jutarnji</a>
    <a href="?portal=vecernji" class="btn <?= $portal === 'vecernji' ? 'btn-primary' : 'btn-outline' ?>">Vecernji</a>
</div>

<?php if ($xml): ?>
<div style="background: #d1fae5; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
    RSS dohvaćen: <?= number_format(strlen($xml)) ?> znakova
</div>

<?php
$feed = simplexml_load_string($xml);

if ($feed) {
    // Registriraj namespace za content:encoded
    $namespaces = $feed->getNamespaces(true);

    echo '<div style="background: #f3f4f6; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">';
    echo '<strong>Namespaces:</strong><br>';
    echo '<pre>' . print_r($namespaces, true) . '</pre>';
    echo '</div>';

    $items = $feed->channel->item ?? $feed->entry ?? [];

    $count = 0;
    foreach ($items as $item) {
        if ($count >= 3) break;

        echo '<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">';

        // Naslov
        echo '<h3 style="margin: 0 0 0.5rem 0;">' . e((string)$item->title) . '</h3>';

        // Provjeri različite načine dohvaćanja sadržaja
        echo '<div style="font-size: 0.875rem; color: #6b7280;">';

        // 1. description
        $desc = (string)($item->description ?? '');
        echo '<p><strong>description:</strong> ' . (strlen($desc) > 0 ? strlen($desc) . ' znakova' : 'PRAZNO') . '</p>';
        if ($desc) {
            echo '<pre style="background: #f9fafb; padding: 0.5rem; font-size: 0.75rem; max-height: 100px; overflow: auto;">' . e(substr(strip_tags($desc), 0, 500)) . '</pre>';
        }

        // 2. content:encoded
        $contentEncoded = '';
        if (isset($namespaces['content'])) {
            $content = $item->children($namespaces['content']);
            if (isset($content->encoded)) {
                $contentEncoded = (string)$content->encoded;
            }
        }
        echo '<p><strong>content:encoded:</strong> ' . (strlen($contentEncoded) > 0 ? strlen($contentEncoded) . ' znakova' : 'PRAZNO') . '</p>';
        if ($contentEncoded) {
            echo '<pre style="background: #f9fafb; padding: 0.5rem; font-size: 0.75rem; max-height: 100px; overflow: auto;">' . e(substr(strip_tags($contentEncoded), 0, 500)) . '</pre>';
        }

        // 3. content (Atom)
        $atomContent = (string)($item->content ?? '');
        echo '<p><strong>content (atom):</strong> ' . (strlen($atomContent) > 0 ? strlen($atomContent) . ' znakova' : 'PRAZNO') . '</p>';

        // 4. summary (Atom)
        $summary = (string)($item->summary ?? '');
        echo '<p><strong>summary:</strong> ' . (strlen($summary) > 0 ? strlen($summary) . ' znakova' : 'PRAZNO') . '</p>';

        echo '</div>';
        echo '</div>';

        $count++;
    }
}
?>

<?php else: ?>
<div style="background: #fee2e2; padding: 1rem; border-radius: 8px;">
    Greška pri dohvaćanju RSS feeda
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
