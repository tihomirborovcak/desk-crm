<?php
/**
 * AI Generator slika - DALL-E
 */

define('PAGE_TITLE', 'AI Slike');

require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Lokalna konfiguracija s API ključevima
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$generatedImage = null;
$error = null;
$prompt = '';

// DALL-E API
function generirajSliku($prompt) {
    if (!defined('OPENAI_API_KEY')) {
        return ['error' => 'API ključ nije konfiguriran'];
    }
    $apiKey = OPENAI_API_KEY;

    $ch = curl_init('https://api.openai.com/v1/images/generations');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1024x1024',
            'quality' => 'standard'
        ])
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        return ['error' => $data['error']['message'] ?? 'Greška pri generiranju slike'];
    }

    return ['url' => $data['data'][0]['url'] ?? null];
}

// Obrada forme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $prompt = trim($_POST['prompt'] ?? '');

    if (empty($prompt)) {
        $error = 'Unesite opis slike';
    } else {
        $result = generirajSliku($prompt);

        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            $generatedImage = $result['url'];

            // Logiraj aktivnost
            logActivity('ai_image_generate', 'ai', null);
        }
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>AI Generator slika</h1>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Generiraj sliku s DALL-E</h2>
    </div>
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>

            <div class="form-group">
                <label class="form-label">Opis slike (prompt) *</label>
                <textarea name="prompt" class="form-control" rows="3" placeholder="Opišite sliku koju želite generirati... npr. 'Profesionalna fotografija modernog ureda, prirodno svjetlo, DSLR kvaliteta'"><?= e($prompt) ?></textarea>
                <small class="form-text">Što detaljniji opis, to bolja slika. Pišite na engleskom za bolje rezultate.</small>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
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
    <div class="card-header">
        <h2 class="card-title">Generirana slika</h2>
    </div>
    <div class="card-body" style="text-align: center;">
        <img src="<?= e($generatedImage) ?>" alt="AI generirana slika" style="max-width: 100%; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">

        <div style="margin-top: 1rem; display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?= e($generatedImage) ?>" download class="btn btn-success" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Preuzmi sliku
            </a>
            <a href="<?= e($generatedImage) ?>" target="_blank" class="btn btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                    <polyline points="15 3 21 3 21 9"/>
                    <line x1="10" y1="14" x2="21" y2="3"/>
                </svg>
                Otvori u novom tabu
            </a>
        </div>

        <p class="text-muted" style="margin-top: 1rem; font-size: 0.85rem;">
            Slika je dostupna 1 sat. Preuzmite je ako je želite sačuvati.
        </p>
    </div>
</div>
<?php endif; ?>

<!-- Primjeri promptova -->
<div class="card mt-2">
    <div class="card-header">
        <h2 class="card-title">Primjeri promptova</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="prompt-examples">
            <div class="prompt-example" onclick="usePrompt(this)">
                <strong>Vijesti / Press</strong>
                <p>Professional news photo of a press conference with journalists and microphones, realistic, high quality photography</p>
            </div>
            <div class="prompt-example" onclick="usePrompt(this)">
                <strong>Sport</strong>
                <p>Dynamic sports photography of soccer players in action, stadium background, dramatic lighting, professional DSLR quality</p>
            </div>
            <div class="prompt-example" onclick="usePrompt(this)">
                <strong>Kultura</strong>
                <p>Elegant concert hall with orchestra performing, warm lighting, artistic photography style</p>
            </div>
            <div class="prompt-example" onclick="usePrompt(this)">
                <strong>Priroda Zagorja</strong>
                <p>Beautiful rolling hills of Croatian Zagorje region, vineyards, traditional houses, golden hour photography</p>
            </div>
            <div class="prompt-example" onclick="usePrompt(this)">
                <strong>Biznis</strong>
                <p>Modern business meeting in a professional office, diverse team collaboration, natural lighting, corporate photography</p>
            </div>
        </div>
    </div>
</div>

<style>
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
function usePrompt(el) {
    const promptText = el.querySelector('p').textContent;
    document.querySelector('textarea[name="prompt"]').value = promptText;
    window.scrollTo({top: 0, behavior: 'smooth'});
}
</script>

<?php include 'includes/footer.php'; ?>
