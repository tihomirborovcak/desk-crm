<?php
/**
 * Git Webhook za automatski deploy
 *
 * GitHub: Settings → Webhooks → Add webhook
 * URL: https://your-domain.com/webhook.php
 * Content type: application/json
 * Secret: isti kao WEBHOOK_SECRET dolje
 */

// Konfiguracija
$secret = 'PROMIJENI_OVO_U_TAJNI_KLJUC_123'; // Postavite isti secret u GitHub/GitLab
$branch = 'main'; // Branch koji trigera deploy
$logFile = __DIR__ . '/webhook.log';

// Samo POST zahtjevi
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Logiranje
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

// Dohvati payload
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

// Verifikacija GitHub signature
$hubSignature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
if ($hubSignature) {
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($expectedSignature, $hubSignature)) {
        logMessage('GREŠKA: Nevažeći GitHub signature');
        http_response_code(403);
        die('Invalid signature');
    }
}

// Verifikacija GitLab token
$gitlabToken = $_SERVER['HTTP_X_GITLAB_TOKEN'] ?? '';
if ($gitlabToken && $gitlabToken !== $secret) {
    logMessage('GREŠKA: Nevažeći GitLab token');
    http_response_code(403);
    die('Invalid token');
}

// Ako nema nikakvog tokena, odbij
if (!$hubSignature && !$gitlabToken) {
    // Provjeri query string kao fallback
    if (($_GET['token'] ?? '') !== $secret) {
        logMessage('GREŠKA: Nedostaje autentifikacija');
        http_response_code(403);
        die('Authentication required');
    }
}

// Provjeri branch (GitHub)
if (isset($data['ref'])) {
    $pushBranch = str_replace('refs/heads/', '', $data['ref']);
    if ($pushBranch !== $branch) {
        logMessage("SKIP: Push na branch '$pushBranch', očekujem '$branch'");
        die("Ignoring push to $pushBranch");
    }
}

// Provjeri branch (GitLab)
if (isset($data['object_kind']) && $data['object_kind'] === 'push') {
    $pushBranch = $data['ref'] ?? '';
    $pushBranch = str_replace('refs/heads/', '', $pushBranch);
    if ($pushBranch !== $branch) {
        logMessage("SKIP: Push na branch '$pushBranch', očekujem '$branch'");
        die("Ignoring push to $pushBranch");
    }
}

logMessage('Deploy započet...');

// Izvršavanje git pull
$repoPath = __DIR__;
$output = [];
$returnCode = 0;

// Promijeni u direktorij projekta i povuci promjene
chdir($repoPath);

// Reset lokalnih promjena (opcionalno - odkomentiraj ako treba)
// exec('git reset --hard HEAD 2>&1', $output, $returnCode);

// Git pull
exec('git pull origin ' . escapeshellarg($branch) . ' 2>&1', $output, $returnCode);

$outputStr = implode("\n", $output);
logMessage("Git pull rezultat (code $returnCode):\n$outputStr");

if ($returnCode !== 0) {
    logMessage('GREŠKA: Git pull nije uspio');
    http_response_code(500);
    echo "Deploy failed:\n$outputStr";
    exit;
}

// Opcionalno: Composer install (odkomentiraj ako treba)
// exec('composer install --no-dev --optimize-autoloader 2>&1', $output, $returnCode);

// Opcionalno: Clear cache, migrate, itd.
// exec('php artisan migrate --force 2>&1', $output, $returnCode);

logMessage('Deploy uspješno završen!');

http_response_code(200);
echo "Deploy successful!\n";
echo $outputStr;
