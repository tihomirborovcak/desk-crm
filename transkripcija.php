<?php
/**
 * Transkripcija audio datoteka - Google Gemini
 */

define('PAGE_TITLE', 'Transkripcija');

require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

requireLogin();

set_time_limit(300);

$transcription = null;
$correctedText = null;
$article = null;
$error = null;
$success = null;
$audioFileName = null;
$audioTempPath = null;

// Direktorij za audio datoteke
$audioUploadDir = UPLOAD_PATH . 'audio/' . date('Y/m/');
if (!is_dir($audioUploadDir)) {
    mkdir($audioUploadDir, 0755, true);
}

// Dohvati spremljene transkripcije
function getSavedTranscriptions($limit = 20) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT t.*, u.full_name as author_name
            FROM transcriptions t
            LEFT JOIN users u ON t.created_by = u.id
            ORDER BY t.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // Tablica ne postoji
        return [];
    }
}

// Spremi transkripciju
function saveTranscription($title, $transcript, $article, $audioFilename, $audioPath = null) {
    $db = getDB();

    $stmt = $db->prepare("
        INSERT INTO transcriptions (title, transcript, article, audio_filename, audio_path, created_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$title, $transcript, $article, $audioFilename, $audioPath, $_SESSION['user_id']]);

    return $db->lastInsertId();
}

// Obrada spremanja
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $title = trim($_POST['title'] ?? '');
    $transcriptToSave = base64_decode($_POST['transcript_b64'] ?? '');
    $articleToSave = base64_decode($_POST['article_b64'] ?? '');
    $audioFile = $_POST['audio_filename'] ?? '';
    $audioPath = $_POST['audio_path'] ?? '';

    if (empty($title)) {
        $error = 'Unesite naslov za spremanje';
        $transcription = $transcriptToSave;
        $article = $articleToSave;
    } elseif (empty($transcriptToSave) && empty($articleToSave)) {
        $error = 'Nema sadržaja za spremanje!';
        $transcription = $transcriptToSave;
        $article = $articleToSave;
    } else {
        $newId = saveTranscription($title, $transcriptToSave, $articleToSave, $audioFile, $audioPath);
        $success = 'Transkripcija spremljena!';
        logActivity('transcription_save', 'ai', null);
    }
}

// Google Cloud - JWT autentifikacija
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

// Gemini 2.5 - transkripcija audio datoteke
function transcribeAudio($filePath, $fileName) {
    $auth = getGoogleAccessToken();
    if (isset($auth['error'])) {
        return $auth;
    }

    // Učitaj audio i kodiraj u base64
    $audioContent = base64_encode(file_get_contents($filePath));

    // Odredi MIME type na temelju ekstenzije (pouzdanije od mime_content_type)
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $mimeTypes = [
        'mp3'  => 'audio/mpeg',
        'mp4'  => 'audio/mp4',
        'm4a'  => 'audio/mp4',
        'wav'  => 'audio/wav',
        'webm' => 'audio/webm',
        'ogg'  => 'audio/ogg',
        'flac' => 'audio/flac',
        'aac'  => 'audio/aac',
        'mpeg' => 'audio/mpeg',
        'mpga' => 'audio/mpeg',
    ];
    $mimeType = $mimeTypes[$ext] ?? mime_content_type($filePath);

    $projectId = $auth['project_id'];
    $region = 'europe-central2';
    $model = 'gemini-2.0-flash-001';

    $url = "https://{$region}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$region}/publishers/google/models/{$model}:generateContent";

    $systemPrompt = "Ti si profesionalni transkripcionist. Tvoj zadatak je transkribirati audio snimku na hrvatski jezik.

Pravila:
- Transkribiraj TOČNO što se govori u audio snimci
- Koristi pravilan hrvatski pravopis i interpunkciju
- Razdvoji tekst u odlomke gdje ima smisla (npr. kad se mijenja tema ili govornik)
- Ako ima više govornika, označi ih s 'Govornik 1:', 'Govornik 2:' itd.
- Ako nešto nije jasno, stavi [nejasno]
- Ne dodaj ništa što se ne čuje u snimci
- Vrati SAMO transkripciju, bez dodatnih komentara";

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
                        'text' => 'Transkribiraj ovu audio snimku na hrvatski jezik.'
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

    // Retry logic s exponential backoff za 429 greške
    $maxRetries = 3;
    $retryDelay = 5; // sekundi

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

        // Ako je 429 (rate limit), pokušaj ponovno nakon pauze
        if ($httpCode === 429 && $attempt < $maxRetries) {
            sleep($retryDelay * $attempt); // exponential backoff: 5s, 10s, 15s
            continue;
        }

        if ($httpCode !== 200) {
            $errMsg = $data['error']['message'] ?? 'HTTP ' . $httpCode;
            if ($httpCode === 429) {
                $errMsg .= ' (pokušano ' . $maxRetries . ' puta)';
            }
            return ['error' => 'Gemini API greška: ' . $errMsg];
        }

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return ['error' => 'Nema odgovora od Gemini-ja. Response: ' . substr($response, 0, 500)];
        }

        return ['text' => $data['candidates'][0]['content']['parts'][0]['text']];
    }

    return ['error' => 'Gemini API greška: Resource exhausted nakon ' . $maxRetries . ' pokušaja'];
}

// Gemini - napravi članak od transkripcije
function makeArticle($text) {
    $auth = getGoogleAccessToken();
    if (isset($auth['error'])) {
        return $auth;
    }

    $projectId = $auth['project_id'];
    $region = 'europe-central2';
    $model = 'gemini-2.0-flash-001';

    $url = "https://{$region}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$region}/publishers/google/models/{$model}:generateContent";

    $systemPrompt = "Ti si iskusni novinar koji piše za dnevne novine na hrvatskom jeziku.

Tvoj zadatak je pretvoriti transkripciju press konferencije ili izjave u OPŠIRAN i DETALJAN novinarski članak.

VAŽNO - Članak mora biti DUGAČAK i DETALJAN:
- Uključi SVE važne informacije iz transkripcije
- Citiraj izjave govornika (u navodnicima)
- Dodaj kontekst i pozadinu gdje je potrebno
- Ne skraćuj previše - bolje je da članak bude predugačak nego prekratak
- Minimalna dužina: 400-600 riječi

Pravila pisanja:
- Napiši članak u stilu vijesti s jasnim naslovom
- Koristi obrnuti piramidalni stil (najvažnije informacije na početku)
- Izvuci SVE ključne izjave i činjenice - ne ispuštaj ništa važno
- Ignoriraj samo ponavljanja, mucanja i potpuno nevažne dijelove
- Ako ima više govornika, jasno navedi tko je što rekao
- Koristi pravilan hrvatski jezik i pravopis

Format:
NASLOV (kratak, informativan)

LEAD (2-3 rečenice - sažetak najvažnijeg)

TIJELO ČLANKA (svi detalji, izjave s citatima, kontekst, pozadina)";

    $postData = json_encode([
        'contents' => [
            [
                'role' => 'user',
                'parts' => [['text' => "Pretvori ovu transkripciju u novinarski članak:\n\n" . $text]]
            ]
        ],
        'systemInstruction' => [
            'parts' => [['text' => $systemPrompt]]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 4000
        ]
    ]);

    // Retry logic s exponential backoff za 429 greške
    $maxRetries = 3;
    $retryDelay = 5;

    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 120,
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

        // Ako je 429 (rate limit), pokušaj ponovno nakon pauze
        if ($httpCode === 429 && $attempt < $maxRetries) {
            sleep($retryDelay * $attempt);
            continue;
        }

        if ($httpCode !== 200) {
            $errMsg = $data['error']['message'] ?? 'HTTP ' . $httpCode;
            if ($httpCode === 429) {
                $errMsg .= ' (pokušano ' . $maxRetries . ' puta)';
            }
            return ['error' => 'Gemini API greška: ' . $errMsg];
        }

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return ['error' => 'Nema odgovora od Gemini-ja'];
        }

        // Očisti višak praznih redova (max 1 prazan red između odlomaka)
        $resultText = $data['candidates'][0]['content']['parts'][0]['text'];
        $resultText = preg_replace("/\n{3,}/", "\n\n", $resultText);
        $resultText = trim($resultText);

        return ['text' => $resultText];
    }

    return ['error' => 'Gemini API greška: Resource exhausted nakon ' . $maxRetries . ' pokušaja'];
}

// Obrada - napravi članak
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'article' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $rawText = $_POST['raw_text'] ?? '';
    $audioFileName = $_POST['audio_filename'] ?? '';
    $audioTempPath = $_POST['audio_path'] ?? '';
    if (!empty($rawText)) {
        $result = makeArticle($rawText);
        if (isset($result['error'])) {
            $error = $result['error'];
            $transcription = $rawText;
        } else {
            $article = $result['text'];
            $transcription = $rawText;
        }
    }
}

// Obrada uploada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    if (empty($_FILES['audio']['tmp_name'][0])) {
        $error = 'Odaberite audio datoteku';
    } else {
        $files = $_FILES['audio'];
        $fileCount = count($files['tmp_name']);
        $allowedExts = ['mp3', 'mp4', 'm4a', 'wav', 'webm', 'mpeg', 'mpga', 'ogg', 'flac', 'aac'];

        $allTranscriptions = [];
        $errors = [];
        $audioFileNames = [];

        for ($i = 0; $i < $fileCount; $i++) {
            if (empty($files['tmp_name'][$i])) continue;

            $fileName = $files['name'][$i];
            $fileSize = $files['size'][$i];
            $tmpName = $files['tmp_name'][$i];

            // Max 20MB za Gemini inline audio
            if ($fileSize > 20 * 1024 * 1024) {
                $errors[] = "$fileName: prevelika (max 20MB)";
                continue;
            }

            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts)) {
                $errors[] = "$fileName: nedozvoljeni format";
                continue;
            }

            $audioFileNames[] = $fileName;
            $result = transcribeAudio($tmpName, $fileName);

            if (isset($result['error'])) {
                $errors[] = "$fileName: " . $result['error'];
            } else {
                $partNum = $fileCount > 1 ? "[Dio " . ($i + 1) . "]\n" : "";
                $allTranscriptions[] = $partNum . $result['text'];

                // Spremi audio datoteku trajno (samo prvu ako ih ima više)
                if (empty($audioTempPath)) {
                    $savedAudioName = date('Y-m-d_His_') . bin2hex(random_bytes(4)) . '.' . $ext;
                    $savedAudioPath = $audioUploadDir . $savedAudioName;
                    if (copy($tmpName, $savedAudioPath)) {
                        $audioTempPath = str_replace(UPLOAD_PATH, '', $savedAudioPath);
                    }
                }
            }
        }

        if (!empty($allTranscriptions)) {
            $transcription = implode("\n\n", $allTranscriptions);
            $audioFileName = implode(', ', $audioFileNames);
            logActivity('audio_transcribe', 'ai', null);
        }

        if (!empty($errors)) {
            $error = implode('; ', $errors);
        } elseif (empty($allTranscriptions)) {
            $error = 'Transkripcija nije uspjela';
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
        <h2 class="card-title">Audio u tekst (Gemini)</h2>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>

            <div class="form-group">
                <label class="form-label">Audio datoteke *</label>
                <input type="file" name="audio[]" class="form-control" accept=".mp3,.mp4,.m4a,.wav,.webm,.mpeg,.mpga,.ogg,.flac,.aac" multiple required>
                <small class="form-text">Dozvoljeni formati: MP3, MP4, M4A, WAV, WEBM, OGG, FLAC, AAC (max 20MB po datoteci)</small>
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

<?php if ($transcription):
    $charCount = mb_strlen($transcription);
    $wordCount = str_word_count($transcription, 0, 'ČčĆćŽžŠšĐđ');
?>
<div class="card mt-2">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card-title">Transkript <?= $article ? '(sirovi)' : '' ?></h2>
        <span class="badge badge-secondary"><?= number_format($wordCount) ?> riječi · <?= number_format($charCount) ?> znakova</span>
    </div>
    <div class="card-body">
        <div class="transcription-text" id="transcriptText"><?= nl2br(e($transcription)) ?></div>

        <div style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <?php if (!$article): ?>
            <form method="POST" style="display: inline;" id="articleForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="article">
                <input type="hidden" name="raw_text" value="<?= e($transcription) ?>">
                <input type="hidden" name="audio_filename" value="<?= e($audioFileName ?? '') ?>">
                <input type="hidden" name="audio_path" value="<?= e($audioTempPath ?? '') ?>">
                <button type="submit" class="btn btn-primary" id="articleBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    Napravi članak
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

<?php if ($article):
    // Normaliziraj razmake - ukloni višestruke prazne redove
    $articleClean = preg_replace('/\n{3,}/', "\n\n", $article);
    $articleClean = trim($articleClean);
    $articleCharCount = mb_strlen($articleClean);
    $articleWordCount = str_word_count($articleClean, 0, 'ČčĆćŽžŠšĐđ');
?>
<div class="card mt-2">
    <div class="card-header" style="background: #dcfce7; display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card-title" style="color: #166534;">Članak za objavu</h2>
        <span class="badge" style="background: #166534; color: white;"><?= number_format($articleWordCount) ?> riječi · <?= number_format($articleCharCount) ?> znakova</span>
    </div>
    <div class="card-body">
        <div class="transcription-text" id="articleText"><?= nl2br(e($articleClean)) ?></div>

        <div style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <button onclick="copyArticle()" class="btn btn-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
                Kopiraj članak
            </button>
            <button onclick="downloadArticle()" class="btn btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Preuzmi članak
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($article): ?>
<div class="card mt-2">
    <div class="card-header">
        <h2 class="card-title">Spremi transkripciju</h2>
    </div>
    <div class="card-body">
        <form method="POST" id="saveForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="transcript_b64" value="<?= base64_encode($transcription ?? '') ?>">
            <input type="hidden" name="article_b64" value="<?= base64_encode($articleClean ?? '') ?>">
            <input type="hidden" name="audio_filename" value="<?= e($audioFileName ?? '') ?>">
            <input type="hidden" name="audio_path" value="<?= e($audioTempPath ?? '') ?>">

            <div class="form-group" style="margin-bottom: 1rem;">
                <label class="form-label">Naslov *</label>
                <input type="text" name="title" class="form-control" placeholder="Npr: Press konferencija gradonačelnika 16.01.2026." required>
            </div>

            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>
                Spremi
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php
// Popis spremljenih transkripcija
$savedTranscriptions = getSavedTranscriptions(10);
if (!empty($savedTranscriptions)):
?>
<div class="card mt-2">
    <div class="card-header">
        <h2 class="card-title">Spremljene transkripcije</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <table class="table">
            <thead>
                <tr>
                    <th>Naslov</th>
                    <th>Datum</th>
                    <th>Autor</th>
                    <th style="width: 100px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($savedTranscriptions as $saved): ?>
                <tr>
                    <td>
                        <strong><?= e($saved['title']) ?></strong>
                        <?php if ($saved['audio_filename']): ?>
                        <br><small class="text-muted"><?= e($saved['audio_filename']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= formatDateTime($saved['created_at']) ?></td>
                    <td><?= e($saved['author_name']) ?></td>
                    <td>
                        <a href="transkripcija-view.php?id=<?= $saved['id'] ?>" class="btn btn-sm btn-outline">Otvori</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($success): ?>
<script>alert('<?= e($success) ?>');</script>
<?php endif; ?>

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
document.querySelector('form[enctype]')?.addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;margin-right:8px;"></span> Transkribiram...';
    btn.disabled = true;
});

document.getElementById('articleForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('articleBtn');
    btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;margin-right:8px;"></span> Pišem članak...';
    btn.disabled = true;
});

function copyTranscript() {
    const text = <?= json_encode($transcription ?? '') ?>;
    navigator.clipboard.writeText(text).then(() => {
        alert('Tekst kopiran!');
    });
}

function copyArticle() {
    const text = <?= json_encode($articleClean ?? '') ?>;
    navigator.clipboard.writeText(text).then(() => {
        alert('Članak kopiran!');
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

function downloadArticle() {
    const text = <?= json_encode($articleClean ?? '') ?>;
    const blob = new Blob([text], {type: 'text/plain;charset=utf-8'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'clanak-<?= date('Y-m-d-His') ?>.txt';
    a.click();
    URL.revokeObjectURL(url);
}

</script>

<?php include 'includes/footer.php'; ?>
