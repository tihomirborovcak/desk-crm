<?php
/**
 * Video Titlovi - generiranje SRT titlova iz videa
 * Koristi ffmpeg za ekstrakciju audia i Gemini za transkripciju
 */

define('PAGE_TITLE', 'Video Titlovi');

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

set_time_limit(600); // 10 minuta za dulje videe

$error = null;
$success = null;
$srtContent = null;
$videoName = null;
$processingLog = [];

// Direktorij za privremene datoteke
$tempDir = UPLOAD_PATH . 'temp/';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

// Direktorij za titlove
$subtitlesDir = UPLOAD_PATH . 'subtitles/' . date('Y/m/');
if (!is_dir($subtitlesDir)) {
    mkdir($subtitlesDir, 0755, true);
}

// Provjeri je li ffmpeg instaliran
function isFfmpegInstalled() {
    $output = [];
    $returnCode = 0;
    exec('ffmpeg -version 2>&1', $output, $returnCode);
    return $returnCode === 0;
}

// Dohvati trajanje audio/video datoteke u sekundama
function getMediaDuration($filePath) {
    $cmd = sprintf(
        'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
        escapeshellarg($filePath)
    );
    $output = trim(shell_exec($cmd));
    return is_numeric($output) ? floatval($output) : 0;
}

// Izvuci audio iz videa
function extractAudio($videoPath, $audioPath) {
    // Konvertiraj u MP3 (manji file za Gemini)
    $cmd = sprintf(
        'ffmpeg -i %s -vn -acodec libmp3lame -ar 16000 -ac 1 -b:a 64k %s -y 2>&1',
        escapeshellarg($videoPath),
        escapeshellarg($audioPath)
    );

    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);

    return [
        'success' => $returnCode === 0 && file_exists($audioPath),
        'output' => implode("\n", $output)
    ];
}

// Google Cloud - JWT autentifikacija (kopija iz transkripcija.php)
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

// Gemini - generiraj SRT titlove
function generateSubtitlesWithGemini($audioPath, $language = 'hr', $duration = 0) {
    $auth = getGoogleAccessToken();
    if (isset($auth['error'])) {
        return $auth;
    }

    // Učitaj audio i kodiraj u base64
    $audioContent = base64_encode(file_get_contents($audioPath));

    // Odredi MIME type
    $ext = strtolower(pathinfo($audioPath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'mp3'  => 'audio/mpeg',
        'wav'  => 'audio/wav',
        'm4a'  => 'audio/mp4',
    ];
    $mimeType = $mimeTypes[$ext] ?? 'audio/mpeg';

    $projectId = $auth['project_id'];
    $region = 'europe-central2';
    $model = 'gemini-2.0-flash-001';

    $url = "https://{$region}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$region}/publishers/google/models/{$model}:generateContent";

    $langName = [
        'hr' => 'hrvatski',
        'en' => 'engleski',
        'de' => 'njemački',
        'sl' => 'slovenski',
        'sr' => 'srpski'
    ][$language] ?? 'hrvatski';

    $systemPrompt = "Ti si profesionalni prevoditelj koji stvara titlove za video.
Tvoj zadatak je transkribirati audio i generirati SRT titlove s vremenskim oznakama.

PRAVILA ZA SRT FORMAT:
1. Svaki titl ima redni broj, vremenske oznake i tekst
2. Format vremena: HH:MM:SS,mmm --> HH:MM:SS,mmm
3. Maksimalno 2 reda teksta po titlu
4. Maksimalno 42 znaka po redu
5. Titl traje 1-6 sekundi
6. Pauza između titlova: minimalno 0.1 sekunde

PRIMJER SRT FORMATA:
1
00:00:01,000 --> 00:00:04,500
Ovo je prvi titl koji
se proteže u dva reda.

2
00:00:05,000 --> 00:00:08,200
A ovo je drugi titl.

VAŽNO:
- Transkribiraj na {$langName} jeziku
- Generiraj SAMO SRT sadržaj, bez dodatnih objašnjenja
- Procijeni vremenske oznake na temelju govora
- Audio traje otprilike " . round($duration) . " sekundi";

    $postData = json_encode([
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    [
                        'inlineData' => [
                            'mimeType' => $mimeType,
                            'data' => $audioContent
                        ]
                    ],
                    [
                        'text' => 'Generiraj SRT titlove za ovaj audio. Vrati SAMO SRT format, ništa drugo.'
                    ]
                ]
            ]
        ],
        'systemInstruction' => [
            'parts' => [['text' => $systemPrompt]]
        ],
        'generationConfig' => [
            'temperature' => 0.1,
            'maxOutputTokens' => 8000
        ]
    ]);

    // Retry logic
    $maxRetries = 3;
    $retryDelay = 5;

    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $auth['token']
            ],
            CURLOPT_POSTFIELDS => $postData
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['error' => 'Greška: ' . $curlError];
        }

        $data = json_decode($response, true);

        if ($httpCode === 429 && $attempt < $maxRetries) {
            sleep($retryDelay * $attempt);
            continue;
        }

        if ($httpCode !== 200) {
            $errMsg = $data['error']['message'] ?? 'HTTP ' . $httpCode;
            return ['error' => 'Gemini API greška: ' . $errMsg];
        }

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return ['error' => 'Nema odgovora od Gemini-ja'];
        }

        $srtContent = $data['candidates'][0]['content']['parts'][0]['text'];

        // Očisti markdown ako Gemini doda ```
        $srtContent = preg_replace('/^```(srt)?\s*/m', '', $srtContent);
        $srtContent = preg_replace('/```\s*$/m', '', $srtContent);
        $srtContent = trim($srtContent);

        return ['srt' => $srtContent];
    }

    return ['error' => 'Gemini API greška nakon ' . $maxRetries . ' pokušaja'];
}

// Burn titlove u video (s kompresijom)
function burnSubtitles($videoPath, $srtPath, $outputPath) {
    // Escape za ffmpeg subtitle filter
    $srtPathEscaped = str_replace(['\\', ':', "'"], ['\\\\', '\\:', "\\'"], $srtPath);

    // Kompresija: H.264, CRF 28 (manji file), preset medium
    $cmd = sprintf(
        'ffmpeg -i %s -vf "subtitles=\'%s\':force_style=\'FontSize=16,PrimaryColour=&HFFFFFF&,OutlineColour=&H000000&,Outline=1,MarginV=20\'" -c:v libx264 -crf 28 -preset medium -c:a aac -b:a 128k -movflags +faststart -y %s 2>&1',
        escapeshellarg($videoPath),
        $srtPathEscaped,
        escapeshellarg($outputPath)
    );

    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);

    return [
        'success' => $returnCode === 0 && file_exists($outputPath),
        'output' => implode("\n", $output)
    ];
}

// Status provjera
$ffmpegOK = isFfmpegInstalled();
$geminiOK = file_exists(__DIR__ . '/google-credentials.json');

// Obrada uploada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {

    if (!$ffmpegOK) {
        $error = 'FFmpeg nije instaliran na serveru!';
    } elseif (!$geminiOK) {
        $error = 'Google credentials nisu postavljeni!';
    } elseif (empty($_FILES['video']['tmp_name'])) {
        $error = 'Odaberite video datoteku';
    } else {
        $videoFile = $_FILES['video'];
        $videoName = $videoFile['name'];
        $language = $_POST['language'] ?? 'hr';
        $burnSubs = isset($_POST['burn_subtitles']);

        // Provjeri format
        $allowedExts = ['mp4', 'mkv', 'avi', 'mov', 'webm', 'flv', 'wmv', 'mp3', 'wav', 'm4a'];
        $ext = strtolower(pathinfo($videoName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            $error = 'Nedozvoljeni format. Dozvoljeni: ' . implode(', ', $allowedExts);
        } else {
            // Generiraj jedinstveno ime
            $uniqueId = date('Ymd_His_') . bin2hex(random_bytes(4));
            $tempVideoPath = $tempDir . $uniqueId . '.' . $ext;
            $tempAudioPath = $tempDir . $uniqueId . '.mp3';

            // Pomakni uploadanu datoteku
            if (!move_uploaded_file($videoFile['tmp_name'], $tempVideoPath)) {
                $error = 'Greška pri uploadu videa';
            } else {
                $processingLog[] = "Datoteka učitana: " . $videoName;

                // Dohvati trajanje
                $duration = getMediaDuration($tempVideoPath);
                $processingLog[] = "Trajanje: " . gmdate("H:i:s", (int)$duration);

                // Ako je audio format, koristi direktno
                $audioExts = ['mp3', 'wav', 'm4a'];
                if (in_array($ext, $audioExts)) {
                    $tempAudioPath = $tempVideoPath;
                    $processingLog[] = "Audio format - preskačem ekstrakciju";
                } else {
                    // 1. Izvuci audio
                    $processingLog[] = "Ekstrahiram audio...";
                    $audioResult = extractAudio($tempVideoPath, $tempAudioPath);

                    if (!$audioResult['success']) {
                        $error = 'Greška pri ekstrakciji audia: ' . $audioResult['output'];
                    } else {
                        $processingLog[] = "Audio ekstrahiran uspješno";
                    }
                }

                if (!$error) {
                    // Provjeri veličinu audia (Gemini limit 20MB)
                    $audioSize = filesize($tempAudioPath);
                    if ($audioSize > 20 * 1024 * 1024) {
                        $error = 'Audio prevelik za Gemini (max 20MB). Probaj kraći video.';
                    } else {
                        $processingLog[] = "Audio veličina: " . round($audioSize / 1024 / 1024, 2) . " MB";

                        // 2. Generiraj titlove s Geminijem
                        $processingLog[] = "Generiram titlove s Geminijem (jezik: $language)...";
                        $subtitleResult = generateSubtitlesWithGemini($tempAudioPath, $language, $duration);

                        if (isset($subtitleResult['error'])) {
                            $error = 'Greška pri generiranju titlova: ' . $subtitleResult['error'];
                        } else {
                            $processingLog[] = "Titlovi generirani uspješno!";

                            $srtContent = $subtitleResult['srt'];

                            // Spremi SRT trajno
                            $finalSrtName = pathinfo($videoName, PATHINFO_FILENAME) . '_' . $uniqueId . '.srt';
                            $finalSrtPath = $subtitlesDir . $finalSrtName;
                            file_put_contents($finalSrtPath, $srtContent);

                            $success = 'SRT titlovi uspješno generirani!';

                            // Burn titlove u video ako je odabrano
                            $burnedVideoUrl = null;
                            if ($burnSubs && !in_array($ext, ['mp3', 'wav', 'm4a'])) {
                                $processingLog[] = "Ugrađujem titlove u video...";
                                $burnedVideoName = pathinfo($videoName, PATHINFO_FILENAME) . '_titlovi_' . $uniqueId . '.mp4';
                                $burnedVideoPath = $subtitlesDir . $burnedVideoName;

                                $burnResult = burnSubtitles($tempVideoPath, $finalSrtPath, $burnedVideoPath);

                                if ($burnResult['success']) {
                                    $processingLog[] = "Video s titlovima spreman!";
                                    $burnedVideoUrl = str_replace(UPLOAD_PATH, 'uploads/', $burnedVideoPath);
                                    $success = 'Titlovi generirani i ugrađeni u video!';
                                } else {
                                    $processingLog[] = "Greška pri ugradnji: " . substr($burnResult['output'], 0, 200);
                                }
                            }

                            logActivity('video_subtitles', 'ai', null);
                        }
                    }
                }

                // Očisti temp datoteke
                if ($tempAudioPath !== $tempVideoPath) {
                    @unlink($tempAudioPath);
                }
                @unlink($tempVideoPath);
            }
        }
    }
}

// Dohvati spremljene titlove
function getSavedSubtitles($limit = 20) {
    global $subtitlesDir;
    $files = glob(UPLOAD_PATH . 'subtitles/*/*.srt');
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    return array_slice($files, 0, $limit);
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Video Titlovi</h1>
</div>

<!-- Status -->
<div class="card mb-2">
    <div class="card-body" style="padding: 1rem;">
        <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
            <div>
                <strong>FFmpeg:</strong>
                <?php if ($ffmpegOK): ?>
                <span style="color: #16a34a;">Instaliran</span>
                <?php else: ?>
                <span style="color: #dc2626;">Nije instaliran</span>
                <?php endif; ?>
            </div>
            <div>
                <strong>Gemini:</strong>
                <?php if ($geminiOK): ?>
                <span style="color: #16a34a;">Konfiguriran</span>
                <?php else: ?>
                <span style="color: #dc2626;">Nedostaje google-credentials.json</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Generiraj titlove iz videa</h2>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <?= csrfField() ?>

            <div class="form-group">
                <label class="form-label">Video ili audio datoteka *</label>
                <input type="file" name="video" class="form-control" accept=".mp4,.mkv,.avi,.mov,.webm,.flv,.wmv,.mp3,.wav,.m4a" required>
                <small class="form-text">Video: MP4, MKV, AVI, MOV, WEBM | Audio: MP3, WAV, M4A (max 20MB za audio)</small>
            </div>

            <div class="form-group">
                <label class="form-label">Jezik</label>
                <select name="language" class="form-control">
                    <option value="hr">Hrvatski</option>
                    <option value="en">Engleski</option>
                    <option value="de">Njemački</option>
                    <option value="sl">Slovenski</option>
                    <option value="sr">Srpski</option>
                </select>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="burn_subtitles" value="1">
                    <span>Ugradi titlove u video (hardcoded)</span>
                </label>
                <small class="form-text">Kreira novi MP4 video s trajno ugrađenim titlovima</small>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary" id="submitBtn" <?= (!$ffmpegOK || !$geminiOK) ? 'disabled' : '' ?>>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="23 7 16 12 23 17 23 7"/>
                        <rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
                    </svg>
                    Generiraj titlove
                </button>
            </div>
        </form>

        <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-top: 1rem;">
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($processingLog)): ?>
        <div style="margin-top: 1rem; padding: 1rem; background: #f1f5f9; border-radius: 8px; font-family: monospace; font-size: 0.85rem;">
            <?php foreach ($processingLog as $log): ?>
            <div><?= e($log) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($srtContent): ?>
<div class="card mt-2">
    <div class="card-header" style="background: #dcfce7; display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card-title" style="color: #166534;">Generirani titlovi (SRT)</h2>
        <span class="badge" style="background: #166534; color: white;"><?= e($videoName) ?></span>
    </div>
    <div class="card-body">
        <?php if (!empty($burnedVideoUrl)): ?>
        <div style="margin-bottom: 1rem; padding: 1rem; background: #dbeafe; border-radius: 8px; border: 1px solid #93c5fd;">
            <strong style="color: #1d4ed8;">Video s ugrađenim titlovima spreman!</strong>
            <div style="margin-top: 0.5rem;">
                <a href="<?= e($burnedVideoUrl) ?>" download class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="23 7 16 12 23 17 23 7"/>
                        <rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
                    </svg>
                    Preuzmi video s titlovima
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div class="srt-preview" id="srtContent"><?= e($srtContent) ?></div>

        <div style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <button onclick="copySrt()" class="btn btn-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
                Kopiraj SRT
            </button>
            <button onclick="downloadSrt()" class="btn btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Preuzmi SRT
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$savedSubtitles = getSavedSubtitles(10);
if (!empty($savedSubtitles)):
?>
<div class="card mt-2">
    <div class="card-header">
        <h2 class="card-title">Spremljeni titlovi</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <table class="table">
            <thead>
                <tr>
                    <th>Datoteka</th>
                    <th>Datum</th>
                    <th>Veličina</th>
                    <th style="width: 100px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($savedSubtitles as $srtFile): ?>
                <tr>
                    <td><?= e(basename($srtFile)) ?></td>
                    <td><?= date('d.m.Y H:i', filemtime($srtFile)) ?></td>
                    <td><?= round(filesize($srtFile) / 1024, 1) ?> KB</td>
                    <td>
                        <a href="<?= str_replace(UPLOAD_PATH, 'uploads/', $srtFile) ?>" download class="btn btn-sm btn-outline">Preuzmi</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<style>
.srt-preview {
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    padding: 1rem;
    font-family: monospace;
    font-size: 0.85rem;
    line-height: 1.6;
    max-height: 400px;
    overflow-y: auto;
    white-space: pre-wrap;
}
</style>

<script>
document.getElementById('uploadForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;margin-right:8px;"></span> Obrađujem video...';
    btn.disabled = true;
});

function copySrt() {
    const text = <?= json_encode($srtContent ?? '') ?>;
    navigator.clipboard.writeText(text).then(() => {
        alert('SRT kopiran!');
    });
}

function downloadSrt() {
    const text = <?= json_encode($srtContent ?? '') ?>;
    const filename = <?= json_encode(($videoName ? pathinfo($videoName, PATHINFO_FILENAME) : 'titlovi') . '.srt') ?>;
    const blob = new Blob([text], {type: 'text/plain;charset=utf-8'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<?php include 'includes/footer.php'; ?>
