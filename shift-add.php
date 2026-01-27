<?php
/**
 * Brzo dodavanje dežurstva
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

if (!isEditor()) {
    redirectWith('shifts.php', 'danger', 'Nemate ovlasti');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    redirectWith('shifts.php', 'danger', 'Nevažeći zahtjev');
}

$db = getDB();
$userId = $_SESSION['user_id'];

$shiftDate = $_POST['shift_date'] ?? '';
$shiftType = $_POST['shift_type'] ?? '';
$assignedUserId = intval($_POST['user_id'] ?? 0);

if (empty($shiftDate) || empty($shiftType) || !$assignedUserId) {
    redirectWith('shifts.php', 'danger', 'Sva polja su obavezna');
}

// Mapiranje starih naziva na nove enum vrijednosti ako je potrebno
$shiftTypeMap = [
    'jutarnja' => 'morning',
    'popodnevna' => 'afternoon',
    'vecernja' => 'full',
    'morning' => 'morning',
    'afternoon' => 'afternoon',
    'full' => 'full'
];

$mappedShiftType = $shiftTypeMap[$shiftType] ?? $shiftType;

// Validiraj shift type
if (!in_array($mappedShiftType, ['morning', 'afternoon', 'full'])) {
    redirectWith('shifts.php', 'danger', 'Nevažeći tip smjene');
}

// Provjeri da li korisnik postoji
$stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$assignedUserId]);
if (!$stmt->fetch()) {
    redirectWith('shifts.php', 'danger', 'Korisnik nije pronađen');
}

// Provjeri da li već postoji ista smjena za isti dan i korisnika
$stmt = $db->prepare("
    SELECT id FROM shifts
    WHERE shift_date = ? AND shift_type = ? AND user_id = ?
");
$stmt->execute([$shiftDate, $mappedShiftType, $assignedUserId]);
if ($stmt->fetch()) {
    redirectWith('shifts.php', 'warning', 'Ta smjena već postoji za tog korisnika na taj dan');
}

// Dodaj dežurstvo u shifts tablicu
try {
    $stmt = $db->prepare("
        INSERT INTO shifts (user_id, shift_date, shift_type, assigned_by)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$assignedUserId, $shiftDate, $mappedShiftType, $userId]);

    $shiftId = $db->lastInsertId();
    logActivity('shift_add', 'shift', $shiftId);

    // Redirect na mjesec tog datuma
    $year = date('Y', strtotime($shiftDate));
    $month = date('n', strtotime($shiftDate));
    redirectWith("shifts.php?year=$year&month=$month", 'success', 'Dežurstvo dodano');
} catch (PDOException $e) {
    redirectWith('shifts.php', 'danger', 'Greška pri dodavanju: ' . $e->getMessage());
}
