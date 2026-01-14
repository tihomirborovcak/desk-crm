<?php
/**
 * Brzo dodavanje dežurstva
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

if (!isEditor()) {
    redirectWith('events.php', 'danger', 'Nemate ovlasti');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    redirectWith('events.php', 'danger', 'Nevažeći zahtjev');
}

$db = getDB();
$userId = $_SESSION['user_id'];

$shiftDate = $_POST['shift_date'] ?? '';
$shiftType = $_POST['shift_type'] ?? '';
$assignedUserId = intval($_POST['user_id'] ?? 0);

if (empty($shiftDate) || empty($shiftType) || !$assignedUserId) {
    redirectWith('events.php', 'danger', 'Sva polja su obavezna');
}

// Dohvati ime korisnika
$stmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$assignedUserId]);
$user = $stmt->fetch();

if (!$user) {
    redirectWith('events.php', 'danger', 'Korisnik nije pronađen');
}

// Smjena detalji
$shiftLabels = [
    'jutarnja' => ['☀️ Jutarnja smjena', '07:30', '12:00'],
    'popodnevna' => ['🌤️ Popodnevna smjena', '12:00', '19:30'],
    'vecernja' => ['🌙 Večernja smjena', '19:30', '07:30']
];

$label = $shiftLabels[$shiftType] ?? ['Dežurstvo', null, null];
$title = $label[0] . ' - ' . $user['full_name'];

// Provjeri da li već postoji ista smjena za isti dan
$stmt = $db->prepare("
    SELECT id FROM events 
    WHERE event_date = ? AND event_type = 'dezurstvo' AND title LIKE ?
");
$stmt->execute([$shiftDate, '%' . $shiftType . '%']);
if ($stmt->fetch()) {
    redirectWith('events.php', 'warning', 'Ta smjena već postoji za taj dan');
}

// Dodaj događaj
$stmt = $db->prepare("
    INSERT INTO events (title, event_date, event_time, end_time, event_type, importance, created_by)
    VALUES (?, ?, ?, ?, 'dezurstvo', 'normal', ?)
");
$stmt->execute([$title, $shiftDate, $label[1], $label[2], $userId]);

$eventId = $db->lastInsertId();

// Dodaj assignment
$stmt = $db->prepare("
    INSERT INTO event_assignments (event_id, user_id, role)
    VALUES (?, ?, 'dezurni')
");
$stmt->execute([$eventId, $assignedUserId]);

logActivity('shift_add', 'event', $eventId);

// Redirect na mjesec tog datuma
$month = date('Y-m', strtotime($shiftDate));
redirectWith("events.php?month=$month", 'success', 'Dežurstvo dodano');
