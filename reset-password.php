<?php
/**
 * Reset lozinke - OBRIŠI NAKON KORIŠTENJA!
 */

require_once 'config/database.php';

$db = getDB();

// Nova lozinka
$newPassword = 'admin123';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

// Resetiraj sve korisnike na istu lozinku
$stmt = $db->prepare("UPDATE users SET password = ?");
$stmt->execute([$hash]);

echo "Lozinke resetirane!<br>";
echo "Username: admin<br>";
echo "Password: admin123<br><br>";

// Prikaži sve korisnike
$stmt = $db->query("SELECT id, username, full_name, role FROM users");
$users = $stmt->fetchAll();

echo "<h3>Svi korisnici:</h3>";
foreach ($users as $u) {
    echo "- {$u['username']} ({$u['full_name']}) - {$u['role']}<br>";
}

echo "<br><strong style='color:red;'>⚠️ OBRIŠI OVAJ FAJL NAKON KORIŠTENJA!</strong>";
