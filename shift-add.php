<?php
/**
 * Brzo dodavanje/izmjena dežurstva
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();


if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    redirectWith('events.php', 'danger', 'Nevažeći zahtjev');
}

$db = getDB();
$userId = $_SESSION['user_id'];

$shiftDate = $_POST['shift_date'] ?? '';
$shiftType = $_POST['shift_type'] ?? '';
$assignedUserId = intval($_POST['user_id'] ?? 0);

// Redirect će ići na events.php s pravim mjesecom
$monthParam = !empty($shiftDate) ? date('Y-m', strtotime($shiftDate)) : date('Y-m');
$redirectUrl = "events.php?month=$monthParam";

if (empty($shiftDate) || empty($shiftType) || !$assignedUserId) {
    redirectWith($redirectUrl, 'danger', 'Sva polja su obavezna');
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
    redirectWith($redirectUrl, 'danger', 'Nevažeći tip smjene');
}

// Provjeri da li korisnik postoji
$stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$assignedUserId]);
if (!$stmt->fetch()) {
    redirectWith($redirectUrl, 'danger', 'Korisnik nije pronađen');
}

try {
    // Provjeri da li već postoji smjena za taj dan i tip (bilo koji korisnik)
    $stmt = $db->prepare("SELECT id, user_id FROM shifts WHERE shift_date = ? AND shift_type = ?");
    $stmt->execute([$shiftDate, $mappedShiftType]);
    $existingShift = $stmt->fetch();

    if ($existingShift) {
        // Ako postoji, ažuriraj korisnika
        $stmt = $db->prepare("UPDATE shifts SET user_id = ?, assigned_by = ? WHERE id = ?");
        $stmt->execute([$assignedUserId, $userId, $existingShift['id']]);

        logActivity('shift_update', 'shift', $existingShift['id']);
        redirectWith($redirectUrl, 'success', 'Dežurstvo ažurirano');
    } else {
        // Ako ne postoji, dodaj novo
        $stmt = $db->prepare("
            INSERT INTO shifts (user_id, shift_date, shift_type, assigned_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$assignedUserId, $shiftDate, $mappedShiftType, $userId]);

        $shiftId = $db->lastInsertId();
        logActivity('shift_add', 'shift', $shiftId);
        redirectWith($redirectUrl, 'success', 'Dežurstvo dodano');
    }
} catch (PDOException $e) {
    redirectWith($redirectUrl, 'danger', 'Greška: ' . $e->getMessage());
}
