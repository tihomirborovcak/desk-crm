<?php
/**
 * Akcije za teme
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$isEditor = isEditor();

$action = $_REQUEST['action'] ?? '';
$id = intval($_REQUEST['id'] ?? 0);
$week = intval($_REQUEST['week'] ?? date('W'));
$year = intval($_REQUEST['year'] ?? date('Y'));

$redirect = "themes.php?week=$week&year=$year";

switch ($action) {
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWith($redirect, 'danger', 'Nevažeći zahtjev');
        }
        
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = $_POST['category'] ?? 'vijesti';
        $priority = $_POST['priority'] ?? 'normalna';
        $plannedDate = $_POST['planned_date'] ?? null;
        
        if (empty($title)) {
            redirectWith($redirect, 'danger', 'Naslov je obavezan');
        }
        
        $stmt = $db->prepare("
            INSERT INTO themes (title, description, category, priority, week_number, year, planned_date, proposed_by, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'predlozeno')
        ");
        $stmt->execute([$title, $description, $category, $priority, $week, $year, $plannedDate ?: null, $userId]);
        
        logActivity('theme_propose', 'theme', $db->lastInsertId());
        redirectWith($redirect, 'success', 'Tema je predložena i čeka odobrenje');
        break;
        
    case 'approve':
        if (!$isEditor) {
            redirectWith($redirect, 'danger', 'Nemate ovlasti');
        }
        
        $stmt = $db->prepare("UPDATE themes SET status = 'odobreno', approved_by = ? WHERE id = ? AND status = 'predlozeno'");
        $stmt->execute([$userId, $id]);
        
        logActivity('theme_approve', 'theme', $id);
        redirectWith($redirect, 'success', 'Tema je odobrena');
        break;
        
    case 'reject':
        if (!$isEditor) {
            redirectWith($redirect, 'danger', 'Nemate ovlasti');
        }
        
        $reason = trim($_POST['reason'] ?? $_GET['reason'] ?? '');
        
        $stmt = $db->prepare("UPDATE themes SET status = 'odbijeno', rejection_reason = ?, approved_by = ? WHERE id = ? AND status = 'predlozeno'");
        $stmt->execute([$reason, $userId, $id]);
        
        logActivity('theme_reject', 'theme', $id);
        redirectWith($redirect, 'info', 'Tema je odbijena');
        break;
        
    case 'assign':
        if (!$isEditor) {
            redirectWith($redirect, 'danger', 'Nemate ovlasti');
        }
        
        $assignTo = intval($_POST['assigned_to'] ?? 0);
        
        $stmt = $db->prepare("UPDATE themes SET assigned_to = ?, status = 'u_izradi' WHERE id = ?");
        $stmt->execute([$assignTo ?: null, $id]);
        
        logActivity('theme_assign', 'theme', $id);
        redirectWith("theme-edit.php?id=$id", 'success', 'Tema je dodijeljena');
        break;
        
    case 'start':
        // Novinar uzima temu
        $stmt = $db->prepare("UPDATE themes SET assigned_to = ?, status = 'u_izradi' WHERE id = ? AND status = 'odobreno'");
        $stmt->execute([$userId, $id]);
        
        logActivity('theme_start', 'theme', $id);
        redirectWith($redirect, 'success', 'Počeli ste raditi na temi');
        break;
        
    case 'complete':
        $stmt = $db->prepare("UPDATE themes SET status = 'zavrseno' WHERE id = ? AND (assigned_to = ? OR ?)", );
        $stmt->execute([$id, $userId, $isEditor ? 1 : 0]);
        
        logActivity('theme_complete', 'theme', $id);
        redirectWith($redirect, 'success', 'Tema je označena kao završena');
        break;
        
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWith($redirect, 'danger', 'Nevažeći zahtjev');
        }
        
        // Dohvati postojeću temu
        $stmt = $db->prepare("SELECT * FROM themes WHERE id = ?");
        $stmt->execute([$id]);
        $theme = $stmt->fetch();
        
        if (!$theme) {
            redirectWith($redirect, 'danger', 'Tema nije pronađena');
        }
        
        // Samo autor ili urednik može uređivati
        if ($theme['proposed_by'] != $userId && !$isEditor) {
            redirectWith($redirect, 'danger', 'Nemate ovlasti za uređivanje');
        }
        
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = $_POST['category'] ?? 'vijesti';
        $priority = $_POST['priority'] ?? 'normalna';
        $plannedDate = $_POST['planned_date'] ?? null;
        $status = $_POST['status'] ?? $theme['status'];
        $assignedTo = intval($_POST['assigned_to'] ?? 0);
        
        // Samo urednik može mijenjati status i dodjelu
        if (!$isEditor) {
            $status = $theme['status'];
            $assignedTo = $theme['assigned_to'];
        }
        
        $stmt = $db->prepare("
            UPDATE themes SET 
                title = ?, description = ?, category = ?, priority = ?, 
                planned_date = ?, status = ?, assigned_to = ?
            WHERE id = ?
        ");
        $stmt->execute([$title, $description, $category, $priority, $plannedDate ?: null, $status, $assignedTo ?: null, $id]);
        
        logActivity('theme_update', 'theme', $id);
        redirectWith("theme-edit.php?id=$id", 'success', 'Tema je ažurirana');
        break;
        
    case 'comment':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirectWith("theme-edit.php?id=$id", 'danger', 'Nevažeći zahtjev');
        }
        
        $comment = trim($_POST['comment'] ?? '');
        
        if (empty($comment)) {
            redirectWith("theme-edit.php?id=$id", 'danger', 'Komentar je obavezan');
        }
        
        $stmt = $db->prepare("INSERT INTO theme_comments (theme_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$id, $userId, $comment]);
        
        redirectWith("theme-edit.php?id=$id", 'success', 'Komentar je dodan');
        break;
        
    case 'delete':
        if (!$isEditor) {
            redirectWith($redirect, 'danger', 'Nemate ovlasti');
        }

        // Umjesto brisanja, postavi status na odbijeno
        $stmt = $db->prepare("UPDATE themes SET status = 'odbijeno', rejection_reason = 'Obrisano', approved_by = ? WHERE id = ?");
        $stmt->execute([$userId, $id]);

        logActivity('theme_delete', 'theme', $id);
        redirectWith($redirect, 'success', 'Tema je premještena u odbijene');
        break;
        
    default:
        redirectWith($redirect, 'danger', 'Nepoznata akcija');
}
