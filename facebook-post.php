<?php
/**
 * Objavi na Facebook - s planiranjem
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'config/facebook.php';

requireLogin();

if (!isEditor()) {
    header('Location: dashboard.php');
    exit;
}

define('PAGE_TITLE', 'Facebook objave');

$db = getDB();
$message = '';
$messageType = '';

// Provjeri postoji li tablica
try {
    $db->query("SELECT 1 FROM facebook_posts LIMIT 1");
} catch (PDOException $e) {
    header('Location: facebook-setup.php');
    exit;
}

// Obriši/otkaži zakazanu objavu
if (isset($_GET['cancel']) && verifyCSRFToken($_GET['token'] ?? '')) {
    $cancelId = intval($_GET['cancel']);
    $stmt = $db->prepare("UPDATE facebook_posts SET status = 'cancelled' WHERE id = ? AND status = 'scheduled'");
    $stmt->execute([$cancelId]);
    setMessage('success', 'Objava otkazana');
    header('Location: facebook-post.php');
    exit;
}

// Objavi odmah zakazanu objavu
if (isset($_GET['post_now']) && verifyCSRFToken($_GET['token'] ?? '')) {
    $postId = intval($_GET['post_now']);
    $stmt = $db->prepare("SELECT * FROM facebook_posts WHERE id = ? AND status = 'scheduled'");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();

    if ($post) {
        $result = postToFacebook($post['url'], $post['message']);
        if ($result['success']) {
            $stmt = $db->prepare("UPDATE facebook_posts SET status = 'posted', posted_at = NOW(), post_id = ? WHERE id = ?");
            $stmt->execute([$result['post_id'], $postId]);
            setMessage('success', 'Objavljeno na Facebook!');
        } else {
            $stmt = $db->prepare("UPDATE facebook_posts SET status = 'failed', error_message = ? WHERE id = ?");
            $stmt->execute([$result['error'], $postId]);
            setMessage('danger', 'Greška: ' . $result['error']);
        }
    }
    header('Location: facebook-post.php');
    exit;
}

// Obrada forme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $url = trim($_POST['url'] ?? '');
    $text = trim($_POST['text'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $action = $_POST['action'] ?? 'now';
    $scheduledDate = $_POST['scheduled_date'] ?? '';
    $scheduledTime = $_POST['scheduled_time'] ?? '';

    if (empty($url)) {
        $message = 'URL je obavezan';
        $messageType = 'danger';
    } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
        $message = 'Nevažeći URL';
        $messageType = 'danger';
    } elseif ($action === 'schedule' && (empty($scheduledDate) || empty($scheduledTime))) {
        $message = 'Datum i vrijeme su obavezni za zakazivanje';
        $messageType = 'danger';
    } else {
        if ($action === 'now') {
            // Objavi odmah
            $result = postToFacebook($url, $text);

            if ($result['success']) {
                // Spremi u bazu
                $stmt = $db->prepare("INSERT INTO facebook_posts (url, title, message, scheduled_at, posted_at, post_id, status, created_by) VALUES (?, ?, ?, NOW(), NOW(), ?, 'posted', ?)");
                $stmt->execute([$url, $title, $text, $result['post_id'], $_SESSION['user_id']]);

                $message = 'Objavljeno na Facebook!';
                $messageType = 'success';
                logActivity('facebook_post', 'social', null, ['url' => $url]);
            } else {
                $message = 'Greška: ' . $result['error'];
                $messageType = 'danger';
            }
        } else {
            // Zakaži za kasnije
            $scheduledAt = $scheduledDate . ' ' . $scheduledTime . ':00';
            $stmt = $db->prepare("INSERT INTO facebook_posts (url, title, message, scheduled_at, status, created_by) VALUES (?, ?, ?, ?, 'scheduled', ?)");
            $stmt->execute([$url, $title, $text, $scheduledAt, $_SESSION['user_id']]);

            $message = 'Objava zakazana za ' . date('d.m.Y. H:i', strtotime($scheduledAt));
            $messageType = 'success';
            logActivity('facebook_schedule', 'social', null, ['url' => $url, 'scheduled' => $scheduledAt]);
        }
    }
}

// Dohvati zakazane objave
$stmt = $db->query("SELECT fp.*, u.full_name FROM facebook_posts fp JOIN users u ON fp.created_by = u.id WHERE fp.status = 'scheduled' ORDER BY fp.scheduled_at ASC");
$scheduledPosts = $stmt->fetchAll();

// Dohvati zadnje objave
$stmt = $db->query("SELECT fp.*, u.full_name FROM facebook_posts fp JOIN users u ON fp.created_by = u.id WHERE fp.status IN ('posted', 'failed') ORDER BY fp.created_at DESC LIMIT 20");
$recentPosts = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="d-flex" style="justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <h1>📘 Facebook objave</h1>
    <span style="color: #059669; font-size: 0.8rem;">✓ Zagorje.com</span>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?> mt-2"><?= e($message) ?></div>
<?php endif; ?>

<?php $flashMsg = getMessage(); if ($flashMsg): ?>
<div class="alert alert-<?= $flashMsg['type'] ?> mt-2"><?= e($flashMsg['text']) ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
    <!-- Nova objava -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Nova objava</h2>
        </div>
        <div class="card-body">
            <form method="POST">
                <?= csrfField() ?>

                <div class="form-group">
                    <label class="form-label" for="url">Link članka *</label>
                    <input type="url" id="url" name="url" class="form-control" placeholder="https://www.zagorje.com/clanak/..." required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="title">Naslov (za evidenciju)</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="Kratki naslov...">
                </div>

                <div class="form-group">
                    <label class="form-label" for="text">Tekst objave</label>
                    <div class="emoji-picker">
                        <?php
                        $emojiGroups = [
                            'Vijesti' => ['🔴', '🟠', '🟡', '🟢', '🔵', '⚫', '⚪', '🚨', '⚠️', '📢', '📣', '💥', '⚡', '🔥', '💯'],
                            'Oznake' => ['📍', '📌', '🎯', '✅', '❌', '⭕', '❗', '❓', '💡', '📝', '🔗', '🏷️', '🆕', '🆓', '🔝'],
                            'Mediji' => ['📰', '🗞️', '📸', '📷', '🎥', '🎬', '📺', '📻', '🎙️', '🎤', '📡', '💻', '📱', '🖥️', '⌨️'],
                            'Događaji' => ['🎉', '🎊', '🎭', '🎪', '🎨', '🎵', '🎶', '🎸', '🎹', '🎺', '🥁', '🎧', '🎫', '🎁', '🎈'],
                            'Sport' => ['⚽', '🏀', '🏈', '⚾', '🎾', '🏐', '🏉', '🎱', '🏓', '🏸', '🥊', '🚴', '🏃', '🏊', '🏆'],
                            'Priroda' => ['☀️', '🌤️', '⛅', '🌧️', '⛈️', '❄️', '🌊', '🌳', '🌲', '🌸', '🌺', '🍀', '🌈', '⭐', '🌙'],
                            'Ruke' => ['👉', '👆', '👇', '👈', '☝️', '👍', '👎', '👏', '🙌', '🤝', '✋', '🖐️', '✌️', '🤞', '💪'],
                            'Lica' => ['😀', '😃', '😊', '🥳', '😍', '🤩', '😎', '🤔', '😮', '😢', '😡', '🥺', '😱', '🤯', '😴'],
                            'Simboli' => ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '💔', '💕', '💖', '💗', '💝', '💘', '💞'],
                            'Hrana' => ['☕', '🍺', '🍷', '🥂', '🍕', '🍔', '🌭', '🥗', '🍰', '🎂', '🍎', '🍇', '🥐', '🍞', '🧀']
                        ];
                        foreach ($emojiGroups as $group => $emojis):
                        ?>
                        <div class="emoji-row">
                            <small><?= $group ?>:</small>
                            <?php foreach ($emojis as $emoji): ?>
                            <button type="button" class="emoji-btn" onclick="insertEmoji('<?= $emoji ?>')"><?= $emoji ?></button>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <textarea id="text" name="text" class="form-control" rows="2" placeholder="Tekst koji će se prikazati uz link..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Kada objaviti?</label>
                    <div style="display: flex; gap: 1rem; margin-bottom: 0.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="radio" name="action" value="now" checked onchange="toggleSchedule()"> Odmah
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="radio" name="action" value="schedule" onchange="toggleSchedule()"> Zakaži
                        </label>
                    </div>
                    <div id="scheduleFields" style="display: none; gap: 0.5rem;">
                        <input type="date" name="scheduled_date" class="form-control" style="width: auto;" value="<?= date('Y-m-d') ?>">
                        <input type="time" name="scheduled_time" class="form-control" style="width: auto;" value="<?= date('H:i', strtotime('+1 hour')) ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="background: #1877f2; width: 100%;">
                    📤 Objavi / Zakaži
                </button>
            </form>
        </div>
    </div>

    <!-- Zakazane objave -->
    <div class="card">
        <div class="card-header" style="background: #fef3c7;">
            <h2 class="card-title" style="color: #92400e;">📅 Zakazano (<?= count($scheduledPosts) ?>)</h2>
        </div>
        <div class="card-body" style="padding: 0; max-height: 400px; overflow-y: auto;">
            <?php if (empty($scheduledPosts)): ?>
                <p style="padding: 1rem; color: #6b7280; text-align: center;">Nema zakazanih objava</p>
            <?php else: ?>
                <?php foreach ($scheduledPosts as $i => $post): ?>
                <div style="padding: 0.75rem; border-bottom: 1px solid #e5e7eb; background: <?= $i % 2 ? '#f9fafb' : 'white' ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: start; gap: 0.5rem;">
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 600; font-size: 0.8rem; color: #92400e;">
                                ⏰ <?= date('d.m. H:i', strtotime($post['scheduled_at'])) ?>
                            </div>
                            <div style="font-size: 0.75rem; margin-top: 0.25rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= e($post['title'] ?: $post['url']) ?>
                            </div>
                            <?php if ($post['message']): ?>
                            <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;"><?= e(mb_substr($post['message'], 0, 50)) ?>...</div>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; gap: 0.25rem;">
                            <a href="?post_now=<?= $post['id'] ?>&token=<?= generateCSRFToken() ?>" class="btn btn-sm btn-success" title="Objavi odmah" onclick="return confirm('Objaviti odmah?')">▶</a>
                            <a href="?cancel=<?= $post['id'] ?>&token=<?= generateCSRFToken() ?>" class="btn btn-sm btn-danger" title="Otkaži" onclick="return confirm('Otkazati objavu?')">×</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Zadnje objave -->
<div class="card mt-2">
    <div class="card-header">
        <h2 class="card-title">📜 Zadnje objave</h2>
    </div>
    <div class="table-responsive">
        <table class="table" style="font-size: 0.75rem;">
            <thead>
                <tr>
                    <th style="width: 120px;">Datum</th>
                    <th>Objava</th>
                    <th style="width: 100px;">Status</th>
                    <th style="width: 100px;">Objavio</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentPosts)): ?>
                <tr><td colspan="4" style="text-align: center; color: #6b7280;">Nema objava</td></tr>
                <?php else: ?>
                <?php foreach ($recentPosts as $post): ?>
                <tr>
                    <td style="white-space: nowrap;"><?= date('d.m.Y. H:i', strtotime($post['posted_at'] ?? $post['created_at'])) ?></td>
                    <td>
                        <a href="<?= e($post['url']) ?>" target="_blank" style="text-decoration: none; color: inherit;">
                            <?= e($post['title'] ?: mb_substr($post['url'], 0, 60)) ?>
                        </a>
                        <?php if ($post['message']): ?>
                        <br><small style="color: #6b7280;"><?= e(mb_substr($post['message'], 0, 40)) ?>...</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($post['status'] === 'posted'): ?>
                        <span class="badge badge-success">✓ Objavljeno</span>
                        <?php else: ?>
                        <span class="badge badge-danger" title="<?= e($post['error_message']) ?>">✗ Greška</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($post['full_name']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.emoji-picker {
    margin-bottom: 0.5rem;
    padding: 0.5rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    max-height: 180px;
    overflow-y: auto;
}
.emoji-row {
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 2px;
}
.emoji-row small {
    color: #6b7280;
    font-size: 0.6rem;
    min-width: 50px;
}
.emoji-btn {
    width: 24px;
    height: 24px;
    border: none;
    border-radius: 4px;
    background: transparent;
    cursor: pointer;
    font-size: 0.85rem;
    padding: 0;
}
.emoji-btn:hover {
    background: #e5e7eb;
    transform: scale(1.2);
}
#scheduleFields {
    display: flex;
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

function toggleSchedule() {
    const isSchedule = document.querySelector('input[name="action"]:checked').value === 'schedule';
    document.getElementById('scheduleFields').style.display = isSchedule ? 'flex' : 'none';
}
</script>

<!-- Objave s Facebook stranice -->
<?php
$fbResult = getFacebookPosts(15, true);
$fbPosts = $fbResult['posts'] ?? [];
$fbError = $fbResult['error'] ?? null;
$fbRaw = $fbResult['raw'] ?? null;
$todayPosts = [];
$olderPosts = [];
$today = date('Y-m-d');

foreach ($fbPosts as $post) {
    $postDate = date('Y-m-d', strtotime($post['created_time']));
    if ($postDate === $today) {
        $todayPosts[] = $post;
    } else {
        $olderPosts[] = $post;
    }
}
?>

<div class="card mt-2">
    <div class="card-header" style="background: #1877f2; color: white;">
        <h2 class="card-title" style="color: white;">📘 Objavljeno na Facebook stranici</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <!-- DEBUG -->
        <div style="background: #fef3c7; padding: 0.5rem; font-size: 0.7rem; border-bottom: 1px solid #e5e7eb;">
            <strong>Debug:</strong> Objava: <?= count($fbPosts) ?> |
            <?php if ($fbError): ?>
            <span style="color: red;">Error: <?= e($fbError['message'] ?? print_r($fbError, true)) ?></span>
            <?php else: ?>
            <span style="color: green;">OK</span>
            <?php endif; ?>
            <details><summary>Raw</summary><pre style="font-size: 0.6rem;"><?= e(print_r($fbRaw, true)) ?></pre></details>
        </div>
        <!-- /DEBUG -->

        <?php if (!empty($todayPosts)): ?>
        <div style="background: #dcfce7; padding: 0.5rem 0.75rem; font-weight: 600; color: #166534; font-size: 0.8rem;">
            🟢 Danas (<?= count($todayPosts) ?>)
        </div>
        <?php endif; ?>

        <?php foreach ($todayPosts as $post): ?>
        <div style="padding: 0.75rem; border-bottom: 1px solid #e5e7eb; display: flex; gap: 0.75rem;">
            <?php if (!empty($post['full_picture'])): ?>
            <img src="<?= e($post['full_picture']) ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; flex-shrink: 0;">
            <?php endif; ?>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 0.75rem; color: #6b7280; margin-bottom: 0.25rem;">
                    <?= date('H:i', strtotime($post['created_time'])) ?>
                </div>
                <div style="font-size: 0.8rem; line-height: 1.3;">
                    <?= e(mb_substr($post['message'] ?? '(bez teksta)', 0, 150)) ?><?= mb_strlen($post['message'] ?? '') > 150 ? '...' : '' ?>
                </div>
                <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.5rem; display: flex; gap: 1rem; align-items: center;">
                    <?php
                    $likes = $post['likes']['summary']['total_count'] ?? 0;
                    $comments = $post['comments']['summary']['total_count'] ?? 0;
                    $shares = $post['shares']['count'] ?? 0;
                    ?>
                    <span>👍 <?= $likes ?></span>
                    <span>💬 <?= $comments ?></span>
                    <span>🔄 <?= $shares ?></span>
                    <a href="<?= e($post['permalink_url']) ?>" target="_blank" style="color: #1877f2;">↗ Otvori</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (!empty($olderPosts)): ?>
        <div style="background: #f3f4f6; padding: 0.5rem 0.75rem; font-weight: 600; color: #6b7280; font-size: 0.8rem;">
            📅 Ranije
        </div>
        <?php endif; ?>

        <?php foreach ($olderPosts as $post): ?>
        <div style="padding: 0.5rem 0.75rem; border-bottom: 1px solid #e5e7eb; display: flex; gap: 0.5rem; background: #fafafa;">
            <?php if (!empty($post['full_picture'])): ?>
            <img src="<?= e($post['full_picture']) ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; flex-shrink: 0;">
            <?php endif; ?>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 0.7rem; color: #9ca3af;">
                    <?= date('d.m. H:i', strtotime($post['created_time'])) ?>
                </div>
                <div style="font-size: 0.75rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <?= e(mb_substr($post['message'] ?? '(bez teksta)', 0, 80)) ?>
                </div>
                <div style="font-size: 0.65rem; margin-top: 0.25rem; display: flex; gap: 0.75rem; align-items: center;">
                    <?php
                    $likes = $post['likes']['summary']['total_count'] ?? 0;
                    $comments = $post['comments']['summary']['total_count'] ?? 0;
                    $shares = $post['shares']['count'] ?? 0;
                    ?>
                    <span style="color: #6b7280;">👍<?= $likes ?> 💬<?= $comments ?> 🔄<?= $shares ?></span>
                    <a href="<?= e($post['permalink_url']) ?>" target="_blank" style="color: #1877f2;">↗</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($fbPosts)): ?>
        <p style="padding: 1rem; text-align: center; color: #6b7280;">
            Nema objava
            <?php if ($fbError): ?>
            <br><small style="color: #dc2626;">Greška: <?= e($fbError['message'] ?? 'Nepoznata greška') ?></small>
            <?php endif; ?>
        </p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
