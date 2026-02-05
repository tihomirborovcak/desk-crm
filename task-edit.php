<?php
/**
 * Uređivanje / Kreiranje taska
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

// Samo admini mogu pristupiti taskovima
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$db = getDB();
$userId = $_SESSION['user_id'];

$id = intval($_GET['id'] ?? 0);
$task = null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$id]);
    $task = $stmt->fetch();

    if (!$task) {
        redirectWith('tasks.php', 'danger', 'Task nije pronađen');
    }

    // Dohvati assignee-e za ovaj task
    $stmt = $db->prepare("SELECT user_id FROM task_assignees WHERE task_id = ?");
    $stmt->execute([$id]);
    $taskAssignees = $stmt->fetchAll(PDO::FETCH_COLUMN);

    define('PAGE_TITLE', 'Uredi task');
} else {
    $taskAssignees = [$userId]; // Kreator je automatski assignee
    define('PAGE_TITLE', 'Novi task');
}

// Obrada forme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        redirectWith($_SERVER['REQUEST_URI'], 'danger', 'Nevažeći sigurnosni token');
    }
    
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $assignees = $_POST['assignees'] ?? [];
    $priority = $_POST['priority'] ?? 'normal';
    $status = $_POST['status'] ?? 'pending';
    $dueDate = $_POST['due_date'] ?? null;
    $dueTime = $_POST['due_time'] ?? null;

    // Kreator mora uvijek biti među assignee-ima
    if (!in_array($userId, $assignees)) {
        $assignees[] = $userId;
    }
    
    $errors = [];
    
    if (empty($title)) {
        $errors[] = 'Naslov je obavezan';
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            if ($id) {
                $stmt = $db->prepare("
                    UPDATE tasks SET
                        title = ?, description = ?,
                        priority = ?, status = ?, due_date = ?, due_time = ?,
                        completed_at = CASE WHEN ? = 'done' AND status != 'done' THEN NOW() ELSE completed_at END
                    WHERE id = ?
                ");
                $stmt->execute([$title, $description, $priority, $status, $dueDate ?: null, $dueTime ?: null, $status, $id]);

                // Ažuriraj assignee-e
                $db->prepare("DELETE FROM task_assignees WHERE task_id = ?")->execute([$id]);
                $insertStmt = $db->prepare("INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?)");
                foreach ($assignees as $uid) {
                    $insertStmt->execute([$id, $uid]);
                }

                $db->commit();
                logActivity('task_update', 'task', $id);
                redirectWith("task-edit.php?id=$id", 'success', 'Task je spremljen');
            } else {
                $stmt = $db->prepare("
                    INSERT INTO tasks (title, description, created_by, priority, status, due_date, due_time)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $description, $userId, $priority, $status, $dueDate ?: null, $dueTime ?: null]);

                $newId = $db->lastInsertId();

                // Dodaj assignee-e
                $insertStmt = $db->prepare("INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?)");
                foreach ($assignees as $uid) {
                    $insertStmt->execute([$newId, $uid]);
                }

                $db->commit();
                logActivity('task_create', 'task', $newId);
                redirectWith('tasks.php', 'success', 'Task je kreiran');
            }
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Greška: ' . $e->getMessage();
        }
    }
}

// Komentari
$comments = [];
if ($id) {
    $stmt = $db->prepare("
        SELECT tc.*, u.full_name 
        FROM task_comments tc 
        JOIN users u ON tc.user_id = u.id 
        WHERE tc.task_id = ? 
        ORDER BY tc.created_at DESC
    ");
    $stmt->execute([$id]);
    $comments = $stmt->fetchAll();
}

// Dodaj komentar
if ($id && isset($_POST['add_comment'])) {
    $comment = trim($_POST['comment'] ?? '');
    if ($comment) {
        $stmt = $db->prepare("INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$id, $userId, $comment]);
        header("Location: task-edit.php?id=$id");
        exit;
    }
}

$users = getUsers();

include 'includes/header.php';
?>

<div class="d-flex" style="justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <h1><?= $id ? 'Uredi task' : 'Novi task' ?></h1>
    <a href="tasks.php" class="btn btn-outline">← Natrag</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mt-2">
    <?php foreach ($errors as $error): ?>
    <div><?= e($error) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" class="mt-2">
    <?= csrfField() ?>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Podaci o tasku</h2>
            <?php if ($task): ?>
            <span class="badge badge-<?= taskStatusColor($task['status']) ?>">
                <?= translateTaskStatus($task['status']) ?>
            </span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label" for="title">Naslov *</label>
                <input type="text" id="title" name="title" class="form-control" 
                       value="<?= e($task['title'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="description">Opis</label>
                <textarea id="description" name="description" class="form-control" rows="4"
                          placeholder="Detaljniji opis zadatka..."><?= e($task['description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Dodijeljeno</label>
                <div class="assignees-checkboxes" style="display: flex; flex-wrap: wrap; gap: 0.75rem; padding: 0.5rem; background: var(--gray-50); border-radius: var(--radius);">
                    <?php foreach ($users as $u):
                        $isCreator = ($task['created_by'] ?? $userId) == $u['id'];
                        $isChecked = in_array($u['id'], $taskAssignees);
                    ?>
                    <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer; <?= $isCreator ? 'font-weight: 600;' : '' ?>">
                        <input type="checkbox" name="assignees[]" value="<?= $u['id'] ?>"
                               <?= $isChecked ? 'checked' : '' ?>
                               <?= $isCreator ? 'checked disabled' : '' ?>>
                        <?= e($u['full_name']) ?>
                        <?php if ($isCreator): ?>
                        <span style="font-size: 0.7rem; color: var(--gray-500);">(kreator)</span>
                        <input type="hidden" name="assignees[]" value="<?= $u['id'] ?>">
                        <?php endif; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <small class="text-muted">Kreator taska je uvijek uključen</small>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="priority">Prioritet</label>
                <select id="priority" name="priority" class="form-control">
                    <option value="low" <?= ($task['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Nizak</option>
                    <option value="normal" <?= ($task['priority'] ?? 'normal') === 'normal' ? 'selected' : '' ?>>Normalan</option>
                    <option value="high" <?= ($task['priority'] ?? '') === 'high' ? 'selected' : '' ?>>Visok</option>
                    <option value="urgent" <?= ($task['priority'] ?? '') === 'urgent' ? 'selected' : '' ?>>Hitno!</option>
                </select>
            </div>
            
            <?php if ($id): ?>
            <div class="form-group">
                <label class="form-label" for="status">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="pending" <?= ($task['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Čeka</option>
                    <option value="in_progress" <?= ($task['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>U tijeku</option>
                    <option value="done" <?= ($task['status'] ?? '') === 'done' ? 'selected' : '' ?>>Završeno</option>
                    <option value="cancelled" <?= ($task['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Otkazano</option>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="d-flex gap-1" style="flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 150px;">
                    <label class="form-label" for="due_date">Rok (datum)</label>
                    <input type="date" id="due_date" name="due_date" class="form-control"
                           value="<?= e($task['due_date'] ?? '') ?>">
                </div>
                <div class="form-group" style="flex: 1; min-width: 150px;">
                    <label class="form-label" for="due_time">Vrijeme</label>
                    <input type="time" id="due_time" name="due_time" class="form-control"
                           value="<?= e($task['due_time'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mt-2">
        <div class="card-body">
            <div class="d-flex gap-1 flex-wrap">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Spremi
                </button>
                
                <?php if ($id && $task['status'] !== 'done'): ?>
                <a href="task-action.php?action=done&id=<?= $id ?>" class="btn btn-success">
                    ✓ Označi kao završeno
                </a>
                <?php endif; ?>
                
                <?php if ($id): ?>
                <a href="task-action.php?action=delete&id=<?= $id ?>" 
                   class="btn btn-danger"
                   data-confirm="Obrisati ovaj task?">
                    Obriši
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>

<?php if ($id): ?>
<!-- Komentari -->
<div class="card mt-2">
    <div class="card-header">
        <h2 class="card-title">💬 Komentari</h2>
    </div>
    <div class="card-body">
        <form method="POST" class="mb-2">
            <?= csrfField() ?>
            <div class="d-flex gap-1">
                <input type="text" name="comment" class="form-control" placeholder="Dodaj komentar..." required>
                <button type="submit" name="add_comment" class="btn btn-primary">Pošalji</button>
            </div>
        </form>
        
        <?php if (empty($comments)): ?>
        <p class="text-muted text-center">Nema komentara</p>
        <?php else: ?>
        <div class="comments-list">
            <?php foreach ($comments as $comment): ?>
            <div class="comment-item">
                <div class="comment-header">
                    <strong><?= e($comment['full_name']) ?></strong>
                    <span class="text-muted text-sm"><?= timeAgo($comment['created_at']) ?></span>
                </div>
                <div class="comment-body"><?= e($comment['comment']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.comments-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.comment-item {
    padding: 0.75rem;
    background: var(--gray-50);
    border-radius: var(--radius);
}
.comment-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
}
.comment-body {
    color: var(--gray-700);
}
</style>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
