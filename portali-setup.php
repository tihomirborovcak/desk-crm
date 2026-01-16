<?php
/**
 * Setup za praćenje portala
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
    // Tablica portala
    $db->exec("
        CREATE TABLE IF NOT EXISTS portali (
            id INT AUTO_INCREMENT PRIMARY KEY,
            naziv VARCHAR(100) NOT NULL,
            url VARCHAR(255) NOT NULL,
            rss_url VARCHAR(255),
            aktivan TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");
    $messages[] = "✓ Tablica portali kreirana";

    // Tablica najčitanijih članaka
    $db->exec("
        CREATE TABLE IF NOT EXISTS portal_najcitanije (
            id INT AUTO_INCREMENT PRIMARY KEY,
            portal_id INT NOT NULL,
            pozicija INT NOT NULL,
            naslov VARCHAR(500) NOT NULL,
            url VARCHAR(500) NOT NULL,
            objavljeno_at DATETIME NULL,
            dohvaceno_at DATETIME NOT NULL,
            FOREIGN KEY (portal_id) REFERENCES portali(id) ON DELETE CASCADE,
            INDEX idx_portal_datum (portal_id, dohvaceno_at)
        ) ENGINE=InnoDB
    ");
    $messages[] = "✓ Tablica portal_najcitanije kreirana";

    // Dodaj stupac objavljeno_at ako ne postoji (za postojeće instalacije)
    try {
        $db->exec("ALTER TABLE portal_najcitanije ADD COLUMN objavljeno_at DATETIME NULL AFTER url");
        $messages[] = "✓ Dodan stupac objavljeno_at";
    } catch (PDOException $e) {
        // Stupac već postoji, ignoriraj
    }

    // Dodaj portale ako ne postoje
    $count = $db->query("SELECT COUNT(*) FROM portali")->fetchColumn();
    if ($count == 0) {
        $db->exec("
            INSERT INTO portali (naziv, url, rss_url) VALUES
            ('Index.hr', 'https://www.index.hr', 'https://www.index.hr/rss'),
            ('24sata', 'https://www.24sata.hr', 'https://www.24sata.hr/feeds/aktualno.xml'),
            ('Jutarnji list', 'https://www.jutarnji.hr', 'https://www.jutarnji.hr/feed'),
            ('Večernji list', 'https://www.vecernji.hr', 'https://www.vecernji.hr/feeds/latest')
        ");
        $messages[] = "✓ Portali dodani";
    }

    $success = true;
} catch (PDOException $e) {
    $messages[] = "✗ Greška: " . $e->getMessage();
    $success = false;
}

define('PAGE_TITLE', 'Setup - Portali');
include 'includes/header.php';
?>

<div class="page-header">
    <h1>Setup - Praćenje portala</h1>
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
        <a href="portali.php" class="btn btn-primary" style="margin-top: 0.5rem;">Idi na praćenje portala</a>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
