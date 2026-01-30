<?php
/**
 * API endpoint za preradu teksta s Google Gemini (Vertex AI)
 */

// Debug: log every request
file_put_contents('/tmp/rewrite_debug.log', date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);

require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Provjera autentikacije
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Niste prijavljeni']);
    exit;
}

// Samo POST metoda
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metoda nije dozvoljena']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Dohvati JSON input
$input = json_decode(file_get_contents('php://input'), true);
$text = trim($input['text'] ?? '');
$sourceUrl = trim($input['source_url'] ?? '');
$sourceName = trim($input['source_name'] ?? '');
$userInstructions = trim($input['instructions'] ?? '');

if (empty($text)) {
    echo json_encode(['success' => false, 'error' => 'Tekst je prazan']);
    exit;
}

// Očisti markdown oznake i višestruke prazne linije iz ulaznog teksta
$text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text); // **bold** -> bold
$text = preg_replace('/\*(.+?)\*/', '$1', $text);     // *italic* -> italic
$text = preg_replace('/^\*\s+/m', '- ', $text);       // * bullet -> - bullet
$text = preg_replace('/^#+\s*/m', '', $text);         // ### heading -> heading
$text = preg_replace("/\n{3,}/", "\n\n", $text);      // višestruke prazne linije
$text = trim($text);

// Google Gemini - JWT token
function getGoogleAccessToken() {
    $credentialsFile = dirname(__DIR__) . '/google-credentials.json';

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

    return [
        'token' => $data['access_token'],
        'project_id' => $credentials['project_id']
    ];
}

// Dohvati Google access token
$auth = getGoogleAccessToken();

if (isset($auth['error'])) {
    echo json_encode(['success' => false, 'error' => $auth['error']]);
    exit;
}

$projectId = $auth['project_id'];
$region = 'europe-central2';
$model = 'gemini-2.0-flash-001';

$url = "https://{$region}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$region}/publishers/google/models/{$model}:generateContent";

$systemPrompt = "Ti si profesionalni urednik i novinar koji piše na hrvatskom jeziku.
Tvoj zadatak je preraditi novinarski članak tako da bude originalan.

VAŽNO: Tekst je automatski izvučen sa web stranice i može sadržavati elemente koji NISU dio članka:
- Twitter/X embedovi, hashtag-ovi (#FISAlpine, pic.twitter.com, itd.)
- Tekstovi na engleskom jeziku koji su dio social media postova
- Navigacijski elementi, linkovi na druge članke
- Reklame, promocije, pozivi na pretplatu
- Upozorenja o igrama na sreću, kladionice (npr. "Igre na sreću mogu izazvati ovisnost. 18+")
- Komentari čitatelja, tagovi, kategorije
- Footer informacije, kontakt podaci portala
- Bilo kakav tekst koji nije dio samog novinarskog članka
IGNORIRAJ sve takve elemente i koristi SAMO sadržaj koji je dio novinarskog članka na hrvatskom jeziku.

FORMAT ODGOVORA:
NASLOV: [novi, originalni naslov članka]

[prerađeni tekst članka]

Pravila:
- OBAVEZNO na početku napiši 'NASLOV:' i zatim novi, originalni naslov
- Piši isključivo na hrvatskom jeziku
- Koristi pravilan hrvatski pravopis i gramatiku
- Zadrži sve činjenice i ključne informacije iz originalnog teksta
- Promijeni strukturu rečenica i koristi sinonime
- Ne dodaji nove informacije koje nisu u originalnom tekstu
- Naslov mora biti drugačiji od originalnog ali prenositi istu poruku
- NE koristi markdown formatiranje u odgovoru - bez **, *, #, bullet points i slično. Piši čisti tekst.";

// Ako imamo izvor, dodaj instrukciju za navođenje izvora
$sourceInstruction = "";
if (!empty($sourceName)) {
    $systemPrompt .= "\n- Na kraju članka OBAVEZNO dodaj rečenicu koja navodi izvor informacija";
    $sourceInstruction = "\n\nIZVOR: " . $sourceName;
    if (!empty($sourceUrl)) {
        $sourceInstruction .= " (" . $sourceUrl . ")";
    }
    $sourceInstruction .= "\nNa kraju prerađenog teksta dodaj rečenicu poput: 'Kako navodi " . $sourceName . ", ...' ili 'Prenosi " . $sourceName . ".' - uklopi prirodno u tekst.";
}

// Dodaj korisničke upute ako postoje
$customInstructions = "";
if (!empty($userInstructions)) {
    $customInstructions = "\n\nDODATNE UPUTE OD KORISNIKA: " . $userInstructions;
}

$userPrompt = "Preradi sljedeći članak:\n\n" . $text . $sourceInstruction . $customInstructions;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_TIMEOUT => 120,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $auth['token']
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'contents' => [
            [
                'role' => 'user',
                'parts' => [['text' => $userPrompt]]
            ]
        ],
        'systemInstruction' => [
            'parts' => [['text' => $systemPrompt]]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 8192
        ]
    ])
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['success' => false, 'error' => 'Curl greška: ' . $curlError]);
    exit;
}

$result = json_decode($response, true);

if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    // Debug log
    $rawText = $result['candidates'][0]['content']['parts'][0]['text'];
    file_put_contents('/tmp/rewrite_debug.log', "=== RAW ===\n" . $rawText . "\n\n", FILE_APPEND);

    // Očisti markdown i višestruke prazne linije iz odgovora
    $cleanText = trim($rawText);
    // Normaliziraj sve vrste line endings (uključujući Unicode)
    $cleanText = str_replace(["\r\n", "\r", "\xE2\x80\xA8", "\xE2\x80\xA9"], "\n", $cleanText);
    // Zamijeni non-breaking space s običnim
    $cleanText = str_replace(["\xC2\xA0", "\xE2\x80\x89", "\xE2\x80\xAF"], " ", $cleanText);
    $cleanText = preg_replace('/\*\*(.+?)\*\*/s', '$1', $cleanText);  // **bold** -> bold
    $cleanText = preg_replace('/\*([^*\n]+)\*/s', '$1', $cleanText);  // *italic* -> italic
    $cleanText = str_replace('**', '', $cleanText);                   // preostali **
    $cleanText = preg_replace('/^[\*\-•]\s*/m', '', $cleanText);       // bullet na početku linije
    $cleanText = preg_replace('/\*+$/m', '', $cleanText);             // * na kraju linija
    $cleanText = preg_replace('/^#+\s*/m', '', $cleanText);           // ### heading -> ukloni
    // Ukloni sve prazne linije
    while (strpos($cleanText, "\n\n") !== false) {
        $cleanText = str_replace("\n\n", "\n", $cleanText);
    }
    // Ukloni linije koje su samo razmaci
    $lines = explode("\n", $cleanText);
    $lines = array_filter($lines, function($line) {
        return trim($line) !== '';
    });
    $cleanText = trim(implode("\n", $lines));

    // Debug log cleaned text
    file_put_contents('/tmp/rewrite_debug.log', "=== CLEANED ===\n" . $cleanText . "\n\n=== END ===\n\n", FILE_APPEND);

    echo json_encode([
        'success' => true,
        'text' => $cleanText
    ]);
} else {
    $errorMsg = $result['error']['message'] ?? 'Neočekivan odgovor od API-ja';
    echo json_encode(['success' => false, 'error' => $errorMsg]);
}
