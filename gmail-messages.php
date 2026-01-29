<?php
/**
 * Gmail poruke - Inbox s kategorizacijom po partnerima
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'config/gmail.php';

requireLogin();

if (!isEditor()) {
    header('Location: dashboard.php');
    exit;
}

define('PAGE_TITLE', 'Gmail poruke');

$db = getDB();

// Provjeri postoje li tablice
try {
    $db->query("SELECT 1 FROM gmail_messages LIMIT 1");
} catch (PDOException $e) {
    header('Location: gmail-setup.php');
    exit;
}

// Provjeri ima li povezanih racuna
$accounts = $db->query("SELECT * FROM gmail_oauth_tokens ORDER BY email")->fetchAll();
if (empty($accounts)) {
    header('Location: gmail-setup.php');
    exit;
}

// Rucno osvjezi poruke
if (isset($_GET['refresh'])) {
    define('CRON_RUN', true);
    include 'cron/fetch-gmail-messages.php';
    setMessage('success', 'Poruke osvjezene');
    header('Location: gmail-messages.php' . (isset($_GET['partner']) ? '?partner=' . $_GET['partner'] : ''));
    exit;
}

// Oznaci thread kao procitan
if (isset($_GET['read']) && isset($_GET['token']) && verifyCSRFToken($_GET['token'])) {
    $threadId = $_GET['read'];
    $db->prepare("UPDATE gmail_messages SET is_read = 1 WHERE thread_id = ?")->execute([$threadId]);
    $db->prepare("UPDATE gmail_threads SET unread_count = 0 WHERE thread_id = ?")->execute([$threadId]);
    header('Location: gmail-messages.php?thread=' . urlencode($threadId));
    exit;
}

// Obrisi thread (soft delete)
if (isset($_GET['delete']) && isset($_GET['token']) && verifyCSRFToken($_GET['token'])) {
    $threadId = $_GET['delete'];
    $db->prepare("UPDATE gmail_threads SET deleted = 1 WHERE thread_id = ?")->execute([$threadId]);
    setMessage('success', 'Nit obrisana');
    header('Location: gmail-messages.php');
    exit;
}

// Filter po partneru
$partnerId = $_GET['partner'] ?? null;
$partnerFilter = '';
$partnerParams = [];

if ($partnerId) {
    $partnerFilter = 'AND t.partner_id = ?';
    $partnerParams[] = $partnerId;
}

// Dohvati threadove s info o partneru
$sql = "
    SELECT t.*,
           p.name as partner_name,
           p.email as partner_email,
           p.company as partner_company
    FROM gmail_threads t
    LEFT JOIN partners p ON t.partner_id = p.id
    WHERE (t.deleted = 0 OR t.deleted IS NULL)
    $partnerFilter
    ORDER BY t.last_message_at DESC
    LIMIT 50
";

$stmt = $db->prepare($sql);
$stmt->execute($partnerParams);
$threads = $stmt->fetchAll();

// Odabrani thread
$selectedThread = $_GET['thread'] ?? null;
$messages = [];
$threadInfo = null;

if ($selectedThread) {
    $stmt = $db->prepare("
        SELECT * FROM gmail_messages
        WHERE thread_id = ?
        ORDER BY sent_at ASC
    ");
    $stmt->execute([$selectedThread]);
    $messages = $stmt->fetchAll();

    // Oznaci kao procitano
    $db->prepare("UPDATE gmail_messages SET is_read = 1 WHERE thread_id = ?")->execute([$selectedThread]);
    $db->prepare("UPDATE gmail_threads SET unread_count = 0 WHERE thread_id = ?")->execute([$selectedThread]);

    // Dohvati info o threadu
    foreach ($threads as $t) {
        if ($t['thread_id'] === $selectedThread) {
            $threadInfo = $t;
            break;
        }
    }

    // Ako thread nije u listi (npr. obrisan ili drugi filter), dohvati direktno
    if (!$threadInfo) {
        $stmt = $db->prepare("
            SELECT t.*, p.name as partner_name, p.email as partner_email, p.company as partner_company
            FROM gmail_threads t
            LEFT JOIN partners p ON t.partner_id = p.id
            WHERE t.thread_id = ?
        ");
        $stmt->execute([$selectedThread]);
        $threadInfo = $stmt->fetch();
    }
}

// Ukupno neprocitanih
$totalUnread = $db->query("SELECT SUM(unread_count) FROM gmail_threads WHERE deleted = 0 OR deleted IS NULL")->fetchColumn() ?: 0;

// Dohvati sve partnere za filter dropdown
$partners = $db->query("
    SELECT p.*, COUNT(t.id) as thread_count
    FROM partners p
    JOIN gmail_threads t ON t.partner_id = p.id
    WHERE t.deleted = 0 OR t.deleted IS NULL
    GROUP BY p.id
    ORDER BY p.name
")->fetchAll();

include 'includes/header.php';
?>

<div class="d-flex" style="justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <h1>
        <span style="color: #ea4335;">Gmail</span> Poruke
        <?php if ($totalUnread > 0): ?>
        <span class="badge badge-danger"><?= $totalUnread ?></span>
        <?php endif; ?>
    </h1>
    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        <select onchange="location.href='gmail-messages.php'+(this.value ? '?partner='+this.value : '')" style="padding: 0.5rem; border-radius: 4px; border: 1px solid #e5e7eb; min-width: 180px;">
            <option value="">Svi partneri</option>
            <?php foreach ($partners as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $partnerId == $p['id'] ? 'selected' : '' ?>>
                <?= e($p['name']) ?> (<?= $p['thread_count'] ?>)
            </option>
            <?php endforeach; ?>
        </select>
        <a href="?refresh=1<?= $partnerId ? '&partner='.$partnerId : '' ?>" class="btn btn-outline">Osvjezi</a>
        <a href="gmail-setup.php" class="btn btn-outline">Postavke</a>
    </div>
</div>

<?php $flashMsg = getMessage(); if ($flashMsg): ?>
<div class="alert alert-<?= $flashMsg['type'] ?> mt-2"><?= e($flashMsg['text']) ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 320px 1fr; gap: 1rem; margin-top: 1rem; min-height: 500px;">
    <!-- Lista threadova -->
    <div class="card" style="overflow: hidden;">
        <div class="card-header" style="background: #ea4335; color: white;">
            <h2 class="card-title" style="color: white; margin: 0;">
                Inbox
                <?php if ($partnerId): ?>
                <a href="gmail-messages.php" style="color: white; font-size: 0.75rem; margin-left: 0.5rem;">(ocisti filter)</a>
                <?php endif; ?>
            </h2>
        </div>
        <div style="overflow-y: auto; max-height: 600px;">
            <?php if (empty($threads)): ?>
            <p style="padding: 1rem; color: #6b7280; text-align: center;">Nema poruka</p>
            <?php else: ?>
            <?php foreach ($threads as $thread): ?>
            <a href="?thread=<?= urlencode($thread['thread_id']) ?><?= $partnerId ? '&partner='.$partnerId : '' ?>"
               class="conv-item <?= $selectedThread === $thread['thread_id'] ? 'active' : '' ?> <?= $thread['unread_count'] > 0 ? 'unread' : '' ?>">
                <div class="conv-avatar gmail">
                    <?= mb_strtoupper(mb_substr($thread['partner_name'] ?? $thread['subject'] ?? 'G', 0, 1)) ?>
                </div>
                <div class="conv-info">
                    <div class="conv-name">
                        <?= e($thread['partner_name'] ?? 'Nepoznato') ?>
                        <?php if ($thread['unread_count'] > 0): ?>
                        <span class="badge badge-danger" style="font-size: 0.6rem;"><?= $thread['unread_count'] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="conv-subject">
                        <?= e(mb_substr($thread['subject'] ?? '', 0, 45)) ?><?= mb_strlen($thread['subject'] ?? '') > 45 ? '...' : '' ?>
                    </div>
                    <div class="conv-preview">
                        <?= e(mb_substr($thread['snippet'] ?? '', 0, 50)) ?>...
                    </div>
                    <div class="conv-time"><?= $thread['last_message_at'] ? date('d.m. H:i', strtotime($thread['last_message_at'])) : '' ?></div>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Poruke -->
    <div class="card">
        <?php if ($selectedThread && !empty($messages)): ?>
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
            <div style="flex: 1; min-width: 0;">
                <h2 class="card-title" style="margin: 0; word-break: break-word;"><?= e($threadInfo['subject'] ?? 'Konverzacija') ?></h2>
                <?php if (!empty($threadInfo['partner_name'])): ?>
                <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.85rem;">
                    Partner: <strong><?= e($threadInfo['partner_name']) ?></strong>
                    <?php if (!empty($threadInfo['partner_email'])): ?>
                    &lt;<?= e($threadInfo['partner_email']) ?>&gt;
                    <?php endif; ?>
                    <?php if (!empty($threadInfo['partner_company'])): ?>
                    - <?= e($threadInfo['partner_company']) ?>
                    <?php endif; ?>
                </p>
                <?php endif; ?>
            </div>
            <a href="?delete=<?= urlencode($selectedThread) ?>&token=<?= generateCSRFToken() ?>"
               class="btn btn-sm btn-danger"
               onclick="return confirm('Obrisati ovu nit?')"
               title="Obrisi nit"
               style="flex-shrink: 0;">Obrisi</a>
        </div>
        <div class="messages-container">
            <?php foreach ($messages as $msg): ?>
            <div class="message <?= $msg['is_inbound'] ? 'received' : 'sent' ?>">
                <div class="message-header">
                    <strong><?= e($msg['from_name'] ?: $msg['from_email']) ?></strong>
                    <span style="color: #9ca3af; font-size: 0.7rem;">
                        &rarr; <?= e($msg['to_email']) ?>
                    </span>
                </div>
                <div class="message-bubble">
                    <?php if (!empty($msg['body_html'])): ?>
                    <div class="email-body"><?= $msg['body_html'] ?></div>
                    <?php elseif (!empty($msg['body_text'])): ?>
                    <div class="email-body-text"><?= nl2br(e($msg['body_text'])) ?></div>
                    <?php else: ?>
                    <em style="opacity: 0.6;">(prazan sadrzaj)</em>
                    <?php endif; ?>
                    <?php if ($msg['has_attachments']): ?>
                    <p style="margin-top: 0.5rem; color: #6b7280; font-size: 0.85rem;">
                        <span style="background: #f3f4f6; padding: 2px 6px; border-radius: 3px;">Sadrzi privitke</span>
                    </p>
                    <?php endif; ?>
                </div>
                <div class="message-time">
                    <?= date('d.m.Y. H:i', strtotime($msg['sent_at'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="card-footer" style="background: #f9fafb; padding: 1rem; text-align: center;">
            <p style="color: #9ca3af; font-size: 0.8rem; margin: 0;">
                Ovo je read-only pregled. Za odgovor otvori
                <a href="https://mail.google.com" target="_blank" style="color: #ea4335;">Gmail</a>
            </p>
        </div>
        <?php else: ?>
        <div class="card-body" style="display: flex; align-items: center; justify-content: center; min-height: 400px;">
            <div style="text-align: center;">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
                <p style="color: #9ca3af; margin-top: 1rem;">Odaberi nit za pregled</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.conv-item {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e5e7eb;
    text-decoration: none;
    color: inherit;
    transition: background 0.15s;
}
.conv-item:hover {
    background: #f3f4f6;
}
.conv-item.active {
    background: #fef2f2;
    border-left: 3px solid #ea4335;
}
.conv-item.unread {
    background: #fef2f2;
}
.conv-item.unread .conv-name {
    font-weight: 700;
}
.conv-item.unread .conv-subject {
    font-weight: 600;
}
.conv-avatar {
    width: 40px;
    height: 40px;
    background: #6b7280;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    flex-shrink: 0;
}
.conv-avatar.gmail {
    background: #ea4335;
}
.conv-info {
    flex: 1;
    min-width: 0;
}
.conv-name {
    font-weight: 500;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.conv-subject {
    font-size: 0.85rem;
    color: #374151;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 2px;
}
.conv-preview {
    font-size: 0.8rem;
    color: #6b7280;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.conv-time {
    font-size: 0.7rem;
    color: #9ca3af;
    margin-top: 2px;
}
.messages-container {
    padding: 1rem;
    max-height: 500px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.message {
    display: flex;
    flex-direction: column;
    max-width: 85%;
}
.message.received {
    align-self: flex-start;
}
.message.sent {
    align-self: flex-end;
}
.message-header {
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 4px;
    padding: 0 0.5rem;
}
.message.sent .message-header {
    text-align: right;
}
.message-bubble {
    padding: 1rem;
    border-radius: 12px;
    font-size: 0.9rem;
    line-height: 1.5;
}
.message.received .message-bubble {
    background: #f3f4f6;
    color: #1f2937;
    border-left: 3px solid #ea4335;
}
.message.sent .message-bubble {
    background: #e8f0fe;
    color: #1f2937;
    border-left: 3px solid #4285f4;
}
.message-time {
    font-size: 0.7rem;
    color: #9ca3af;
    margin-top: 4px;
    padding: 0 0.5rem;
}
.message.sent .message-time {
    text-align: right;
}
.email-body {
    max-height: 400px;
    overflow-y: auto;
    overflow-x: hidden;
}
.email-body img {
    max-width: 100%;
    height: auto;
}
.email-body-text {
    white-space: pre-wrap;
    word-break: break-word;
}

@media (max-width: 768px) {
    div[style*="grid-template-columns: 320px"] {
        grid-template-columns: 1fr !important;
    }
    .messages-container {
        max-height: 400px;
    }
    .message {
        max-width: 95%;
    }
}
</style>

<?php if ($selectedThread): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var container = document.querySelector('.messages-container');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
