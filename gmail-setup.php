<?php
/**
 * Setup za Gmail integraciju - kreira tablice i prikazuje status
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'config/gmail.php';

requireLogin();

if (!isEditor()) {
    die('Samo urednici mogu pristupiti.');
}

$db = getDB();
$messages = [];

try {
    // Kreiraj tablicu partnera
    $db->exec("
        CREATE TABLE IF NOT EXISTS partners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            phone VARCHAR(50),
            company VARCHAR(255),
            notes TEXT,
            source ENUM('manual', 'gmail', 'facebook') DEFAULT 'manual',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $messages[] = ['type' => 'success', 'text' => 'Tablica partners kreirana'];

    // Kreiraj tablicu za OAuth tokene
    $db->exec("
        CREATE TABLE IF NOT EXISTS gmail_oauth_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            access_token TEXT,
            refresh_token TEXT NOT NULL,
            token_expires_at DATETIME,
            scopes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $messages[] = ['type' => 'success', 'text' => 'Tablica gmail_oauth_tokens kreirana'];

    // Kreiraj tablicu za threadove
    $db->exec("
        CREATE TABLE IF NOT EXISTS gmail_threads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            thread_id VARCHAR(100) NOT NULL UNIQUE,
            gmail_account_id INT NOT NULL,
            partner_id INT,
            subject VARCHAR(500),
            snippet TEXT,
            last_message_at DATETIME,
            unread_count INT DEFAULT 0,
            deleted TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_last_msg (last_message_at),
            INDEX idx_partner (partner_id),
            INDEX idx_account (gmail_account_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $messages[] = ['type' => 'success', 'text' => 'Tablica gmail_threads kreirana'];

    // Kreiraj tablicu za poruke
    $db->exec("
        CREATE TABLE IF NOT EXISTS gmail_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            thread_id VARCHAR(100) NOT NULL,
            message_id VARCHAR(100) NOT NULL UNIQUE,
            gmail_account_id INT NOT NULL,
            from_email VARCHAR(255),
            from_name VARCHAR(255),
            to_email TEXT,
            subject VARCHAR(500),
            body_text MEDIUMTEXT,
            body_html MEDIUMTEXT,
            sent_at DATETIME NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            is_inbound TINYINT(1) DEFAULT 1,
            has_attachments TINYINT(1) DEFAULT 0,
            labels TEXT,
            email_sent TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_thread (thread_id),
            INDEX idx_sent (sent_at),
            INDEX idx_read (is_read),
            INDEX idx_from (from_email),
            INDEX idx_account (gmail_account_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $messages[] = ['type' => 'success', 'text' => 'Tablica gmail_messages kreirana'];

    $success = true;
} catch (PDOException $e) {
    $messages[] = ['type' => 'error', 'text' => 'Greska: ' . $e->getMessage()];
    $success = false;
}

// Dohvati povezane racune
$accounts = [];
try {
    $accounts = $db->query("SELECT * FROM gmail_oauth_tokens ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    // Tablica mozda jos ne postoji
}

define('PAGE_TITLE', 'Setup - Gmail');
include 'includes/header.php';
?>

<div class="page-header">
    <h1><span style="color: #ea4335;">Gmail</span> integracija</h1>
</div>

<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; max-width: 700px;">
    <h3 style="margin-top: 0;">Status baze</h3>
    <?php foreach ($messages as $msg): ?>
    <p style="margin: 0.5rem 0; color: <?= $msg['type'] === 'success' ? '#059669' : '#dc2626' ?>;">
        <?= $msg['type'] === 'success' ? '&#10004;' : '&#10008;' ?>
        <?= e($msg['text']) ?>
    </p>
    <?php endforeach; ?>

    <?php if ($success): ?>
    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
        <h3>Povezani Gmail racuni</h3>

        <?php if (empty($accounts)): ?>
        <p style="color: #6b7280;">Nema povezanih racuna.</p>
        <?php else: ?>
        <ul style="list-style: none; padding: 0;">
            <?php foreach ($accounts as $acc): ?>
            <li style="padding: 0.75rem; background: #f3f4f6; margin: 0.5rem 0; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong><?= e($acc['email']) ?></strong>
                    <span style="color: #6b7280; font-size: 0.85rem;">
                        - povezan <?= date('d.m.Y. H:i', strtotime($acc['created_at'])) ?>
                    </span>
                </div>
                <span style="background: #059669; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">Aktivan</span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <div style="margin-top: 1.5rem;">
            <a href="<?= e(getGmailAuthUrl()) ?>" class="btn btn-primary" style="background: #ea4335; border-color: #ea4335;">
                + Povezi Gmail racun
            </a>

            <?php if (!empty($accounts)): ?>
            <a href="gmail-messages.php" class="btn btn-outline" style="margin-left: 0.5rem;">
                Idi na poruke
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 1rem; margin-top: 1rem; max-width: 700px;">
    <h4 style="margin-top: 0; color: #92400e;">Kako postaviti Gmail API</h4>
    <ol style="color: #78350f; margin-bottom: 0; line-height: 1.8;">
        <li>Idi na <a href="https://console.cloud.google.com" target="_blank" style="color: #1d4ed8;">Google Cloud Console</a></li>
        <li>Kreiraj novi projekt ili odaberi postojeci</li>
        <li>Omoguci <strong>Gmail API</strong> u API Library</li>
        <li>Idi na <strong>Credentials</strong> → <strong>Create Credentials</strong> → <strong>OAuth 2.0 Client IDs</strong></li>
        <li>Odaberi <strong>Web application</strong></li>
        <li>Dodaj <strong>OBA</strong> redirect URI-ja:
            <div style="background: #fef9c3; padding: 8px 12px; border-radius: 4px; margin: 8px 0; font-family: monospace; font-size: 0.85rem;">
                http://localhost/desk-crm/gmail-callback.php<br>
                https://zagorje-promocija.com/desk-crm/gmail-callback.php
            </div>
        </li>
        <li>Kopiraj Client ID i Client Secret u <code>config/gmail.php</code></li>
        <li>U OAuth consent screen dodaj svoj email kao test user</li>
    </ol>
</div>

<div style="background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px; padding: 1rem; margin-top: 1rem; max-width: 700px;">
    <h4 style="margin-top: 0; color: #0369a1;">Trenutna konfiguracija</h4>
    <p style="margin: 0.5rem 0; color: #0c4a6e;">
        <strong>Client ID:</strong>
        <?= GMAIL_CLIENT_ID === 'YOUR_CLIENT_ID.apps.googleusercontent.com' ? '<span style="color: #dc2626;">Nije konfigurirano</span>' : '<span style="color: #059669;">Konfigurirano</span>' ?>
    </p>
    <p style="margin: 0.5rem 0; color: #0c4a6e;">
        <strong>Redirect URI:</strong> <?= e(GMAIL_REDIRECT_URI) ?>
    </p>
    <p style="margin: 0.5rem 0; color: #0c4a6e;">
        <strong>Scope:</strong> <?= e(GMAIL_SCOPES) ?> (samo citanje)
    </p>
</div>

<?php include 'includes/footer.php'; ?>
