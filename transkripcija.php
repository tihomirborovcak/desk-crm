<?php
/**
 * Transkripcija audio datoteka - Whisper API
 */

define('PAGE_TITLE', 'Transkripcija');

require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Lokalna konfiguracija s API ključevima
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

requireLogin();

// Produlji vrijeme izvršavanja za duže transkripcije
set_time_limit(300);

$transcription = null;
$correctedText = null;
$error = null;
$duration = null;

// GPT-4 - ispravljanje teksta
function correctTranscription($text) {
    if (!defined('OPENAI_API_KEY')) {
        return ['error' => 'API ključ nije konfiguriran'];
    }

    $ch = curl_init('https://api.openai.com/v1/chat/completions');

    $prompt = "Ispravi sljedeću transkripciju na hrvatskom jeziku. Popravi:
- Pravopisne greške
- Interpunkciju (točke, zareze, upitnike)
- Velika slova na početku rečenica i za vlastita imena
- Očite pogreške u riječima prema kontekstu
- Razdvoji u odlomke gdje ima smisla

Zadrži originalni smisao, samo ispravi greške. Vrati SAMO ispravljeni tekst bez objašnjenja.

Transkripcija:
$text";

    $postFields = json_encode([
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.3
    ]);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ],
        CURLOPT_POSTFIELDS => $postFields
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
        return ['error' => 'API greška: ' . $errMsg];
    }

    if (!isset($data['choices'][0]['message']['content'])) {
        return ['error' => 'Nema odgovora od API-ja'];
    }

    return ['text' => $data['choices'][0]['message']['content']];
}

// Whisper API - transkripcija
function transcribeAudio($filePath, $fileName) {
    if (!defined('OPENAI_API_KEY')) {
        return ['error' => 'API ključ nije konfiguriran'];
    }

    $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');

    $postFields = [
        'file' => new CURLFile($filePath, mime_content_type($filePath), $fileName),
        'model' => 'whisper-1',
        'language' => 'hr',
        'response_format' => 'verbose_json'
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 300, // 5 minuta za duže datoteke
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . OPENAI_API_KEY
        ],
        CURLOPT_POSTFIELDS => $postFields
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
        return ['error' => 'API greška: ' . $errMsg];
    }

    return [
        'text' => $data['text'] ?? '',
        'duration' => $data['duration'] ?? null
    ];
}

// Obrada ispravljanja teksta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'correct' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $rawText = $_POST['raw_text'] ?? '';
    if (!empty($rawText)) {
        $result = correctTranscription($rawText);
        if (isset($result['error'])) {
            $error = $result['error'];
            $transcription = $rawText;
        } else {
            $correctedText = $result['text'];
            $transcription = $rawText;
        }
    }
}

// Obrada uploada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    if (empty($_FILES['audio']['tmp_name'])) {
        $error = 'Odaberite audio datoteku';
    } else {
        $file = $_FILES['audio'];

        // Provjeri veličinu (max 24MB za Whisper API)
        if ($file['size'] > 24 * 1024 * 1024) {
            $error = 'Datoteka je prevelika (max 24MB). Koristite online alate ispod za kompresiju.';
        } else {
            // Dozvoljeni formati
            $allowedExts = ['mp3', 'mp4', 'm4a', 'wav', 'webm', 'mpeg', 'mpga', 'ogg', 'flac'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExts)) {
                $error = 'Nedozvoljeni format. Dozvoljeni: ' . implode(', ', $allowedExts);
            } else {
                $result = transcribeAudio($file['tmp_name'], $file['name']);

                if (isset($result['error'])) {
                    $error = $result['error'];
                } else {
                    $transcription = $result['text'];
                    $duration = $result['duration'];
                    logActivity('audio_transcribe', 'ai', null);
                }
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Transkripcija</h1>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Audio u tekst (Whisper AI)</h2>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>

            <div class="form-group">
                <label class="form-label">Audio datoteka *</label>
                <input type="file" name="audio" class="form-control" accept=".mp3,.mp4,.m4a,.wav,.webm,.mpeg,.mpga,.ogg,.flac" required>
                <small class="form-text">Dozvoljeni formati: MP3, MP4, M4A, WAV, WEBM, OGG, FLAC (max 24MB)</small>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                        <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                        <line x1="12" y1="19" x2="12" y2="23"/>
                        <line x1="8" y1="23" x2="16" y2="23"/>
                    </svg>
                    Transkribiraj
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

<?php if ($transcription): ?>
<div class="card mt-2">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card-title">Transkript <?= $correctedText ? '(original)' : '' ?></h2>
        <?php if ($duration): ?>
        <span class="badge badge-secondary"><?= gmdate('H:i:s', (int)$duration) ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="transcription-text" id="transcriptText"><?= nl2br(e($transcription)) ?></div>

        <div style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <?php if (!$correctedText): ?>
            <form method="POST" style="display: inline;" id="correctForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="correct">
                <input type="hidden" name="raw_text" value="<?= e($transcription) ?>">
                <button type="submit" class="btn btn-primary" id="correctBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 20h9"/>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                    </svg>
                    Ispravi greške (AI)
                </button>
            </form>
            <?php endif; ?>
            <button onclick="copyTranscript()" class="btn btn-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
                Kopiraj
            </button>
            <button onclick="downloadTranscript()" class="btn btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Preuzmi
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($correctedText): ?>
<div class="card mt-2">
    <div class="card-header" style="background: #dcfce7;">
        <h2 class="card-title" style="color: #166534;">Ispravljeni tekst</h2>
    </div>
    <div class="card-body">
        <div class="transcription-text" id="correctedText"><?= nl2br(e($correctedText)) ?></div>

        <div style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <button onclick="copyCorrected()" class="btn btn-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
                Kopiraj ispravljeno
            </button>
            <button onclick="downloadCorrected()" class="btn btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Preuzmi ispravljeno
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card mt-2">
    <div class="card-header">
        <h2 class="card-title">Ako je datoteka prevelika</h2>
    </div>
    <div class="card-body">
        <p style="margin-bottom: 0.75rem; color: var(--gray-600);">Datoteka mora biti manja od 24MB. Za kompresiju koristi:</p>
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
            <a href="https://www.freeconvert.com/audio-compressor" target="_blank" class="btn btn-outline">
                FreeConvert
            </a>
            <a href="https://online-audio-converter.com/" target="_blank" class="btn btn-outline">
                Online Audio Converter
            </a>
            <a href="https://mp3smaller.com/" target="_blank" class="btn btn-outline">
                MP3 Smaller
            </a>
        </div>
        <p style="margin-top: 0.75rem; font-size: 0.85rem; color: var(--gray-500);">Preporučeno: MP3 format, 64-96 kbps, mono</p>
    </div>
</div>

<style>
.transcription-text {
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    padding: 1rem;
    line-height: 1.8;
    max-height: 400px;
    overflow-y: auto;
    white-space: pre-wrap;
}
</style>

<script>
// Loading state za upload formu
document.querySelector('form[enctype]')?.addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;margin-right:8px;"></span> Transkribiram...';
    btn.disabled = true;
});

// Loading state za ispravljanje
document.getElementById('correctForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('correctBtn');
    btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;margin-right:8px;"></span> Ispravljam...';
    btn.disabled = true;
});

function copyTranscript() {
    const text = <?= json_encode($transcription ?? '') ?>;
    navigator.clipboard.writeText(text).then(() => {
        alert('Tekst kopiran!');
    });
}

function copyCorrected() {
    const text = <?= json_encode($correctedText ?? '') ?>;
    navigator.clipboard.writeText(text).then(() => {
        alert('Ispravljeni tekst kopiran!');
    });
}

function downloadTranscript() {
    const text = <?= json_encode($transcription ?? '') ?>;
    const blob = new Blob([text], {type: 'text/plain;charset=utf-8'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'transkript-<?= date('Y-m-d-His') ?>.txt';
    a.click();
    URL.revokeObjectURL(url);
}

function downloadCorrected() {
    const text = <?= json_encode($correctedText ?? '') ?>;
    const blob = new Blob([text], {type: 'text/plain;charset=utf-8'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'transkript-ispravljen-<?= date('Y-m-d-His') ?>.txt';
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<?php include 'includes/footer.php'; ?>
