<?php
/**
 * Skini tekst s URL-a i preradi ga
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

// Duži timeout za AI operacije
set_time_limit(300);

$db = getDB();
$result = null;
$error = null;
$originalText = '';
$processedText = '';
$articleTitle = '';
$articleUrl = $_GET['url'] ?? '';
$autoFetch = !empty($articleUrl);

// Funkcija za detekciju reklamnog teksta
function isAdText($text) {
    $text = mb_strtolower($text);
    $adPatterns = [
        'sponzorirani sadržaj', 'sponsored', 'oglašavanje', 'reklama',
        'klikni ovdje', 'click here', 'saznaj više', 'learn more',
        'prijavite se', 'subscribe', 'pretplatite se', 'newsletter',
        'popust', 'discount', 'akcija', 'special offer', 'promo kod',
        'kupi sada', 'buy now', 'naruči', 'order now', 'shop now',
        'besplatna dostava', 'free shipping', 'gratis',
        'cookies', 'kolačići', 'privatnost', 'privacy policy',
        'prihvaćam', 'i accept', 'slažem se', 'i agree',
        'pratite nas', 'follow us', 'like us', 'share this',
        'pročitajte još', 'read more', 'related articles', 'povezani članci',
        'advertisement', 'advert', 'ad break',
        'copyright ©', 'sva prava pridržana', 'all rights reserved'
    ];
    foreach ($adPatterns as $pattern) {
        if (mb_strpos($text, $pattern) !== false) {
            return true;
        }
    }
    // Ako paragraf ima previše URL-ova (više od 3) i kratak je - vjerojatno je footer/linkovi
    $urlCount = preg_match_all('/https?:\/\/[^\s]+/i', $text);
    if ($urlCount > 3 && mb_strlen($text) < 200) {
        return true;
    }
    return false;
}

// Funkcija za dohvat HTML-a
function fetchHtml($url) {
    // Koristimo curl za bolju kontrolu nad requestom
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
    $error = curl_error($ch);
    curl_close($ch);

    // Debug log
    error_log("fetchHtml: URL=$url, HTTP=$httpCode, Error=$error, HTML length=" . strlen($html));

    if ($httpCode !== 200 || empty($html)) {
        return false;
    }

    return $html;
}

// Funkcija za ekstrakciju glavnog teksta iz HTML-a
function extractArticleContent($html, $url) {
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

    // Ukloni nepotrebne elemente (reklame, navigacija, itd.)
    $removeSelectors = [
        '//script', '//style', '//nav', '//header', '//footer',
        '//aside', '//form', '//iframe', '//noscript',
        '//*[contains(@class, "comment")]',
        '//*[contains(@class, "share")]', '//*[contains(@class, "social")]',
        '//*[contains(@class, "related")]', '//*[contains(@class, "sidebar")]',
        '//*[contains(@class, "advertisement")]', '//*[contains(@class, "ad-")]',
        '//*[contains(@class, "advert")]', '//*[contains(@class, "ads")]',
        '//*[contains(@class, "banner")]', '//*[contains(@class, "promo")]',
        '//*[contains(@class, "sponsor")]', '//*[contains(@class, "commercial")]',
        '//*[contains(@class, "newsletter")]', '//*[contains(@class, "subscription")]',
        '//*[contains(@class, "widget")]', '//*[contains(@class, "popup")]',
        '//*[contains(@class, "modal")]', '//*[contains(@class, "cookie")]',
        '//*[contains(@class, "gdpr")]', '//*[contains(@class, "consent")]',
        '//*[contains(@id, "ad")]', '//*[contains(@id, "banner")]',
        '//*[contains(@id, "sponsor")]', '//*[contains(@id, "promo")]',
        '//*[@data-ad]', '//*[@data-advertisement]',
        '//ins[contains(@class, "adsbygoogle")]',
        '//*[contains(@class, "google-auto-placed")]',
        '//*[contains(@class, "wp-block-embed")]'
    ];

    foreach ($removeSelectors as $selector) {
        $nodes = $xpath->query($selector);
        foreach ($nodes as $node) {
            $node->parentNode->removeChild($node);
        }
    }

    // Pokušaj pronaći glavni sadržaj članka
    $contentSelectors = [
        // Jutarnji.hr / Hanza Media portali - specifični selektori
        "//*[contains(@class, 'excerpt')]",
        "//*[contains(@class, 'itemFullText')]",
        "//*[contains(@class, 'item__body')]",
        "//*[contains(@class, 'item__wrap-body')]",
        // Vecernji.hr
        "//*[contains(@class, 'article__body')]",
        "//*[contains(@class, 'article-body')]",
        "//*[contains(@class, 'article-content')]",
        // 24sata.hr - specifični selektor (article__content BEZ _block/_container/_wrap)
        "//*[contains(@class, 'article__content') and contains(@class, 'article_content_container')]",
        "//*[@class='article__content']",
        // Index.hr
        "//*[contains(@class, 'txt')]",
        // Net.hr, Tportal
        "//*[contains(@class, 'story-body')]",
        "//*[contains(@class, 'story__body')]",
        // WordPress
        "//*[contains(@class, 'post-content')]",
        "//*[contains(@class, 'entry-content')]",
        // Generički
        "//*[contains(@class, 'content-body')]",
        "//*[contains(@class, 'text-body')]",
        "//*[contains(@class, 'news-body')]",
        "//*[contains(@class, 'news__body')]",
        "//article",
        "//*[@itemprop='articleBody']",
        "//main",
    ];

    $content = '';
    foreach ($contentSelectors as $selector) {
        $nodes = $xpath->query($selector);
        error_log("Selector: $selector, Found: " . $nodes->length);
        if ($nodes->length > 0) {
            // Uzmi paragraphe iz pronađenog elementa
            $paragraphs = [];
            $pNodes = $xpath->query(".//p", $nodes->item(0));
            error_log("Paragraphs found: " . $pNodes->length);
            foreach ($pNodes as $p) {
                $text = trim($p->textContent);
                if (strlen($text) > 30 && !isAdText($text)) {
                    $paragraphs[] = $text;
                }
            }
            if (count($paragraphs) > 0) {
                $content = implode("\n\n", $paragraphs);
                error_log("Content extracted, length: " . strlen($content));
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
            if (strlen($text) > 50 && !isAdText($text)) {
                $paragraphs[] = $text;
            }
        }
        $content = implode("\n\n", $paragraphs);
    }

    // Dekodiraj HTML entitete (npr. &Zcaron; -> Ž, &cacute; -> ć)
    $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return [
        'title' => $title,
        'content' => $content
    ];
}

// Google Vertex AI autentifikacija
function getGoogleAccessToken() {
    $credentialsFile = __DIR__ . '/google-credentials.json';

    if (!file_exists($credentialsFile)) {
        return ['error' => 'Google credentials datoteka nije pronađena'];
    }

    $credentials = json_decode(file_get_contents($credentialsFile), true);

    if (!$credentials || !isset($credentials['private_key'])) {
        return ['error' => 'Neispravna credentials datoteka'];
    }

    $header = ['alg' => 'RS256', 'typ' => 'JWT'];

    $now = time();
    $payload = [
        'iss' => $credentials['client_email'],
        'scope' => 'https://www.googleapis.com/auth/cloud-platform',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600
    ];

    $base64UrlEncode = function($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    };

    $headerEncoded = $base64UrlEncode(json_encode($header));
    $payloadEncoded = $base64UrlEncode(json_encode($payload));

    $dataToSign = $headerEncoded . '.' . $payloadEncoded;
    $privateKey = openssl_pkey_get_private($credentials['private_key']);

    if (!$privateKey) {
        return ['error' => 'Neispravan privatni ključ'];
    }

    openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    $jwt = $dataToSign . '.' . $base64UrlEncode($signature);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ])
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode !== 200 || !isset($data['access_token'])) {
        $errMsg = $data['error_description'] ?? $data['error'] ?? 'HTTP ' . $httpCode;
        return ['error' => 'Google auth greška: ' . $errMsg];
    }

    $credentials = json_decode(file_get_contents($credentialsFile), true);

    return [
        'token' => $data['access_token'],
        'project_id' => $credentials['project_id']
    ];
}

// Funkcija za preradu teksta s Google Vertex AI (Gemini)
function rewriteWithGemini($text, $title) {
    $auth = getGoogleAccessToken();

    if (isset($auth['error'])) {
        return ['error' => $auth['error']];
    }

    $projectId = $auth['project_id'];
    $region = 'europe-central2';
    $model = 'gemini-2.5-flash';

    $url = "https://{$region}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$region}/publishers/google/models/{$model}:generateContent";

    $prompt = "Preradi sljedeći novinarski članak na hrvatski jezik. Zadrži sve ključne informacije ali promijeni strukturu rečenica i riječi tako da tekst bude originalan. Ne dodaji ništa što nije u originalnom tekstu. Vrati samo prerađeni tekst bez dodatnih komentara.\n\nNaslov: $title\n\nTekst:\n$text";

    $data = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 4096,
            'thinkingConfig' => ['thinkingBudget' => 0]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 180,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $auth['token']
        ],
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        return ['error' => 'Greška konekcije: ' . $curlError];
    }

    if ($httpCode !== 200) {
        $errData = json_decode($response, true);
        $errMsg = $errData['error']['message'] ?? 'HTTP ' . $httpCode;
        return ['error' => 'Gemini API greška: ' . $errMsg];
    }

    $result = json_decode($response, true);

    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        return ['text' => $result['candidates'][0]['content']['parts'][0]['text']];
    }

    return ['error' => 'Neočekivan odgovor od API-ja'];
}

// Funkcija za obradu URL-a
function processUrl($url, $rewrite = false) {
    $result = ['error' => null, 'title' => '', 'content' => '', 'rewritten' => '', 'debug' => ''];

    if (empty($url)) {
        $result['error'] = 'Molimo unesite URL članka.';
        return $result;
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $result['error'] = 'Neispravan URL format.';
        return $result;
    }

    // Dohvati HTML
    $html = fetchHtml($url);

    if (!$html) {
        $result['error'] = 'Nije moguće dohvatiti stranicu. Provjerite URL.';
        return $result;
    }

    // Debug - prvih 500 znakova HTML-a
    $result['debug'] = "HTML dohvaćen: " . strlen($html) . " bajtova\n";
    $result['debug'] .= "Prvih 300 znakova: " . substr($html, 0, 300) . "\n\n";

    // Provjeri ima li uopće item__body ili itemFullText u HTML-u
    if (strpos($html, 'item__body') !== false) {
        $result['debug'] .= "PRONAĐENO: item__body\n";
    }
    if (strpos($html, 'itemFullText') !== false) {
        $result['debug'] .= "PRONAĐENO: itemFullText\n";
    }
    if (strpos($html, 'excerpt') !== false) {
        $result['debug'] .= "PRONAĐENO: excerpt\n";
    }

    // Ekstrahiraj sadržaj
    $extracted = extractArticleContent($html, $url);
    $result['title'] = $extracted['title'];
    $result['content'] = $extracted['content'];
    $result['debug'] .= "\nNaslov: " . $result['title'] . "\n";
    $result['debug'] .= "Sadržaj duljina: " . strlen($result['content']) . "\n";

    if (empty($result['content'])) {
        $result['error'] = 'Nije pronađen tekst članka na stranici.';
        return $result;
    }

    // Ako je zatražena prerada
    if ($rewrite) {
        $rewritten = rewriteWithGemini($result['content'], $result['title']);
        if (isset($rewritten['error'])) {
            $result['error'] = $rewritten['error'];
        } else {
            $result['rewritten'] = $rewritten['text'];
        }
    }

    return $result;
}

// Obrada forme (POST)
$debugInfo = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $articleUrl = trim($_POST['url'] ?? '');
    $rewrite = isset($_POST['rewrite']) && $_POST['rewrite'] === '1';

    $result = processUrl($articleUrl, $rewrite);
    $error = $result['error'];
    $articleTitle = $result['title'];
    $originalText = $result['content'];
    $processedText = $result['rewritten'];
    $debugInfo = $result['debug'] ?? '';
}
// Auto-fetch kad je URL proslijeđen preko GET
elseif ($autoFetch) {
    $result = processUrl($articleUrl, false);
    $error = $result['error'];
    $articleTitle = $result['title'];
    $originalText = $result['content'];
    $debugInfo = $result['debug'] ?? '';
}

define('PAGE_TITLE', 'Skini tekst');
include 'includes/header.php';
?>

<div class="page-header">
    <h1>Skini i preradi tekst</h1>
    <p style="color: #6b7280; margin: 0.25rem 0 0 0;">Unesite URL članka za ekstrakciju i preradu teksta</p>
</div>

<form method="POST" id="mainForm" style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem;">
    <?= csrfField() ?>

    <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
        <input type="url" name="url" value="<?= e($articleUrl) ?>" placeholder="https://www.portal.hr/clanak/..."
               style="flex: 1; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;">
        <button type="submit" name="rewrite" value="0" class="btn btn-outline" id="btnSkini">Samo skini</button>
        <button type="submit" name="rewrite" value="1" class="btn btn-primary" id="btnPreradi">Skini i preradi</button>
    </div>
</form>

<?php if ($error): ?>
<div style="background: #fee2e2; border: 1px solid #fecaca; color: #dc2626; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <?= e($error) ?>
</div>
<?php endif; ?>

<?php if (!empty($debugInfo)): ?>
<div style="background: #fef3c7; border: 1px solid #fcd34d; color: #92400e; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; font-family: monospace; font-size: 0.8rem; white-space: pre-wrap;"><strong>DEBUG:</strong>
<?= e($debugInfo) ?></div>
<?php endif; ?>

<?php if ($originalText): ?>
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
    <!-- Originalni tekst -->
    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
        <div style="background: #f3f4f6; padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">
            <strong>Originalni tekst</strong>
            <?php if ($articleTitle): ?>
            <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;"><?= e($articleTitle) ?></div>
            <?php endif; ?>
            <div style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem;">
                <?= number_format(mb_strlen($originalText)) ?> znakova
            </div>
        </div>
        <div style="padding: 1rem;">
            <textarea id="originalText" readonly style="width: 100%; height: 400px; border: 1px solid #e5e7eb; border-radius: 4px; padding: 0.75rem; font-size: 0.875rem; line-height: 1.6; resize: vertical;"><?= e($originalText) ?></textarea>
            <button type="button" onclick="copyText('originalText')" class="btn btn-outline" style="margin-top: 0.5rem;">
                Kopiraj
            </button>
        </div>
    </div>

    <!-- Prerađeni tekst -->
    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
        <div style="background: #dbeafe; padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong>Prerađeni tekst</strong>
                <?php if ($processedText): ?>
                <div style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem;">
                    <?= number_format(mb_strlen($processedText)) ?> znakova
                </div>
                <?php endif; ?>
            </div>
            <?php if (!$processedText && $originalText): ?>
            <form method="POST" id="rewriteForm" style="margin: 0;">
                <?= csrfField() ?>
                <input type="hidden" name="url" value="<?= e($articleUrl) ?>">
                <button type="submit" name="rewrite" value="1" class="btn btn-primary" id="btnPreradi2" style="padding: 0.25rem 0.75rem; font-size: 0.75rem;">
                    Preradi s AI
                </button>
            </form>
            <?php endif; ?>
        </div>
        <div style="padding: 1rem;">
            <?php if ($processedText): ?>
            <textarea id="processedText" readonly style="width: 100%; height: 400px; border: 1px solid #e5e7eb; border-radius: 4px; padding: 0.75rem; font-size: 0.875rem; line-height: 1.6; resize: vertical;"><?= e($processedText) ?></textarea>
            <button type="button" onclick="copyText('processedText')" class="btn btn-primary" style="margin-top: 0.5rem;">
                Kopiraj
            </button>
            <?php else: ?>
            <div style="height: 400px; display: flex; align-items: center; justify-content: center; color: #9ca3af; background: #f9fafb; border-radius: 4px; flex-direction: column; gap: 0.5rem;">
                <span>Kliknite "Preradi s AI" za preradu teksta</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function copyText(elementId) {
    const textarea = document.getElementById(elementId);
    textarea.select();
    document.execCommand('copy');

    // Vizualna potvrda
    const btn = textarea.parentNode.querySelector('button');
    const originalText = btn.textContent;
    btn.textContent = 'Kopirano!';
    setTimeout(() => btn.textContent = originalText, 1500);
}

// Loading state za forme
document.getElementById('mainForm')?.addEventListener('submit', function(e) {
    const btn = e.submitter;
    if (btn.value === '1') {
        btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;border-width:2px;margin-right:6px;display:inline-block;vertical-align:middle;"></span> Skidam i prerađujem...';
    } else {
        btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;border-width:2px;margin-right:6px;display:inline-block;vertical-align:middle;"></span> Skidam...';
    }
    // Disable nakon kratke pauze da forma stigne otići
    setTimeout(() => { btn.disabled = true; }, 100);
});

document.getElementById('rewriteForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('btnPreradi2');
    btn.innerHTML = '<span class="spinner" style="width:12px;height:12px;border-width:2px;margin-right:4px;display:inline-block;vertical-align:middle;"></span> Radim...';
    // Disable nakon kratke pauze da forma stigne otići
    setTimeout(() => { btn.disabled = true; }, 100);
});
</script>
<style>
.spinner {
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
