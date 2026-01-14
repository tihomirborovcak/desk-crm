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

$transcription = null;
$error = null;
$duration = null;

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
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        return ['error' => $data['error']['message'] ?? 'Greška pri transkripciji'];
    }

    return [
        'text' => $data['text'] ?? '',
        'duration' => $data['duration'] ?? null
    ];
}

// Obrada uploada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    if (empty($_FILES['audio']['tmp_name'])) {
        $error = 'Odaberite audio datoteku';
    } else {
        $file = $_FILES['audio'];

        // Provjeri veličinu (max 25MB za Whisper)
        if ($file['size'] > 25 * 1024 * 1024) {
            $error = 'Datoteka je prevelika (max 25MB)';
        } else {
            // Dozvoljeni formati
            $allowedTypes = ['audio/mpeg', 'audio/mp3', 'audio/mp4', 'audio/wav', 'audio/webm', 'audio/m4a', 'video/mp4', 'video/webm'];
            $allowedExts = ['mp3', 'mp4', 'm4a', 'wav', 'webm', 'mpeg', 'mpga'];

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
                <input type="file" name="audio" class="form-control" accept=".mp3,.mp4,.m4a,.wav,.webm,.mpeg,.mpga" required>
                <small class="form-text">Dozvoljeni formati: MP3, MP4, M4A, WAV, WEBM (max 25MB)</small>
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
        <h2 class="card-title">Transkript</h2>
        <?php if ($duration): ?>
        <span class="badge badge-secondary"><?= gmdate('H:i:s', (int)$duration) ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="transcription-text" id="transcriptText"><?= nl2br(e($transcription)) ?></div>

        <div style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <button onclick="copyTranscript()" class="btn btn-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
                Kopiraj tekst
            </button>
            <button onclick="downloadTranscript()" class="btn btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Preuzmi .txt
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card mt-2">
    <div class="card-header">
        <h2 class="card-title">Savjeti</h2>
    </div>
    <div class="card-body">
        <ul style="margin: 0; padding-left: 1.25rem; color: var(--gray-600);">
            <li>Kvalitetniji audio = točnija transkripcija</li>
            <li>Izbjegavajte pozadinsku buku</li>
            <li>Dulje datoteke traju duže za obradu</li>
            <li>Whisper automatski prepoznaje hrvatski jezik</li>
            <li>Radi i s video datotekama (MP4, WEBM)</li>
        </ul>
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
// Loading state
document.querySelector('form').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;margin-right:8px;"></span> Transkribiram...';
    btn.disabled = true;
});

function copyTranscript() {
    const text = <?= json_encode($transcription ?? '') ?>;
    navigator.clipboard.writeText(text).then(() => {
        alert('Tekst kopiran!');
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
</script>

<?php include 'includes/footer.php'; ?>
