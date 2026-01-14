<?php
/**
 * Lista taskova
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

define('PAGE_TITLE', 'Taskovi');

$db = getDB();
$userId = $_SESSION['user_id'];
$isEditorRole = isEditor();

// Filteri
$status = $_GET['status'] ?? '';
$assigned = $_GET['assigned'] ?? '';
$priority = $_GET['priority'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// Gradnja upita
$where = [];
$params = [];

if ($status) {
    $where[] = "t.status = ?";
    $params[] = $status;
}

if ($assigned) {
    $where[] = "t.assigned_to = ?";
    $params[] = $assigned;
}

if ($priority) {
    $where[] = "t.priority = ?";
    $params[] = $priority;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Ukupan broj
$stmt = $db->prepare("SELECT COUNT(*) FROM tasks t $whereClause");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Dohvati taskove
$offset = ($page - 1) * $perPage;
$sql = "
    SELECT t.*, 
           ua.full_name as assigned_name,
           uc.full_name as creator_name
    FROM tasks t 
    LEFT JOIN users ua ON t.assigned_to = ua.id 
    LEFT JOIN users uc ON t.created_by = uc.id
    $whereClause
    ORDER BY 
        CASE t.status WHEN 'in_progress' THEN 1 WHEN 'pending' THEN 2 ELSE 3 END,
        CASE t.priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END,
        t.due_date ASC,
        t.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

$users = getUsers();

include 'includes/header.php';
?>

<div class="d-flex" style="justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <h1>Taskovi</h1>
    <a href="task-edit.php" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Novi task
    </a>
</div>

<!-- Brzi filteri -->
<div class="quick-filters mt-2">
    <a href="tasks.php" class="quick-filter <?= !$status && !$assigned ? 'active' : '' ?>">Svi</a>
    <a href="tasks.php?assigned=<?= $userId ?>" class="quick-filter <?= $assigned == $userId ? 'active' : '' ?>">Moji</a>
    <a href="tasks.php?status=pending" class="quick-filter <?= $status === 'pending' ? 'active' : '' ?>">Čekaju</a>
    <a href="tasks.php?status=in_progress" class="quick-filter <?= $status === 'in_progress' ? 'active' : '' ?>">U tijeku</a>
    <a href="tasks.php?status=done" class="quick-filter <?= $status === 'done' ? 'active' : '' ?>">Završeno</a>
</div>

<!-- Filteri -->
<div class="card mt-2">
    <div class="card-body">
        <form method="GET" class="d-flex flex-wrap gap-1">
            <select name="status" class="form-control" style="width: auto; min-width: 120px;">
                <option value="">Svi statusi</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Čeka</option>
                <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>U tijeku</option>
                <option value="done" <?= $status === 'done' ? 'selected' : '' ?>>Završeno</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Otkazano</option>
            </select>
            
            <select name="priority" class="form-control" style="width: auto; min-width: 120px;">
                <option value="">Svi prioriteti</option>
                <option value="urgent" <?= $priority === 'urgent' ? 'selected' : '' ?>>Hitno</option>
                <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>Visok</option>
                <option value="normal" <?= $priority === 'normal' ? 'selected' : '' ?>>Normalan</option>
                <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Nizak</option>
            </select>
            
            <select name="assigned" class="form-control" style="width: auto; min-width: 130px;">
                <option value="">Svi korisnici</option>
                <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= $assigned == $u['id'] ? 'selected' : '' ?>>
                    <?= e($u['full_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="btn btn-primary">Filtriraj</button>
            
            <?php if ($status || $assigned || $priority): ?>
            <a href="tasks.php" class="btn btn-outline">Očisti</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Lista taskova -->
<?php if (empty($tasks)): ?>
<div class="card mt-2">
    <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        <h3>Nema taskova</h3>
        <p>Kreirajte prvi task</p>
        <a href="task-edit.php" class="btn btn-primary mt-2">Novi task</a>
    </div>
</div>
<?php else: ?>

<div class="mobile-cards mt-2">
    <?php foreach ($tasks as $task): ?>
    <div class="mobile-card">
        <div class="mobile-card-header">
            <div class="mobile-card-title">
                <?php if ($task['priority'] === 'urgent'): ?>
                <span class="badge badge-danger">HITNO</span>
                <?php elseif ($task['priority'] === 'high'): ?>
                <span class="badge badge-warning">VAŽNO</span>
                <?php endif; ?>
                <a href="task-edit.php?id=<?= $task['id'] ?>"><?= e($task['title']) ?></a>
            </div>
            <span class="badge badge-<?= taskStatusColor($task['status']) ?>">
                <?= translateTaskStatus($task['status']) ?>
            </span>
        </div>
        
        <?php if ($task['description']): ?>
        <p class="text-sm text-muted" style="margin: 0.5rem 0;"><?= e(truncate($task['description'], 100)) ?></p>
        <?php endif; ?>
        
        <div class="mobile-card-meta">
            <span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <?php if ($task['assigned_name']): ?>
                <?= e($task['assigned_name']) ?>
                <?php else: ?>
                <span class="badge badge-info">Svi</span>
                <?php endif; ?>
            </span>
            <?php if ($task['due_date']): ?>
            <span class="<?= strtotime($task['due_date']) < time() && $task['status'] !== 'done' ? 'text-danger' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <?= formatDate($task['due_date'], 'j.n.Y.') ?>
                <?= $task['due_time'] ? date('H:i', strtotime($task['due_time'])) : '' ?>
            </span>
            <?php endif; ?>
            <span class="text-muted">Kreirao: <?= e($task['creator_name']) ?></span>
        </div>
        
        <div class="mobile-card-actions">
            <a href="task-edit.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-outline">Uredi</a>
            <?php if ($task['status'] === 'pending'): ?>
            <a href="task-action.php?action=start&id=<?= $task['id'] ?>" class="btn btn-sm btn-info">Započni</a>
            <?php elseif ($task['status'] === 'in_progress'): ?>
            <a href="task-action.php?action=done&id=<?= $task['id'] ?>" class="btn btn-sm btn-success">Završi</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Paginacija -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">‹</a>
    <?php endif; ?>
    
    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
    
    <?php if ($page < $totalPages): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">›</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<p class="text-muted text-sm text-center mt-2">Ukupno: <?= $total ?> taskova</p>

<?php include 'includes/footer.php'; ?>
