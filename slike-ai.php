<?php
/**
 * AI Generator slika - Google Imagen 3 + Replicate (Face)
 */

define('PAGE_TITLE', 'AI Slike');

require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

requireLogin();

set_time_limit(180);

$generatedImage = null;
$error = null;
$prompt = '';
$activeTab = $_GET['tab'] ?? 'imagen';
$faceImagePreview = null;

// Brisanje slike
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $filename = basename($_GET['delete']); // Sigurnost - samo ime datoteke
    $filepath = __DIR__ . '/uploads/' . $filename;

    // Provjeri da je imagen_ datoteka (sigurnost)
    if (strpos($filename, 'imagen_') === 0 && file_exists($filepath)) {
        @unlink($filepath);
        // Obriši i txt datoteku s promptom
        $txtFile = __DIR__ . '/uploads/' . str_replace('.png', '.txt', $filename);
        @unlink($txtFile);
        setMessage('success', 'Slika obrisana');
    }

    header('Location: slike-ai.php');
    exit;
}

// Google Imagen - JWT token
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

// Google Imagen 3 - Generiranje slike
function generirajSliku($prompt) {
    $auth = getGoogleAccessToken();

    if (isset($auth['error'])) {
        return $auth;
    }

    $projectId = $auth['project_id'];
    $region = 'europe-central2';
    $model = 'imagen-3.0-generate-001';

    $url = "https://{$region}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$region}/publishers/google/models/{$model}:predict";

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
            'instances' => [
                ['prompt' => $prompt]
            ],
            'parameters' => [
                'sampleCount' => 1,
                'aspectRatio' => '16:9',
                'outputOptions' => ['mimeType' => 'image/png'],
                'safetyFilterLevel' => 'block_few',
                'personGeneration' => 'allow_all'
            ]
        ])
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['error' => 'Curl greška: ' . $curlError];
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        $errMsg = $data['error']['message'] ?? 'HTTP ' . $httpCode;
        return ['error' => 'Google API greška: ' . $errMsg];
    }

    if (!isset($data['predictions'][0]['bytesBase64Encoded'])) {
        return ['error' => 'Nema slike u odgovoru'];
    }

    $imageBase64 = $data['predictions'][0]['bytesBase64Encoded'];

    $baseFilename = 'imagen_' . time() . '_' . uniqid();
    $filename = $baseFilename . '.png';
    $filepath = __DIR__ . '/uploads/' . $filename;

    if (!file_put_contents($filepath, base64_decode($imageBase64))) {
        return ['error' => 'Greška pri spremanju slike'];
    }

    // Spremi prompt u txt datoteku
    file_put_contents(__DIR__ . '/uploads/' . $baseFilename . '.txt', $prompt);

    return ['url' => 'uploads/' . $filename, 'filename' => $filename];
}

// Replicate - generiranje slike s licem (InstantID/PhotoMaker)
function generirajSlikuSLicem($faceImagePath, $prompt) {
    $apiToken = defined('REPLICATE_API_TOKEN') ? REPLICATE_API_TOKEN : getenv('REPLICATE_API_TOKEN');

    if (!$apiToken) {
        return ['error' => 'REPLICATE_API_TOKEN nije postavljen'];
    }

    // Učitaj sliku i pretvori u base64 data URL
    $imageData = file_get_contents($faceImagePath);
    $mimeType = mime_content_type($faceImagePath);
    $base64Image = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);

    // Koristi InstantID model
    $model = 'zsxkib/instant-id:2e4e9a854e32a47e0dce6a9413e6e24d445a0c0c0b8e9c9e4e0b1c1e0e1e2e3e';

    // Alternativno koristi PhotoMaker - bolji za stylizaciju
    $model = 'tencentarc/photomaker:ddfc2b08d209f9fa8c1uj09a0bf6f36d37887e8e';

    // Najnoviji i najbolji - Pulid
    $model = 'fofr/pulid:62d0c9a6b04b3e53aec29b23d1a2353c79c1b72acb6d2c0c4f9c8b7a6e5d4c3b';

    // Koristi face-to-many koji je provjereno dostupan
    $url = 'https://api.replicate.com/v1/predictions';

    $payload = [
        'version' => 'a07f252abbbd832009640b27f063ea52d87d7a23a185ca165bec23b5adc8deaf',
        'input' => [
            'image' => $base64Image,
            'prompt' => $prompt,
            'style' => 'Photographic (Default)',
            'negative_prompt' => 'blurry, bad quality, distorted face, ugly',
            'num_outputs' => 1,
            'guidance_scale' => 7.5,
            'num_inference_steps' => 30
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiToken,
            'Prefer: wait'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['error' => 'Curl greška: ' . $curlError];
    }

    $data = json_decode($response, true);

    if ($httpCode === 422) {
        return ['error' => 'Replicate greška: ' . ($data['detail'] ?? 'Validation error')];
    }

    if ($httpCode !== 200 && $httpCode !== 201) {
        return ['error' => 'Replicate API greška: HTTP ' . $httpCode . ' - ' . ($data['detail'] ?? json_encode($data))];
    }

    // Ako je status "processing" ili "starting", čekaj rezultat
    $predictionId = $data['id'] ?? null;
    $status = $data['status'] ?? '';

    if ($status === 'succeeded' && isset($data['output'])) {
        $outputUrl = is_array($data['output']) ? $data['output'][0] : $data['output'];
    } else if ($predictionId && in_array($status, ['starting', 'processing'])) {
        // Čekaj do 120 sekundi
        $getUrl = "https://api.replicate.com/v1/predictions/{$predictionId}";
        $maxAttempts = 60;
        $outputUrl = null;

        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep(2);

            $ch = curl_init($getUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiToken
                ]
            ]);
            $pollResponse = curl_exec($ch);
            curl_close($ch);

            $pollData = json_decode($pollResponse, true);
            $pollStatus = $pollData['status'] ?? '';

            if ($pollStatus === 'succeeded') {
                $outputUrl = is_array($pollData['output']) ? $pollData['output'][0] : $pollData['output'];
                break;
            } else if ($pollStatus === 'failed') {
                return ['error' => 'Generiranje nije uspjelo: ' . ($pollData['error'] ?? 'Unknown error')];
            }
        }

        if (!$outputUrl) {
            return ['error' => 'Timeout - generiranje predugo traje'];
        }
    } else {
        return ['error' => 'Neočekivan status: ' . $status];
    }

    // Preuzmi sliku i spremi lokalno
    $imageContent = file_get_contents($outputUrl);
    if (!$imageContent) {
        return ['error' => 'Greška pri preuzimanju generirane slike'];
    }

    $baseFilename = 'face_' . time() . '_' . uniqid();
    $filename = $baseFilename . '.png';
    $filepath = __DIR__ . '/uploads/' . $filename;

    if (!file_put_contents($filepath, $imageContent)) {
        return ['error' => 'Greška pri spremanju slike'];
    }

    // Spremi prompt
    file_put_contents(__DIR__ . '/uploads/' . $baseFilename . '.txt', $prompt);

    return ['url' => 'uploads/' . $filename, 'filename' => $filename];
}

// Prevedi na engleski
function prevedNaEngleski($text) {
    if (!defined('OPENAI_API_KEY')) {
        return $text;
    }

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a translator. Translate the following Croatian text to English. Output ONLY the translation, nothing else. Keep it as a good image generation prompt - detailed and descriptive.'
                ],
                ['role' => 'user', 'content' => $text]
            ],
            'temperature' => 0.3
        ])
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return $text;
    }

    $data = json_decode($response, true);
    return $data['choices'][0]['message']['content'] ?? $text;
}

// Dohvati sve generirane slike
function dohvatiSlike() {
    $uploadDir = __DIR__ . '/uploads/';
    $images = [];

    if (is_dir($uploadDir)) {
        $files = glob($uploadDir . 'imagen_*.png');

        // Sortiraj po vremenu (najnovije prvo)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        foreach ($files as $file) {
            $filename = basename($file);
            $txtFile = str_replace('.png', '.txt', $file);
            $prompt = file_exists($txtFile) ? file_get_contents($txtFile) : '';

            $images[] = [
                'filename' => $filename,
                'url' => 'uploads/' . $filename,
                'date' => date('d.m.Y H:i', filemtime($file)),
                'size' => round(filesize($file) / 1024) . ' KB',
                'prompt' => $prompt
            ];
        }
    }

    return $images;
}

// Obrada forme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $prompt = trim($_POST['prompt'] ?? '');
    $mode = $_POST['mode'] ?? 'imagen';
    $activeTab = $mode;

    if (empty($prompt)) {
        $error = 'Unesite opis slike';
    } else {
        if (isset($_POST['translate']) && $_POST['translate'] === '1') {
            $prompt = prevedNaEngleski($prompt);
        }

        if ($mode === 'face') {
            // Face mode - treba uploadanu sliku
            if (!isset($_FILES['face_image']) || $_FILES['face_image']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Molimo uploadajte fotografiju osobe';
            } else {
                $faceFile = $_FILES['face_image'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

                if (!in_array($faceFile['type'], $allowedTypes)) {
                    $error = 'Dozvoljen format: JPG, PNG, WebP';
                } else if ($faceFile['size'] > 10 * 1024 * 1024) {
                    $error = 'Maksimalna veličina: 10MB';
                } else {
                    // Spremi privremeno
                    $tempPath = __DIR__ . '/uploads/temp_face_' . time() . '_' . basename($faceFile['name']);
                    move_uploaded_file($faceFile['tmp_name'], $tempPath);

                    $result = generirajSlikuSLicem($tempPath, $prompt);

                    // Obriši temp file
                    @unlink($tempPath);

                    if (isset($result['error'])) {
                        $error = $result['error'];
                    } else {
                        $generatedImage = $result['url'];
                        logActivity('ai_face_generate', 'ai', null);
                    }
                }
            }
        } else {
            // Imagen mode
            $result = generirajSliku($prompt);

            if (isset($result['error'])) {
                $error = $result['error'];
            } else {
                $generatedImage = $result['url'];
                logActivity('ai_image_generate', 'ai', null);
            }
        }
    }
}

// Dohvati sve slike za prikaz
$allImages = dohvatiSlike();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>AI Generator slika</h1>
</div>

<!-- Tabovi -->
<div class="tabs" style="margin-bottom: 1rem;">
    <a href="?tab=imagen" class="tab <?= $activeTab === 'imagen' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/>
            <polyline points="21 15 16 10 5 21"/>
        </svg>
        Imagen (tekst)
    </a>
    <a href="?tab=face" class="tab <?= $activeTab === 'face' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
        </svg>
        S licem osobe
    </a>
</div>

<?php if ($activeTab === 'imagen'): ?>
<!-- IMAGEN TAB -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Google Imagen 3</h2>
    </div>
    <div class="card-body">
        <form method="POST" id="imageForm">
            <?= csrfField() ?>
            <input type="hidden" name="mode" value="imagen">

            <div class="form-group">
                <label class="form-label">Opis slike *</label>
                <textarea name="prompt" class="form-control" rows="3" placeholder="Opišite sliku koju želite generirati..."><?= e($prompt) ?></textarea>

                <label class="checkbox-label" style="margin-top: 0.5rem; display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="translate" value="1" checked style="width: 18px; height: 18px;">
                    <span>Prevedi na engleski (piši na hrvatskom)</span>
                </label>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                    </svg>
                    Generiraj sliku
                </button>
            </div>
        </form>

        <?php if ($error && $activeTab === 'imagen'): ?>
        <div class="alert alert-danger" style="margin-top: 1rem;">
            <?= e($error) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- FACE TAB -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Generiraj sliku s licem osobe</h2>
    </div>
    <div class="card-body">
        <form method="POST" id="faceForm" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="mode" value="face">

            <div class="form-group">
                <label class="form-label">Fotografija osobe *</label>
                <div class="face-upload-area" id="faceUploadArea">
                    <input type="file" name="face_image" id="faceImageInput" accept="image/jpeg,image/png,image/webp" style="display: none;">
                    <div id="facePreview" style="display: none;">
                        <img id="facePreviewImg" src="" alt="Preview">
                        <button type="button" onclick="clearFaceImage()" class="btn btn-sm btn-outline" style="margin-top: 0.5rem;">Ukloni</button>
                    </div>
                    <div id="faceUploadPrompt" onclick="document.getElementById('faceImageInput').click()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <p>Kliknite za upload fotografije</p>
                        <small>JPG, PNG ili WebP (max 10MB)</small>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Opis željene slike *</label>
                <textarea name="prompt" class="form-control" rows="3" placeholder="Npr: osoba u poslovnom odijelu ispred moderne zgrade, profesionalna fotografija..."><?= e($prompt) ?></textarea>

                <label class="checkbox-label" style="margin-top: 0.5rem; display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="translate" value="1" checked style="width: 18px; height: 18px;">
                    <span>Prevedi na engleski</span>
                </label>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary" id="faceSubmitBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                    </svg>
                    Generiraj sliku
                </button>
            </div>
        </form>

        <?php if ($error && $activeTab === 'face'): ?>
        <div class="alert alert-danger" style="margin-top: 1rem;">
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <div style="margin-top: 1rem; padding: 1rem; background: #fef3c7; border-radius: 8px; font-size: 0.875rem;">
            <strong>Napomena:</strong> Ova funkcija koristi Replicate API. Trebate postaviti <code>REPLICATE_API_TOKEN</code> u config.local.php ili kao environment varijablu.
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($generatedImage): ?>
<div class="card mt-2">
    <div class="card-header" style="background: #dcfce7;">
        <h2 class="card-title" style="color: #166534;">Nova slika</h2>
    </div>
    <div class="card-body" style="text-align: center;">
        <img src="<?= e($generatedImage) ?>" alt="AI generirana slika" style="max-width: 100%; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">

        <div style="margin-top: 1rem; display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?= e($generatedImage) ?>" download class="btn btn-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Preuzmi
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Sve generirane slike -->
<?php if (!empty($allImages)): ?>
<div class="card mt-2">
    <div class="card-header">
        <h2 class="card-title">Generirane slike (<?= count($allImages) ?>)</h2>
    </div>
    <div class="card-body">
        <div class="ai-images-grid">
            <?php foreach ($allImages as $img): ?>
            <div class="ai-image-item">
                <a href="<?= e($img['url']) ?>" target="_blank">
                    <img src="<?= e($img['url']) ?>" alt="AI slika" loading="lazy">
                </a>
                <?php if ($img['prompt']): ?>
                <div class="ai-image-prompt"><?= e($img['prompt']) ?></div>
                <?php endif; ?>
                <div class="ai-image-info">
                    <span class="ai-image-date"><?= e($img['date']) ?></span>
                    <a href="?delete=<?= e($img['filename']) ?>"
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Obrisati ovu sliku?')"
                       title="Obriši">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Primjeri -->
<div class="card mt-2">
    <div class="card-header">
        <h2 class="card-title">Primjeri</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="prompt-examples">
            <div class="prompt-example" onclick="usePrompt(this)">
                <strong>Vijesti</strong>
                <p>Novinarska konferencija s novinarima i mikrofonima, profesionalna fotografija</p>
            </div>
            <div class="prompt-example" onclick="usePrompt(this)">
                <strong>Sport</strong>
                <p>Dinamična sportska fotografija nogometne utakmice, stadion, dramatično osvjetljenje</p>
            </div>
            <div class="prompt-example" onclick="usePrompt(this)">
                <strong>Kultura</strong>
                <p>Elegantna koncertna dvorana s orkestrom, toplo osvjetljenje</p>
            </div>
            <div class="prompt-example" onclick="usePrompt(this)">
                <strong>Zagorje</strong>
                <p>Brežuljci Hrvatskog zagorja s vinogradima i tradicionalnim kućama pri zalasku sunca</p>
            </div>
            <div class="prompt-example" onclick="usePrompt(this)">
                <strong>Biznis</strong>
                <p>Poslovni sastanak u modernom uredu, tim ljudi u suradnji, prirodno svjetlo</p>
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
.face-upload-area {
    border: 2px dashed var(--gray-300);
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.15s;
    background: var(--gray-50);
}
.face-upload-area:hover {
    border-color: var(--primary-color);
    background: white;
}
#faceUploadPrompt {
    color: var(--gray-500);
}
#faceUploadPrompt svg {
    margin-bottom: 0.5rem;
    opacity: 0.5;
}
#faceUploadPrompt p {
    margin: 0.5rem 0 0.25rem;
    font-weight: 500;
}
#faceUploadPrompt small {
    color: var(--gray-400);
}
#facePreviewImg {
    max-width: 200px;
    max-height: 200px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.ai-images-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}
.ai-image-item {
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    overflow: hidden;
    background: var(--gray-50);
}
.ai-image-item img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    display: block;
}
.ai-image-info {
    padding: 0.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.ai-image-prompt {
    padding: 0.5rem;
    font-size: 0.75rem;
    color: var(--gray-600);
    border-top: 1px solid var(--gray-200);
    max-height: 60px;
    overflow-y: auto;
    line-height: 1.3;
}
.ai-image-date {
    font-size: 0.75rem;
    color: var(--gray-500);
}
.prompt-examples {
    display: flex;
    flex-direction: column;
}
.prompt-example {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-200);
    cursor: pointer;
    transition: background 0.15s;
}
.prompt-example:hover {
    background: var(--gray-50);
}
.prompt-example:last-child {
    border-bottom: none;
}
.prompt-example strong {
    display: block;
    margin-bottom: 0.25rem;
    color: var(--primary-color);
}
.prompt-example p {
    margin: 0;
    font-size: 0.85rem;
    color: var(--gray-600);
}
</style>

<script>
// Imagen form submit
document.getElementById('imageForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;margin-right:8px;"></span> Generiram...';
    btn.disabled = true;
});

// Face form submit
document.getElementById('faceForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('faceSubmitBtn');
    btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;margin-right:8px;"></span> Generiram...';
    btn.disabled = true;
});

// Face image preview
document.getElementById('faceImageInput')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('facePreviewImg').src = e.target.result;
            document.getElementById('facePreview').style.display = 'block';
            document.getElementById('faceUploadPrompt').style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
});

function clearFaceImage() {
    document.getElementById('faceImageInput').value = '';
    document.getElementById('facePreview').style.display = 'none';
    document.getElementById('faceUploadPrompt').style.display = 'block';
}

function usePrompt(el) {
    const promptText = el.querySelector('p').textContent;
    document.querySelector('textarea[name="prompt"]').value = promptText;
    window.scrollTo({top: 0, behavior: 'smooth'});
}
</script>

<?php include 'includes/footer.php'; ?>
