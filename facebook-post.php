<?php
/**
 * Objavi na Facebook
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'config/facebook.php';

requireLogin();

if (!isEditor()) {
    header('Location: dashboard.php');
    exit;
}

define('PAGE_TITLE', 'Objavi na Facebook');

$db = getDB();
$message = '';
$messageType = '';
$postedUrl = '';

// Obrada forme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $url = trim($_POST['url'] ?? '');
    $text = trim($_POST['text'] ?? '');

    if (empty($url)) {
        $message = 'URL je obavezan';
        $messageType = 'danger';
    } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
        $message = 'Nevažeći URL';
        $messageType = 'danger';
    } else {
        $result = postToFacebook($url, $text);

        if ($result['success']) {
            $message = 'Objavljeno na Facebook!';
            $messageType = 'success';
            $postedUrl = $url;

            // Logiraj aktivnost
            logActivity('facebook_post', 'social', null, ['url' => $url]);
        } else {
            $message = 'Greška: ' . $result['error'];
            $messageType = 'danger';
        }
    }
}

include 'includes/header.php';
?>

<div class="d-flex" style="justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <h1>📘 Objavi na Facebook</h1>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> mt-2">
    <?= e($message) ?>
    <?php if ($postedUrl): ?>
    <br><small>Link s UTM: <?= e($postedUrl) ?>?utm_source=facebook&utm_medium=social&utm_campaign=post</small>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card mt-2">
    <div class="card-header">
        <h2 class="card-title">Nova objava</h2>
    </div>
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>

            <div class="form-group">
                <label class="form-label" for="url">Link članka *</label>
                <input type="url"
                       id="url"
                       name="url"
                       class="form-control"
                       placeholder="https://www.zagorje.com/clanak/..."
                       required
                       style="font-size: 1rem;">
                <small style="color: #6b7280;">UTM parametri se automatski dodaju</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="text">Tekst objave (opcionalno)</label>
                <div class="emoji-picker" style="margin-bottom: 0.5rem;">
                    <?php
                    $emojiGroups = [
                        'Vijesti' => ['🔴', '🟠', '🟡', '🟢', '🔵', '⚫', '⚪', '🚨', '⚠️', '📢', '📣', '💥', '⚡', '🔥', '💯'],
                        'Oznake' => ['📍', '📌', '🎯', '✅', '❌', '⭕', '❗', '❓', '💡', '📝', '🔗', '🏷️', '🆕', '🆓', '🔝'],
                        'Mediji' => ['📰', '🗞️', '📸', '📷', '🎥', '🎬', '📺', '📻', '🎙️', '🎤', '📡', '💻', '📱', '🖥️', '⌨️'],
                        'Događaji' => ['🎉', '🎊', '🎭', '🎪', '🎨', '🎵', '🎶', '🎸', '🎹', '🎺', '🥁', '🎤', '🎧', '🎫', '🎁'],
                        'Sport' => ['⚽', '🏀', '🏈', '⚾', '🎾', '🏐', '🏉', '🎱', '🏓', '🏸', '🥊', '🚴', '🏃', '🏊', '🏆'],
                        'Priroda' => ['☀️', '🌤️', '⛅', '🌧️', '⛈️', '❄️', '🌊', '🌳', '🌲', '🌸', '🌺', '🍀', '🌈', '⭐', '🌙'],
                        'Ruke' => ['👉', '👆', '👇', '👈', '☝️', '👍', '👎', '👏', '🙌', '🤝', '✋', '🖐️', '✌️', '🤞', '💪'],
                        'Lica' => ['😀', '😃', '😊', '🥳', '😍', '🤩', '😎', '🤔', '😮', '😢', '😡', '🥺', '😱', '🤯', '😴'],
                        'Simboli' => ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '💔', '💕', '💖', '💗', '💝', '💘', '💞'],
                        'Hrana' => ['☕', '🍺', '🍷', '🥂', '🍕', '🍔', '🌭', '🥗', '🍰', '🎂', '🍎', '🍇', '🥐', '🍞', '🧀']
                    ];
                    foreach ($emojiGroups as $group => $emojis):
                    ?>
                    <div style="margin-bottom: 0.25rem;">
                        <small style="color: #6b7280; font-size: 0.65rem;"><?= $group ?>:</small>
                        <?php foreach ($emojis as $emoji): ?>
                        <button type="button" class="emoji-btn" onclick="insertEmoji('<?= $emoji ?>')"><?= $emoji ?></button>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <textarea id="text"
                          name="text"
                          class="form-control"
                          rows="3"
                          placeholder="Dodaj komentar uz link..."></textarea>
            </div>

<style>
.emoji-picker {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.5rem;
    max-height: 200px;
    overflow-y: auto;
}
.emoji-btn {
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 4px;
    background: transparent;
    cursor: pointer;
    font-size: 1rem;
    padding: 0;
    transition: all 0.15s;
}
.emoji-btn:hover {
    background: #e5e7eb;
    transform: scale(1.2);
}
</style>

<script>
function insertEmoji(emoji) {
    const textarea = document.getElementById('text');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    textarea.value = text.substring(0, start) + emoji + text.substring(end);
    textarea.selectionStart = textarea.selectionEnd = start + emoji.length;
    textarea.focus();
}
</script>

            <button type="submit" class="btn btn-primary" style="background: #1877f2;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 0.5rem;">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                Objavi na Facebook
            </button>
        </form>
    </div>
</div>

<!-- Status info -->
<div class="card mt-2">
    <div class="card-header">
        <h3 class="card-title">Status povezivanja</h3>
    </div>
    <div class="card-body" style="font-size: 0.8rem;">
        <span style="color: #059669;">✓ Povezano sa stranicom: <strong>Zagorje.com</strong></span>
        <br><small style="color: #6b7280;">Page ID: <?= FB_PAGE_ID ?></small>
    </div>
</div>

<!-- Upute -->
<div class="card mt-2">
    <div class="card-header">
        <h3 class="card-title">Kako koristiti</h3>
    </div>
    <div class="card-body" style="font-size: 0.875rem; color: #4b5563;">
        <ol style="margin: 0; padding-left: 1.25rem;">
            <li>Objavi članak na portalu</li>
            <li>Kopiraj URL članka</li>
            <li>Zalijepi ovdje i klikni "Objavi na Facebook"</li>
            <li>Link će automatski imati UTM parametre za praćenje u GA4</li>
        </ol>
        <div style="margin-top: 1rem; padding: 0.75rem; background: #f3f4f6; border-radius: 0.5rem;">
            <strong>UTM parametri koji se dodaju:</strong><br>
            <code style="font-size: 0.75rem;">?utm_source=facebook&utm_medium=social&utm_campaign=post</code>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
