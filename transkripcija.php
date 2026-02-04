<?php
/**
 * Transkripcija audio datoteka - Google Gemini
 */

define('PAGE_TITLE', 'Transkripcija');

// Spriječi keširanje stranice
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

requireLogin();

set_time_limit(300);

$activeTab = $_GET['tab'] ?? $_POST['tab'] ?? 'single';
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
        // Admin vidi sve, ostali samo one koje nisu admin_only
        if (isAdmin()) {
            $stmt = $db->prepare("
                SELECT t.*, u.full_name as author_name
                FROM transcriptions t
                LEFT JOIN users u ON t.created_by = u.id
                ORDER BY t.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
        } else {
            $stmt = $db->prepare("
                SELECT t.*, u.full_name as author_name
                FROM transcriptions t
                LEFT JOIN users u ON t.created_by = u.id
                WHERE t.admin_only = 0
                ORDER BY t.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
        }
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // Tablica ne postoji
        return [];
    }
}

// Spremi transkripciju
function saveTranscription($title, $transcript, $article, $audioFilename, $audioPath = null, $adminOnly = 0) {
    $db = getDB();

    $stmt = $db->prepare("
        INSERT INTO transcriptions (title, transcript, article, audio_filename, audio_path, admin_only, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$title, $transcript, $article, $audioFilename, $audioPath, $adminOnly, $_SESSION['user_id']]);

    return $db->lastInsertId();
}

// Ažuriraj transkripciju - postavi vidljivu za sve
function updateTranscriptionVisibility($id, $title) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE transcriptions SET title = ?, admin_only = 0 WHERE id = ?");
    $stmt->execute([$title, $id]);
}

// Obrada spremanja
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $title = trim($_POST['title'] ?? '');
    $transcriptToSave = base64_decode($_POST['transcript_b64'] ?? '');
    $articleToSave = base64_decode($_POST['article_b64'] ?? '');
    $audioFile = $_POST['audio_filename'] ?? '';
    $audioPath = $_POST['audio_path'] ?? '';
    $existingId = (int)($_POST['auto_saved_id'] ?? 0);

    if (empty($title)) {
        $error = 'Unesite naslov za spremanje';
        $transcription = $transcriptToSave;
        $article = $articleToSave;
    } elseif (empty($transcriptToSave) && empty($articleToSave)) {
        $error = 'Nema sadržaja za spremanje!';
        $transcription = $transcriptToSave;
        $article = $articleToSave;
    } else {
        if ($existingId > 0) {
            // Ažuriraj postojeći auto-spremljeni zapis - učini ga vidljivim svima
            updateTranscriptionVisibility($existingId, $title);
        } else {
            // Nema auto-spremljenog, spremi kao novo (vidljivo svima)
            saveTranscription($title, $transcriptToSave, $articleToSave, $audioFile, $audioPath, 0);
        }
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

        // Agresivno čišćenje praznih redova
        $resultText = $data['candidates'][0]['content']['parts'][0]['text'];
        // Ukloni Unicode line separatore
        $resultText = str_replace(["\u{2028}", "\u{2029}", "\u{0085}"], "\n", $resultText);
        // Normaliziraj line breakove
        $resultText = str_replace(["\r\n", "\r"], "\n", $resultText);
        // Ukloni redove koji sadrže samo razmake/tabove
        $resultText = preg_replace('/^[ \t]+$/m', '', $resultText);
        // Ukloni razmake na kraju redova
        $resultText = preg_replace('/[ \t]+$/m', '', $resultText);
        // Max 1 prazan red između odlomaka
        $resultText = preg_replace('/\n{2,}/', "\n\n", $resultText);
        $resultText = trim($resultText);

        return ['text' => $resultText];
    }

    return ['error' => 'Gemini API greška: Resource exhausted nakon ' . $maxRetries . ' pokušaja'];
}

// Gemini - napravi članak od transkripcije
function makeArticle($text, $customInstructions = '') {
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

TIJELO ČLANKA (svi detalji, izjave s citatima, kontekst, pozadina)

VAŽNO ZA FORMATIRANJE:
- Koristi SAMO JEDAN prazan red između odlomaka
- NIKADA ne stavljaj više od jednog praznog reda između rečenica ili odlomaka
- Tekst treba biti kompaktan i čitljiv";

    // Dodaj korisničke upute ako postoje
    if (!empty($customInstructions)) {
        $systemPrompt .= "\n\nDODATNE UPUTE OD KORISNIKA:\n" . $customInstructions;
    }

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

        // Agresivno čišćenje praznih redova
        $resultText = $data['candidates'][0]['content']['parts'][0]['text'];

        // Ukloni Unicode line separatore i paragraph separatore
        $resultText = str_replace(["\u{2028}", "\u{2029}", "\u{0085}"], "\n", $resultText);
        // Normaliziraj sve vrste line breakova
        $resultText = str_replace(["\r\n", "\r"], "\n", $resultText);
        // Ukloni redove koji sadrže samo razmake/tabove
        $resultText = preg_replace('/^[ \t]+$/m', '', $resultText);
        // Ukloni razmake na kraju redova
        $resultText = preg_replace('/[ \t]+$/m', '', $resultText);
        // Ukloni višestruke prazne redove (2+ uzastopna newlinea = max 1 prazan red)
        $resultText = preg_replace('/\n{2,}/', "\n\n", $resultText);
        $resultText = trim($resultText);

        return ['text' => $resultText];
    }

    return ['error' => 'Gemini API greška: Resource exhausted nakon ' . $maxRetries . ' pokušaja'];
}

// Obrada - napravi članak
$autoSavedId = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'article' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $rawText = $_POST['raw_text'] ?? '';
    $audioFileName = $_POST['audio_filename'] ?? '';
    $audioTempPath = $_POST['audio_path'] ?? '';
    $customInstructions = trim($_POST['custom_instructions'] ?? '');
    if (!empty($rawText)) {
        $result = makeArticle($rawText, $customInstructions);
        if (isset($result['error'])) {
            $error = $result['error'];
            $transcription = $rawText;
        } else {
            $article = $result['text'];
            $transcription = $rawText;

            // Automatski spremi za admina (admin_only = 1)
            $autoTitle = 'Auto: ' . date('d.m.Y H:i') . ' - ' . mb_substr($audioFileName, 0, 50);
            $autoSavedId = saveTranscription($autoTitle, $rawText, $article, $audioFileName, $audioTempPath, 1);
        }
    }
}

// Lock datoteka za sprječavanje istovremenih transkripcija
$lockFile = sys_get_temp_dir() . '/desk_crm_transcription.lock';
$lockActive = false;
$lockUser = '';

// Debug log
file_put_contents('/tmp/transkripcija_debug.log', date('Y-m-d H:i:s') . ' - Page load. Lock file: ' . $lockFile . ' Exists: ' . (file_exists($lockFile) ? 'YES' : 'NO') . "\n", FILE_APPEND);

if (file_exists($lockFile)) {
    $lockData = json_decode(file_get_contents($lockFile), true);
    file_put_contents('/tmp/transkripcija_debug.log', date('Y-m-d H:i:s') . ' - Lock data: ' . json_encode($lockData) . ' Age: ' . (time() - ($lockData['time'] ?? 0)) . "s\n", FILE_APPEND);
    // Lock istječe nakon 5 minuta
    if ($lockData && (time() - $lockData['time']) < 300) {
        $lockActive = true;
        $lockUser = $lockData['user'] ?? 'Nepoznat';
    } else {
        // Istekao lock, obriši
        @unlink($lockFile);
    }
}

file_put_contents('/tmp/transkripcija_debug.log', date('Y-m-d H:i:s') . ' - lockActive: ' . ($lockActive ? 'TRUE' : 'FALSE') . "\n", FILE_APPEND);

// Obrada uploada - više tonova
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'multi_transcribe' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $activeTab = 'multi';
    if ($lockActive) {
        $error = 'Transkripcija je trenutno u tijeku (korisnik: ' . $lockUser . '). Pokušajte za par minuta.';
    } elseif (empty($_FILES['audio_multi']['tmp_name'][0])) {
        $error = 'Odaberite barem jednu audio datoteku';
    } else {
        file_put_contents($lockFile, json_encode([
            'time' => time(),
            'user' => $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Nepoznat'
        ]));

        $files = $_FILES['audio_multi'];
        $descriptions = $_POST['descriptions'] ?? [];
        $fileCount = count($files['tmp_name']);
        $allowedExts = ['mp3', 'mp4', 'm4a', 'wav', 'webm', 'mpeg', 'mpga', 'ogg', 'flac', 'aac'];

        $allTranscriptions = [];
        $errors = [];
        $audioFileNames = [];
        $audioPaths = [];

        for ($i = 0; $i < $fileCount; $i++) {
            if (empty($files['tmp_name'][$i])) continue;

            $fileName = $files['name'][$i];
            $fileSize = $files['size'][$i];
            $tmpName = $files['tmp_name'][$i];
            $description = trim($descriptions[$i] ?? 'Ton ' . ($i + 1));

            if ($fileSize > 100 * 1024 * 1024) {
                $errors[] = "$fileName: prevelika (max 100MB)";
                continue;
            }

            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts)) {
                $errors[] = "$fileName: nedozvoljeni format";
                continue;
            }

            $audioPath = $tmpName;
            $compressedPath = null;
            if ($fileSize > 20 * 1024 * 1024) {
                $compressedPath = sys_get_temp_dir() . '/compressed_' . uniqid() . '.mp3';
                $cmd = 'ffmpeg -i ' . escapeshellarg($tmpName) . ' -ac 1 -ar 16000 -b:a 64k -y ' . escapeshellarg($compressedPath) . ' 2>&1';
                exec($cmd, $output, $returnCode);
                if ($returnCode === 0 && file_exists($compressedPath)) {
                    $audioPath = $compressedPath;
                }
            }

            $audioFileNames[] = $description . ' (' . $fileName . ')';
            $result = transcribeAudio($audioPath, $fileName);

            if ($compressedPath && file_exists($compressedPath)) {
                unlink($compressedPath);
            }

            if (isset($result['error'])) {
                $errors[] = "$fileName: " . $result['error'];
            } else {
                $header = mb_strtoupper($description);
                $allTranscriptions[] = $header . ":\n" . $result['text'];

                // Spremi svaki audio trajno
                $savedAudioName = date('Y-m-d_His_') . bin2hex(random_bytes(4)) . '.' . $ext;
                $savedAudioPath = $audioUploadDir . $savedAudioName;
                if (copy($tmpName, $savedAudioPath)) {
                    $audioPaths[] = str_replace(UPLOAD_PATH, '', $savedAudioPath);
                }
            }
        }

        if (!empty($allTranscriptions)) {
            $transcription = implode("\n\n", $allTranscriptions);
            $audioFileName = implode(', ', $audioFileNames);
            $audioTempPath = implode(', ', $audioPaths);
            logActivity('audio_transcribe', 'ai', null);
        }

        if (!empty($errors)) {
            $error = implode('; ', $errors);
        } elseif (empty($allTranscriptions)) {
            $error = 'Transkripcija nije uspjela';
        }

        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
    }
}

// Obrada uploada - pojedinačna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    if ($lockActive) {
        $error = 'Transkripcija je trenutno u tijeku (korisnik: ' . $lockUser . '). Pokušajte za par minuta.';
    } elseif (empty($_FILES['audio']['tmp_name'][0])) {
        $error = 'Odaberite audio datoteku';
    } else {
        // Postavi lock
        file_put_contents($lockFile, json_encode([
            'time' => time(),
            'user' => $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Nepoznat'
        ]));
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

            // Max 100MB za Gemini inline audio
            if ($fileSize > 100 * 1024 * 1024) {
                $errors[] = "$fileName: prevelika (max 100MB)";
                continue;
            }

            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts)) {
                $errors[] = "$fileName: nedozvoljeni format";
                continue;
            }

            // Kompresiraj ako je veći od 20MB
            $audioPath = $tmpName;
            $compressedPath = null;
            if ($fileSize > 20 * 1024 * 1024) {
                $compressedPath = sys_get_temp_dir() . '/compressed_' . uniqid() . '.mp3';
                $cmd = 'ffmpeg -i ' . escapeshellarg($tmpName) . ' -ac 1 -ar 16000 -b:a 64k -y ' . escapeshellarg($compressedPath) . ' 2>&1';
                exec($cmd, $output, $returnCode);
                if ($returnCode === 0 && file_exists($compressedPath)) {
                    $audioPath = $compressedPath;
                }
            }

            $audioFileNames[] = $fileName;
            $result = transcribeAudio($audioPath, $fileName);

            // Obriši kompresiranu datoteku
            if ($compressedPath && file_exists($compressedPath)) {
                unlink($compressedPath);
            }

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

        // Otključaj
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Transkripcija</h1>
</div>

<?php if ($lockActive): ?>
<div class="card">
    <div class="card-body" style="background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; text-align: center; padding: 2rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2" style="margin-bottom: 1rem;">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
        </svg>
        <h3 style="color: #ea580c; margin-bottom: 0.5rem;">Transkripcija u tijeku</h3>
        <p style="color: #9a3412; margin: 0;">Korisnik <strong><?= e($lockUser) ?></strong> trenutno koristi transkripciju. Pričekajte da završi pa pokušajte ponovno.</p>
        <p style="color: #9a3412; margin-top: 0.5rem;"><small>Stranica će se automatski osvježiti za 30 sekundi.</small></p>
    </div>
</div>
<script>setTimeout(function(){ location.reload(); }, 30000);</script>
<?php else: ?>
<!-- Tabovi -->
<div class="tabs" style="margin-bottom: 1rem;">
    <a href="?tab=single" class="tab <?= $activeTab === 'single' ? 'active' : '' ?>">Transkripcija</a>
    <a href="?tab=multi" class="tab <?= $activeTab === 'multi' ? 'active' : '' ?>">Više tonova</a>
</div>

<?php if ($activeTab === 'single'): ?>
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
                <small class="form-text">Dozvoljeni formati: MP3, MP4, M4A, WAV, WEBM, OGG, FLAC, AAC (max 100MB po datoteci)</small>
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
<?php endif; ?>

<?php if ($activeTab === 'multi'): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Više tonova s jednog događaja</h2>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="multiForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="multi_transcribe">
            <input type="hidden" name="tab" value="multi">

            <div id="toneRows">
                <div class="tone-row">
                    <div class="tone-row-header">
                        <span class="tone-number">Ton 1</span>
                    </div>
                    <div class="tone-row-fields">
                        <div class="form-group" style="flex: 1; min-width: 200px;">
                            <label class="form-label">Opis *</label>
                            <input type="text" name="descriptions[]" class="form-control" placeholder="Npr: Izjava župana" required>
                        </div>
                        <div class="form-group" style="flex: 2; min-width: 200px;">
                            <label class="form-label">Audio datoteka *</label>
                            <input type="file" name="audio_multi[]" class="form-control" accept=".mp3,.mp4,.m4a,.wav,.webm,.mpeg,.mpga,.ogg,.flac,.aac" required>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin: 1rem 0; display: flex; gap: 0.5rem;">
                <button type="button" class="btn btn-outline" onclick="addToneRow()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="16"/>
                        <line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                    Dodaj ton
                </button>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                        <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                        <line x1="12" y1="19" x2="12" y2="23"/>
                        <line x1="8" y1="23" x2="16" y2="23"/>
                    </svg>
                    Transkribiraj sve
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
<?php endif; ?>

<?php if ($transcription):
    // Očisti transkript za prikaz
    $transcriptClean = str_replace(["\u{2028}", "\u{2029}", "\u{0085}"], "\n", $transcription);
    $transcriptClean = str_replace(["\r\n", "\r"], "\n", $transcriptClean);
    $transcriptClean = preg_replace('/^[ \t]+$/m', '', $transcriptClean);
    $transcriptClean = preg_replace('/[ \t]+$/m', '', $transcriptClean);
    $transcriptClean = preg_replace('/\n{2,}/', "\n\n", $transcriptClean);
    $transcriptClean = trim($transcriptClean);
    $charCount = mb_strlen($transcriptClean);
    $wordCount = str_word_count($transcriptClean, 0, 'ČčĆćŽžŠšĐđ');
?>
<div class="card mt-2">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card-title">Transkript <?= $article ? '(sirovi)' : '' ?></h2>
        <span class="badge badge-secondary"><?= number_format($wordCount) ?> riječi · <?= number_format($charCount) ?> znakova</span>
    </div>
    <div class="card-body">
        <div class="transcription-text" id="transcriptText"><?= e($transcriptClean) ?></div>

        <?php if (!$article): ?>
        <!-- Upute za članak -->
        <div style="margin-top: 1rem; padding: 1rem; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px;">
            <form method="POST" id="articleForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="article">
                <input type="hidden" name="tab" value="<?= e($activeTab) ?>">
                <input type="hidden" name="raw_text" value="<?= e($transcriptClean) ?>">
                <input type="hidden" name="audio_filename" value="<?= e($audioFileName ?? '') ?>">
                <input type="hidden" name="audio_path" value="<?= e($audioTempPath ?? '') ?>">

                <div class="form-group" style="margin-bottom: 0.75rem;">
                    <label class="form-label" style="font-weight: 600;">Upute za članak (opcionalno)</label>
                    <textarea name="custom_instructions" class="form-control" rows="2" placeholder="Npr: Fokusiraj se na izjave gradonačelnika, članak neka bude kraći..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" id="articleBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    Napiši članak
                </button>
            </form>
        </div>
        <?php endif; ?>

        <div style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
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
    // Agresivno čišćenje praznih redova
    $articleClean = str_replace(["\u{2028}", "\u{2029}", "\u{0085}"], "\n", $article);
    $articleClean = str_replace(["\r\n", "\r"], "\n", $articleClean);
    $articleClean = preg_replace('/^[ \t]+$/m', '', $articleClean);
    $articleClean = preg_replace('/[ \t]+$/m', '', $articleClean);
    $articleClean = preg_replace('/\n{2,}/', "\n\n", $articleClean);
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
        <div class="transcription-text" id="articleText"><?= e($articleClean) ?></div>

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
            <input type="hidden" name="tab" value="<?= e($activeTab) ?>">
            <input type="hidden" name="transcript_b64" value="<?= base64_encode($transcriptClean ?? '') ?>">
            <input type="hidden" name="article_b64" value="<?= base64_encode($articleClean ?? '') ?>">
            <input type="hidden" name="audio_filename" value="<?= e($audioFileName ?? '') ?>">
            <input type="hidden" name="audio_path" value="<?= e($audioTempPath ?? '') ?>">
            <input type="hidden" name="auto_saved_id" value="<?= (int)($autoSavedId ?? 0) ?>">

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

<?php endif; /* endif lockActive */ ?>

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
                        <?php if (!empty($saved['admin_only'])): ?>
                        <span style="background: #dc3545; color: white; font-size: 10px; padding: 2px 6px; border-radius: 3px; margin-left: 8px;">Samo admin</span>
                        <?php endif; ?>
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
.tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid var(--gray-200);
}
.tab {
    padding: 0.6rem 1.2rem;
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--gray-500);
    text-decoration: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: color 0.2s, border-color 0.2s;
}
.tab:hover { color: var(--primary); text-decoration: none; }
.tab.active { color: var(--primary); border-bottom-color: var(--primary); }
.tone-row {
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
}
.tone-row-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}
.tone-number {
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--primary);
}
.tone-row-fields {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}
.tone-remove {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    font-size: 1.2rem;
    font-weight: bold;
    padding: 0 0.25rem;
    line-height: 1;
}
.tone-remove:hover { color: #a71d2a; }
</style>

<script>
// Više tonova - dodavanje/micanje redova
var toneCounter = 1;
function addToneRow() {
    toneCounter++;
    var container = document.getElementById('toneRows');
    var row = document.createElement('div');
    row.className = 'tone-row';
    row.innerHTML = '<div class="tone-row-header"><span class="tone-number">Ton ' + toneCounter + '</span><button type="button" class="tone-remove" onclick="removeToneRow(this)" title="Ukloni">&times;</button></div><div class="tone-row-fields"><div class="form-group" style="flex: 1; min-width: 200px;"><label class="form-label">Opis *</label><input type="text" name="descriptions[]" class="form-control" placeholder="Npr: Izjava pročelnika" required></div><div class="form-group" style="flex: 2; min-width: 200px;"><label class="form-label">Audio datoteka *</label><input type="file" name="audio_multi[]" class="form-control" accept=".mp3,.mp4,.m4a,.wav,.webm,.mpeg,.mpga,.ogg,.flac,.aac" required></div></div>';
    container.appendChild(row);
    renumberTones();
}
function removeToneRow(btn) {
    var row = btn.closest('.tone-row');
    var container = document.getElementById('toneRows');
    if (container.children.length > 1) {
        row.remove();
        renumberTones();
    }
}
function renumberTones() {
    var rows = document.querySelectorAll('#toneRows .tone-row');
    toneCounter = rows.length;
    rows.forEach(function(row, i) {
        row.querySelector('.tone-number').textContent = 'Ton ' + (i + 1);
    });
}

document.querySelectorAll('form[enctype]').forEach(function(form) {
form.addEventListener('submit', function(e) {
    e.preventDefault();

    const btn = document.getElementById('submitBtn');
    btn.textContent = 'Šaljem datoteke...';
    btn.disabled = true;

    let statusDiv = document.getElementById('transcriptionStatus');
    if (!statusDiv) {
        statusDiv = document.createElement('div');
        statusDiv.id = 'transcriptionStatus';
        statusDiv.style.cssText = 'margin-top: 1rem; padding: 1rem; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; font-size: 0.9rem;';
        btn.parentNode.appendChild(statusDiv);
    }

    const fileInputs = form.querySelectorAll('input[type="file"]');
    let fileCount = 0;
    let totalSize = 0;
    fileInputs.forEach(function(fi) {
        for (let i = 0; i < fi.files.length; i++) {
            fileCount++;
            totalSize += fi.files[i].size;
        }
    });
    const sizeMB = (totalSize / 1024 / 1024).toFixed(1);

    statusDiv.innerHTML = '<strong>Uploading...</strong><br>' + fileCount + ' datoteka (' + sizeMB + ' MB)';

    const messages = [
        { time: 3, btn: 'Uploading audio...', msg: '<strong>Upload u tijeku...</strong><br>Šaljem ' + fileCount + ' datoteka (' + sizeMB + ' MB) na server.' },
        { time: 8, btn: 'Pripremam audio...', msg: '<strong>Priprema...</strong><br>Server je primio datoteke. Priprema audio za transkripciju.' },
        { time: 15, btn: 'Transkribiram...', msg: '<strong>Transkripcija u tijeku...</strong><br>AI obrađuje audio. Ovo može potrajati ovisno o duljini snimke.' },
        { time: 30, btn: 'Još transkribiram...', msg: '<strong>Još radim...</strong><br>Dulje snimke zahtijevaju više vremena. Molimo pričekajte.' },
        { time: 60, btn: 'Obrađujem...', msg: '<strong>Obrada traje dulje od očekivanog...</strong><br>Velika datoteka se obrađuje. Ne zatvarajte stranicu.' },
        { time: 120, btn: 'Još malo...', msg: '<strong>Skoro gotovo...</strong><br>Obrada je u završnoj fazi. Hvala na strpljenju.' },
        { time: 180, btn: 'Završavam...', msg: '<strong>Obrada traje dugo...</strong><br>Ako se ništa ne dogodi u sljedećih minutu, probajte ponovno s manjom datotekom.' }
    ];

    let elapsed = 0;
    const timer = setInterval(function() {
        elapsed++;
        for (let i = messages.length - 1; i >= 0; i--) {
            if (elapsed >= messages[i].time) {
                btn.textContent = messages[i].btn;
                statusDiv.innerHTML = messages[i].msg + '<br><small style="color: #6b7280;">Proteklo: ' + Math.floor(elapsed / 60) + ':' + String(elapsed % 60).padStart(2, '0') + '</small>';
                break;
            }
        }
    }, 1000);

    const formData = new FormData(form);
    fetch(form.action || window.location.href, {
        method: 'POST',
        body: formData
    }).then(function(response) {
        return response.text();
    }).then(function(html) {
        clearInterval(timer);
        document.open();
        document.write(html);
        document.close();
    }).catch(function(err) {
        clearInterval(timer);
        btn.textContent = 'Transkribiraj';
        btn.disabled = false;
        statusDiv.innerHTML = '<strong style="color: #dc2626;">Greška:</strong> ' + err.message + '<br>Pokušajte ponovno.';
    });
});
});

document.getElementById('articleForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const btn = document.getElementById('articleBtn');
    btn.textContent = 'Pišem članak...';
    btn.disabled = true;

    const formData = new FormData(form);
    fetch(form.action || window.location.href, {
        method: 'POST',
        body: formData
    }).then(function(response) {
        return response.text();
    }).then(function(html) {
        document.open();
        document.write(html);
        document.close();
    }).catch(function(err) {
        btn.textContent = 'Napiši članak';
        btn.disabled = false;
        alert('Greška: ' + err.message);
    });
});

function copyTranscript() {
    const text = <?= json_encode($transcriptClean ?? '') ?>;
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
    const text = <?= json_encode($transcriptClean ?? '') ?>;
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
