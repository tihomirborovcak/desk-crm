<?php
/**
 * Teme za Zagorski list - Tjedni kalendar
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

define('PAGE_TITLE', 'Teme - Zagorski list');

$db = getDB();
$userId = $_SESSION['user_id'];
$isEditor = isEditor();

// Trenutni tjedan
$week = intval($_GET['week'] ?? date('W'));
$year = intval($_GET['year'] ?? date('Y'));

// Navigacija tjedana
$currentDate = new DateTime();
$currentDate->setISODate($year, $week);
$weekStart = clone $currentDate;
$weekStart->modify('monday this week');
$weekEnd = clone $weekStart;
$weekEnd->modify('+6 days');

$prevWeek = clone $weekStart;
$prevWeek->modify('-1 week');
$nextWeek = clone $weekStart;
$nextWeek->modify('+1 week');

// Dohvati teme za ovaj tjedan
$stmt = $db->prepare("
    SELECT t.*, 
           u1.full_name as proposed_by_name,
           u2.full_name as approved_by_name,
           u3.full_name as assigned_to_name
    FROM themes t
    LEFT JOIN users u1 ON t.proposed_by = u1.id
    LEFT JOIN users u2 ON t.approved_by = u2.id
    LEFT JOIN users u3 ON t.assigned_to = u3.id
    WHERE t.year = ? AND t.week_number = ?
    ORDER BY 
        CASE t.status 
            WHEN 'odobreno' THEN 1 
            WHEN 'u_izradi' THEN 2 
            WHEN 'predlozeno' THEN 3 
            WHEN 'zavrseno' THEN 4 
            ELSE 5 
        END,
        CASE t.priority 
            WHEN 'hitno' THEN 1 
            WHEN 'visoka' THEN 2 
            WHEN 'normalna' THEN 3 
            ELSE 4 
        END,
        t.planned_date
");
$stmt->execute([$year, $week]);
$themes = $stmt->fetchAll();

// Grupiraj po statusu
$themesByStatus = [
    'predlozeno' => [],
    'odobreno' => [],
    'u_izradi' => [],
    'zavrseno' => [],
    'odbijeno' => []
];
foreach ($themes as $theme) {
    $themesByStatus[$theme['status']][] = $theme;
}

// Statistike
$stats = [
    'total' => count($themes),
    'predlozeno' => count($themesByStatus['predlozeno']),
    'odobreno' => count($themesByStatus['odobreno']),
    'u_izradi' => count($themesByStatus['u_izradi']),
    'zavrseno' => count($themesByStatus['zavrseno'])
];

$users = getUsers();

include 'includes/header.php';
?>

<div class="d-flex" style="justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <h1>📰 Teme - Zagorski list</h1>
    <button class="btn btn-primary" data-modal="themeModal">+ Nova tema</button>
</div>

<!-- Navigacija tjedana -->
<div class="card mt-2">
    <div class="card-body" style="padding: 0.75rem;">
        <div class="d-flex gap-1" style="align-items: center; justify-content: center;">
            <a href="?week=<?= $prevWeek->format('W') ?>&year=<?= $prevWeek->format('Y') ?>" class="btn btn-outline">‹</a>
            <div style="text-align: center; padding: 0 1rem; min-width: 250px;">
                <div style="font-weight: 600; font-size: 1.1rem;">Tjedan <?= $week ?>/<?= $year ?></div>
                <div class="text-muted text-sm"><?= $weekStart->format('d.m.') ?> - <?= $weekEnd->format('d.m.Y') ?></div>
            </div>
            <a href="?week=<?= $nextWeek->format('W') ?>&year=<?= $nextWeek->format('Y') ?>" class="btn btn-outline">›</a>
            <?php if ($week != date('W') || $year != date('Y')): ?>
            <a href="themes.php" class="btn btn-outline" style="margin-left: 1rem;">Danas</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Statistike -->
<div class="stats-grid mt-2" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card">
        <div class="stat-value"><?= $stats['predlozeno'] ?></div>
        <div class="stat-label">Prijedlozi</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-value"><?= $stats['odobreno'] ?></div>
        <div class="stat-label">Odobreno</div>
    </div>
    <div class="stat-card primary">
        <div class="stat-value"><?= $stats['u_izradi'] ?></div>
        <div class="stat-label">U izradi</div>
    </div>
    <div class="stat-card success">
        <div class="stat-value"><?= $stats['zavrseno'] ?></div>
        <div class="stat-label">Završeno</div>
    </div>
</div>

<!-- Kanban prikaz -->
<div class="kanban-board mt-2">
    <!-- Prijedlozi -->
    <div class="kanban-column">
        <div class="kanban-header predlozeno">
            <span>💡 Prijedlozi</span>
            <span class="badge"><?= $stats['predlozeno'] ?></span>
        </div>
        <div class="kanban-cards">
            <?php foreach ($themesByStatus['predlozeno'] as $theme): ?>
            <?= renderThemeCard($theme, $isEditor) ?>
            <?php endforeach; ?>
            <?php if (empty($themesByStatus['predlozeno'])): ?>
            <div class="kanban-empty">Nema prijedloga</div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Odobreno -->
    <div class="kanban-column">
        <div class="kanban-header odobreno">
            <span>✅ Odobreno</span>
            <span class="badge"><?= $stats['odobreno'] ?></span>
        </div>
        <div class="kanban-cards">
            <?php foreach ($themesByStatus['odobreno'] as $theme): ?>
            <?= renderThemeCard($theme, $isEditor) ?>
            <?php endforeach; ?>
            <?php if (empty($themesByStatus['odobreno'])): ?>
            <div class="kanban-empty">Nema odobrenih</div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- U izradi -->
    <div class="kanban-column">
        <div class="kanban-header u_izradi">
            <span>✍️ U izradi</span>
            <span class="badge"><?= $stats['u_izradi'] ?></span>
        </div>
        <div class="kanban-cards">
            <?php foreach ($themesByStatus['u_izradi'] as $theme): ?>
            <?= renderThemeCard($theme, $isEditor) ?>
            <?php endforeach; ?>
            <?php if (empty($themesByStatus['u_izradi'])): ?>
            <div class="kanban-empty">Nitko ne piše</div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Završeno -->
    <div class="kanban-column">
        <div class="kanban-header zavrseno">
            <span>🎉 Završeno</span>
            <span class="badge"><?= $stats['zavrseno'] ?></span>
        </div>
        <div class="kanban-cards">
            <?php foreach ($themesByStatus['zavrseno'] as $theme): ?>
            <?= renderThemeCard($theme, $isEditor) ?>
            <?php endforeach; ?>
            <?php if (empty($themesByStatus['zavrseno'])): ?>
            <div class="kanban-empty">Još ništa</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($themesByStatus['odbijeno'])): ?>
<!-- Odbijeni prijedlozi -->
<div class="card mt-2">
    <div class="card-header">
        <h2 class="card-title">❌ Odbijeni prijedlozi (<?= count($themesByStatus['odbijeno']) ?>)</h2>
    </div>
    <div class="card-body">
        <?php foreach ($themesByStatus['odbijeno'] as $theme): ?>
        <div class="rejected-theme">
            <strong><?= e($theme['title']) ?></strong>
            <span class="text-muted">- <?= e($theme['proposed_by_name']) ?></span>
            <?php if ($theme['rejection_reason']): ?>
            <div class="text-sm text-danger">Razlog: <?= e($theme['rejection_reason']) ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Modal za novu temu -->
<div class="modal" id="themeModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Nova tema</h3>
            <button class="modal-close" onclick="closeModal('themeModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="theme-action.php" id="themeForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="week" value="<?= $week ?>">
                <input type="hidden" name="year" value="<?= $year ?>">
                
                <div class="form-group">
                    <label class="form-label">Naslov teme *</label>
                    <input type="text" name="title" class="form-control" required placeholder="npr. Otvaranje nove tvornice u Zaboku">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Opis / Detalji</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Dodatne informacije, kontakti, ideje..."></textarea>
                </div>
                
                <div class="d-flex gap-1">
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Kategorija</label>
                        <select name="category" class="form-control">
                            <option value="vijesti">Vijesti</option>
                            <option value="lokalno">Lokalno</option>
                            <option value="sport">Sport</option>
                            <option value="kultura">Kultura</option>
                            <option value="gospodarstvo">Gospodarstvo</option>
                            <option value="lifestyle">Lifestyle</option>
                            <option value="crna_kronika">Crna kronika</option>
                            <option value="politika">Politika</option>
                            <option value="ostalo">Ostalo</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Prioritet</label>
                        <select name="priority" class="form-control">
                            <option value="niska">Niska</option>
                            <option value="normalna" selected>Normalna</option>
                            <option value="visoka">Visoka</option>
                            <option value="hitno">Hitno</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Planirani datum objave</label>
                    <input type="date" name="planned_date" class="form-control" 
                           min="<?= $weekStart->format('Y-m-d') ?>" 
                           max="<?= $weekEnd->format('Y-m-d') ?>">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('themeModal')">Odustani</button>
            <button type="submit" form="themeForm" class="btn btn-primary">Predloži temu</button>
        </div>
    </div>
</div>

<?php
function renderThemeCard($theme, $isEditor) {
    $priorityColors = [
        'hitno' => 'danger',
        'visoka' => 'warning', 
        'normalna' => 'info',
        'niska' => 'secondary'
    ];
    $priorityColor = $priorityColors[$theme['priority']] ?? 'secondary';
    
    $categoryLabels = [
        'vijesti' => '📰',
        'lokalno' => '🏠',
        'sport' => '⚽',
        'kultura' => '🎭',
        'gospodarstvo' => '💼',
        'lifestyle' => '✨',
        'crna_kronika' => '🚨',
        'politika' => '🏛️',
        'ostalo' => '📌'
    ];
    $categoryIcon = $categoryLabels[$theme['category']] ?? '📌';
    
    ob_start();
    ?>
    <div class="kanban-card priority-<?= $theme['priority'] ?>">
        <div class="kanban-card-header">
            <span class="category-icon"><?= $categoryIcon ?></span>
            <?php if ($theme['priority'] === 'hitno'): ?>
            <span class="badge badge-danger">HITNO</span>
            <?php elseif ($theme['priority'] === 'visoka'): ?>
            <span class="badge badge-warning">!</span>
            <?php endif; ?>
        </div>
        <h4 class="kanban-card-title"><?= e($theme['title']) ?></h4>
        <?php if ($theme['description']): ?>
        <p class="kanban-card-desc"><?= e(mb_substr($theme['description'], 0, 100)) ?>...</p>
        <?php endif; ?>
        <div class="kanban-card-meta">
            <span title="Predložio">👤 <?= e($theme['proposed_by_name']) ?></span>
            <?php if ($theme['assigned_to_name']): ?>
            <span title="Piše">✍️ <?= e($theme['assigned_to_name']) ?></span>
            <?php endif; ?>
            <?php if ($theme['planned_date']): ?>
            <span title="Planirano">📅 <?= date('d.m.', strtotime($theme['planned_date'])) ?></span>
            <?php endif; ?>
        </div>
        <div class="kanban-card-actions">
            <a href="theme-edit.php?id=<?= $theme['id'] ?>" class="btn btn-sm btn-outline">Detalji</a>
            <?php if ($isEditor && $theme['status'] === 'predlozeno'): ?>
            <a href="theme-action.php?action=approve&id=<?= $theme['id'] ?>" class="btn btn-sm btn-success">✓</a>
            <a href="theme-action.php?action=reject&id=<?= $theme['id'] ?>" class="btn btn-sm btn-danger">✗</a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>

<style>
.kanban-board {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    overflow-x: auto;
}
@media (max-width: 1200px) {
    .kanban-board {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 768px) {
    .kanban-board {
        grid-template-columns: 1fr;
    }
}
.kanban-column {
    background: var(--gray-100);
    border-radius: var(--radius);
    min-height: 400px;
}
.kanban-header {
    padding: 0.75rem 1rem;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: var(--radius) var(--radius) 0 0;
}
.kanban-header.predlozeno { background: #e3f2fd; color: #1565c0; }
.kanban-header.odobreno { background: #fff3e0; color: #ef6c00; }
.kanban-header.u_izradi { background: #e8f5e9; color: #2e7d32; }
.kanban-header.zavrseno { background: #f3e5f5; color: #7b1fa2; }
.kanban-header .badge {
    background: rgba(0,0,0,0.1);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.8rem;
}
.kanban-cards {
    padding: 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.kanban-card {
    background: white;
    border-radius: var(--radius);
    padding: 0.75rem;
    box-shadow: var(--shadow);
    border-left: 3px solid var(--gray-300);
}
.kanban-card.priority-hitno { border-left-color: #e74c3c; }
.kanban-card.priority-visoka { border-left-color: #f39c12; }
.kanban-card.priority-normalna { border-left-color: #3498db; }
.kanban-card.priority-niska { border-left-color: #95a5a6; }
.kanban-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}
.category-icon {
    font-size: 1.2rem;
}
.kanban-card-title {
    font-size: 0.95rem;
    font-weight: 600;
    margin: 0 0 0.5rem;
    line-height: 1.3;
}
.kanban-card-desc {
    font-size: 0.8rem;
    color: var(--gray-600);
    margin: 0 0 0.5rem;
}
.kanban-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-bottom: 0.5rem;
}
.kanban-card-actions {
    display: flex;
    gap: 0.25rem;
}
.kanban-empty {
    text-align: center;
    color: var(--gray-400);
    padding: 2rem;
    font-style: italic;
}
.rejected-theme {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--gray-200);
}
.rejected-theme:last-child {
    border-bottom: none;
}
/* Mobilna verzija */
@media (max-width: 767px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 0.5rem;
    }
    .stat-card {
        padding: 0.5rem;
    }
    .stat-value {
        font-size: 1.2rem;
    }
    .stat-label {
        font-size: 0.65rem;
    }
    .kanban-card-title {
        font-size: 0.85rem;
    }
    .kanban-card-meta {
        font-size: 0.65rem;
    }
    .kanban-header {
        padding: 0.5rem 0.75rem;
        font-size: 0.85rem;
    }
}
</style>

<script>
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
</script>

<?php include 'includes/footer.php'; ?>
