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

// Dohvati HTML s curl (bolje za jutarnji.hr i slične portale)
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_ENCODING => '',
    CURLOPT_HTTPHEADER => [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language: hr-HR,hr;q=0.9,en-US;q=0.8,en;q=0.7',
        'Cache-Control: no-cache',
        'Sec-Ch-Ua: "Chromium";v="122", "Not(A:Brand";v="24", "Google Chrome";v="122"',
        'Sec-Ch-Ua-Mobile: ?0',
        'Sec-Ch-Ua-Platform: "Windows"',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-User: ?1',
        'Upgrade-Insecure-Requests: 1',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
    ]
]);

$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || empty($html)) {
    echo json_encode(['success' => false, 'error' => 'Nije moguće dohvatiti stranicu']);
    exit;
}

// Parsiraj HTML
$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
$dom = new DOMDocument();
$dom->preserveWhiteSpace = false;
libxml_use_internal_errors(true);
@$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
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

// Specifični selektori za hrvatske portale
$portalSelectors = [];

if (strpos($url, '24sata.hr') !== false) {
    $portalSelectors = [
        "//*[contains(@class, 'article__text')]",
        "//*[contains(@class, 'article-text')]",
        "//*[contains(@data-testid, 'article-body')]",
        "//div[contains(@class, 'content')]//p",
    ];
} elseif (strpos($url, 'index.hr') !== false) {
    $portalSelectors = [
        "//*[contains(@class, 'text')]",
        "//*[contains(@class, 'article-text')]",
        "//div[@id='text']",
    ];
} elseif (strpos($url, 'jutarnji.hr') !== false) {
    $portalSelectors = [
        "//*[contains(@class, 'excerpt')]",
        "//*[contains(@class, 'itemFullText')]",
        "//*[contains(@class, 'item__body')]",
    ];
} elseif (strpos($url, 'vecernji.hr') !== false) {
    $portalSelectors = [
        "//*[contains(@class, 'article__body')]",
        "//*[contains(@class, 'article-body')]",
        "//*[contains(@class, 'single-article')]",
    ];
}

// Pokušaj pronaći glavni sadržaj članka
$contentSelectors = array_merge($portalSelectors, [
    "//*[contains(@class, 'article-body')]",
    "//*[contains(@class, 'article-content')]",
    "//*[contains(@class, 'article__body')]",
    "//*[contains(@class, 'article__content')]",
    "//*[contains(@class, 'article__text')]",
    "//*[contains(@class, 'story-body')]",
    "//*[contains(@class, 'post-content')]",
    "//*[contains(@class, 'entry-content')]",
    "//*[contains(@class, 'content-body')]",
    "//*[contains(@class, 'text-body')]",
    "//*[contains(@class, 'article-text')]",
    "//*[@itemprop='articleBody']",
    "//article",
    "//main",
]);

$content = '';
foreach ($contentSelectors as $selector) {
    $nodes = $xpath->query($selector);
    if ($nodes->length > 0) {
        $paragraphs = [];
        $pNodes = $xpath->query(".//p", $nodes->item(0));
        foreach ($pNodes as $p) {
            $text = trim($p->textContent);
            if (mb_strlen($text) > 20) {
                $paragraphs[] = $text;
            }
        }
        if (count($paragraphs) > 2) {
            $content = implode("\n\n", $paragraphs);
            break;
        }
    }
}

// Fallback - uzmi sve paragrafe iz body-a
if (empty($content)) {
    $paragraphs = [];
    $pNodes = $xpath->query("//body//p");
    foreach ($pNodes as $p) {
        $text = trim($p->textContent);
        // Preskoči kratke i one koji izgledaju kao navigacija/linkovi
        if (mb_strlen($text) > 40 && mb_strlen($text) < 5000) {
            // Preskoči ako ima previše linkova (vjerojatno navigacija)
            $linkCount = $xpath->query(".//a", $p)->length;
            $wordCount = str_word_count($text);
            if ($linkCount < 3 || $wordCount > 20) {
                $paragraphs[] = $text;
            }
        }
    }
    $content = implode("\n\n", $paragraphs);
}

// Regex fallback za jutarnji.hr i slične portale gdje DOM ne radi
if (empty($content)) {
    // Funkcija za provjeru reklamnog teksta
    $isAdText = function($text) {
        $text = mb_strtolower($text);
        $adPatterns = ['sponzorirani', 'sponsored', 'reklama', 'newsletter', 'pretplatite', 'cookies', 'kolačići', 'pratite nas', 'follow us', 'copyright'];
        foreach ($adPatterns as $pattern) {
            if (mb_strpos($text, $pattern) !== false) return true;
        }
        return false;
    };

    // Probaj izvući iz itemFullText ili excerpt (jutarnji.hr)
    if (preg_match('/<div[^>]*class="[^"]*(?:itemFullText|excerpt)[^"]*"[^>]*>(.*?)<\/div>\s*<div[^>]*class="[^"]*(?:piano|position_item)/is', $html, $match)) {
        $innerHtml = $match[1];
    } else if (preg_match('/<div[^>]*class="[^"]*item__body[^"]*"[^>]*>(.*?)<\/div>\s*<div[^>]*class="[^"]*item__/is', $html, $match)) {
        $innerHtml = $match[1];
    } else {
        $innerHtml = $html;
    }

    // Izvuci sve <p> tagove regexom
    if (preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $innerHtml, $pMatches)) {
        $paragraphs = [];
        foreach ($pMatches[1] as $pHtml) {
            $text = trim(strip_tags($pHtml));
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (strlen($text) > 30 && !$isAdText($text)) {
                $paragraphs[] = $text;
            }
        }
        if (count($paragraphs) > 0) {
            $content = implode("\n\n", $paragraphs);
        }
    }
}

// Dekodiraj HTML entitete i očisti whitespace
$content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$content = preg_replace('/\s+/', ' ', $content);
$content = str_replace(' .', '.', $content);
$content = str_replace(' ,', ',', $content);
$title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');

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
