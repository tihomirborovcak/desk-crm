<?php
/**
 * Worker API - za lokalni PC koji obrađuje transkripcije
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

// Jednostavna API key autentikacija
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
$validApiKey = 'REDACTED_WORKER_KEY'; // Promijeni ovo!

if ($apiKey !== $validApiKey) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Kreiraj tablicu ako ne postoji
$db->exec("
    CREATE TABLE IF NOT EXISTS transcription_jobs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
        original_filename VARCHAR(255),
        video_path VARCHAR(500),
        audio_path VARCHAR(500),
        srt_content MEDIUMTEXT,
        srt_path VARCHAR(500),
        video_with_subs_path VARCHAR(500),
        language VARCHAR(10) DEFAULT 'hr',
        burn_subtitles TINYINT(1) DEFAULT 0,
        error_message TEXT,
        duration_seconds FLOAT,
        processing_time_seconds FLOAT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        started_at TIMESTAMP NULL,
        completed_at TIMESTAMP NULL,
        created_by INT,
        INDEX idx_status (status),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

switch ($action) {

    // Dohvati pending jobove
    case 'pending':
        $stmt = $db->query("
            SELECT id, original_filename, language, burn_subtitles, duration_seconds
            FROM transcription_jobs
            WHERE status = 'pending'
            ORDER BY created_at ASC
            LIMIT 5
        ");
        echo json_encode(['jobs' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    // Preuzmi job (označi kao processing)
    case 'claim':
        $jobId = intval($_GET['id'] ?? 0);
        if (!$jobId) {
            echo json_encode(['error' => 'Missing job ID']);
            break;
        }

        // Atomski claim - samo ako je još pending
        $stmt = $db->prepare("
            UPDATE transcription_jobs
            SET status = 'processing', started_at = NOW()
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$jobId]);

        if ($stmt->rowCount() === 0) {
            echo json_encode(['error' => 'Job not available']);
            break;
        }

        // Dohvati detalje
        $stmt = $db->prepare("SELECT * FROM transcription_jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['job' => $job]);
        break;

    // Download video file za worker
    case 'download':
        $jobId = intval($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT video_path, original_filename FROM transcription_jobs WHERE id = ? AND status = 'processing'");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job || !$job['video_path']) {
            http_response_code(404);
            echo json_encode(['error' => 'Video not found']);
            break;
        }

        $filePath = __DIR__ . '/../../' . $job['video_path'];
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo json_encode(['error' => 'Video file not found on disk']);
            break;
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($job['original_filename']) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;

    // Upload rezultata
    case 'complete':
        if ($method !== 'POST') {
            echo json_encode(['error' => 'POST required']);
            break;
        }

        $jobId = intval($_GET['id'] ?? 0);

        // Provjeri je li multipart (s video datotekom) ili JSON
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'multipart/form-data') !== false) {
            // Multipart upload
            $srtContent = $_POST['srt_content'] ?? '';
            $processingTime = floatval($_POST['processing_time'] ?? 0);
            $duration = floatval($_POST['duration'] ?? 0);
            $error = !empty($_POST['error']) ? $_POST['error'] : null;
        } else {
            // JSON upload
            $input = json_decode(file_get_contents('php://input'), true);
            $srtContent = $input['srt_content'] ?? '';
            $processingTime = floatval($input['processing_time'] ?? 0);
            $duration = floatval($input['duration'] ?? 0);
            $error = $input['error'] ?? null;
        }

        if (!$jobId || empty($srtContent)) {
            echo json_encode(['error' => 'Missing data']);
            break;
        }

        if ($error) {
            $stmt = $db->prepare("
                UPDATE transcription_jobs
                SET status = 'failed', error_message = ?, completed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$error, $jobId]);
            echo json_encode(['success' => false, 'error' => $error]);
            break;
        }

        // Spremi SRT
        $srtDir = __DIR__ . '/../../uploads/subtitles/' . date('Y/m/');
        if (!is_dir($srtDir)) {
            mkdir($srtDir, 0755, true);
        }

        // Dohvati original filename za ime SRT-a
        $stmt = $db->prepare("SELECT original_filename, video_path, burn_subtitles FROM transcription_jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        $baseName = pathinfo($job['original_filename'], PATHINFO_FILENAME);
        $uniqueId = date('Ymd_His') . '_' . bin2hex(random_bytes(4));
        $srtFilename = $baseName . '_' . $uniqueId . '.srt';
        $srtPath = 'uploads/subtitles/' . date('Y/m/') . '/' . $srtFilename;

        file_put_contents(__DIR__ . '/../../' . $srtPath, $srtContent);

        $videoWithSubsPath = null;

        // Debug log
        $debugLog = __DIR__ . '/../../uploads/worker_debug.log';
        file_put_contents($debugLog, date('Y-m-d H:i:s') . " Job $jobId - duration=$duration, processing_time=$processingTime, POST: " . print_r($_POST, true) . "\n", FILE_APPEND);

        // Provjeri je li worker uploadao video s titlovima
        if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $videoFilename = $baseName . '_titlovi_' . $uniqueId . '.mp4';
            $videoWithSubsPath = 'uploads/subtitles/' . date('Y/m/') . '/' . $videoFilename;
            $videoFullPath = __DIR__ . '/../../' . $videoWithSubsPath;

            $moveResult = move_uploaded_file($_FILES['video']['tmp_name'], $videoFullPath);
            file_put_contents($debugLog, date('Y-m-d H:i:s') . " Move result: " . ($moveResult ? 'OK' : 'FAILED') . " to $videoFullPath\n", FILE_APPEND);
        } else {
            $fileError = isset($_FILES['video']) ? $_FILES['video']['error'] : 'no file';
            file_put_contents($debugLog, date('Y-m-d H:i:s') . " No video file or error: $fileError\n", FILE_APPEND);
        }

        // Očisti temp datoteke iz queue
        if ($job['audio_path']) {
            @unlink(__DIR__ . '/../../' . $job['audio_path']);
        }
        if ($job['video_path']) {
            @unlink(__DIR__ . '/../../' . $job['video_path']);
        }

        // Update job
        $stmt = $db->prepare("
            UPDATE transcription_jobs
            SET status = 'completed',
                srt_content = ?,
                srt_path = ?,
                video_with_subs_path = ?,
                processing_time_seconds = ?,
                duration_seconds = CASE WHEN ? > 0 THEN ? ELSE duration_seconds END,
                completed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$srtContent, $srtPath, $videoWithSubsPath, $processingTime, $duration, $duration, $jobId]);

        echo json_encode([
            'success' => true,
            'srt_path' => $srtPath,
            'video_path' => $videoWithSubsPath,
            'message' => 'Job completed'
        ]);
        break;

    // Status joba
    case 'status':
        $jobId = intval($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT id, status, srt_path, video_with_subs_path, error_message, processing_time_seconds FROM transcription_jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['job' => $job]);
        break;

    default:
        echo json_encode([
            'api' => 'Transcription Worker API',
            'actions' => ['pending', 'claim', 'download', 'complete', 'status']
        ]);
}
