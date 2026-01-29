<?php
/**
 * Gmail Inbox - brzi pregled iz lokalne baze
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'GmailClient.php';

requireLogin();

if (!isEditor()) {
    header('Location: dashboard.php');
    exit;
}

define('PAGE_TITLE', 'Gmail');

$db = getDB();

// Provjeri tablicu
try {
    $db->query("SELECT 1 FROM gmail_messages_cache LIMIT 1");
} catch (PDOException $e) {
    // Tablica ne postoji - pokreni sync
    echo "<p>Pokreni sync: <code>php cron/sync-gmail.php full</code></p>";
    exit;
}

// Pretraživanje
$search = $_GET['q'] ?? '';
$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (subject LIKE ? OR from_name LIKE ? OR from_email LIKE ? OR snippet LIKE ?)";
    $searchTerm = "%{$search}%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

// Dohvati poruke iz baze
$stmt = $db->prepare("
    SELECT * FROM gmail_messages_cache
    WHERE {$where}
    ORDER BY received_at DESC
    LIMIT 50
");
$stmt->execute($params);
$messages = $stmt->fetchAll();

// Odabrana poruka
$selectedId = $_GET['view'] ?? null;
$selectedMessage = null;
$attachments = [];
if ($selectedId) {
    $stmt = $db->prepare("SELECT * FROM gmail_messages_cache WHERE message_id = ?");
    $stmt->execute([$selectedId]);
    $selectedMessage = $stmt->fetch();

    // Dohvati attachmente (ako tablica postoji)
    try {
        $stmt = $db->prepare("SELECT * FROM gmail_attachments WHERE message_id = ?");
        $stmt->execute([$selectedId]);
        $attachments = $stmt->fetchAll();
    } catch (PDOException $e) {
        $attachments = [];
    }

    // Označi kao pročitano u bazi
    if ($selectedMessage && $selectedMessage['is_unread']) {
        $db->prepare("UPDATE gmail_messages_cache SET is_unread = 0 WHERE message_id = ?")->execute([$selectedId]);
        $selectedMessage['is_unread'] = 0;

        // Označi i na Gmail-u
        try {
            $gmail = new GmailClient();
            if ($gmail->isAuthorized()) {
                $gmail->markAsRead($selectedId);
            }
        } catch (Exception $e) {
            // Ignoraj greške
        }
    }
}

// Statistika
$stats = $db->query("
    SELECT
        COUNT(*) as total,
        SUM(is_unread) as unread
    FROM gmail_messages_cache
")->fetch();

include 'includes/header.php';
?>

<div class="d-flex" style="justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <h1>
        <span style="color: #ea4335;">Gmail</span> Inbox
        <?php if ($stats['unread'] > 0): ?>
        <span class="badge badge-danger"><?= $stats['unread'] ?></span>
        <?php endif; ?>
    </h1>
    <div style="display: flex; gap: 0.5rem; align-items: center;">
        <form method="get" style="display: flex; gap: 0.5rem;">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Pretraži..." class="form-control" style="width: 200px;">
            <button type="submit" class="btn btn-outline">Traži</button>
            <?php if ($search): ?>
            <a href="gmail-inbox.php" class="btn btn-outline">×</a>
            <?php endif; ?>
        </form>
        <a href="gmail-dashboard.php" class="btn btn-outline">Live API</a>
    </div>
</div>

<p style="color: #6b7280; font-size: 0.85rem; margin: 0.5rem 0;">
    <?= number_format($stats['total']) ?> poruka u bazi
    • <a href="gmail-inbox.php" style="color: #ea4335;">Osvježi</a>
</p>

<div style="display: grid; grid-template-columns: 380px 1fr; gap: 1rem; margin-top: 1rem; min-height: 500px;">
    <!-- Lista poruka -->
    <div class="card" style="overflow: hidden;">
        <div class="card-header" style="background: #ea4335; color: white;">
            <h2 class="card-title" style="color: white; margin: 0;">Poruke</h2>
        </div>
        <div style="overflow-y: auto; max-height: 600px;">
            <?php if (empty($messages)): ?>
            <p style="padding: 1rem; color: #6b7280; text-align: center;">
                Nema poruka. Pokreni: <code>php cron/sync-gmail.php full</code>
            </p>
            <?php else: ?>
            <?php foreach ($messages as $msg): ?>
            <a href="?view=<?= urlencode($msg['message_id']) ?><?= $search ? '&q='.urlencode($search) : '' ?>"
               class="conv-item <?= $selectedId === $msg['message_id'] ? 'active' : '' ?> <?= $msg['is_unread'] ? 'unread' : '' ?>">
                <div class="conv-avatar gmail">
                    <?= mb_strtoupper(mb_substr($msg['from_name'] ?: $msg['from_email'], 0, 1)) ?>
                </div>
                <div class="conv-info">
                    <div class="conv-name">
                        <?= e($msg['from_name'] ?: $msg['from_email']) ?>
                        <?php if ($msg['is_unread']): ?>
                        <span class="badge badge-danger" style="font-size: 0.6rem;">•</span>
                        <?php endif; ?>
                    </div>
                    <div class="conv-subject">
                        <?php if (!empty($msg['has_attachments'])): ?><span title="Ima privitke">📎</span> <?php endif; ?>
                        <?= e(mb_substr($msg['subject'], 0, 50)) ?>
                    </div>
                    <div class="conv-preview"><?= e(mb_substr($msg['snippet'], 0, 60)) ?>...</div>
                    <div class="conv-time"><?= $msg['received_at'] ? date('d.m. H:i', strtotime($msg['received_at'])) : '' ?></div>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sadržaj poruke -->
    <div class="card">
        <?php if ($selectedMessage): ?>
        <div class="card-header">
            <h2 class="card-title" style="margin: 0; word-break: break-word;"><?= e($selectedMessage['subject']) ?></h2>
        </div>
        <div class="card-body" style="padding: 1.5rem;">
            <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                <p style="margin: 0.25rem 0;"><strong>Od:</strong> <?= e($selectedMessage['from_name']) ?> &lt;<?= e($selectedMessage['from_email']) ?>&gt;</p>
                <p style="margin: 0.25rem 0;"><strong>Za:</strong> <?= e($selectedMessage['to_email']) ?></p>
                <p style="margin: 0.25rem 0; color: #6b7280;"><strong>Datum:</strong> <?= date('d.m.Y. H:i', strtotime($selectedMessage['received_at'])) ?></p>
            </div>
            <?php if (!empty($attachments)): ?>
            <div style="margin-bottom: 1rem; padding: 1rem; background: #f9fafb; border-radius: 6px;">
                <strong style="display: block; margin-bottom: 0.5rem;">Privici (<?= count($attachments) ?>):</strong>
                <?php foreach ($attachments as $att): ?>
                <a href="gmail-attachment.php?message=<?= urlencode($selectedMessage['message_id']) ?>&id=<?= urlencode($att['attachment_id']) ?>&name=<?= urlencode($att['filename']) ?>"
                   target="_blank"
                   style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; background: white; border: 1px solid #e5e7eb; border-radius: 4px; margin: 0.25rem; text-decoration: none; color: #374151; font-size: 0.85rem;">
                    <span style="font-size: 1.1rem;">📎</span>
                    <?= e($att['filename']) ?>
                    <span style="color: #9ca3af; font-size: 0.75rem;">(<?= round($att['size'] / 1024) ?> KB)</span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div style="line-height: 1.6; max-height: 400px; overflow-y: auto;">
                <?php if (!empty($selectedMessage['body_text'])): ?>
                <pre style="white-space: pre-wrap; font-family: inherit; margin: 0;"><?= e($selectedMessage['body_text']) ?></pre>
                <?php elseif (!empty($selectedMessage['body_html'])): ?>
                <div class="email-html"><?= $selectedMessage['body_html'] ?></div>
                <?php elseif (!empty($selectedMessage['snippet'])): ?>
                <p style="color: #6b7280;"><?= e($selectedMessage['snippet']) ?></p>
                <?php else: ?>
                <p style="color: #9ca3af;">(Nema sadržaja)</p>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="card-body" style="display: flex; align-items: center; justify-content: center; min-height: 400px;">
            <p style="color: #9ca3af;">Odaberi poruku za pregled</p>
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
.conv-item:hover { background: #f3f4f6; }
.conv-item.active { background: #fef2f2; border-left: 3px solid #ea4335; }
.conv-item.unread { background: #fef2f2; }
.conv-item.unread .conv-name, .conv-item.unread .conv-subject { font-weight: 600; }
.conv-avatar {
    width: 40px; height: 40px;
    background: #6b7280; color: white;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 600; flex-shrink: 0;
}
.conv-avatar.gmail { background: #ea4335; }
.conv-info { flex: 1; min-width: 0; }
.conv-name { font-weight: 500; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; }
.conv-subject { font-size: 0.85rem; color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.conv-preview { font-size: 0.8rem; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.conv-time { font-size: 0.7rem; color: #9ca3af; }
.email-html { max-width: 100%; overflow-x: auto; }
.email-html img { max-width: 100%; height: auto; }
.email-html table { max-width: 100%; }
</style>

<?php include 'includes/footer.php'; ?>
