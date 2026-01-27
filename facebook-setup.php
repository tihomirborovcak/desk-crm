<?php
/**
 * Setup za Facebook objave
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
    // Tablica planiranih objava
    $db->exec("
        CREATE TABLE IF NOT EXISTS facebook_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            url VARCHAR(500) NOT NULL,
            title VARCHAR(500),
            message TEXT,
            scheduled_at DATETIME NOT NULL,
            posted_at DATETIME NULL,
            post_id VARCHAR(100) NULL,
            status ENUM('scheduled', 'posted', 'failed', 'cancelled') DEFAULT 'scheduled',
            error_message TEXT NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_scheduled (scheduled_at, status),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $messages[] = "✓ Tablica facebook_posts kreirana";

    $success = true;
} catch (PDOException $e) {
    $messages[] = "✗ Greška: " . $e->getMessage();
    $success = false;
}

define('PAGE_TITLE', 'Setup - Facebook');
include 'includes/header.php';
?>

<div class="page-header">
    <h1>Setup - Facebook objave</h1>
</div>

<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; max-width: 600px;">
    <?php foreach ($messages as $msg): ?>
    <p style="margin: 0.5rem 0; <?= strpos($msg, '✗') !== false ? 'color: #dc2626;' : 'color: #059669;' ?>">
        <?= e($msg) ?>
    </p>
    <?php endforeach; ?>

    <?php if ($success): ?>
    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
        <p style="color: #059669; font-weight: 500;">Setup uspješno završen!</p>
        <a href="facebook-post.php" class="btn btn-primary" style="margin-top: 0.5rem;">Idi na Facebook objave</a>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
