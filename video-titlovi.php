<?php
/**
 * Video Titlovi - generiranje SRT titlova iz videa
 * Koristi ffmpeg za ekstrakciju audia i Whisper za transkripciju
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

// Provjeri je li Whisper instaliran
function isWhisperInstalled() {
    $output = [];
    $returnCode = 0;
    exec('whisper --help 2>&1', $output, $returnCode);
    return $returnCode === 0 || strpos(implode('', $output), 'usage:') !== false;
}

// Provjeri je li ffmpeg instaliran
function isFfmpegInstalled() {
    $output = [];
    $returnCode = 0;
    exec('ffmpeg -version 2>&1', $output, $returnCode);
    return $returnCode === 0;
}

// Izvuci audio iz videa
function extractAudio($videoPath, $audioPath) {
    // Konvertiraj u WAV 16kHz mono (optimalno za Whisper)
    $cmd = sprintf(
        'ffmpeg -i %s -vn -acodec pcm_s16le -ar 16000 -ac 1 %s -y 2>&1',
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

// Generiraj titlove s Whisperom
function generateSubtitles($audioPath, $outputDir, $language = 'hr') {
    $cmd = sprintf(
        'whisper %s --language %s --output_format srt --output_dir %s 2>&1',
        escapeshellarg($audioPath),
        escapeshellarg($language),
        escapeshellarg($outputDir)
    );

    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);

    // Pronađi generirani SRT
    $audioBasename = pathinfo($audioPath, PATHINFO_FILENAME);
    $srtPath = $outputDir . '/' . $audioBasename . '.srt';

    return [
        'success' => file_exists($srtPath),
        'srt_path' => $srtPath,
        'output' => implode("\n", $output)
    ];
}

// Burn titlove u video (opcionalno)
function burnSubtitles($videoPath, $srtPath, $outputPath) {
    $cmd = sprintf(
        'ffmpeg -i %s -vf "subtitles=%s:force_style=\'FontSize=24,PrimaryColour=&HFFFFFF&,OutlineColour=&H000000&,Outline=2\'" -c:a copy %s -y 2>&1',
        escapeshellarg($videoPath),
        escapeshellarg(str_replace('\\', '/', $srtPath)),
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
$whisperOK = isWhisperInstalled();

// Obrada uploada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {

    if (!$ffmpegOK) {
        $error = 'FFmpeg nije instaliran na serveru!';
    } elseif (!$whisperOK) {
        $error = 'Whisper nije instaliran na serveru! Pokreni: pip install openai-whisper';
    } elseif (empty($_FILES['video']['tmp_name'])) {
        $error = 'Odaberite video datoteku';
    } else {
        $videoFile = $_FILES['video'];
        $videoName = $videoFile['name'];
        $language = $_POST['language'] ?? 'hr';
        $burnSubs = isset($_POST['burn_subtitles']);

        // Provjeri format
        $allowedExts = ['mp4', 'mkv', 'avi', 'mov', 'webm', 'flv', 'wmv'];
        $ext = strtolower(pathinfo($videoName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            $error = 'Nedozvoljeni format. Dozvoljeni: ' . implode(', ', $allowedExts);
        } else {
            // Generiraj jedinstveno ime
            $uniqueId = date('Ymd_His_') . bin2hex(random_bytes(4));
            $tempVideoPath = $tempDir . $uniqueId . '.' . $ext;
            $tempAudioPath = $tempDir . $uniqueId . '.wav';

            // Pomakni uploadanu datoteku
            if (!move_uploaded_file($videoFile['tmp_name'], $tempVideoPath)) {
                $error = 'Greška pri uploadu videa';
            } else {
                $processingLog[] = "Video učitan: " . $videoName;

                // 1. Izvuci audio
                $processingLog[] = "Ekstrahiram audio...";
                $audioResult = extractAudio($tempVideoPath, $tempAudioPath);

                if (!$audioResult['success']) {
                    $error = 'Greška pri ekstrakciji audia: ' . $audioResult['output'];
                } else {
                    $processingLog[] = "Audio ekstrahiran uspješno";

                    // 2. Generiraj titlove
                    $processingLog[] = "Generiram titlove s Whisperom (jezik: $language)...";
                    $subtitleResult = generateSubtitles($tempAudioPath, $tempDir, $language);

                    if (!$subtitleResult['success']) {
                        $error = 'Greška pri generiranju titlova: ' . $subtitleResult['output'];
                    } else {
                        $processingLog[] = "Titlovi generirani uspješno!";

                        // Učitaj SRT sadržaj
                        $srtContent = file_get_contents($subtitleResult['srt_path']);

                        // Spremi SRT trajno
                        $finalSrtName = pathinfo($videoName, PATHINFO_FILENAME) . '_' . $uniqueId . '.srt';
                        $finalSrtPath = $subtitlesDir . $finalSrtName;
                        copy($subtitleResult['srt_path'], $finalSrtPath);

                        // 3. Burn titlove ako je odabrano
                        if ($burnSubs && $srtContent) {
                            $processingLog[] = "Ugrađujem titlove u video...";
                            $outputVideoPath = $subtitlesDir . pathinfo($videoName, PATHINFO_FILENAME) . '_subtitled_' . $uniqueId . '.mp4';

                            $burnResult = burnSubtitles($tempVideoPath, $subtitleResult['srt_path'], $outputVideoPath);

                            if ($burnResult['success']) {
                                $processingLog[] = "Video s titlovima spreman!";
                                $success = 'Titlovi generirani i ugrađeni u video!';
                            } else {
                                $processingLog[] = "Upozorenje: Nije uspjelo ugraditi titlove u video";
                            }
                        } else {
                            $success = 'SRT titlovi uspješno generirani!';
                        }

                        logActivity('video_subtitles', 'ai', null);
                    }

                    // Očisti temp audio
                    @unlink($tempAudioPath);
                }

                // Očisti temp video
                @unlink($tempVideoPath);

                // Očisti temp SRT
                if (isset($subtitleResult['srt_path'])) {
                    @unlink($subtitleResult['srt_path']);
                }
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
                <strong>Whisper:</strong>
                <?php if ($whisperOK): ?>
                <span style="color: #16a34a;">Instaliran</span>
                <?php else: ?>
                <span style="color: #dc2626;">Nije instaliran</span>
                <br><small><code>pip install openai-whisper</code></small>
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
                <label class="form-label">Video datoteka *</label>
                <input type="file" name="video" class="form-control" accept=".mp4,.mkv,.avi,.mov,.webm,.flv,.wmv" required>
                <small class="form-text">Dozvoljeni formati: MP4, MKV, AVI, MOV, WEBM, FLV, WMV</small>
            </div>

            <div class="form-group">
                <label class="form-label">Jezik</label>
                <select name="language" class="form-control">
                    <option value="hr">Hrvatski</option>
                    <option value="en">Engleski</option>
                    <option value="de">Njemački</option>
                    <option value="sl">Slovenski</option>
                    <option value="sr">Srpski</option>
                    <option value="auto">Automatska detekcija</option>
                </select>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="burn_subtitles" value="1">
                    <span>Ugradi titlove u video (hardcoded)</span>
                </label>
                <small class="form-text">Ovo će kreirati novi video s trajno ugrađenim titlovima</small>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary" id="submitBtn" <?= (!$ffmpegOK || !$whisperOK) ? 'disabled' : '' ?>>
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
