<?php
/**
 * API endpoint za dohvat punog teksta članka s URL-a
 */

require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Niste prijavljeni']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metoda nije dozvoljena']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$url = trim($input['url'] ?? '');

if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'error' => 'Neispravan URL']);
    exit;
}

// Dohvati HTML
$ctx = stream_context_create([
    'http' => [
        'timeout' => 20,
        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\nAccept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\nAccept-Language: hr,en;q=0.5\r\n"
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$html = @file_get_contents($url, false, $ctx);

if (!$html) {
    echo json_encode(['success' => false, 'error' => 'Nije moguće dohvatiti stranicu']);
    exit;
}

// Parsiraj HTML
$dom = new DOMDocument();
libxml_use_internal_errors(true);
@$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
libxml_clear_errors();

$xpath = new DOMXPath($dom);

// Dohvati naslov
$title = '';
$titleNodes = $xpath->query("//h1");
if ($titleNodes->length > 0) {
    $title = trim($titleNodes->item(0)->textContent);
}
if (empty($title)) {
    $titleNodes = $xpath->query("//title");
    if ($titleNodes->length > 0) {
        $title = trim($titleNodes->item(0)->textContent);
    }
}

// Ukloni nepotrebne elemente
$removeSelectors = [
    '//script', '//style', '//nav', '//header', '//footer',
    '//aside', '//form', '//*[contains(@class, "comment")]',
    '//*[contains(@class, "share")]', '//*[contains(@class, "social")]',
    '//*[contains(@class, "related")]', '//*[contains(@class, "sidebar")]',
    '//*[contains(@class, "advertisement")]', '//*[contains(@class, "ad-")]',
    '//*[contains(@class, "newsletter")]', '//*[contains(@class, "subscription")]',
    '//*[contains(@class, "author")]', '//*[contains(@class, "meta")]'
];

foreach ($removeSelectors as $selector) {
    $nodes = $xpath->query($selector);
    foreach ($nodes as $node) {
        if ($node->parentNode) {
            $node->parentNode->removeChild($node);
        }
    }
}

// Pokušaj pronaći glavni sadržaj članka
$contentSelectors = [
    "//*[contains(@class, 'article-body')]",
    "//*[contains(@class, 'article-content')]",
    "//*[contains(@class, 'article__body')]",
    "//*[contains(@class, 'article__content')]",
    "//*[contains(@class, 'story-body')]",
    "//*[contains(@class, 'post-content')]",
    "//*[contains(@class, 'entry-content')]",
    "//*[contains(@class, 'content-body')]",
    "//*[contains(@class, 'text-body')]",
    "//*[contains(@class, 'article-text')]",
    "//article",
    "//*[@itemprop='articleBody']",
    "//main",
];

$content = '';
foreach ($contentSelectors as $selector) {
    $nodes = $xpath->query($selector);
    if ($nodes->length > 0) {
        $paragraphs = [];
        $pNodes = $xpath->query(".//p", $nodes->item(0));
        foreach ($pNodes as $p) {
            $text = trim($p->textContent);
            if (strlen($text) > 30) {
                $paragraphs[] = $text;
            }
        }
        if (count($paragraphs) > 0) {
            $content = implode("\n\n", $paragraphs);
            break;
        }
    }
}

// Fallback - uzmi sve paragrafe
if (empty($content)) {
    $paragraphs = [];
    $pNodes = $xpath->query("//p");
    foreach ($pNodes as $p) {
        $text = trim($p->textContent);
        if (strlen($text) > 50) {
            $paragraphs[] = $text;
        }
    }
    $content = implode("\n\n", $paragraphs);
}

// Očisti whitespace
$content = preg_replace('/\s+/', ' ', $content);
$content = str_replace(' .', '.', $content);
$content = str_replace(' ,', ',', $content);

if (empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Nije pronađen tekst članka']);
    exit;
}

echo json_encode([
    'success' => true,
    'title' => $title,
    'content' => $content,
    'length' => mb_strlen($content)
]);
