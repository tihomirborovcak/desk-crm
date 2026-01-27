<?php
/**
 * Facebook poruke - Inbox
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'config/facebook.php';

requireLogin();

if (!isEditor()) {
    header('Location: dashboard.php');
    exit;
}

define('PAGE_TITLE', 'Facebook poruke');

$db = getDB();

// Provjeri postoji li tablica
try {
    $db->query("SELECT 1 FROM facebook_messages LIMIT 1");
} catch (PDOException $e) {
    header('Location: facebook-messages-setup.php');
    exit;
}

// Označi poruke kao pročitane
if (isset($_GET['read']) && isset($_GET['token']) && verifyCSRFToken($_GET['token'])) {
    $convId = $_GET['read'];
    $db->prepare("UPDATE facebook_messages SET is_read = 1 WHERE conversation_id = ?")->execute([$convId]);
    $db->prepare("UPDATE facebook_conversations SET unread_count = 0 WHERE conversation_id = ?")->execute([$convId]);
    header('Location: facebook-messages.php?conv=' . urlencode($convId));
    exit;
}

// Ručno osvježi poruke
if (isset($_GET['refresh'])) {
    define('CRON_RUN', true);
    include 'cron/fetch-facebook-messages.php';
    setMessage('success', 'Poruke osvježene');
    header('Location: facebook-messages.php');
    exit;
}

// Dohvati konverzacije
$conversations = $db->query("
    SELECT c.*,
           (SELECT message_text FROM facebook_messages m WHERE m.conversation_id = c.conversation_id ORDER BY sent_at DESC LIMIT 1) as last_message
    FROM facebook_conversations c
    ORDER BY c.last_message_at DESC
    LIMIT 50
")->fetchAll();

// Odabrana konverzacija
$selectedConv = $_GET['conv'] ?? null;
$messages = [];

if ($selectedConv) {
    $stmt = $db->prepare("
        SELECT * FROM facebook_messages
        WHERE conversation_id = ?
        ORDER BY sent_at ASC
    ");
    $stmt->execute([$selectedConv]);
    $messages = $stmt->fetchAll();

    // Označi kao pročitano
    $db->prepare("UPDATE facebook_messages SET is_read = 1 WHERE conversation_id = ?")->execute([$selectedConv]);
    $db->prepare("UPDATE facebook_conversations SET unread_count = 0 WHERE conversation_id = ?")->execute([$selectedConv]);
}

// Ukupno nepročitanih
$totalUnread = $db->query("SELECT SUM(unread_count) FROM facebook_conversations")->fetchColumn() ?: 0;

include 'includes/header.php';
?>

<div class="d-flex" style="justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <h1>
        <span style="color: #1877f2;">FB</span> Poruke
        <?php if ($totalUnread > 0): ?>
        <span class="badge badge-danger"><?= $totalUnread ?></span>
        <?php endif; ?>
    </h1>
    <a href="?refresh=1" class="btn btn-outline">Osvježi</a>
</div>

<?php $flashMsg = getMessage(); if ($flashMsg): ?>
<div class="alert alert-<?= $flashMsg['type'] ?> mt-2"><?= e($flashMsg['text']) ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 300px 1fr; gap: 1rem; margin-top: 1rem; min-height: 500px;">
    <!-- Lista konverzacija -->
    <div class="card" style="overflow: hidden;">
        <div class="card-header" style="background: #1877f2; color: white;">
            <h2 class="card-title" style="color: white;">Inbox</h2>
        </div>
        <div style="overflow-y: auto; max-height: 600px;">
            <?php if (empty($conversations)): ?>
            <p style="padding: 1rem; color: #6b7280; text-align: center;">Nema poruka</p>
            <?php else: ?>
            <?php foreach ($conversations as $conv): ?>
            <a href="?conv=<?= urlencode($conv['conversation_id']) ?>"
               class="conv-item <?= $selectedConv === $conv['conversation_id'] ? 'active' : '' ?> <?= $conv['unread_count'] > 0 ? 'unread' : '' ?>">
                <div class="conv-avatar">
                    <?= mb_strtoupper(mb_substr($conv['participant_name'], 0, 1)) ?>
                </div>
                <div class="conv-info">
                    <div class="conv-name">
                        <?= e($conv['participant_name']) ?>
                        <?php if ($conv['unread_count'] > 0): ?>
                        <span class="badge badge-danger" style="font-size: 0.6rem;"><?= $conv['unread_count'] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="conv-preview"><?= e(mb_substr($conv['last_message'] ?? '', 0, 40)) ?>...</div>
                    <div class="conv-time"><?= $conv['last_message_at'] ? date('d.m. H:i', strtotime($conv['last_message_at'])) : '' ?></div>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Poruke -->
    <div class="card">
        <?php if ($selectedConv && !empty($messages)): ?>
        <?php
        $convInfo = null;
        foreach ($conversations as $c) {
            if ($c['conversation_id'] === $selectedConv) {
                $convInfo = $c;
                break;
            }
        }
        ?>
        <div class="card-header">
            <h2 class="card-title"><?= e($convInfo['participant_name'] ?? 'Konverzacija') ?></h2>
        </div>
        <div class="messages-container">
            <?php foreach ($messages as $msg): ?>
            <div class="message <?= $msg['is_from_page'] ? 'sent' : 'received' ?>">
                <div class="message-bubble">
                    <?= nl2br(e($msg['message_text'])) ?>
                </div>
                <div class="message-time">
                    <?= date('d.m. H:i', strtotime($msg['sent_at'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="card-footer" style="background: #f9fafb; padding: 1rem;">
            <p style="color: #6b7280; font-size: 0.85rem; text-align: center;">
                Za odgovor koristi <a href="https://www.facebook.com/messages/t/<?= e($convInfo['participant_id'] ?? '') ?>" target="_blank" style="color: #1877f2;">Facebook Messenger</a>
            </p>
        </div>
        <?php else: ?>
        <div class="card-body" style="display: flex; align-items: center; justify-content: center; min-height: 400px;">
            <p style="color: #9ca3af;">Odaberi konverzaciju</p>
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
    background: #dbeafe;
    border-left: 3px solid #1877f2;
}
.conv-item.unread {
    background: #eff6ff;
}
.conv-item.unread .conv-name {
    font-weight: 700;
}
.conv-avatar {
    width: 40px;
    height: 40px;
    background: #1877f2;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    flex-shrink: 0;
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
}
.messages-container {
    padding: 1rem;
    max-height: 500px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.message {
    display: flex;
    flex-direction: column;
    max-width: 70%;
}
.message.received {
    align-self: flex-start;
}
.message.sent {
    align-self: flex-end;
}
.message-bubble {
    padding: 0.75rem 1rem;
    border-radius: 18px;
    font-size: 0.9rem;
    line-height: 1.4;
}
.message.received .message-bubble {
    background: #e4e6eb;
    color: #050505;
    border-bottom-left-radius: 4px;
}
.message.sent .message-bubble {
    background: #1877f2;
    color: white;
    border-bottom-right-radius: 4px;
}
.message-time {
    font-size: 0.7rem;
    color: #9ca3af;
    margin-top: 0.25rem;
    padding: 0 0.5rem;
}
.message.sent .message-time {
    text-align: right;
}
</style>

<?php include 'includes/footer.php'; ?>
