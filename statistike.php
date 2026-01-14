<?php
/**
 * Statistike - pregled dežurstava i događaja po zaposlenicima
 */

define('PAGE_TITLE', 'Statistike');

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

// Samo urednik i admin
if (!isEditor()) {
    redirectWith('dashboard.php', 'danger', 'Nemate ovlasti za pristup statistikama');
}

$db = getDB();

// Filteri
$month = intval($_GET['month'] ?? date('n'));
$year = intval($_GET['year'] ?? date('Y'));

// Izračunaj prvi i zadnji dan mjeseca
$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate = date('Y-m-t', strtotime($startDate));

$monthNames = [
    1 => 'Siječanj', 2 => 'Veljača', 3 => 'Ožujak', 4 => 'Travanj',
    5 => 'Svibanj', 6 => 'Lipanj', 7 => 'Srpanj', 8 => 'Kolovoz',
    9 => 'Rujan', 10 => 'Listopad', 11 => 'Studeni', 12 => 'Prosinac'
];

// Dohvati sve aktivne korisnike
$users = $db->query("SELECT id, full_name, role FROM users WHERE active = 1 ORDER BY full_name")->fetchAll();

// Statistika dežurstava po korisniku
$shiftStats = [];
$stmt = $db->prepare("
    SELECT
        ea.user_id,
        COUNT(*) as shift_count,
        SUM(CASE WHEN e.title LIKE '%jutarnja%' THEN 1 ELSE 0 END) as jutarnja,
        SUM(CASE WHEN e.title LIKE '%popodnevna%' THEN 1 ELSE 0 END) as popodnevna,
        SUM(CASE WHEN e.title LIKE '%večernja%' OR e.title LIKE '%vecernja%' THEN 1 ELSE 0 END) as vecernja
    FROM events e
    JOIN event_assignments ea ON e.id = ea.event_id
    WHERE e.event_type = 'dezurstvo'
      AND e.event_date BETWEEN ? AND ?
    GROUP BY ea.user_id
");
$stmt->execute([$startDate, $endDate]);
while ($row = $stmt->fetch()) {
    $shiftStats[$row['user_id']] = $row;
}

// Statistika događaja po korisniku (bez dežurstava)
$eventStats = [];
$stmt = $db->prepare("
    SELECT
        ea.user_id,
        COUNT(*) as event_count
    FROM events e
    JOIN event_assignments ea ON e.id = ea.event_id
    WHERE e.event_type != 'dezurstvo'
      AND e.event_date BETWEEN ? AND ?
    GROUP BY ea.user_id
");
$stmt->execute([$startDate, $endDate]);
while ($row = $stmt->fetch()) {
    $eventStats[$row['user_id']] = $row;
}

// Popis događaja po korisniku
$eventList = [];
$stmt = $db->prepare("
    SELECT
        ea.user_id,
        e.id,
        e.title,
        e.event_date,
        e.event_time,
        e.location,
        e.event_type
    FROM events e
    JOIN event_assignments ea ON e.id = ea.event_id
    WHERE e.event_type != 'dezurstvo'
      AND e.event_date BETWEEN ? AND ?
    ORDER BY e.event_date, e.event_time
");
$stmt->execute([$startDate, $endDate]);
while ($row = $stmt->fetch()) {
    if (!isset($eventList[$row['user_id']])) {
        $eventList[$row['user_id']] = [];
    }
    $eventList[$row['user_id']][] = $row;
}

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1>Statistike</h1>
</div>

<!-- Filter -->
<div class="card" style="margin-bottom: 1rem;">
    <form method="get" class="filter-form" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        <select name="month" class="form-control" style="width: auto;">
            <?php foreach ($monthNames as $num => $name): ?>
            <option value="<?= $num ?>" <?= $month == $num ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>
        <select name="year" class="form-control" style="width: auto;">
            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
            <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="btn btn-primary">Prikaži</button>
    </form>
</div>

<h2 class="section-title"><?= $monthNames[$month] ?> <?= $year ?></h2>

<!-- Statistika po zaposlenicima -->
<?php foreach ($users as $user):
    $shifts = $shiftStats[$user['id']] ?? null;
    $events = $eventStats[$user['id']] ?? null;
    $userEvents = $eventList[$user['id']] ?? [];

    // Preskoči ako nema ništa
    if (!$shifts && !$events) continue;
?>
<div class="card" style="margin-bottom: 1rem;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; background: var(--bg-secondary); border-bottom: 1px solid var(--border-color);">
        <strong><?= e($user['full_name']) ?></strong>
        <span class="badge badge-secondary"><?= translateRole($user['role']) ?></span>
    </div>
    <div class="card-body" style="padding: 1rem;">
        <?php if ($shifts): ?>
        <div style="margin-bottom: 1rem;">
            <h4 style="font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--text-secondary);">Dežurstva (<?= $shifts['shift_count'] ?>)</h4>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <?php if ($shifts['jutarnja'] > 0): ?>
                <span class="badge" style="background: #fef3c7; color: #92400e;">J: <?= $shifts['jutarnja'] ?></span>
                <?php endif; ?>
                <?php if ($shifts['popodnevna'] > 0): ?>
                <span class="badge" style="background: #dbeafe; color: #1e40af;">P: <?= $shifts['popodnevna'] ?></span>
                <?php endif; ?>
                <?php if ($shifts['vecernja'] > 0): ?>
                <span class="badge" style="background: #e0e7ff; color: #3730a3;">V: <?= $shifts['vecernja'] ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($events): ?>
        <div>
            <h4 style="font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--text-secondary);">Događaji (<?= $events['event_count'] ?>)</h4>
            <?php if (!empty($userEvents)): ?>
            <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.875rem;">
                <?php foreach ($userEvents as $event): ?>
                <li style="margin-bottom: 0.25rem;">
                    <a href="event-edit.php?id=<?= $event['id'] ?>" style="color: var(--primary-color);">
                        <?= e($event['title']) ?>
                    </a>
                    <span style="color: var(--text-muted); font-size: 0.8rem;">
                        - <?= formatDate($event['event_date'], 'd.m.') ?>
                        <?php if ($event['event_time']): ?>
                        <?= date('H:i', strtotime($event['event_time'])) ?>
                        <?php endif; ?>
                        <?php if ($event['location']): ?>
                        (<?= e($event['location']) ?>)
                        <?php endif; ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<?php
// Provjeri ima li uopće podataka
$hasData = false;
foreach ($users as $user) {
    if (isset($shiftStats[$user['id']]) || isset($eventStats[$user['id']])) {
        $hasData = true;
        break;
    }
}
if (!$hasData):
?>
<div class="empty-state">
    <p>Nema podataka za odabrani mjesec</p>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
