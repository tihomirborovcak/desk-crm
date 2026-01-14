<?php
/**
 * Detalji / uređivanje teme
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$isEditor = isEditor();

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    redirectWith('themes.php', 'danger', 'Tema nije pronađena');
}

// Dohvati temu
$stmt = $db->prepare("
    SELECT t.*, 
           u1.full_name as proposed_by_name,
           u2.full_name as approved_by_name,
           u3.full_name as assigned_to_name
    FROM themes t
    LEFT JOIN users u1 ON t.proposed_by = u1.id
    LEFT JOIN users u2 ON t.approved_by = u2.id
    LEFT JOIN users u3 ON t.assigned_to = u3.id
    WHERE t.id = ?
");
$stmt->execute([$id]);
$theme = $stmt->fetch();

if (!$theme) {
    redirectWith('themes.php', 'danger', 'Tema nije pronađena');
}

define('PAGE_TITLE', 'Tema: ' . $theme['title']);

// Dohvati komentare
$stmt = $db->prepare("
    SELECT c.*, u.full_name 
    FROM theme_comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.theme_id = ? 
    ORDER BY c.created_at DESC
");
$stmt->execute([$id]);
$comments = $stmt->fetchAll();

$users = getUsers();

$canEdit = ($theme['proposed_by'] == $userId || $isEditor);

$categoryLabels = [
    'vijesti' => 'Vijesti',
    'lokalno' => 'Lokalno',
    'sport' => 'Sport',
    'kultura' => 'Kultura',
    'gospodarstvo' => 'Gospodarstvo',
    'lifestyle' => 'Lifestyle',
    'crna_kronika' => 'Crna kronika',
    'politika' => 'Politika',
    'ostalo' => 'Ostalo'
];

$statusLabels = [
    'predlozeno' => ['Predloženo', 'secondary'],
    'odobreno' => ['Odobreno', 'warning'],
    'u_izradi' => ['U izradi', 'primary'],
    'zavrseno' => ['Završeno', 'success'],
    'odbijeno' => ['Odbijeno', 'danger']
];

$priorityLabels = [
    'niska' => ['Niska', 'secondary'],
    'normalna' => ['Normalna', 'info'],
    'visoka' => ['Visoka', 'warning'],
    'hitno' => ['Hitno', 'danger']
];

include 'includes/header.php';
?>

<div class="d-flex" style="justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <h1>📝 <?= e($theme['title']) ?></h1>
    <a href="themes.php?week=<?= $theme['week_number'] ?>&year=<?= $theme['year'] ?>" class="btn btn-outline">← Natrag</a>
</div>

<div class="row-2-col mt-2">
    <!-- Lijeva kolona - detalji -->
    <div>
        <form method="POST" action="theme-action.php">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="week" value="<?= $theme['week_number'] ?>">
            <input type="hidden" name="year" value="<?= $theme['year'] ?>">
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Detalji teme</h2>
                    <span class="badge badge-<?= $statusLabels[$theme['status']][1] ?>">
                        <?= $statusLabels[$theme['status']][0] ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Naslov *</label>
                        <input type="text" name="title" class="form-control" 
                               value="<?= e($theme['title']) ?>" required <?= !$canEdit ? 'readonly' : '' ?>>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Opis</label>
                        <textarea name="description" class="form-control" rows="4" <?= !$canEdit ? 'readonly' : '' ?>><?= e($theme['description']) ?></textarea>
                    </div>
                    
                    <div class="d-flex gap-1" style="flex-wrap: wrap;">
                        <div class="form-group" style="flex: 1; min-width: 150px;">
                            <label class="form-label">Kategorija</label>
                            <select name="category" class="form-control" <?= !$canEdit ? 'disabled' : '' ?>>
                                <?php foreach ($categoryLabels as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $theme['category'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="flex: 1; min-width: 150px;">
                            <label class="form-label">Prioritet</label>
                            <select name="priority" class="form-control" <?= !$canEdit ? 'disabled' : '' ?>>
                                <?php foreach ($priorityLabels as $key => $data): ?>
                                <option value="<?= $key ?>" <?= $theme['priority'] === $key ? 'selected' : '' ?>><?= $data[0] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Planirani datum</label>
                        <input type="date" name="planned_date" class="form-control" 
                               value="<?= e($theme['planned_date']) ?>" <?= !$canEdit ? 'readonly' : '' ?>>
                    </div>
                    
                    <?php if ($isEditor): ?>
                    <div class="d-flex gap-1" style="flex-wrap: wrap;">
                        <div class="form-group" style="flex: 1; min-width: 150px;">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <?php foreach ($statusLabels as $key => $data): ?>
                                <option value="<?= $key ?>" <?= $theme['status'] === $key ? 'selected' : '' ?>><?= $data[0] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="flex: 1; min-width: 150px;">
                            <label class="form-label">Dodjeli novinaru</label>
                            <select name="assigned_to" class="form-control">
                                <option value="">-- Nedodijeljeno --</option>
                                <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $theme['assigned_to'] == $u['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($canEdit): ?>
            <div class="card mt-2">
                <div class="card-body">
                    <div class="d-flex gap-1 flex-wrap">
                        <button type="submit" class="btn btn-primary">💾 Spremi</button>
                        
                        <?php if ($theme['status'] === 'odobreno' && !$theme['assigned_to']): ?>
                        <a href="theme-action.php?action=start&id=<?= $id ?>&week=<?= $theme['week_number'] ?>&year=<?= $theme['year'] ?>" 
                           class="btn btn-success">✍️ Uzmi temu</a>
                        <?php endif; ?>
                        
                        <?php if ($theme['status'] === 'u_izradi' && ($theme['assigned_to'] == $userId || $isEditor)): ?>
                        <a href="theme-action.php?action=complete&id=<?= $id ?>&week=<?= $theme['week_number'] ?>&year=<?= $theme['year'] ?>" 
                           class="btn btn-success">✅ Završi</a>
                        <?php endif; ?>
                        
                        <?php if ($isEditor): ?>
                        <a href="theme-action.php?action=delete&id=<?= $id ?>&week=<?= $theme['week_number'] ?>&year=<?= $theme['year'] ?>" 
                           class="btn btn-danger" data-confirm="Obrisati temu?">🗑️ Obriši</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Desna kolona - info i komentari -->
    <div>
        <!-- Info -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Informacije</h2>
            </div>
            <div class="card-body">
                <div class="info-row">
                    <span class="info-label">Tjedan:</span>
                    <span><?= $theme['week_number'] ?>/<?= $theme['year'] ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Predložio:</span>
                    <span><?= e($theme['proposed_by_name']) ?></span>
                </div>
                <?php if ($theme['approved_by_name']): ?>
                <div class="info-row">
                    <span class="info-label">Odobrio:</span>
                    <span><?= e($theme['approved_by_name']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($theme['assigned_to_name']): ?>
                <div class="info-row">
                    <span class="info-label">Piše:</span>
                    <span><?= e($theme['assigned_to_name']) ?></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Kreirano:</span>
                    <span><?= formatDate($theme['created_at'], 'd.m.Y H:i') ?></span>
                </div>
                <?php if ($theme['rejection_reason']): ?>
                <div class="alert alert-danger mt-2">
                    <strong>Razlog odbijanja:</strong><br>
                    <?= e($theme['rejection_reason']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Komentari -->
        <div class="card mt-2">
            <div class="card-header">
                <h2 class="card-title">💬 Komentari (<?= count($comments) ?>)</h2>
            </div>
            <div class="card-body">
                <!-- Novi komentar -->
                <form method="POST" action="theme-action.php" class="mb-2">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="comment">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div class="d-flex gap-1">
                        <input type="text" name="comment" class="form-control" placeholder="Dodaj komentar..." required>
                        <button type="submit" class="btn btn-primary">Pošalji</button>
                    </div>
                </form>
                
                <!-- Lista komentara -->
                <?php if (empty($comments)): ?>
                <p class="text-muted text-center">Nema komentara</p>
                <?php else: ?>
                <div class="comments-list">
                    <?php foreach ($comments as $c): ?>
                    <div class="comment">
                        <div class="comment-header">
                            <strong><?= e($c['full_name']) ?></strong>
                            <span class="text-muted text-sm"><?= timeAgo($c['created_at']) ?></span>
                        </div>
                        <div class="comment-body"><?= e($c['comment']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.info-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--gray-200);
}
.info-row:last-child {
    border-bottom: none;
}
.info-label {
    color: var(--gray-600);
}
.comments-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.comment {
    background: var(--gray-50);
    padding: 0.75rem;
    border-radius: var(--radius);
}
.comment-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
}
.comment-body {
    font-size: 0.9rem;
}
</style>

<?php include 'includes/footer.php'; ?>
