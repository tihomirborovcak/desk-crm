<?php
/**
 * Dežurstva - Kalendar
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

define('PAGE_TITLE', 'Dežurstva');

$db = getDB();
$userId = $_SESSION['user_id'];
$isEditorRole = isEditor();

// Trenutni mjesec
$year = intval($_GET['year'] ?? date('Y'));
$month = intval($_GET['month'] ?? date('n'));

// Validacija
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$startDayOfWeek = date('N', $firstDay); // 1=Pon, 7=Ned
$monthName = date('F Y', $firstDay);

// Prethodni i sljedeći mjesec
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// Dohvati dežurstva za mjesec
$startDate = date('Y-m-d', $firstDay);
$endDate = date('Y-m-t', $firstDay);

$stmt = $db->prepare("
    SELECT s.*, u.full_name 
    FROM shifts s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.shift_date BETWEEN ? AND ?
    ORDER BY s.shift_date, s.shift_type
");
$stmt->execute([$startDate, $endDate]);
$shifts = $stmt->fetchAll();

// Grupiraj po datumu
$shiftsByDate = [];
foreach ($shifts as $shift) {
    $date = $shift['shift_date'];
    if (!isset($shiftsByDate[$date])) {
        $shiftsByDate[$date] = [];
    }
    $shiftsByDate[$date][] = $shift;
}

// Dodavanje/uklanjanje dežurstva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isEditorRole) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setMessage('danger', 'Nevažeći sigurnosni token');
    } else {
        $action = $_POST['shift_action'] ?? '';
        $shiftUserId = intval($_POST['user_id'] ?? 0);
        $shiftDate = $_POST['shift_date'] ?? '';
        $shiftType = $_POST['shift_type'] ?? '';
        
        if ($action === 'add' && $shiftUserId && $shiftDate && $shiftType) {
            try {
                $stmt = $db->prepare("INSERT INTO shifts (user_id, shift_date, shift_type, assigned_by) VALUES (?, ?, ?, ?)");
                $stmt->execute([$shiftUserId, $shiftDate, $shiftType, $userId]);
                
                logActivity('shift_add', 'shift', $db->lastInsertId());
                setMessage('success', 'Dežurstvo je dodano');
            } catch (Exception $e) {
                setMessage('warning', 'Dežurstvo već postoji za taj dan i smjenu');
            }
        } elseif ($action === 'remove') {
            $shiftId = intval($_POST['shift_id'] ?? 0);
            $stmt = $db->prepare("DELETE FROM shifts WHERE id = ?");
            $stmt->execute([$shiftId]);
            
            logActivity('shift_remove', 'shift', $shiftId);
            setMessage('success', 'Dežurstvo je uklonjeno');
        }
        
        header("Location: shifts.php?year=$year&month=$month");
        exit;
    }
}

// Korisnici za select
$users = getUsers();

// Hrvatski nazivi dana
$daysHr = ['Pon', 'Uto', 'Sri', 'Čet', 'Pet', 'Sub', 'Ned'];
$monthsHr = [
    1 => 'Siječanj', 2 => 'Veljača', 3 => 'Ožujak', 4 => 'Travanj',
    5 => 'Svibanj', 6 => 'Lipanj', 7 => 'Srpanj', 8 => 'Kolovoz',
    9 => 'Rujan', 10 => 'Listopad', 11 => 'Studeni', 12 => 'Prosinac'
];

include 'includes/header.php';
?>

<div class="d-flex" style="justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <h1>Dežurstva</h1>
    <?php if ($isEditorRole): ?>
    <button class="btn btn-primary" data-modal="addShiftModal">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Dodaj
    </button>
    <?php endif; ?>
</div>

<!-- Kalendar -->
<div class="calendar mt-2">
    <div class="calendar-header">
        <div class="calendar-nav">
            <a href="?year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: white; border: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
            </a>
        </div>
        <div class="calendar-title"><?= $monthsHr[$month] ?> <?= $year ?></div>
        <div class="calendar-nav">
            <a href="?year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: white; border: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </a>
        </div>
    </div>
    
    <div class="calendar-grid">
        <!-- Zaglavlja dana -->
        <?php foreach ($daysHr as $day): ?>
        <div class="calendar-day-header"><?= $day ?></div>
        <?php endforeach; ?>
        
        <!-- Prazne ćelije na početku -->
        <?php for ($i = 1; $i < $startDayOfWeek; $i++): ?>
        <div class="calendar-day other-month"></div>
        <?php endfor; ?>
        
        <!-- Dani u mjesecu -->
        <?php for ($day = 1; $day <= $daysInMonth; $day++): 
            $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $isToday = ($currentDate === date('Y-m-d'));
            $dayShifts = $shiftsByDate[$currentDate] ?? [];
        ?>
        <div class="calendar-day <?= $isToday ? 'today' : '' ?>">
            <div class="calendar-day-number"><?= $day ?></div>
            <?php foreach ($dayShifts as $shift): ?>
            <div class="calendar-shift <?= $shift['shift_type'] ?>" title="<?= e($shift['full_name']) ?> - <?= translateShift($shift['shift_type']) ?>">
                <span class="shift-name"><?= e(mb_substr($shift['full_name'], 0, 8)) ?></span>
                <?php if ($isEditorRole): ?>
                <form method="POST" class="shift-delete-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="shift_action" value="remove">
                    <input type="hidden" name="shift_id" value="<?= $shift['id'] ?>">
                    <button type="submit" class="shift-delete-btn" title="Obriši dežurstvo">×</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endfor; ?>
        
        <!-- Prazne ćelije na kraju -->
        <?php 
        $totalCells = $startDayOfWeek - 1 + $daysInMonth;
        $remainingCells = 7 - ($totalCells % 7);
        if ($remainingCells < 7):
            for ($i = 0; $i < $remainingCells; $i++): 
        ?>
        <div class="calendar-day other-month"></div>
        <?php endfor; endif; ?>
    </div>
</div>

<!-- Legenda -->
<div class="card mt-2">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-1">
            <span class="badge" style="background: #fef3c7; color: #92400e;">Jutarnja</span>
            <span class="badge" style="background: #dbeafe; color: #1e40af;">Popodnevna</span>
            <span class="badge" style="background: #dcfce7; color: #166534;">Cijeli dan</span>
        </div>
    </div>
</div>

<!-- Moja dežurstva ovaj mjesec -->
<?php
$stmt = $db->prepare("
    SELECT * FROM shifts 
    WHERE user_id = ? AND shift_date BETWEEN ? AND ?
    ORDER BY shift_date
");
$stmt->execute([$userId, $startDate, $endDate]);
$myShifts = $stmt->fetchAll();

if (!empty($myShifts)):
?>
<div class="card mt-2">
    <div class="card-header">
        <h2 class="card-title">Moja dežurstva ovaj mjesec</h2>
    </div>
    <div class="card-body">
        <?php foreach ($myShifts as $shift): ?>
        <div class="d-flex gap-1 mb-1" style="align-items: center;">
            <span class="badge" style="<?php
                if ($shift['shift_type'] === 'morning') echo 'background: #fef3c7; color: #92400e;';
                elseif ($shift['shift_type'] === 'afternoon') echo 'background: #dbeafe; color: #1e40af;';
                else echo 'background: #dcfce7; color: #166534;';
            ?>">
                <?= translateShift($shift['shift_type']) ?>
            </span>
            <span><?= formatDate($shift['shift_date'], 'l, j.n.Y.') ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($isEditorRole): ?>
<!-- Modal za dodavanje dežurstva -->
<div class="modal" id="addShiftModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Dodaj dežurstvo</h3>
            <button class="modal-close">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST" id="addShiftForm">
                <?= csrfField() ?>
                <input type="hidden" name="shift_action" value="add">
                
                <div class="form-group">
                    <label class="form-label" for="user_id">Korisnik</label>
                    <select id="user_id" name="user_id" class="form-control" required>
                        <option value="">-- Odaberite korisnika --</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= e($u['full_name']) ?> (<?= translateRole($u['role']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="shift_date">Datum</label>
                    <input type="date" 
                           id="shift_date" 
                           name="shift_date" 
                           class="form-control" 
                           value="<?= date('Y-m-d') ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="shift_type">Smjena</label>
                    <select id="shift_type" name="shift_type" class="form-control" required>
                        <option value="morning">Jutarnja (06:00 - 14:00)</option>
                        <option value="afternoon">Popodnevna (14:00 - 22:00)</option>
                        <option value="full">Cijeli dan</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" data-modal-close>Odustani</button>
            <button type="submit" form="addShiftForm" class="btn btn-primary">Dodaj dežurstvo</button>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.calendar-shift {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 4px;
}
.shift-name {
    overflow: hidden;
    text-overflow: ellipsis;
}
.shift-delete-form {
    display: inline;
    flex-shrink: 0;
}
.shift-delete-btn {
    background: rgba(220,53,69,0.15);
    border: none;
    color: #dc3545;
    cursor: pointer;
    padding: 0 4px;
    font-size: 14px;
    font-weight: bold;
    border-radius: 3px;
    line-height: 1;
}
.shift-delete-btn:hover {
    background: #dc3545;
    color: white;
}
</style>

<?php include 'includes/footer.php'; ?>
