<?php
/**
 * AI Generator slika - Google Imagen 3
 */

define('PAGE_TITLE', 'AI Slike');

require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

requireLogin();

set_time_limit(120);

$generatedImage = null;
$error = null;
$prompt = '';

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

    if (empty($prompt)) {
        $error = 'Unesite opis slike';
    } else {
        if (isset($_POST['translate']) && $_POST['translate'] === '1') {
            $prompt = prevedNaEngleski($prompt);
        }

        $result = generirajSliku($prompt);

        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            $generatedImage = $result['url'];
            logActivity('ai_image_generate', 'ai', null);
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

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Google Imagen 3</h2>
    </div>
    <div class="card-body">
        <form method="POST" id="imageForm">
            <?= csrfField() ?>

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

        <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-top: 1rem;">
            <?= e($error) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

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
document.getElementById('imageForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;margin-right:8px;"></span> Generiram...';
    btn.disabled = true;
});

function usePrompt(el) {
    const promptText = el.querySelector('p').textContent;
    document.querySelector('textarea[name="prompt"]').value = promptText;
    window.scrollTo({top: 0, behavior: 'smooth'});
}
</script>

<?php include 'includes/footer.php'; ?>
