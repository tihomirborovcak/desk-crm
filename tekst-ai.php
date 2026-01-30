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
    // Normaliziraj line endings
    $clean = str_replace(["\r\n", "\r", "\xE2\x80\xA8", "\xE2\x80\xA9"], "\n", $clean);
    $clean = str_replace(["\xC2\xA0", "\xE2\x80\x89", "\xE2\x80\xAF"], " ", $clean);
    // Ukloni markdown
    $clean = preg_replace('/\*\*(.+?)\*\*/s', '$1', $clean);  // **bold**
    $clean = preg_replace('/\*([^*\n]+)\*/s', '$1', $clean);  // *italic*
    $clean = str_replace('**', '', $clean);
    $clean = preg_replace('/^[\*\-•]\s*/m', '', $clean);       // bullets
    $clean = preg_replace('/\*+$/m', '', $clean);              // trailing *
    $clean = preg_replace('/^#+\s*/m', '', $clean);            // headings
    // Ukloni prazne linije
    while (strpos($clean, "\n\n") !== false) {
        $clean = str_replace("\n\n", "\n", $clean);
    }
    $lines = explode("\n", $clean);
    $lines = array_filter($lines, function($line) { return trim($line) !== ''; });
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

    if ($mode === 'novi') {
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
</div>

<?php if ($activeTab === 'prerada'): ?>
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

<?php else: ?>
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
