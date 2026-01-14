<?php
/**
 * Kalendar događaja
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

define('PAGE_TITLE', 'Kalendar');

$db = getDB();
$userId = $_SESSION['user_id'];
$isEditorRole = isEditor();

// Mjesec za prikaz
$month = $_GET['month'] ?? date('Y-m');
$monthStart = $month . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));

// Navigacija
$prevMonth = date('Y-m', strtotime($monthStart . ' -1 month'));
$nextMonth = date('Y-m', strtotime($monthStart . ' +1 month'));

// Hrvatski nazivi mjeseci
$monthNames = ['', 'Siječanj', 'Veljača', 'Ožujak', 'Travanj', 'Svibanj', 'Lipanj', 
               'Srpanj', 'Kolovoz', 'Rujan', 'Listopad', 'Studeni', 'Prosinac'];
$monthNum = intval(date('n', strtotime($monthStart)));
$year = date('Y', strtotime($monthStart));
$monthName = $monthNames[$monthNum] . ' ' . $year;

// Dohvati korisnike za modal
$users = getUsers();

// Dohvati evente za mjesec
$stmt = $db->prepare("
    SELECT e.*, 
           GROUP_CONCAT(DISTINCT u.full_name ORDER BY u.full_name SEPARATOR ', ') as assigned_people,
           COUNT(ea.id) as assigned_count
    FROM events e 
    LEFT JOIN event_assignments ea ON e.id = ea.event_id
    LEFT JOIN users u ON ea.user_id = u.id
    WHERE e.event_date BETWEEN ? AND ?
    GROUP BY e.id
    ORDER BY e.event_date, e.event_time
");
$stmt->execute([$monthStart, $monthEnd]);
$events = $stmt->fetchAll();

// Grupiraj evente po datumu
$eventsByDate = [];
foreach ($events as $event) {
    $date = $event['event_date'];
    if (!isset($eventsByDate[$date])) {
        $eventsByDate[$date] = [];
    }
    $eventsByDate[$date][] = $event;
}

// Kalendar podaci
$firstDayOfMonth = date('N', strtotime($monthStart)); // 1=Pon, 7=Ned
$daysInMonth = date('t', strtotime($monthStart));
$today = date('Y-m-d');

// Hrvatski dani
$daysHr = ['Pon', 'Uto', 'Sri', 'Čet', 'Pet', 'Sub', 'Ned'];

include 'includes/header.php';
?>

<div class="d-flex" style="justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <h1>📅 Kalendar</h1>
    <?php if ($isEditorRole): ?>
    <div class="d-flex gap-1">
        <button class="btn btn-outline" data-modal="shiftModal">+ Dežurstvo</button>
        <a href="event-edit.php" class="btn btn-primary">+ Novi događaj</a>
    </div>
    <?php endif; ?>
</div>

<!-- Modal za brzo dežurstvo -->
<div class="modal" id="shiftModal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">Novo dežurstvo</h3>
            <button class="modal-close" onclick="closeModal('shiftModal')">&times;</button>
        </div>
        <form method="POST" action="shift-add.php">
            <?= csrfField() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Datum *</label>
                    <input type="date" name="shift_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Smjena *</label>
                    <select name="shift_type" class="form-control" required>
                        <option value="jutarnja">☀️ Jutarnja (06-14h)</option>
                        <option value="popodnevna">🌤️ Popodnevna (14-22h)</option>
                        <option value="vecernja">🌙 Večernja (22-06h)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tko dežura *</label>
                    <select name="user_id" class="form-control" required>
                        <option value="">-- Odaberi --</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= e($u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('shiftModal')">Odustani</button>
                <button type="submit" class="btn btn-primary">Dodaj</button>
            </div>
        </form>
    </div>
</div>

<!-- Navigacija mjeseca -->
<div class="card mt-2">
    <div class="card-body" style="padding: 0.75rem;">
        <div class="d-flex gap-1" style="align-items: center; justify-content: center;">
            <a href="?month=<?= $prevMonth ?>" class="btn btn-outline">‹</a>
            <span style="font-weight: 600; font-size: 1.2rem; padding: 0 1rem; min-width: 180px; text-align: center;"><?= $monthName ?></span>
            <a href="?month=<?= $nextMonth ?>" class="btn btn-outline">›</a>
        </div>
    </div>
</div>

<!-- Mobilna lista događaja -->
<div class="mobile-events-list mt-2">
    <?php if (empty($events)): ?>
    <div class="card">
        <div class="card-body text-center text-muted">
            Nema događaja za ovaj mjesec
        </div>
    </div>
    <?php else: ?>
    <?php 
    $currentDate = '';
    foreach ($events as $evt): 
        $evtDate = $evt['event_date'];
        if ($evtDate !== $currentDate):
            $currentDate = $evtDate;
    ?>
    <div style="font-weight: 600; color: var(--gray-600); padding: 0.5rem 0; border-bottom: 2px solid var(--primary); margin-top: 0.5rem;">
        <?= date('l, j.n.', strtotime($evtDate)) ?>
    </div>
    <?php endif; ?>
    
    <div class="mobile-event-card type-<?= $evt['event_type'] ?> <?= !empty($evt['skip_coverage']) ? 'skipped' : '' ?>">
        <div class="mobile-event-header">
            <div class="mobile-event-title">
                <?php if (!empty($evt['skip_coverage'])): ?>
                <span class="badge badge-secondary">NE IDEMO</span>
                <?php elseif ($evt['importance'] === 'must_cover'): ?>
                <span class="badge badge-danger">OBAVEZNO</span>
                <?php elseif ($evt['importance'] === 'important'): ?>
                <span class="badge badge-warning">VAŽNO</span>
                <?php endif; ?>
                <a href="event-edit.php?id=<?= $evt['id'] ?>"><?= e($evt['title']) ?></a>
            </div>
            <?php if ($isEditorRole): ?>
            <a href="event-edit.php?id=<?= $evt['id'] ?>&delete=1" class="btn-delete-evt" data-confirm="Obrisati događaj?" title="Obriši">×</a>
            <?php endif; ?>
        </div>

        <div class="mobile-event-meta">
            <?php if ($evt['event_time']): ?>
            <span>🕐 <?= date('H:i', strtotime($evt['event_time'])) ?><?= $evt['end_time'] ? '-' . date('H:i', strtotime($evt['end_time'])) : '' ?></span>
            <?php endif; ?>
            <?php if ($evt['location']): ?>
            <span>📍 <?= e(truncate($evt['location'], 20)) ?></span>
            <?php endif; ?>
            <?php if ($evt['assigned_people'] && empty($evt['skip_coverage'])): ?>
            <span>👥 <?= e($evt['assigned_people']) ?></span>
            <?php elseif (empty($evt['skip_coverage']) && !$evt['assigned_count']): ?>
            <span class="text-danger">⚠️ Nitko</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Desktop kalendar -->
<div class="calendar-wrapper mt-2">
    <div class="calendar">
        <!-- Dani u tjednu -->
        <?php foreach ($daysHr as $day): ?>
        <div class="calendar-header"><?= $day ?></div>
        <?php endforeach; ?>
        
        <!-- Prazne ćelije prije prvog dana -->
        <?php for ($i = 1; $i < $firstDayOfMonth; $i++): ?>
        <div class="calendar-day empty"></div>
        <?php endfor; ?>
        
        <!-- Dani u mjesecu -->
        <?php for ($day = 1; $day <= $daysInMonth; $day++): 
            $date = $month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
            $isToday = $date === $today;
            $hasEvents = isset($eventsByDate[$date]);
            $dayEvents = $eventsByDate[$date] ?? [];
            
            // Odvoji dežurstva od ostalih događaja
            $shifts = ['jutarnja' => null, 'popodnevna' => null, 'vecernja' => null];
            $regularEvents = [];
            
            foreach ($dayEvents as $evt) {
                if ($evt['event_type'] === 'dezurstvo') {
                    // Odredi tip smjene iz naslova
                    if (stripos($evt['title'], 'jutarn') !== false) {
                        $shifts['jutarnja'] = $evt;
                    } elseif (stripos($evt['title'], 'popodnevn') !== false) {
                        $shifts['popodnevna'] = $evt;
                    } elseif (stripos($evt['title'], 'večern') !== false || stripos($evt['title'], 'vecern') !== false) {
                        $shifts['vecernja'] = $evt;
                    }
                } else {
                    $regularEvents[] = $evt;
                }
            }
            
            $hasShifts = $shifts['jutarnja'] || $shifts['popodnevna'] || $shifts['vecernja'];
        ?>
        <div class="calendar-day <?= $isToday ? 'today' : '' ?> <?= $hasEvents ? 'has-events' : '' ?>">
            <div class="calendar-day-number"><?= $day ?></div>
            
            <?php if ($hasShifts): ?>
            <div class="shifts-compact">
                <div class="shift-row">
                    <span class="shift-label">J:</span>
                    <span class="shift-name"><?= $shifts['jutarnja'] ? e($shifts['jutarnja']['assigned_people'] ?: '—') : '—' ?></span>
                </div>
                <div class="shift-row">
                    <span class="shift-label">P:</span>
                    <span class="shift-name"><?= $shifts['popodnevna'] ? e($shifts['popodnevna']['assigned_people'] ?: '—') : '—' ?></span>
                </div>
                <div class="shift-row">
                    <span class="shift-label">V:</span>
                    <span class="shift-name"><?= $shifts['vecernja'] ? e($shifts['vecernja']['assigned_people'] ?: '—') : '—' ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($regularEvents)): ?>
            <div class="calendar-events">
                <?php foreach ($regularEvents as $evt): ?>
                <a href="event-edit.php?id=<?= $evt['id'] ?>"
                   class="calendar-event <?= !empty($evt['skip_coverage']) ? 'skipped' : '' ?> type-<?= $evt['event_type'] ?>" title="<?= e($evt['assigned_people'] ?: 'Nitko dodijeljen') ?>">
                    <?php if ($evt['event_time']): ?><span class="evt-time"><?= date('H:i', strtotime($evt['event_time'])) ?></span><?php endif; ?>
                    <span class="evt-name"><?= e(truncate($evt['title'], 25)) ?></span>
                    <?php if (!empty($evt['skip_coverage'])): ?><span class="evt-badge skip">✗</span>
                    <?php elseif (!$evt['assigned_count']): ?><span class="evt-badge warn">!</span><?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endfor; ?>
    </div>
</div>

<!-- Legenda -->
<div class="card mt-2">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-1" style="font-size: 0.85rem;">
            <span><span class="legend-dot type-press"></span> Press</span>
            <span><span class="legend-dot type-sport"></span> Sport</span>
            <span><span class="legend-dot type-kultura"></span> Kultura</span>
            <span><span class="legend-dot type-politika"></span> Politika</span>
            <span><span class="legend-dot type-drustvo"></span> Društvo</span>
            <span><span class="legend-dot type-dezurstvo"></span> Dežurstvo</span>
            <span><span class="legend-dot type-ostalo"></span> Ostalo</span>
            <span style="margin-left: 1rem;"><span class="warn-badge-legend">!</span> Nema dodjele</span>
            <span><span class="skip-badge-legend">✗</span> Ne idemo</span>
        </div>
    </div>
</div>

<!-- Lista događaja -->
<div class="card mt-2">
    <div class="card-header">
        <h2 class="card-title">Svi događaji - <?= $monthName ?></h2>
    </div>
    <?php if (empty($events)): ?>
    <div class="card-body">
        <p class="text-muted text-center">Nema događaja za ovaj mjesec</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Događaj</th>
                    <th>Lokacija</th>
                    <th>Tko ide</th>
                    <th>Napomena</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $evt): ?>
                <tr class="<?= !empty($evt['skip_coverage']) ? 'row-skipped' : '' ?>">
                    <td style="white-space: nowrap;">
                        <strong><?= date('j.n.', strtotime($evt['event_date'])) ?></strong>
                        <?php if ($evt['event_time']): ?>
                        <br><span class="text-muted"><?= date('H:i', strtotime($evt['event_time'])) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="event-edit.php?id=<?= $evt['id'] ?>"><strong><?= e($evt['title']) ?></strong></a>
                        <br>
                        <span class="badge badge-<?= eventTypeColor($evt['event_type']) ?>"><?= translateEventType($evt['event_type']) ?></span>
                        <?php if ($evt['importance'] !== 'normal'): ?>
                        <span class="badge badge-<?= $evt['importance'] === 'must_cover' ? 'danger' : 'warning' ?>">
                            <?= $evt['importance'] === 'must_cover' ? 'OBAVEZNO' : 'VAŽNO' ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($evt['location'] ?: '-') ?></td>
                    <td>
                        <?php if (!empty($evt['skip_coverage'])): ?>
                            <span class="text-muted">—</span>
                        <?php elseif ($evt['assigned_people']): ?>
                            <?= e($evt['assigned_people']) ?>
                        <?php else: ?>
                            <span class="text-danger">⚠️ Nitko</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted" style="max-width: 200px;">
                        <?= e($evt['notes'] ? truncate($evt['notes'], 80) : '-') ?>
                    </td>
                    <td>
                        <?php if (!empty($evt['skip_coverage'])): ?>
                            <span class="badge badge-secondary">Ne idemo</span>
                        <?php elseif ($evt['assigned_count']): ?>
                            <span class="badge badge-success">OK</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Čeka</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<style>
/* Desktop kalendar - sakrij na mobitelu */
.calendar-wrapper {
    overflow-x: auto;
    display: none;
}
@media (min-width: 768px) {
    .calendar-wrapper {
        display: block;
    }
    .mobile-events-list {
        display: none;
    }
}
.calendar {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    grid-auto-rows: minmax(120px, auto);
    gap: 1px;
    background: var(--gray-200);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius);
    min-width: 900px;
}

/* Mobilna lista */
.mobile-events-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.mobile-event-card {
    background: var(--white);
    border-radius: var(--radius);
    padding: 0.5rem 0.75rem;
    box-shadow: var(--shadow);
    border-left: 4px solid var(--gray-400);
}
.mobile-event-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.5rem;
}
.btn-delete-evt {
    color: #dc3545;
    font-size: 1.2rem;
    font-weight: bold;
    line-height: 1;
    padding: 0 0.25rem;
    text-decoration: none;
}
.btn-delete-evt:hover {
    color: #a71d2a;
}
.mobile-event-card.type-press { border-left-color: #dc3545; }
.mobile-event-card.type-sport { border-left-color: #28a745; }
.mobile-event-card.type-kultura { border-left-color: #6f42c1; }
.mobile-event-card.type-politika { border-left-color: #fd7e14; }
.mobile-event-card.type-drustvo { border-left-color: #17a2b8; }
.mobile-event-card.type-dezurstvo { border-left-color: #20c997; background: rgba(32,201,151,0.05); }
.mobile-event-card.type-ostalo { border-left-color: #6c757d; }
.mobile-event-card.skipped {
    opacity: 0.6;
    border-left-color: var(--gray-400);
}
.mobile-event-date {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-bottom: 0.25rem;
}
.mobile-event-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}
.mobile-event-title a {
    color: var(--dark);
}
.mobile-event-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-top: 0.25rem;
}
.calendar-header {
    background: var(--gray-100);
    padding: 0.5rem;
    text-align: center;
    font-weight: 600;
    font-size: 0.85rem;
}
.calendar-day {
    background: var(--white);
    min-height: 120px;
    height: auto;
    padding: 0.25rem;
    vertical-align: top;
}
.calendar-day.empty {
    background: var(--gray-50);
}
.calendar-day.today {
    background: #e3f2fd;
}
.calendar-day.today .calendar-day-number {
    background: var(--primary);
    color: white;
}
.calendar-day-number {
    display: inline-block;
    width: 24px;
    height: 24px;
    line-height: 24px;
    text-align: center;
    border-radius: 50%;
    font-size: 0.85rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
}
/* Kompaktni prikaz dežurstava */
.shifts-compact {
    background: rgba(32, 201, 151, 0.1);
    border-radius: 3px;
    padding: 2px 4px;
    margin-bottom: 4px;
    font-size: 0.65rem;
    line-height: 1.3;
}
.shift-row {
    display: flex;
    gap: 3px;
    white-space: nowrap;
    overflow: hidden;
}
.shift-label {
    font-weight: 700;
    color: #0ca678;
    min-width: 12px;
}
.shift-name {
    color: var(--gray-700);
    overflow: hidden;
    text-overflow: ellipsis;
}
.calendar-events {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.calendar-event {
    display: flex;
    align-items: center;
    gap: 3px;
    padding: 2px 4px;
    font-size: 0.65rem;
    border-radius: 3px;
    text-decoration: none;
    border-left: 2px solid;
    white-space: nowrap;
    overflow: hidden;
}
.calendar-event:hover {
    opacity: 0.85;
    text-decoration: none;
}
.calendar-event.skipped {
    opacity: 0.5;
}
.calendar-event.skipped .evt-name {
    text-decoration: line-through;
}
.evt-time {
    font-weight: 600;
    flex-shrink: 0;
}
.evt-name {
    overflow: hidden;
    text-overflow: ellipsis;
}
.evt-badge {
    flex-shrink: 0;
    font-weight: bold;
    font-size: 0.6rem;
}
.evt-badge.warn { color: #ffc107; }
.evt-badge.skip { opacity: 0.7; }
.type-press { background: rgba(220, 53, 69, 0.1); color: #a71d2a; border-left-color: #dc3545; }
.type-sport { background: rgba(40, 167, 69, 0.1); color: #1e7e34; border-left-color: #28a745; }
.type-kultura { background: rgba(111, 66, 193, 0.1); color: #5a32a3; border-left-color: #6f42c1; }
.type-politika { background: rgba(253, 126, 20, 0.1); color: #c96a10; border-left-color: #fd7e14; }
.type-drustvo { background: rgba(23, 162, 184, 0.1); color: #117a8b; border-left-color: #17a2b8; }
.type-dezurstvo { background: rgba(32, 201, 151, 0.15); color: #0ca678; border-left-color: #20c997; }
.type-ostalo { background: rgba(108, 117, 125, 0.1); color: #545b62; border-left-color: #6c757d; }

.legend-dot {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 3px;
    margin-right: 4px;
    vertical-align: middle;
}
.legend-dot.type-dezurstvo { background: #20c997; }
.warn-badge {
    color: #ffc107;
    font-weight: bold;
    margin-left: 4px;
}
.skip-badge {
    opacity: 0.8;
    margin-left: 4px;
}
.warn-badge-legend {
    display: inline-block;
    background: #ffc107;
    color: #000;
    width: 16px;
    height: 16px;
    line-height: 16px;
    text-align: center;
    border-radius: 3px;
    font-size: 0.7rem;
    font-weight: bold;
    margin-right: 4px;
}
.skip-badge-legend {
    display: inline-block;
    background: #6c757d;
    color: #fff;
    width: 16px;
    height: 16px;
    line-height: 16px;
    text-align: center;
    border-radius: 3px;
    font-size: 0.7rem;
    margin-right: 4px;
}
.row-skipped {
    opacity: 0.6;
    background: var(--gray-50);
}
</style>

<?php include 'includes/footer.php'; ?>
