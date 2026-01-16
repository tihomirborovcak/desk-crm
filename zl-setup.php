<?php
/**
 * Setup za Zagorski list tablice
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = getDB();
$messages = [];

// Kreiraj tablice
try {
    // Rubrike
    $db->exec("
        CREATE TABLE IF NOT EXISTS zl_sections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            sort_order INT DEFAULT 0,
            active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");
    $messages[] = "✓ Tablica zl_sections kreirana";

    // Provjeri ima li rubrika
    $count = $db->query("SELECT COUNT(*) FROM zl_sections")->fetchColumn();
    if ($count == 0) {
        $db->exec("
            INSERT INTO zl_sections (name, slug, sort_order) VALUES
            ('Naslovnica', 'naslovnica', 1),
            ('Aktualno', 'aktualno', 2),
            ('Županija', 'zupanija', 3),
            ('Panorama', 'panorama', 4),
            ('Sport', 'sport', 5),
            ('Špajza', 'spajza', 6),
            ('Vodič', 'vodic', 7),
            ('Prilog', 'prilog', 8),
            ('Mala burza', 'mala-burza', 9),
            ('Nekretnine', 'nekretnine', 10),
            ('Zagorski oglasnik', 'zagorski-oglasnik', 11),
            ('Zadnja', 'zadnja', 12),
            ('Ostalo', 'ostalo', 99)
        ");
        $messages[] = "✓ Rubrike dodane";
    }

    // Izdanja
    $db->exec("
        CREATE TABLE IF NOT EXISTS zl_issues (
            id INT AUTO_INCREMENT PRIMARY KEY,
            issue_number INT NOT NULL,
            year INT NOT NULL,
            publish_date DATE NOT NULL,
            status ENUM('priprema', 'u_izradi', 'zatvoren') DEFAULT 'priprema',
            notes TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_issue (issue_number, year),
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB
    ");
    $messages[] = "✓ Tablica zl_issues kreirana";

    // Članci
    $db->exec("
        CREATE TABLE IF NOT EXISTS zl_articles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            issue_id INT,
            section_id INT,
            supertitle VARCHAR(500),
            title VARCHAR(500) NOT NULL,
            subtitle TEXT,
            content LONGTEXT,
            author_id INT,
            author_text VARCHAR(255),
            page_number INT,
            char_count INT DEFAULT 0,
            word_count INT DEFAULT 0,
            status ENUM('nacrt', 'za_pregled', 'odobreno', 'odbijeno', 'objavljeno') DEFAULT 'nacrt',
            reviewed_by INT,
            reviewed_at DATETIME,
            review_notes TEXT,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (issue_id) REFERENCES zl_issues(id) ON DELETE SET NULL,
            FOREIGN KEY (section_id) REFERENCES zl_sections(id) ON DELETE SET NULL,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_issue (issue_id),
            INDEX idx_status (status),
            INDEX idx_section (section_id)
        ) ENGINE=InnoDB
    ");
    $messages[] = "✓ Tablica zl_articles kreirana";

    // Slike
    $db->exec("
        CREATE TABLE IF NOT EXISTS zl_article_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            article_id INT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255),
            filepath VARCHAR(500),
            caption TEXT,
            credit VARCHAR(255),
            is_main TINYINT(1) DEFAULT 0,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (article_id) REFERENCES zl_articles(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    $messages[] = "✓ Tablica zl_article_images kreirana";

    $success = true;
} catch (PDOException $e) {
    $messages[] = "✗ Greška: " . $e->getMessage();
    $success = false;
}

define('PAGE_TITLE', 'Setup ZL');
include 'includes/header.php';
?>

<div class="page-header">
    <h1>Setup - Zagorski list</h1>
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
        <a href="zl-clanci.php" class="btn btn-primary" style="margin-top: 0.5rem;">Idi na članke</a>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
