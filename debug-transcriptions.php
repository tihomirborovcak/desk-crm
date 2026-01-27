<?php
/**
 * Debug script za provjeru transkripcija u bazi
 */
require_once 'config/database.php';

$db = getDB();

// Dodaj audio_path stupac ako ne postoji
try {
    $db->exec("ALTER TABLE transcriptions ADD COLUMN audio_path VARCHAR(500) AFTER audio_filename");
    echo "<p style='color: green;'>Dodan stupac audio_path!</p>";
} catch (PDOException $e) {
    // Stupac već postoji, ignoriraj
}
$stmt = $db->query("SELECT id, title, LENGTH(transcript) as transcript_len, LENGTH(article) as article_len, audio_filename, created_at FROM transcriptions ORDER BY id DESC LIMIT 5");
$rows = $stmt->fetchAll();

echo "<pre>\n";
echo "=== TRANSCRIPTIONS IN DATABASE ===\n\n";

if (empty($rows)) {
    echo "No transcriptions found.\n";
} else {
    foreach ($rows as $row) {
        echo "ID: {$row['id']}\n";
        echo "Title: {$row['title']}\n";
        echo "Transcript length: {$row['transcript_len']} bytes\n";
        echo "Article length: {$row['article_len']} bytes\n";
        echo "Audio: {$row['audio_filename']}\n";
        echo "Created: {$row['created_at']}\n";
        echo "---\n";
    }
}
echo "</pre>";
