<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Niste prijavljeni']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Neispravan ID']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM ai_rewrites WHERE id = ?");
    $stmt->execute([$id]);
    $rewrite = $stmt->fetch();

    if (!$rewrite) {
        echo json_encode(['success' => false, 'error' => 'Prerada nije pronađena']);
        exit;
    }

    echo json_encode(['success' => true, 'rewrite' => $rewrite]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Greška baze podataka']);
}
