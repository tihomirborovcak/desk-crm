<?php
/**
 * Pregled spremljene transkripcije
 */

define('PAGE_TITLE', 'Transkripcija');

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

// Pomoćna funkcija za čišćenje praznih redova
function cleanExcessiveNewlines($text) {
    if (empty($text)) return $text;
    $text = str_replace(["\u{2028}", "\u{2029}", "\u{0085}"], "\n", $text);
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = preg_replace('/^[ \t]+$/m', '', $text);
    $text = preg_replace('/[ \t]+$/m', '', $text);
    $text = preg_replace('/\n{2,}/', "\n\n", $text);
    return trim($text);
}

function countWordsHr($text) {
    if (empty($text)) return 0;
    preg_match_all('/[\p{L}\p{N}]+/u', $text, $matches);
    return count($matches[0]);
}

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: transkripcija.php');
    exit;
}

$db = getDB();

$stmt = $db->prepare("
    SELECT t.*, u.full_name as author_name
    FROM transcriptions t
    LEFT JOIN users u ON t.created_by = u.id
    WHERE t.id = ?
");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: transkripcija.php');
    exit;
}

// Navigacija
$stmt = $db->prepare("SELECT id, title FROM transcriptions WHERE id < ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$id]);
$prevItem = $stmt->fetch();

$stmt = $db->prepare("SELECT id, title FROM transcriptions WHERE id > ? ORDER BY id ASC LIMIT 1");
$stmt->execute([$id]);
$nextItem = $stmt->fetch();

$success = null;
$error = null;

// Brisanje
if (isset($_GET['delete']) && verifyCSRFToken($_GET['token'] ?? '')) {
    if (!empty($item['audio_path'])) {
        $audioFile = UPLOAD_PATH . $item['audio_path'];
        if (file_exists($audioFile)) unlink($audioFile);
    }
    $stmt = $db->prepare("DELETE FROM transcriptions WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: transkripcija.php');
    exit;
}

// Brisanje audio
if (isset($_GET['delete_audio']) && verifyCSRFToken($_GET['token'] ?? '')) {
    if (!empty($item['audio_path'])) {
        $audioFile = UPLOAD_PATH . $item['audio_path'];
        if (file_exists($audioFile)) unlink($audioFile);
        $stmt = $db->prepare("UPDATE transcriptions SET audio_path = NULL WHERE id = ?");
        $stmt->execute([$id]);
    }
    header('Location: transkripcija-view.php?id=' . $id);
    exit;
}

// Spremanje izmjena
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $newTitle = trim($_POST['title'] ?? '');
    $newArticle = cleanExcessiveNewlines($_POST['article'] ?? '');
    $newTranscript = cleanExcessiveNewlines($_POST['transcript'] ?? '');

    if (empty($newTitle)) {
        $error = 'Naslov je obavezan';
    } else {
        $stmt = $db->prepare("UPDATE transcriptions SET title = ?, article = ?, transcript = ? WHERE id = ?");
        $stmt->execute([$newTitle, $newArticle, $newTranscript, $id]);
        $item['title'] = $newTitle;
        $item['article'] = $newArticle;
        $item['transcript'] = $newTranscript;
        $success = 'Izmjene spremljene!';
    }
}

$articleClean = cleanExcessiveNewlines($item['article'] ?? '');
$transcriptClean = cleanExcessiveNewlines($item['transcript'] ?? '');

$articleCharCount = mb_strlen($articleClean);
$articleWordCount = countWordsHr($articleClean);
$charCount = mb_strlen($transcriptClean);
$wordCount = countWordsHr($transcriptClean);

include 'includes/header.php';
?>

<style>
.tv-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
.tv-nav { display: flex; gap: 0.5rem; }
.tv-nav-btn { padding: 0.5rem 0.75rem; border: 1px solid var(--gray-300); border-radius: 4px; background: white; color: var(--gray-700); text-decoration: none; font-size: 0.875rem; }
.tv-nav-btn:hover { background: var(--gray-50); }
.tv-nav-btn.disabled { opacity: 0.4; pointer-events: none; }
.tv-meta { background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
.tv-meta-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
.tv-meta-info { color: #6b7280; }
.tv-delete-btn { background: #dc2626; color: white; padding: 0.25rem 0.75rem; border-radius: 4px; text-decoration: none; font-size: 0.875rem; }
.tv-audio-box { background: #eff6ff; border: 1px solid #93c5fd; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
.tv-audio-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
.tv-audio-title { color: #1e40af; margin: 0; font-size: 1.25rem; display: flex; align-items: center; gap: 0.5rem; }
.tv-section { border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
.tv-section-article { background: #dcfce7; border: 1px solid #86efac; }
.tv-section-transcript { background: #f9fafb; border: 1px solid #d1d5db; }
.tv-section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.tv-section-title { margin: 0; font-size: 1.25rem; }
.tv-section-title-article { color: #166534; }
.tv-section-title-transcript { color: #374151; }
.tv-badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; color: white; }
.tv-badge-green { background: #166534; }
.tv-badge-gray { background: #6b7280; }
.tv-content { background: white; border: 1px solid #d1d5db; border-radius: 8px; padding: 1rem; line-height: 1.8; max-height: 500px; overflow-y: auto; white-space: pre-wrap; }
.tv-content-edit { width: 100%; min-height: 300px; font-family: inherit; font-size: inherit; line-height: 1.8; padding: 1rem; border: 1px solid #d1d5db; border-radius: 8px; resize: vertical; }
.tv-actions { margin-top: 1rem; display: flex; gap: 0.5rem; }
.tv-copy-btn { color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; }
.tv-copy-btn-green { background: #16a34a; }
.tv-copy-btn-gray { background: #6b7280; }
.tv-edit-toggle { background: #2563eb; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; }
.tv-title-input { font-size: 1.5rem; font-weight: bold; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; width: 100%; max-width: 600px; }
.edit-mode { display: none; }
</style>

<div class="page-header tv-header">
    <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
        <a href="transkripcija.php" class="btn btn-outline">← Natrag</a>
        <h1 class="view-mode"><?= e($item['title']) ?></h1>
    </div>
    <div class="tv-nav">
        <a href="<?= $prevItem ? 'transkripcija-view.php?id=' . $prevItem['id'] : '#' ?>" class="tv-nav-btn <?= $prevItem ? '' : 'disabled' ?>">← Prethodna</a>
        <a href="<?= $nextItem ? 'transkripcija-view.php?id=' . $nextItem['id'] : '#' ?>" class="tv-nav-btn <?= $nextItem ? '' : 'disabled' ?>">Sljedeća →</a>
    </div>
</div>

<?php if ($success): ?><div class="alert alert-success" style="margin-bottom: 1rem;"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger" style="margin-bottom: 1rem;"><?= e($error) ?></div><?php endif; ?>

<div class="tv-meta">
    <div class="tv-meta-row">
        <span class="tv-meta-info"><?= formatDateTime($item['created_at']) ?> · <?= e($item['author_name']) ?><?= $item['audio_filename'] ? ' · ' . e($item['audio_filename']) : '' ?></span>
        <div style="display: flex; gap: 0.5rem;">
            <button type="button" class="tv-edit-toggle view-mode" onclick="toggleEditMode()">Uredi</button>
            <a href="?id=<?= $id ?>&delete=1&token=<?= generateCSRFToken() ?>" class="tv-delete-btn" onclick="return confirm('Obrisati?')">Obriši</a>
        </div>
    </div>
</div>

<!-- EDIT MODE FORM -->
<form method="POST" id="editForm" class="edit-mode">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="update">
    <div class="tv-meta" style="background: #fef3c7; border-color: #fcd34d;">
        <div style="margin-bottom: 0.5rem; font-weight: 600;">Uređivanje</div>
        <input type="text" name="title" class="tv-title-input" value="<?= e($item['title']) ?>" required>
        <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary">Spremi</button>
            <button type="button" class="btn btn-outline" onclick="toggleEditMode()">Odustani</button>
        </div>
    </div>

    <div class="tv-section tv-section-article">
        <h2 class="tv-section-title tv-section-title-article">Članak</h2>
        <textarea name="article" class="tv-content-edit"><?= e($articleClean) ?></textarea>
    </div>

    <div class="tv-section tv-section-transcript">
        <h2 class="tv-section-title tv-section-title-transcript">Transkript</h2>
        <textarea name="transcript" class="tv-content-edit"><?= e($transcriptClean) ?></textarea>
    </div>
</form>

<!-- VIEW MODE -->
<?php if (!empty($item['audio_path'])):
    $audioUrl = UPLOAD_URL . $item['audio_path'];
?>
<div class="tv-audio-box view-mode">
    <div class="tv-audio-header">
        <h2 class="tv-audio-title">Audio snimka</h2>
        <a href="?id=<?= $id ?>&delete_audio=1&token=<?= generateCSRFToken() ?>" style="background:#dc2626;color:white;padding:0.25rem 0.75rem;border-radius:4px;font-size:0.75rem;" onclick="return confirm('Obrisati audio?')">Obriši audio</a>
    </div>
    <audio controls style="width: 100%;"><source src="<?= e($audioUrl) ?>" type="audio/mpeg"></audio>
    <div style="margin-top: 0.5rem;"><a href="<?= e($audioUrl) ?>" download style="color:#1e40af;font-size:0.875rem;">Preuzmi audio</a></div>
</div>
<?php endif; ?>

<div class="tv-section tv-section-article view-mode">
    <div class="tv-section-header">
        <h2 class="tv-section-title tv-section-title-article">Članak</h2>
        <span class="tv-badge tv-badge-green"><?= number_format($articleWordCount) ?> riječi · <?= number_format($articleCharCount) ?> znakova</span>
    </div>
    <div class="tv-content"><?= e($articleClean) ?></div>
    <div class="tv-actions">
        <button type="button" onclick="copyText('article')" class="tv-copy-btn tv-copy-btn-green">Kopiraj članak</button>
    </div>
</div>

<div class="tv-section tv-section-transcript view-mode">
    <div class="tv-section-header">
        <h2 class="tv-section-title tv-section-title-transcript">Sirovi transkript</h2>
        <span class="tv-badge tv-badge-gray"><?= number_format($wordCount) ?> riječi · <?= number_format($charCount) ?> znakova</span>
    </div>
    <div class="tv-content"><?= e($transcriptClean) ?></div>
    <div class="tv-actions">
        <button type="button" onclick="copyText('transcript')" class="tv-copy-btn tv-copy-btn-gray">Kopiraj transkript</button>
    </div>
</div>

<script>
const articleText = <?= json_encode($articleClean) ?>;
const transcriptText = <?= json_encode($transcriptClean) ?>;

function copyText(type) {
    navigator.clipboard.writeText(type === 'article' ? articleText : transcriptText).then(() => alert('Kopirano!'));
}

function toggleEditMode() {
    document.querySelectorAll('.view-mode').forEach(el => el.style.display = el.style.display === 'none' ? '' : 'none');
    document.querySelectorAll('.edit-mode').forEach(el => el.style.display = el.style.display === 'none' ? '' : 'none');
}
</script>

<?php include 'includes/footer.php'; ?>
