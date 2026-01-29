<?php
/**
 * Gmail Dashboard - Pregled mailova
 */

require_once __DIR__ . '/GmailClient.php';

session_start();

$gmail = new GmailClient();

// Provjeri autorizaciju
if (!$gmail->isAuthorized()) {
    header('Location: gmail-auth.php');
    exit;
}

// Handle actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        $messageId = $_POST['message_id'] ?? '';
        
        switch ($action) {
            case 'mark_read':
                $gmail->markAsRead($messageId);
                $message = 'Message marked as read.';
                break;
            case 'mark_unread':
                $gmail->markAsUnread($messageId);
                $message = 'Message marked as unread.';
                break;
            case 'archive':
                $gmail->archiveMessage($messageId);
                $message = 'Message archived.';
                break;
            case 'trash':
                $gmail->trashMessage($messageId);
                $message = 'Message moved to trash.';
                break;
            case 'send':
                $to = $_POST['to'] ?? '';
                $subject = $_POST['subject'] ?? '';
                $body = $_POST['body'] ?? '';
                if ($to && $subject) {
                    $gmail->sendMessage($to, $subject, $body);
                    $message = 'Email sent successfully!';
                }
                break;
            case 'logout':
                $gmail->logout();
                session_destroy();
                header('Location: gmail-auth.php');
                exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Dohvati podatke
$selectedMessage = null;
try {
    $profile = $gmail->getProfile();
    $email = $profile['emailAddress'] ?? 'Unknown';
    $messagesCount = $profile['messagesTotal'] ?? 0;

    // Ako je odabrana poruka za prikaz
    if (isset($_GET['view'])) {
        $msgData = $gmail->getMessage($_GET['view'], 'full');
        $selectedMessage = $gmail->parseMessage($msgData);
        // Označi kao pročitano
        if ($selectedMessage['isUnread']) {
            $gmail->markAsRead($_GET['view']);
            $selectedMessage['isUnread'] = false;
        }
    }

    $query = $_GET['q'] ?? '';
    $params = ['maxResults' => 20];
    if ($query) {
        $params['q'] = $query;
    }

    // Dohvati poruke - samo headers, bez body-a
    $params['maxResults'] = 10;
    $list = $gmail->listMessages($params);
    $messages = [];
    foreach ($list['messages'] ?? [] as $msg) {
        $metadata = $gmail->getMessage($msg['id'], 'metadata');
        $parsed = $gmail->parseMessage($metadata);
        $messages[] = $parsed;
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    $messages = [];
    $email = 'Error';
}

?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gmail Dashboard - Desk CRM</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        header { background: #1a73e8; color: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 1.5rem; }
        header .user-info { display: flex; align-items: center; gap: 15px; }
        
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-primary { background: #1a73e8; color: white; }
        .btn-secondary { background: #e0e0e0; color: #333; }
        .btn-danger { background: #d93025; color: white; }
        .btn:hover { opacity: 0.9; }
        
        .alert { padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #e6f4ea; color: #137333; }
        .alert-error { background: #fce8e6; color: #c5221f; }
        
        .card { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { padding: 16px; border-bottom: 1px solid #e0e0e0; font-weight: 600; }
        .card-body { padding: 16px; }
        
        .search-form { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-form input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        
        .message-list { list-style: none; }
        .message-item { padding: 16px; border-bottom: 1px solid #e0e0e0; display: flex; gap: 16px; }
        .message-item:last-child { border-bottom: none; }
        .message-item:hover { background: #f8f9fa; }
        .message-item.unread { background: #e8f0fe; }
        .message-item.unread .message-subject { font-weight: 600; }
        
        .message-checkbox { width: 20px; }
        .message-content { flex: 1; min-width: 0; }
        .message-from { font-weight: 500; color: #333; margin-bottom: 4px; }
        .message-subject { color: #333; margin-bottom: 4px; }
        .message-snippet { color: #666; font-size: 0.9em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .message-date { color: #666; font-size: 0.85em; white-space: nowrap; }
        .message-actions { display: flex; gap: 8px; }
        .message-actions form { display: inline; }
        .message-actions button { background: none; border: none; cursor: pointer; padding: 4px 8px; color: #666; font-size: 12px; }
        .message-actions button:hover { color: #1a73e8; }
        
        .compose-form { display: grid; gap: 12px; }
        .compose-form input, .compose-form textarea { padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; }
        .compose-form textarea { min-height: 150px; resize: vertical; }
        
        .stats { display: flex; gap: 20px; margin-bottom: 20px; }
        .stat-item { background: white; padding: 16px 24px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-value { font-size: 1.5rem; font-weight: 600; color: #1a73e8; }
        .stat-label { color: #666; font-size: 0.9em; }
        
        .tabs { display: flex; border-bottom: 1px solid #ddd; margin-bottom: 20px; }
        .tab { padding: 12px 24px; cursor: pointer; border-bottom: 2px solid transparent; }
        .tab.active { color: #1a73e8; border-bottom-color: #1a73e8; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📧 Gmail Dashboard</h1>
            <div class="user-info">
                <span><?= htmlspecialchars($email) ?></span>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="btn btn-secondary">Logout</button>
                </form>
            </div>
        </header>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-item">
                <div class="stat-value"><?= number_format($messagesCount) ?></div>
                <div class="stat-label">Total Messages</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= count(array_filter($messages, fn($m) => $m['isUnread'])) ?></div>
                <div class="stat-label">Unread</div>
            </div>
        </div>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab('inbox')">Inbox</div>
            <div class="tab" onclick="showTab('compose')">Compose</div>
        </div>
        
        <div id="inbox" class="tab-content active">
            <form method="get" class="search-form">
                <input type="text" name="q" placeholder="Search emails..." value="<?= htmlspecialchars($query) ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($query): ?>
                    <a href="gmail-dashboard.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
            
            <div class="card">
                <div class="card-header">
                    Inbox <?php if ($query): ?> - Search: "<?= htmlspecialchars($query) ?>"<?php endif; ?>
                </div>
                <ul class="message-list">
                    <?php if ($selectedMessage): ?>
                        <li class="message-item selected-message">
                            <div style="width: 100%;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e0e0e0;">
                                    <a href="gmail-dashboard.php" class="btn btn-secondary">&larr; Natrag</a>
                                    <div class="message-actions">
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="archive">
                                            <input type="hidden" name="message_id" value="<?= $selectedMessage['id'] ?>">
                                            <button type="submit" class="btn btn-secondary">Arhiviraj</button>
                                        </form>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Obrisati?')">
                                            <input type="hidden" name="action" value="trash">
                                            <input type="hidden" name="message_id" value="<?= $selectedMessage['id'] ?>">
                                            <button type="submit" class="btn btn-danger">Obriši</button>
                                        </form>
                                    </div>
                                </div>
                                <h2 style="margin-bottom: 10px;"><?= htmlspecialchars($selectedMessage['subject']) ?></h2>
                                <div style="color: #666; margin-bottom: 5px;"><strong>Od:</strong> <?= htmlspecialchars($selectedMessage['from']) ?></div>
                                <div style="color: #666; margin-bottom: 5px;"><strong>Za:</strong> <?= htmlspecialchars($selectedMessage['to']) ?></div>
                                <div style="color: #666; margin-bottom: 15px;"><strong>Datum:</strong> <?= $selectedMessage['timestamp'] ? date('d.m.Y. H:i', $selectedMessage['timestamp']) : '' ?></div>
                                <div style="background: #f9f9f9; padding: 20px; border-radius: 4px; white-space: pre-wrap; line-height: 1.6;"><?= nl2br(htmlspecialchars($selectedMessage['body'])) ?></div>
                            </div>
                        </li>
                    <?php elseif (empty($messages)): ?>
                        <li class="message-item">
                            <div class="message-content">No messages found.</div>
                        </li>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <li class="message-item <?= $msg['isUnread'] ? 'unread' : '' ?>" onclick="window.location='?view=<?= $msg['id'] ?>'" style="cursor: pointer;">
                                <div class="message-content">
                                    <div class="message-from"><?= htmlspecialchars($msg['from']) ?></div>
                                    <div class="message-subject"><?= htmlspecialchars($msg['subject']) ?></div>
                                    <div class="message-snippet"><?= htmlspecialchars($msg['snippet']) ?></div>
                                </div>
                                <div class="message-date">
                                    <?= $msg['timestamp'] ? date('d.m. H:i', $msg['timestamp']) : '' ?>
                                </div>
                                <div class="message-actions" onclick="event.stopPropagation();">
                                    <?php if ($msg['isUnread']): ?>
                                        <form method="post">
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                            <button type="submit" title="Mark as read">✓</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post">
                                            <input type="hidden" name="action" value="mark_unread">
                                            <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                            <button type="submit" title="Mark as unread">●</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post">
                                        <input type="hidden" name="action" value="archive">
                                        <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                        <button type="submit" title="Archive">📥</button>
                                    </form>
                                    <form method="post" onsubmit="return confirm('Move to trash?')">
                                        <input type="hidden" name="action" value="trash">
                                        <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                        <button type="submit" title="Delete">🗑</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <div id="compose" class="tab-content">
            <div class="card">
                <div class="card-header">New Email</div>
                <div class="card-body">
                    <form method="post" class="compose-form">
                        <input type="hidden" name="action" value="send">
                        <input type="email" name="to" placeholder="To" required>
                        <input type="text" name="subject" placeholder="Subject" required>
                        <textarea name="body" placeholder="Message..."></textarea>
                        <div>
                            <button type="submit" class="btn btn-primary">Send Email</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabId) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelector(`.tab[onclick*="${tabId}"]`).classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }
    </script>
</body>
</html>
