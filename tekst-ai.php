<?php
/**
 * AI Prerada teksta za objavu
 */

define('PAGE_TITLE', 'AI Tekst');

require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

requireLogin();

set_time_limit(120);

// Funkcija za čišćenje AI outputa - uklanja markdown i prazne linije
function cleanAiOutput($text) {
    $clean = trim($text);
    // Normaliziraj sve vrste line endings (uključujući Unicode)
    $clean = str_replace(["\r\n", "\r", "\xE2\x80\xA8", "\xE2\x80\xA9"], "\n", $clean);
    // Zamijeni non-breaking space s običnim
    $clean = str_replace(["\xC2\xA0", "\xE2\x80\x89", "\xE2\x80\xAF"], " ", $clean);
    $clean = preg_replace('/\*\*(.+?)\*\*/s', '$1', $clean);  // **bold** -> bold
    $clean = preg_replace('/\*([^*\n]+)\*/s', '$1', $clean);  // *italic* -> italic
    $clean = str_replace('**', '', $clean);                   // preostali **
    $clean = preg_replace('/^(?:[\*\-•]|\xC2\xB7)\s*/m', '', $clean);  // bullet na početku linije
    $clean = preg_replace('/\*+$/m', '', $clean);             // * na kraju linija
    $clean = preg_replace('/^#+\s*/m', '', $clean);           // ### heading -> ukloni
    // Ukloni prazne linije - split, filter, join
    $lines = explode("\n", $clean);
    $lines = array_filter($lines, function($line) {
        return trim($line) !== '';
    });
    return trim(implode("\n", $lines));
}

$originalText = $_POST['original_text'] ?? '';
$instructions = $_POST['instructions'] ?? '';
$resultText = null;
$error = null;
$activeTab = $_GET['tab'] ?? 'prerada';
$mode = $_POST['mode'] ?? 'prerada';

// Google Gemini - JWT token
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

    return [
        'token' => $data['access_token'],
        'project_id' => $credentials['project_id']
    ];
}

// Google Gemini prerada teksta
function preradi($text, $instructions) {
    $auth = getGoogleAccessToken();

    if (isset($auth['error'])) {
        return $auth;
    }

    $projectId = $auth['project_id'];
    $region = 'europe-central2';
    $model = 'gemini-2.0-flash-001';

    $url = "https://{$region}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$region}/publishers/google/models/{$model}:generateContent";

    $systemPrompt = "Ti si profesionalni urednik i novinar koji piše na hrvatskom jeziku.
Tvoj zadatak je preraditi tekst prema uputama korisnika.

Pravila:
- Piši isključivo na hrvatskom jeziku
- Koristi pravilan hrvatski pravopis i gramatiku
- Zadrži činjenice i ključne informacije iz originalnog teksta
- Prilagodi stil prema uputama
- Budi jasan, koncizan i profesionalan
- Ne izmišljaj nove informacije koje nisu u originalnom tekstu
- NE koristi bullet points, liste ni nabrajanja - piši u tekućim paragrafima
- NE koristi markdown formatiranje (**, *, #, itd.)";

    $userPrompt = "ORIGINALNI TEKST:\n" . $text . "\n\nUPUTE ZA PRERADU:\n" . $instructions . "\n\nPreradi tekst prema uputama. Vrati SAMO prerađeni tekst, bez dodatnih objašnjenja.";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 90,
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
                'maxOutputTokens' => 4000
            ]
        ])
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['error' => 'Greška: ' . $curlError];
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        $errMsg = $data['error']['message'] ?? 'HTTP ' . $httpCode;
        return ['error' => 'Gemini API greška: ' . $errMsg];
    }

    if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return ['error' => 'Nema odgovora od Gemini-ja'];
    }

    return ['text' => $data['candidates'][0]['content']['parts'][0]['text']];
}

// Raspakuj ZIP i vrati listu fajlova
function extractZipFiles($zipPath) {
    $extractedFiles = [];
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return [];
    }

    $tempDir = sys_get_temp_dir() . '/zip_' . uniqid();
    mkdir($tempDir, 0755, true);
    $zip->extractTo($tempDir);
    $zip->close();

    // Rekurzivno pronađi sve fajlove
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempDir));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'docx', 'txt'])) {
                $extractedFiles[] = [
                    'path' => $file->getPathname(),
                    'name' => $file->getFilename(),
                    'ext' => $ext
                ];
            }
        }
    }

    return ['files' => $extractedFiles, 'tempDir' => $tempDir];
}

// Obriši temp direktorij
function cleanupTempDir($dir) {
    if (!is_dir($dir)) return;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }
    rmdir($dir);
}

// Kompresiraj sliku za API (max 1024px, kvaliteta 80%)
function compressImageForApi($filePath, $mimeType) {
    $maxDim = 1024;

    // Učitaj sliku
    switch ($mimeType) {
        case 'image/jpeg':
            $img = @imagecreatefromjpeg($filePath);
            break;
        case 'image/png':
            $img = @imagecreatefrompng($filePath);
            break;
        case 'image/gif':
            $img = @imagecreatefromgif($filePath);
            break;
        case 'image/webp':
            $img = @imagecreatefromwebp($filePath);
            break;
        default:
            return null;
    }

    if (!$img) {
        return file_get_contents($filePath);
    }

    $width = imagesx($img);
    $height = imagesy($img);

    // Ako je slika manja od max, vrati original
    if ($width <= $maxDim && $height <= $maxDim) {
        imagedestroy($img);
        return file_get_contents($filePath);
    }

    // Izračunaj nove dimenzije
    if ($width > $height) {
        $newWidth = $maxDim;
        $newHeight = (int)($height * ($maxDim / $width));
    } else {
        $newHeight = $maxDim;
        $newWidth = (int)($width * ($maxDim / $height));
    }

    // Kreiraj novu sliku
    $newImg = imagecreatetruecolor($newWidth, $newHeight);

    // Očuvaj transparentnost za PNG
    if ($mimeType === 'image/png') {
        imagealphablending($newImg, false);
        imagesavealpha($newImg, true);
    }

    imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Spremi u buffer
    ob_start();
    imagejpeg($newImg, null, 80);
    $data = ob_get_clean();

    imagedestroy($img);
    imagedestroy($newImg);

    return $data;
}

// Izvuci tekst iz Word dokumenta (.docx)
function extractTextFromDocx($filePath) {
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        return '';
    }
    $content = $zip->getFromName('word/document.xml');
    $zip->close();
    if (!$content) {
        return '';
    }
    // Ukloni XML tagove i zadrži tekst
    $content = strip_tags($content);
    $content = preg_replace('/\s+/', ' ', $content);
    return trim($content);
}

// Google Gemini - generiraj tekst iz dokumenata
function generirajIzDokumenata($files, $instructions) {
    $auth = getGoogleAccessToken();
    if (isset($auth['error'])) {
        return $auth;
    }

    $projectId = $auth['project_id'];
    $region = 'europe-central2';
    $model = 'gemini-2.0-flash-001';
    $url = "https://{$region}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$region}/publishers/google/models/{$model}:generateContent";

    $systemPrompt = "Ti si profesionalni novinar i urednik koji piše na hrvatskom jeziku.
Tvoj zadatak je pročitati priložene dokumente i napisati tekst prema uputama korisnika.

FORMAT ODGOVORA:
1. Najprije napiši PREGLED SVIH DOKUMENATA u formatu:

NAZIV FAJLA: [ime fajla ili Slika 1, Slika 2 ako nema imena]
[Kratki opis sadržaja dokumenta u 1-2 rečenice]

NAZIV FAJLA: [sljedeći fajl]
[Kratki opis]

... i tako za SVAKI dokument koji si primio. OBAVEZNO navedi SVE dokumente!

2. Zatim napiši prazan red i oznaku '---'
3. Nakon toga napiši traženi tekst prema uputama

Pravila:
- Piši isključivo na hrvatskom jeziku
- Koristi pravilan hrvatski pravopis i gramatiku
- Izvuci ključne informacije iz svih dokumenata
- Budi jasan, koncizan i profesionalan
- NE koristi bullet points, liste ni nabrajanja - piši u tekućim paragrafima
- NE koristi markdown formatiranje (**, *, #, itd.)";

    // Pripremi parts za multimodalni request
    $parts = [];
    $textContent = "";
    $tempDirs = []; // Za cleanup ZIP direktorija

    // Funkcija za obradu jednog fajla
    $processFile = function($filePath, $fileName, $ext, $mimeType = null) use (&$parts, &$textContent) {
        if (!$mimeType) {
            $mimeTypes = [
                'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp',
                'pdf' => 'application/pdf'
            ];
            $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
        }

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            // Dodaj ime fajla prije slike
            $parts[] = ['text' => "[SLIKA: {$fileName}]"];
            // Kompresiraj sliku za manji request
            $imageData = compressImageForApi($filePath, $mimeType);
            $data = base64_encode($imageData);
            $parts[] = [
                'inlineData' => [
                    'mimeType' => 'image/jpeg', // kompresirana je uvijek JPEG
                    'data' => $data
                ]
            ];
        } elseif ($ext === 'pdf') {
            // Dodaj ime fajla prije PDF-a
            $parts[] = ['text' => "[PDF: {$fileName}]"];
            $data = base64_encode(file_get_contents($filePath));
            $parts[] = [
                'inlineData' => [
                    'mimeType' => 'application/pdf',
                    'data' => $data
                ]
            ];
        } elseif ($ext === 'docx') {
            $text = extractTextFromDocx($filePath);
            if ($text) {
                $textContent .= "\n\n--- Dokument: {$fileName} ---\n" . $text;
            }
        } elseif ($ext === 'txt') {
            $text = file_get_contents($filePath);
            if ($text) {
                $textContent .= "\n\n--- Dokument: {$fileName} ---\n" . $text;
            }
        }
    };

    foreach ($files['tmp_name'] as $i => $tmpName) {
        if (empty($tmpName) || $files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }

        $fileName = $files['name'][$i];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $mimeType = $files['type'][$i];

        if ($ext === 'zip') {
            // ZIP - raspakuj i obradi sve fajlove
            $zipResult = extractZipFiles($tmpName);
            if (!empty($zipResult['files'])) {
                $tempDirs[] = $zipResult['tempDir'];
                foreach ($zipResult['files'] as $zipFile) {
                    $processFile($zipFile['path'], $zipFile['name'], $zipFile['ext']);
                }
            }
        } else {
            $processFile($tmpName, $fileName, $ext, $mimeType);
        }
    }

    // Dodaj tekstualni sadržaj ako postoji
    if ($textContent) {
        $parts[] = ['text' => "SADRŽAJ DOKUMENATA:\n" . $textContent];
    }

    // Broj dokumenata za AI
    $docCount = count($parts);
    if ($textContent) $docCount--; // tekstualni sadržaj je jedan part

    // Dodaj upute
    $parts[] = ['text' => "\n\nUKUPNO DOKUMENATA: " . $docCount . "\n\nUPUTE:\n" . $instructions . "\n\nNapiši tekst prema uputama na temelju SVIH priloženih dokumenata. Obavezno navedi SVE dokumente u pregledu!"];

    if (count($parts) < 2) {
        foreach ($tempDirs as $dir) { cleanupTempDir($dir); }
        return ['error' => 'Nije učitan nijedan podržani dokument'];
    }

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
                    'parts' => $parts
                ]
            ],
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 8000
            ]
        ])
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['error' => 'Greška: ' . $curlError];
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        $errMsg = $data['error']['message'] ?? 'HTTP ' . $httpCode;
        return ['error' => 'Gemini API greška: ' . $errMsg];
    }

    if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return ['error' => 'Nema odgovora od Gemini-ja'];
    }

    // Cleanup temp direktorija od ZIP-ova
    foreach ($tempDirs as $dir) {
        cleanupTempDir($dir);
    }

    return ['text' => $data['candidates'][0]['content']['parts'][0]['text']];
}

// Google Gemini - generiraj novi tekst
function generirajTekst($instructions) {
    $auth = getGoogleAccessToken();

    if (isset($auth['error'])) {
        return $auth;
    }

    $projectId = $auth['project_id'];
    $region = 'europe-central2';
    $model = 'gemini-2.0-flash-001';

    $url = "https://{$region}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$region}/publishers/google/models/{$model}:generateContent";

    $systemPrompt = "Ti si profesionalni pisac i novinar koji piše na hrvatskom jeziku.
Tvoj zadatak je napisati tekst prema uputama korisnika.

Pravila:
- Piši isključivo na hrvatskom jeziku
- Koristi pravilan hrvatski pravopis i gramatiku
- Budi kreativan ali činjenično točan
- Prilagodi stil i ton prema uputama
- Budi jasan, koncizan i profesionalan
- Ako upute zahtijevaju specifične informacije koje ne poznaješ, koristi placeholder tekst [DODATI: opis]
- NE koristi bullet points, liste ni nabrajanja - piši u tekućim paragrafima
- NE koristi markdown formatiranje (**, *, #, itd.)";

    $userPrompt = "UPUTE:\n" . $instructions . "\n\nNapiši tekst prema uputama. Vrati SAMO tekst, bez dodatnih objašnjenja.";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 90,
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
                'temperature' => 0.8,
                'maxOutputTokens' => 4000
            ]
        ])
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['error' => 'Greška: ' . $curlError];
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        $errMsg = $data['error']['message'] ?? 'HTTP ' . $httpCode;
        return ['error' => 'Gemini API greška: ' . $errMsg];
    }

    if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return ['error' => 'Nema odgovora od Gemini-ja'];
    }

    return ['text' => $data['candidates'][0]['content']['parts'][0]['text']];
}

// Obrada forme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $originalText = trim($_POST['original_text'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $mode = $_POST['mode'] ?? 'prerada';
    $activeTab = $mode;

    if ($mode === 'dokumenti') {
        // Iz dokumenata
        if (empty($_FILES['documents']['tmp_name'][0])) {
            $error = 'Odaberite barem jedan dokument';
        } elseif (empty($instructions)) {
            $error = 'Unesite upute za pisanje teksta';
        } else {
            $result = generirajIzDokumenata($_FILES['documents'], $instructions);

            if (isset($result['error'])) {
                $error = $result['error'];
            } else {
                $resultText = cleanAiOutput($result['text']);
                logActivity('ai_text_from_docs', 'ai', null);
            }
        }
    } elseif ($mode === 'novi') {
        // Novi tekst - samo upute
        if (empty($instructions)) {
            $error = 'Unesite upute za pisanje teksta';
        } else {
            $result = generirajTekst($instructions);

            if (isset($result['error'])) {
                $error = $result['error'];
            } else {
                $resultText = cleanAiOutput($result['text']);
                logActivity('ai_text_generate', 'ai', null);
            }
        }
    } else {
        // Prerada teksta
        if (empty($originalText)) {
            $error = 'Unesite tekst za preradu';
        } elseif (empty($instructions)) {
            $error = 'Unesite upute za preradu';
        } else {
            $result = preradi($originalText, $instructions);

            if (isset($result['error'])) {
                $error = $result['error'];
            } else {
                $resultText = cleanAiOutput($result['text']);
                logActivity('ai_text_process', 'ai', null);
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>AI Tekst</h1>
</div>

<!-- Tabovi -->
<div class="tabs" style="margin-bottom: 1rem;">
    <a href="?tab=prerada" class="tab <?= $activeTab === 'prerada' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 20h9"/>
            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
        </svg>
        Prerada teksta
    </a>
    <a href="?tab=novi" class="tab <?= $activeTab === 'novi' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="12" y1="18" x2="12" y2="12"/>
            <line x1="9" y1="15" x2="15" y2="15"/>
        </svg>
        Novi tekst
    </a>
    <a href="?tab=dokumenti" class="tab <?= $activeTab === 'dokumenti' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <path d="M9 13h6"/>
            <path d="M9 17h3"/>
        </svg>
        Iz dokumenata
    </a>
</div>

<?php if ($activeTab === 'dokumenti'): ?>
<!-- IZ DOKUMENATA -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Generiraj tekst iz dokumenata</h2>
    </div>
    <div class="card-body">
        <form method="POST" id="docsForm" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="mode" value="dokumenti">

            <div class="form-group">
                <label class="form-label">Dokumenti *</label>
                <input type="file" name="documents[]" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.docx,.txt,.zip" class="form-control" id="fileInput">
                <small class="form-text">Podržani formati: PDF, slike (JPG, PNG, GIF, WebP), Word (.docx), tekst (.txt), ZIP arhive. Možete odabrati više datoteka.</small>
                <div id="fileList" style="margin-top: 0.5rem;"></div>
            </div>

            <div class="form-group">
                <label class="form-label">Upute za pisanje *</label>
                <textarea name="instructions" class="form-control" rows="4" placeholder="Opišite kakav tekst želite na temelju dokumenata...

Npr:
- Napiši vijest na temelju priloženog priopćenja
- Sažmi dokument u 200 riječi
- Izvuci ključne informacije i napiši članak"><?= e($activeTab === 'dokumenti' ? $instructions : '') ?></textarea>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary" id="docsSubmitBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                    </svg>
                    Generiraj tekst
                </button>
            </div>
        </form>

        <?php if ($error && $activeTab === 'dokumenti'): ?>
        <div class="alert alert-danger" style="margin-top: 1rem;">
            <?= e($error) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($activeTab === 'prerada'): ?>
<!-- PRERADA TEKSTA -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Preradi postojeći tekst</h2>
    </div>
    <div class="card-body">
        <form method="POST" id="textForm">
            <?= csrfField() ?>
            <input type="hidden" name="mode" value="prerada">

            <div class="form-group">
                <label class="form-label">Originalni tekst *</label>
                <textarea name="original_text" class="form-control" rows="8" placeholder="Zalijepite tekst koji želite preraditi..."><?= e($originalText) ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Upute za preradu *</label>
                <textarea name="instructions" class="form-control" rows="3" placeholder="Opišite kako želite preraditi tekst..."><?= e($instructions) ?></textarea>
                <small class="form-text">Npr: "Skrati na 200 riječi", "Napiši kao vijest", "Pojednostavi jezik"</small>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 20h9"/>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                    </svg>
                    Preradi tekst
                </button>
            </div>
        </form>

        <?php if ($error && $activeTab === 'prerada'): ?>
        <div class="alert alert-danger" style="margin-top: 1rem;">
            <?= e($error) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($activeTab === 'novi'): ?>
<!-- NOVI TEKST -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Generiraj novi tekst</h2>
    </div>
    <div class="card-body">
        <form method="POST" id="newTextForm">
            <?= csrfField() ?>
            <input type="hidden" name="mode" value="novi">

            <div class="form-group">
                <label class="form-label">Upute za pisanje *</label>
                <textarea name="instructions" class="form-control" rows="6" placeholder="Opišite što želite da AI napiše...

Npr:
- Napiši vijest o otvorenju novog dječjeg vrtića u Krapini
- Napiši najavu za koncert koji se održava 25. siječnja u Dvorani
- Napiši čestitku za Novu godinu u ime gradonačelnika"><?= e($activeTab === 'novi' ? $instructions : '') ?></textarea>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary" id="newSubmitBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                    </svg>
                    Generiraj tekst
                </button>
            </div>
        </form>

        <?php if ($error && $activeTab === 'novi'): ?>
        <div class="alert alert-danger" style="margin-top: 1rem;">
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <!-- Primjeri za novi tekst -->
        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--gray-200);">
            <label class="form-label" style="margin-bottom: 0.5rem;">Brzi primjeri:</label>
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                <button type="button" class="btn btn-sm btn-outline" onclick="setNewInstruction('Napiši kratku vijest o [TEMA]. Uključi naslov i 2-3 odlomka.')">Vijest</button>
                <button type="button" class="btn btn-sm btn-outline" onclick="setNewInstruction('Napiši najavu događaja: [NAZIV DOGAĐAJA], [DATUM], [MJESTO]. Uključi poziv na sudjelovanje.')">Najava</button>
                <button type="button" class="btn btn-sm btn-outline" onclick="setNewInstruction('Napiši svečanu čestitku za [PRIGODA] u ime [OSOBA/INSTITUCIJA].')">Čestitka</button>
                <button type="button" class="btn btn-sm btn-outline" onclick="setNewInstruction('Napiši objavu za društvene mreže o [TEMA]. Kratko, privlačno, s pozivom na akciju.')">Social post</button>
                <button type="button" class="btn btn-sm btn-outline" onclick="setNewInstruction('Napiši press release o [TEMA]. Formalni ton, uključi citate.')">Press release</button>
                <button type="button" class="btn btn-sm btn-outline" onclick="setNewInstruction('Napiši blog članak o [TEMA]. Opušteni ton, 400-600 riječi.')">Blog</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($resultText): ?>
<div class="card mt-2">
    <div class="card-header" style="background: #dcfce7;">
        <h2 class="card-title" style="color: #166534;">Prerađeni tekst</h2>
    </div>
    <div class="card-body">
        <div class="result-text" id="resultText"><?= nl2br(e($resultText)) ?></div>

        <div style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <button onclick="copyResult()" class="btn btn-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
                Kopiraj
            </button>
            <button onclick="useAsOriginal()" class="btn btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="1 4 1 10 7 10"/>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                </svg>
                Koristi kao original (doradi)
            </button>
        </div>
    </div>
</div>

<!-- Dorada rezultata -->
<div class="card mt-2">
    <div class="card-header">
        <h2 class="card-title">Doradi rezultat</h2>
    </div>
    <div class="card-body">
        <form method="POST" id="refineForm">
            <?= csrfField() ?>
            <input type="hidden" name="original_text" value="<?= e($resultText) ?>">

            <div class="form-group">
                <label class="form-label">Dodatne upute</label>
                <textarea name="instructions" class="form-control" rows="2" placeholder="Npr: 'Skrati prvi odlomak', 'Dodaj zaključak', 'Promijeni ton u formalniji'"></textarea>
            </div>

            <button type="submit" class="btn btn-primary" id="refineBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 20h9"/>
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                </svg>
                Doradi
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Primjeri uputa -->
<div class="card mt-2">
    <div class="card-header">
        <h2 class="card-title">Primjeri uputa</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="instruction-examples">
            <div class="instruction-example" onclick="useInstruction(this)">
                <strong>Vijest</strong>
                <p>Pretvori u novinarsku vijest s naslovom, leadom i tijelom teksta. Koristi obrnulu piramidu.</p>
            </div>
            <div class="instruction-example" onclick="useInstruction(this)">
                <strong>Skraćivanje</strong>
                <p>Skrati tekst na maksimalno 150 riječi zadržavajući sve ključne informacije.</p>
            </div>
            <div class="instruction-example" onclick="useInstruction(this)">
                <strong>Pojednostavljenje</strong>
                <p>Pojednostavi tekst da bude razumljiv široj publici. Izbjegavaj stručne termine.</p>
            </div>
            <div class="instruction-example" onclick="useInstruction(this)">
                <strong>Proširenje</strong>
                <p>Proširi tekst s više detalja i konteksta. Dodaj uvod i zaključak.</p>
            </div>
            <div class="instruction-example" onclick="useInstruction(this)">
                <strong>Lektura</strong>
                <p>Ispravi pravopisne i gramatičke greške. Poboljšaj stil pisanja.</p>
            </div>
            <div class="instruction-example" onclick="useInstruction(this)">
                <strong>Najava događaja</strong>
                <p>Pretvori u najavu događaja s vremenom, mjestom i pozivom na sudjelovanje.</p>
            </div>
            <div class="instruction-example" onclick="useInstruction(this)">
                <strong>Objava za društvene mreže</strong>
                <p>Napravi kratku, privlačnu objavu za Facebook/Instagram. Dodaj poziv na akciju.</p>
            </div>
            <div class="instruction-example" onclick="useInstruction(this)">
                <strong>Sažetak</strong>
                <p>Napiši sažetak u 2-3 rečenice s najvažnijim informacijama.</p>
            </div>
        </div>
    </div>
</div>

<style>
.tabs {
    display: flex;
    gap: 0.5rem;
    border-bottom: 2px solid var(--gray-200);
    padding-bottom: 0;
}
.tab {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    text-decoration: none;
    color: var(--gray-600);
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.15s;
}
.tab:hover {
    color: var(--primary-color);
}
.tab.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
    font-weight: 500;
}
.result-text {
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    padding: 1rem;
    line-height: 1.8;
    white-space: pre-wrap;
    max-height: 500px;
    overflow-y: auto;
}
.instruction-examples {
    display: flex;
    flex-direction: column;
}
.instruction-example {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-200);
    cursor: pointer;
    transition: background 0.15s;
}
.instruction-example:hover {
    background: var(--gray-50);
}
.instruction-example:last-child {
    border-bottom: none;
}
.instruction-example strong {
    display: block;
    margin-bottom: 0.25rem;
    color: var(--primary-color);
}
.instruction-example p {
    margin: 0;
    font-size: 0.85rem;
    color: var(--gray-600);
}
</style>

<script>
// Loading state za prerada formu
document.getElementById('textForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;margin-right:8px;"></span> Prerađujem...';
    btn.disabled = true;
});

// Loading state za novi tekst formu
document.getElementById('newTextForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('newSubmitBtn');
    btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;margin-right:8px;"></span> Generiram...';
    btn.disabled = true;
});

// Loading state za dokumente formu
document.getElementById('docsForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('docsSubmitBtn');
    btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;margin-right:8px;"></span> Obrađujem dokumente...';
    btn.disabled = true;
});

// Prikaz odabranih fajlova
document.getElementById('fileInput')?.addEventListener('change', function() {
    const fileList = document.getElementById('fileList');
    if (this.files.length > 0) {
        let html = '<div style="font-size: 0.85rem; color: var(--gray-600);">Odabrano ' + this.files.length + ' datoteka:</div>';
        html += '<ul style="margin: 0.25rem 0 0 1rem; font-size: 0.85rem;">';
        for (let i = 0; i < this.files.length; i++) {
            const size = (this.files[i].size / 1024).toFixed(1);
            html += '<li>' + this.files[i].name + ' (' + size + ' KB)</li>';
        }
        html += '</ul>';
        fileList.innerHTML = html;
    } else {
        fileList.innerHTML = '';
    }
});

// Loading state za doradu
document.getElementById('refineForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('refineBtn');
    btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;margin-right:8px;"></span> Dorađujem...';
    btn.disabled = true;
});

function copyResult() {
    const text = <?= json_encode($resultText ?? '') ?>;
    navigator.clipboard.writeText(text).then(() => {
        alert('Tekst kopiran!');
    });
}

function useAsOriginal() {
    const text = <?= json_encode($resultText ?? '') ?>;
    // Prebaci na prerada tab
    window.location.href = '?tab=prerada';
    // Spremimo u sessionStorage da popunimo nakon učitavanja
    sessionStorage.setItem('originalText', text);
}

// Ako imamo tekst u sessionStorage, popuni
if (sessionStorage.getItem('originalText')) {
    const textarea = document.querySelector('#textForm textarea[name="original_text"]');
    if (textarea) {
        textarea.value = sessionStorage.getItem('originalText');
        sessionStorage.removeItem('originalText');
    }
}

function useInstruction(el) {
    const instruction = el.querySelector('p').textContent;
    document.querySelector('#textForm textarea[name="instructions"]').value = instruction;
    document.querySelector('#textForm textarea[name="instructions"]').scrollIntoView({behavior: 'smooth', block: 'center'});
}

function setNewInstruction(text) {
    document.querySelector('#newTextForm textarea[name="instructions"]').value = text;
}
</script>

<?php include 'includes/footer.php'; ?>
