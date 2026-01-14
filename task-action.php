<?php
/**
 * Akcije nad taskovima
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];

$action = $_GET['action'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (!$id) {
    redirectWith('tasks.php', 'danger', 'Nevažeći ID');
}

$stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->execute([$id]);
$task = $stmt->fetch();

if (!$task) {
    redirectWith('tasks.php', 'danger', 'Task nije pronađen');
}

switch ($action) {
    case 'start':
        $stmt = $db->prepare("UPDATE tasks SET status = 'in_progress' WHERE id = ?");
        $stmt->execute([$id]);
        logActivity('task_start', 'task', $id);
        redirectWith('tasks.php', 'success', 'Task je započet');
        break;
        
    case 'done':
        $stmt = $db->prepare("UPDATE tasks SET status = 'done', completed_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        logActivity('task_done', 'task', $id);
        redirectWith('tasks.php', 'success', 'Task je završen');
        break;
        
    case 'cancel':
        $stmt = $db->prepare("UPDATE tasks SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$id]);
        logActivity('task_cancel', 'task', $id);
        redirectWith('tasks.php', 'success', 'Task je otkazan');
        break;
        
    case 'delete':
        $stmt = $db->prepare("DELETE FROM task_comments WHERE task_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$id]);
        
        logActivity('task_delete', 'task', $id, $task['title']);
        redirectWith('tasks.php', 'success', 'Task je obrisan');
        break;
        
    default:
        redirectWith('tasks.php', 'danger', 'Nepoznata akcija');
}
