<?php
/**
 * Log aktivnosti - Admin
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireRole('admin');

define('PAGE_TITLE', 'Aktivnosti');

$db = getDB();

// Filteri
$userFilter = $_GET['user'] ?? '';
$actionFilter = $_GET['action'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 30;

// Gradnja upita
$where = [];
$params = [];

if ($userFilter) {
    $where[] = "al.user_id = ?";
    $params[] = $userFilter;
}

if ($actionFilter) {
    $where[] = "al.action = ?";
    $params[] = $actionFilter;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Ukupan broj
$stmt = $db->prepare("SELECT COUNT(*) FROM activity_log al $whereClause");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Dohvati aktivnosti
$offset = ($page - 1) * $perPage;
$sql = "
    SELECT al.*, u.full_name, u.username
    FROM activity_log al
    LEFT JOIN users u ON al.user_id = u.id
    $whereClause
    ORDER BY al.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$activities = $stmt->fetchAll();

// Za filtere
$users = getUsers();
$stmt = $db->query("SELECT DISTINCT action FROM activity_log ORDER BY action");
$actions = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Prijevod akcija
function translateAction($action) {
    $translations = [
        'login' => 'Prijava',
        'logout' => 'Odjava',
        'article_create' => 'Kreiran članak',
        'article_update' => 'Ažuriran članak',
        'article_delete' => 'Obrisan članak',
        'article_submit' => 'Poslan članak',
        'article_approve' => 'Odobren članak',
        'article_reject' => 'Odbijen članak',
        'article_publish' => 'Objavljen članak',
        'article_unpublish' => 'Maknut s objave',
        'photo_upload' => 'Uploadana fotografija',
        'photo_delete' => 'Obrisana fotografija',
        'user_create' => 'Kreiran korisnik',
        'user_update' => 'Ažuriran korisnik',
        'user_delete' => 'Obrisan korisnik',
        'category_create' => 'Kreirana kategorija',
        'category_update' => 'Ažurirana kategorija',
        'category_delete' => 'Obrisana kategorija',
        'shift_add' => 'Dodano dežurstvo',
        'shift_remove' => 'Uklonjeno dežurstvo'
    ];
    return $translations[$action] ?? $action;
}

include 'includes/header.php';
?>

<h1>Aktivnosti</h1>

<!-- Filteri -->
<div class="card mt-2">
    <div class="card-body">
        <form method="GET" class="d-flex flex-wrap gap-1">
            <select name="user" class="form-control" style="width: auto; min-width: 150px;">
                <option value="">Svi korisnici</option>
                <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= $userFilter == $u['id'] ? 'selected' : '' ?>>
                    <?= e($u['full_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <select name="action" class="form-control" style="width: auto; min-width: 150px;">
                <option value="">Sve akcije</option>
                <?php foreach ($actions as $a): ?>
                <option value="<?= e($a) ?>" <?= $actionFilter === $a ? 'selected' : '' ?>>
                    <?= translateAction($a) ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="btn btn-primary">Filtriraj</button>
            
            <?php if ($userFilter || $actionFilter): ?>
            <a href="activity.php" class="btn btn-outline">Očisti</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Lista aktivnosti -->
<?php if (empty($activities)): ?>
<div class="card mt-2">
    <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
        </svg>
        <h3>Nema aktivnosti</h3>
    </div>
</div>
<?php else: ?>

<div class="card mt-2">
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Vrijeme</th>
                        <th>Korisnik</th>
                        <th>Akcija</th>
                        <th>Detalji</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $activity): ?>
                    <tr>
                        <td>
                            <div class="text-sm"><?= formatDateTime($activity['created_at']) ?></div>
                            <div class="text-xs text-muted"><?= timeAgo($activity['created_at']) ?></div>
                        </td>
                        <td>
                            <?php if ($activity['full_name']): ?>
                            <div><?= e($activity['full_name']) ?></div>
                            <div class="text-xs text-muted">@<?= e($activity['username']) ?></div>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php
                                if (strpos($activity['action'], 'delete') !== false) echo 'danger';
                                elseif (strpos($activity['action'], 'create') !== false || strpos($activity['action'], 'add') !== false) echo 'success';
                                elseif (strpos($activity['action'], 'login') !== false) echo 'info';
                                else echo 'secondary';
                            ?>">
                                <?= translateAction($activity['action']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($activity['entity_type']): ?>
                            <span class="text-sm">
                                <?= e($activity['entity_type']) ?> #<?= $activity['entity_id'] ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($activity['details']): ?>
                            <div class="text-xs text-muted"><?= e(truncate($activity['details'], 50)) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-xs text-muted"><?= e($activity['ip_address']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Paginacija -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
    </a>
    <?php endif; ?>
    
    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
       class="<?= $i === $page ? 'active' : '' ?>">
        <?= $i ?>
    </a>
    <?php endfor; ?>
    
    <?php if ($page < $totalPages): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="9 18 15 12 9 6"/>
        </svg>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<p class="text-muted text-sm text-center mt-2">Ukupno: <?= $total ?> zapisa</p>

<?php include 'includes/footer.php'; ?>
