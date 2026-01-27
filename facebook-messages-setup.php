<?php
/**
 * Setup za Facebook poruke
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

if (!isEditor()) {
    die('Samo urednici mogu pristupiti.');
}

$db = getDB();
$messages = [];

try {
    // Tablica za poruke
    $db->exec("
        CREATE TABLE IF NOT EXISTS facebook_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            conversation_id VARCHAR(100) NOT NULL,
            message_id VARCHAR(100) NOT NULL UNIQUE,
            sender_id VARCHAR(100) NOT NULL,
            sender_name VARCHAR(255),
            message_text TEXT,
            sent_at DATETIME NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            is_from_page TINYINT(1) DEFAULT 0,
            email_sent TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_conversation (conversation_id),
            INDEX idx_sent (sent_at),
            INDEX idx_read (is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $messages[] = "Tablica facebook_messages kreirana";

    // Tablica za konverzacije
    $db->exec("
        CREATE TABLE IF NOT EXISTS facebook_conversations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            conversation_id VARCHAR(100) NOT NULL UNIQUE,
            participant_id VARCHAR(100),
            participant_name VARCHAR(255),
            last_message_at DATETIME,
            unread_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_last_msg (last_message_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $messages[] = "Tablica facebook_conversations kreirana";

    $success = true;
} catch (PDOException $e) {
    $messages[] = "Greska: " . $e->getMessage();
    $success = false;
}

define('PAGE_TITLE', 'Setup - Facebook poruke');
include 'includes/header.php';
?>

<div class="page-header">
    <h1>Setup - Facebook poruke</h1>
</div>

<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; max-width: 600px;">
    <?php foreach ($messages as $msg): ?>
    <p style="margin: 0.5rem 0; color: #059669;">
        <?= e($msg) ?>
    </p>
    <?php endforeach; ?>

    <?php if ($success): ?>
    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
        <p style="color: #059669; font-weight: 500;">Setup uspjesno zavrsen!</p>
        <a href="facebook-messages.php" class="btn btn-primary" style="margin-top: 0.5rem;">Idi na poruke</a>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
