<?php
/**
 * Statistike - pregled dežurstava i događaja po zaposlenicima
 */

define('PAGE_TITLE', 'Statistike');

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

if (!isEditor()) {
    redirectWith('dashboard.php', 'danger', 'Nemate ovlasti za pristup statistikama');
}

$db = getDB();

// Filteri
$month = intval($_GET['month'] ?? date('n'));
$year = intval($_GET['year'] ?? date('Y'));
$view = $_GET['view'] ?? 'month'; // month ili week

$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate = date('Y-m-t', strtotime($startDate));

$monthNames = [
    1 => 'Sij', 2 => 'Velj', 3 => 'Ožu', 4 => 'Tra',
    5 => 'Svi', 6 => 'Lip', 7 => 'Srp', 8 => 'Kol',
    9 => 'Ruj', 10 => 'Lis', 11 => 'Stu', 12 => 'Pro'
];

// Dohvati sve aktivne korisnike
$users = $db->query("SELECT id, full_name FROM users WHERE active = 1 ORDER BY full_name")->fetchAll();
$userMap = [];
foreach ($users as $u) $userMap[$u['id']] = $u['full_name'];

// Dežurstva po korisniku
$shiftStats = [];
$stmt = $db->prepare("
    SELECT ea.user_id,
        SUM(CASE WHEN e.title LIKE '%jutarnja%' THEN 1 ELSE 0 END) as j,
        SUM(CASE WHEN e.title LIKE '%popodnevna%' THEN 1 ELSE 0 END) as p,
        SUM(CASE WHEN e.title LIKE '%večernja%' OR e.title LIKE '%vecernja%' THEN 1 ELSE 0 END) as v,
        WEEK(e.event_date, 1) as week_num
    FROM events e
    JOIN event_assignments ea ON e.id = ea.event_id
    WHERE e.event_type = 'dezurstvo' AND e.event_date BETWEEN ? AND ?
    GROUP BY ea.user_id, WEEK(e.event_date, 1)
");
$stmt->execute([$startDate, $endDate]);
while ($row = $stmt->fetch()) {
    $uid = $row['user_id'];
    $wk = $row['week_num'];
    if (!isset($shiftStats[$uid])) $shiftStats[$uid] = ['total' => ['j'=>0,'p'=>0,'v'=>0], 'weeks' => []];
    $shiftStats[$uid]['weeks'][$wk] = ['j' => $row['j'], 'p' => $row['p'], 'v' => $row['v']];
    $shiftStats[$uid]['total']['j'] += $row['j'];
    $shiftStats[$uid]['total']['p'] += $row['p'];
    $shiftStats[$uid]['total']['v'] += $row['v'];
}

// Događaji po korisniku
$eventStats = [];
$eventList = [];
$stmt = $db->prepare("
    SELECT ea.user_id, e.id, e.title, e.event_date, e.location, WEEK(e.event_date, 1) as week_num
    FROM events e
    JOIN event_assignments ea ON e.id = ea.event_id
    WHERE e.event_type != 'dezurstvo' AND e.event_date BETWEEN ? AND ?
    ORDER BY e.event_date
");
$stmt->execute([$startDate, $endDate]);
while ($row = $stmt->fetch()) {
    $uid = $row['user_id'];
    if (!isset($eventStats[$uid])) $eventStats[$uid] = 0;
    $eventStats[$uid]++;
    if (!isset($eventList[$uid])) $eventList[$uid] = [];
    $eventList[$uid][] = $row;
}

// Tjedni u mjesecu
$weeks = [];
$d = new DateTime($startDate);
$end = new DateTime($endDate);
while ($d <= $end) {
    $w = (int)$d->format('W');
    if (!in_array($w, $weeks)) $weeks[] = $w;
    $d->modify('+1 day');
}

require_once 'includes/header.php';
?>

<style>
.stat-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
.stat-table th, .stat-table td { padding: 0.4rem 0.5rem; border-bottom: 1px solid var(--border-color); text-align: left; }
.stat-table th { background: var(--bg-secondary); font-weight: 600; font-size: 0.75rem; }
.stat-table td.num { text-align: center; font-weight: 500; }
.stat-table tr:hover { background: var(--bg-secondary); }
.j { color: #b45309; }
.p { color: #1d4ed8; }
.v { color: #4338ca; }
.week-header { font-size: 0.7rem; color: var(--text-muted); }
.event-list { font-size: 0.75rem; color: var(--text-secondary); margin: 0; padding-left: 1rem; }
.event-list li { margin: 2px 0; }
.compact-filter { display: flex; gap: 0.4rem; align-items: center; flex-wrap: wrap; padding: 0.5rem; }
.compact-filter select, .compact-filter button { font-size: 0.8rem; padding: 0.3rem 0.5rem; }
.view-toggle { display: flex; gap: 0; }
.view-toggle a { padding: 0.3rem 0.6rem; font-size: 0.75rem; border: 1px solid var(--border-color); text-decoration: none; color: var(--text-secondary); }
.view-toggle a:first-child { border-radius: 4px 0 0 4px; }
.view-toggle a:last-child { border-radius: 0 4px 4px 0; border-left: 0; }
.view-toggle a.active { background: var(--primary-color); color: white; border-color: var(--primary-color); }
</style>

<div class="page-header" style="margin-bottom: 0.5rem;">
    <h1 style="font-size: 1.1rem;">Statistike - <?= $monthNames[$month] ?> <?= $year ?></h1>
</div>

<div class="card" style="margin-bottom: 0.75rem; padding: 0;">
    <form method="get" class="compact-filter">
        <select name="month">
            <?php foreach ($monthNames as $num => $name): ?>
            <option value="<?= $num ?>" <?= $month == $num ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>
        <select name="year">
            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
            <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <input type="hidden" name="view" value="<?= e($view) ?>">
        <button type="submit" class="btn btn-primary">OK</button>
        <div class="view-toggle" style="margin-left: auto;">
            <a href="?month=<?= $month ?>&year=<?= $year ?>&view=month" class="<?= $view == 'month' ? 'active' : '' ?>">Mjesec</a>
            <a href="?month=<?= $month ?>&year=<?= $year ?>&view=week" class="<?= $view == 'week' ? 'active' : '' ?>">Tjedni</a>
        </div>
    </form>
</div>

<!-- Dežurstva -->
<div class="card" style="margin-bottom: 0.75rem; padding: 0; overflow-x: auto;">
    <table class="stat-table">
        <thead>
            <tr>
                <th>Ime</th>
                <?php if ($view == 'week'): ?>
                    <?php foreach ($weeks as $w): ?>
                    <th class="num week-header">T<?= $w ?></th>
                    <?php endforeach; ?>
                <?php endif; ?>
                <th class="num"><span class="j">J</span></th>
                <th class="num"><span class="p">P</span></th>
                <th class="num"><span class="v">V</span></th>
                <th class="num">Uk</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user):
                $stats = $shiftStats[$user['id']] ?? null;
                if (!$stats) continue;
                $total = $stats['total']['j'] + $stats['total']['p'] + $stats['total']['v'];
            ?>
            <tr>
                <td><?= e($user['full_name']) ?></td>
                <?php if ($view == 'week'): ?>
                    <?php foreach ($weeks as $w):
                        $ws = $stats['weeks'][$w] ?? ['j'=>0,'p'=>0,'v'=>0];
                        $wt = $ws['j'] + $ws['p'] + $ws['v'];
                    ?>
                    <td class="num"><?= $wt ?: '-' ?></td>
                    <?php endforeach; ?>
                <?php endif; ?>
                <td class="num j"><?= $stats['total']['j'] ?: '-' ?></td>
                <td class="num p"><?= $stats['total']['p'] ?: '-' ?></td>
                <td class="num v"><?= $stats['total']['v'] ?: '-' ?></td>
                <td class="num"><strong><?= $total ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Događaji -->
<div class="card" style="padding: 0; overflow-x: auto;">
    <table class="stat-table">
        <thead>
            <tr>
                <th>Ime</th>
                <th class="num">Broj</th>
                <th>Popis događaja</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user):
                $count = $eventStats[$user['id']] ?? 0;
                $events = $eventList[$user['id']] ?? [];
                if (!$count) continue;
            ?>
            <tr>
                <td><?= e($user['full_name']) ?></td>
                <td class="num"><strong><?= $count ?></strong></td>
                <td>
                    <ul class="event-list">
                        <?php foreach ($events as $ev): ?>
                        <li><?= formatDate($ev['event_date'], 'd.m.') ?> - <?= e($ev['title']) ?><?= $ev['location'] ? ' (' . e($ev['location']) . ')' : '' ?></li>
                        <?php endforeach; ?>
                    </ul>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$hasData = !empty($shiftStats) || !empty($eventStats);
if (!$hasData):
?>
<div class="empty-state" style="padding: 2rem; text-align: center; color: var(--text-muted);">
    Nema podataka za odabrani mjesec
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
