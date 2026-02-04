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
    1 => 'Siječanj', 2 => 'Veljača', 3 => 'Ožujak', 4 => 'Travanj',
    5 => 'Svibanj', 6 => 'Lipanj', 7 => 'Srpanj', 8 => 'Kolovoz',
    9 => 'Rujan', 10 => 'Listopad', 11 => 'Studeni', 12 => 'Prosinac'
];

// Navigacija mjeseca
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// Dohvati sve aktivne korisnike
$users = $db->query("SELECT id, full_name FROM users WHERE active = 1 ORDER BY full_name")->fetchAll();
$userMap = [];
foreach ($users as $u) $userMap[$u['id']] = $u['full_name'];

// Dežurstva po korisniku - iz shifts tablice
$shiftStats = [];
$stmt = $db->prepare("
    SELECT user_id,
        SUM(CASE WHEN shift_type = 'morning' THEN 1 ELSE 0 END) as j,
        SUM(CASE WHEN shift_type = 'afternoon' THEN 1 ELSE 0 END) as p,
        SUM(CASE WHEN shift_type = 'full' THEN 1 ELSE 0 END) as v,
        WEEK(shift_date, 1) as week_num
    FROM shifts
    WHERE shift_date BETWEEN ? AND ?
    GROUP BY user_id, WEEK(shift_date, 1)
");
$stmt->execute([$startDate, $endDate]);
while ($row = $stmt->fetch()) {
    $uid = $row['user_id'];
    $wk = $row['week_num'];
    if (!isset($shiftStats[$uid])) $shiftStats[$uid] = ['total' => ['j'=>0,'p'=>0,'v'=>0], 'weeks' => []];
    if (!isset($shiftStats[$uid]['weeks'][$wk])) $shiftStats[$uid]['weeks'][$wk] = ['j'=>0,'p'=>0,'v'=>0];
    $shiftStats[$uid]['weeks'][$wk]['j'] += $row['j'];
    $shiftStats[$uid]['weeks'][$wk]['p'] += $row['p'];
    $shiftStats[$uid]['weeks'][$wk]['v'] += $row['v'];
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

// Boje po osobama
function getUserColorClass($name) {
    $name = mb_strtolower($name);
    if (strpos($name, 'elvis') !== false) return 'user-elvis';
    if (strpos($name, 'ivek') !== false || strpos($name, 'ivan') !== false) return 'user-ivek';
    if (strpos($name, 'jakov') !== false) return 'user-jakov';
    if (strpos($name, 'marta') !== false) return 'user-marta';
    if (strpos($name, 'patrik') !== false) return 'user-patrik';
    if (strpos($name, 'rikard') !== false) return 'user-rikard';
    if (strpos($name, 'sabina') !== false) return 'user-sabina';
    return '';
}

require_once 'includes/header.php';
?>

<style>
.stat-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
.stat-table th, .stat-table td { padding: 0.4rem 0.5rem; border-bottom: 1px solid var(--border-color); text-align: left; }
.stat-table th { background: var(--bg-secondary); font-weight: 600; font-size: 0.75rem; }
.stat-table td.num { text-align: center; font-weight: 500; }
.stat-table tr:hover { opacity: 0.9; }
.j { color: #b45309; }
.p { color: #1d4ed8; }
.v { color: #4338ca; }
.week-header { font-size: 0.7rem; color: var(--text-muted); }
.event-list { font-size: 0.75rem; margin: 0; padding-left: 1rem; }
.event-list li { margin: 2px 0; }
.view-toggle { display: flex; gap: 0; }
.view-toggle a { padding: 0.3rem 0.6rem; font-size: 0.75rem; border: 1px solid var(--border-color); text-decoration: none; color: var(--text-secondary); }
.view-toggle a:first-child { border-radius: 4px 0 0 4px; }
.view-toggle a:last-child { border-radius: 0 4px 4px 0; border-left: 0; }
.view-toggle a.active { background: var(--primary-color); color: white; border-color: var(--primary-color); }
.user-elvis { background: #fce7f3 !important; }
.user-ivek { background: #dcfce7 !important; }
.user-jakov { background: #d7ccc8 !important; }
.user-marta { background: #fecaca !important; }
.user-patrik { background: #fef9c3 !important; }
.user-rikard { background: #fed7aa !important; }
.user-sabina { background: #dbeafe !important; }
</style>

<div class="card" style="margin-bottom: 0.75rem; padding: 0.75rem;">
    <div class="d-flex" style="align-items: center; justify-content: space-between;">
        <div class="d-flex gap-1" style="align-items: center;">
            <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>&view=<?= e($view) ?>" class="btn btn-outline" style="padding: 0.3rem 0.6rem;">&#8249;</a>
            <span style="font-weight: 600; font-size: 1.1rem; min-width: 160px; text-align: center;"><?= $monthNames[$month] ?> <?= $year ?></span>
            <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>&view=<?= e($view) ?>" class="btn btn-outline" style="padding: 0.3rem 0.6rem;">&#8250;</a>
        </div>
        <div class="view-toggle">
            <a href="?month=<?= $month ?>&year=<?= $year ?>&view=month" class="<?= $view == 'month' ? 'active' : '' ?>">Mjesec</a>
            <a href="?month=<?= $month ?>&year=<?= $year ?>&view=week" class="<?= $view == 'week' ? 'active' : '' ?>">Tjedni</a>
        </div>
    </div>
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
                <th class="num"><span class="j">Jutarnja</span></th>
                <th class="num"><span class="p">Popodnevna</span></th>
                <th class="num"><span class="v">Večernja</span></th>
                <th class="num">Ukupno</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user):
                $stats = $shiftStats[$user['id']] ?? null;
                if (!$stats) continue;
                $total = $stats['total']['j'] + $stats['total']['p'] + $stats['total']['v'];
            ?>
            <tr class="<?= getUserColorClass($user['full_name']) ?>">
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
            <tr class="<?= getUserColorClass($user['full_name']) ?>">
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
