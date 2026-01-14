<?php
/**
 * Uređivanje / Kreiranje događaja
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$isEditorRole = isEditor();

$id = intval($_GET['id'] ?? 0);
$event = null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch();
    
    if (!$event) {
        redirectWith('events.php', 'danger', 'Događaj nije pronađen');
    }
    
    define('PAGE_TITLE', 'Uredi događaj');
} else {
    if (!$isEditorRole) {
        redirectWith('events.php', 'danger', 'Nemate ovlasti za kreiranje događaja');
    }
    define('PAGE_TITLE', 'Novi događaj');
}

$errors = [];

// Obrada forme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Nevažeći sigurnosni token';
    } else {
        $action = $_POST['form_action'] ?? 'save_event';
        
        if ($action === 'save_event' && $isEditorRole) {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $eventDate = $_POST['event_date'] ?? '';
            $eventTime = $_POST['event_time'] ?? null;
            $endTime = $_POST['end_time'] ?? null;
            $eventType = $_POST['event_type'] ?? 'ostalo';
            $importance = $_POST['importance'] ?? 'normal';
            $notes = trim($_POST['notes'] ?? '');
            $skipCoverage = isset($_POST['skip_coverage']) ? 1 : 0;
            
            if (empty($title)) $errors[] = 'Naslov je obavezan';
            if (empty($eventDate)) $errors[] = 'Datum je obavezan';
            
            if (empty($errors)) {
                try {
                    if ($id) {
                        $stmt = $db->prepare("
                            UPDATE events SET 
                                title = ?, description = ?, location = ?, 
                                event_date = ?, event_time = ?, end_time = ?,
                                event_type = ?, importance = ?, notes = ?, skip_coverage = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$title, $description, $location, $eventDate, $eventTime ?: null, $endTime ?: null, $eventType, $importance, $notes, $skipCoverage, $id]);
                        
                        logActivity('event_update', 'event', $id);
                        setMessage('success', 'Događaj je spremljen');
                        header("Location: event-edit.php?id=$id");
                        exit;
                    } else {
                        $stmt = $db->prepare("
                            INSERT INTO events (title, description, location, event_date, event_time, end_time, event_type, importance, notes, skip_coverage, created_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$title, $description, $location, $eventDate, $eventTime ?: null, $endTime ?: null, $eventType, $importance, $notes, $skipCoverage, $userId]);

                        $newEventId = $db->lastInsertId();

                        // Dodaj odabrane osobe
                        $assignUsers = $_POST['assign_users'] ?? [];
                        $assignRoles = $_POST['assign_roles'] ?? [];
                        if (!empty($assignUsers)) {
                            $stmtAssign = $db->prepare("INSERT INTO event_assignments (event_id, user_id, role, assigned_by) VALUES (?, ?, ?, ?)");
                            foreach ($assignUsers as $idx => $assignUserId) {
                                if ($assignUserId) {
                                    $role = $assignRoles[$idx] ?? 'reporter';
                                    try {
                                        $stmtAssign->execute([$newEventId, $assignUserId, $role, $userId]);
                                    } catch (Exception $e) {
                                        // Ignore duplicates
                                    }
                                }
                            }
                        }

                        logActivity('event_create', 'event', $newEventId);
                        setMessage('success', 'Događaj je kreiran');
                        header("Location: event-edit.php?id=$newEventId");
                        exit;
                    }
                } catch (Exception $e) {
                    $errors[] = 'Greška: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'add_assignment' && $id && $isEditorRole) {
            $assignUserId = intval($_POST['assign_user_id'] ?? 0);
            $assignRole = $_POST['assign_role'] ?? 'reporter';
            $assignNotes = trim($_POST['assign_notes'] ?? '');
            
            if ($assignUserId) {
                try {
                    $stmt = $db->prepare("INSERT INTO event_assignments (event_id, user_id, role, notes, assigned_by) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$id, $assignUserId, $assignRole, $assignNotes, $userId]);
                    setMessage('success', 'Osoba je dodana');
                } catch (Exception $e) {
                    setMessage('warning', 'Osoba je već dodijeljena');
                }
            }
            
            header("Location: event-edit.php?id=$id#assignments");
            exit;
        } elseif ($action === 'remove_assignment' && $id && $isEditorRole) {
            $assignmentId = intval($_POST['assignment_id'] ?? 0);
            $stmt = $db->prepare("DELETE FROM event_assignments WHERE id = ? AND event_id = ?");
            $stmt->execute([$assignmentId, $id]);
            setMessage('success', 'Osoba je uklonjena');
            
            header("Location: event-edit.php?id=$id#assignments");
            exit;
        } elseif ($action === 'toggle_skip' && $id && $isEditorRole) {
            $stmt = $db->prepare("UPDATE events SET skip_coverage = NOT skip_coverage WHERE id = ?");
            $stmt->execute([$id]);
            
            setMessage('success', 'Status promijenjen');
            header("Location: event-edit.php?id=$id");
            exit;
        }
    }
}

// Brisanje
if (isset($_GET['delete']) && $id && $isEditorRole) {
    $stmt = $db->prepare("DELETE FROM event_assignments WHERE event_id = ?");
    $stmt->execute([$id]);
    
    $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$id]);
    
    logActivity('event_delete', 'event', $id);
    redirectWith('events.php', 'success', 'Događaj je obrisan');
}

// Reload event nakon POST-a
if ($id && !$event) {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch();
}

// Dodijeljeni ljudi
$assignments = [];
if ($id) {
    $stmt = $db->prepare("
        SELECT ea.*, u.full_name 
        FROM event_assignments ea 
        JOIN users u ON ea.user_id = u.id 
        WHERE ea.event_id = ?
        ORDER BY ea.role, u.full_name
    ");
    $stmt->execute([$id]);
    $assignments = $stmt->fetchAll();
}

$users = getUsers();

include 'includes/header.php';
?>

<div class="d-flex" style="justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <h1><?= $id ? 'Uredi događaj' : 'Novi događaj' ?></h1>
    <a href="events.php" class="btn btn-outline">← Kalendar</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mt-2">
    <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($id && !empty($event['skip_coverage'])): ?>
<div class="alert alert-warning mt-2">
    <strong>⚠️ NE IDEMO</strong> - Ovaj događaj je označen da se ne pokriva.
</div>
<?php endif; ?>

<form method="POST" class="mt-2">
    <?= csrfField() ?>
    <input type="hidden" name="form_action" value="save_event">
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Podaci o događaju</h2>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Naslov *</label>
                <input type="text" name="title" class="form-control" 
                       value="<?= e($event['title'] ?? $_POST['title'] ?? '') ?>" required <?= !$isEditorRole ? 'readonly' : '' ?>>
            </div>
            
            <div class="form-group">
                <label class="form-label">Opis</label>
                <textarea name="description" class="form-control" rows="3" <?= !$isEditorRole ? 'readonly' : '' ?>><?= e($event['description'] ?? $_POST['description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Lokacija</label>
                <input type="text" name="location" class="form-control"
                       value="<?= e($event['location'] ?? $_POST['location'] ?? '') ?>" <?= !$isEditorRole ? 'readonly' : '' ?>>
            </div>
            
            <div class="d-flex gap-1" style="flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 150px;">
                    <label class="form-label">Datum *</label>
                    <input type="date" name="event_date" class="form-control"
                           value="<?= e($event['event_date'] ?? $_POST['event_date'] ?? '') ?>" required <?= !$isEditorRole ? 'readonly' : '' ?>>
                </div>
                <div class="form-group" style="flex: 1; min-width: 120px;">
                    <label class="form-label">Početak</label>
                    <input type="time" name="event_time" class="form-control"
                           value="<?= e($event['event_time'] ?? $_POST['event_time'] ?? '') ?>" <?= !$isEditorRole ? 'readonly' : '' ?>>
                </div>
                <div class="form-group" style="flex: 1; min-width: 120px;">
                    <label class="form-label">Kraj</label>
                    <input type="time" name="end_time" class="form-control"
                           value="<?= e($event['end_time'] ?? $_POST['end_time'] ?? '') ?>" <?= !$isEditorRole ? 'readonly' : '' ?>>
                </div>
            </div>
            
            <div class="d-flex gap-1" style="flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 150px;">
                    <label class="form-label">Tip</label>
                    <select name="event_type" class="form-control" id="eventType" <?= !$isEditorRole ? 'disabled' : '' ?>>
                        <option value="press" <?= ($event['event_type'] ?? $_GET['type'] ?? '') === 'press' ? 'selected' : '' ?>>Press konferencija</option>
                        <option value="sport" <?= ($event['event_type'] ?? $_GET['type'] ?? '') === 'sport' ? 'selected' : '' ?>>Sport</option>
                        <option value="kultura" <?= ($event['event_type'] ?? $_GET['type'] ?? '') === 'kultura' ? 'selected' : '' ?>>Kultura</option>
                        <option value="politika" <?= ($event['event_type'] ?? $_GET['type'] ?? '') === 'politika' ? 'selected' : '' ?>>Politika</option>
                        <option value="drustvo" <?= ($event['event_type'] ?? $_GET['type'] ?? '') === 'drustvo' ? 'selected' : '' ?>>Društvo</option>
                        <option value="dezurstvo" <?= ($event['event_type'] ?? $_GET['type'] ?? '') === 'dezurstvo' ? 'selected' : '' ?>>📅 Dežurstvo</option>
                        <option value="ostalo" <?= ($event['event_type'] ?? $_GET['type'] ?? 'ostalo') === 'ostalo' ? 'selected' : '' ?>>Ostalo</option>
                    </select>
                </div>
                <div class="form-group" style="flex: 1; min-width: 150px;">
                    <label class="form-label">Važnost</label>
                    <select name="importance" class="form-control" <?= !$isEditorRole ? 'disabled' : '' ?>>
                        <option value="normal" <?= ($event['importance'] ?? 'normal') === 'normal' ? 'selected' : '' ?>>Normalno</option>
                        <option value="important" <?= ($event['importance'] ?? '') === 'important' ? 'selected' : '' ?>>Važno</option>
                        <option value="must_cover" <?= ($event['importance'] ?? '') === 'must_cover' ? 'selected' : '' ?>>Obavezno pokriti!</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">📝 Napomena (interna)</label>
                <textarea name="notes" class="form-control" rows="2" 
                          placeholder="Napomene za tim, kontakt osoba, posebni zahtjevi..."><?= e($event['notes'] ?? $_POST['notes'] ?? '') ?></textarea>
            </div>
            
            <?php if ($isEditorRole): ?>
            <div class="form-check">
                <input type="checkbox" id="skip_coverage" name="skip_coverage" <?= (!empty($event['skip_coverage'])) ? 'checked' : '' ?>>
                <label for="skip_coverage"><strong>Ne idemo</strong> - označiti ako ne pokrivamo ovaj događaj</label>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isEditorRole && !$id): ?>
    <!-- Odabir osoba prilikom kreiranja -->
    <div class="card mt-2">
        <div class="card-header">
            <h2 class="card-title">👥 Tko ide na događaj</h2>
        </div>
        <div class="card-body">
            <div id="assignmentRows">
                <div class="assignment-row d-flex gap-1 mb-1" style="flex-wrap: wrap;">
                    <select name="assign_users[]" class="form-control" style="flex: 2; min-width: 150px;">
                        <option value="">-- Odaberi osobu --</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= e($u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="assign_roles[]" class="form-control" style="flex: 1; min-width: 120px;">
                        <option value="reporter">Novinar</option>
                        <option value="photographer">Fotograf</option>
                        <option value="camera">Snimatelj</option>
                        <option value="backup">Rezerva</option>
                    </select>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline mt-1" onclick="addAssignmentRow()">+ Dodaj osobu</button>
        </div>
    </div>
    <script>
    function addAssignmentRow() {
        const container = document.getElementById('assignmentRows');
        const row = container.querySelector('.assignment-row').cloneNode(true);
        row.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
        container.appendChild(row);
    }
    </script>
    <?php endif; ?>

    <?php if ($isEditorRole): ?>
    <div class="card mt-2">
        <div class="card-body">
            <div class="d-flex gap-1 flex-wrap">
                <button type="submit" class="btn btn-primary">💾 Spremi</button>
                <?php if ($id): ?>
                <a href="?id=<?= $id ?>&delete=1" class="btn btn-danger" data-confirm="Obrisati događaj?">Obriši</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</form>

<?php if ($id): ?>
<!-- Tko ide -->
<div class="card mt-2" id="assignments">
    <div class="card-header">
        <h2 class="card-title">👥 Tko ide na događaj</h2>
    </div>
    <div class="card-body">
        <?php if ($isEditorRole && empty($event['skip_coverage'])): ?>
        <!-- Dodaj osobu -->
        <form method="POST" class="mb-2" style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius);">
            <?= csrfField() ?>
            <input type="hidden" name="form_action" value="add_assignment">
            
            <div class="d-flex gap-1 flex-wrap" style="margin-bottom: 0.5rem;">
                <select name="assign_user_id" class="form-control" style="flex: 2; min-width: 150px;" required>
                    <option value="">-- Odaberi osobu --</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= e($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select name="assign_role" class="form-control" style="flex: 1; min-width: 120px;">
                    <option value="reporter">Novinar</option>
                    <option value="photographer">Fotograf</option>
                    <option value="camera">Snimatelj</option>
                    <option value="backup">Rezerva</option>
                </select>
                
                <button type="submit" class="btn btn-primary">Dodaj</button>
            </div>
            
            <input type="text" name="assign_notes" class="form-control" placeholder="Napomena za osobu (opcionalno)">
        </form>
        <?php endif; ?>
        
        <?php if (!empty($event['skip_coverage'])): ?>
        <p class="text-muted text-center">Događaj označen kao "Ne idemo"</p>
        <?php elseif (empty($assignments)): ?>
        <p class="text-danger text-center">⚠️ Nitko nije dodijeljen!</p>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Osoba</th>
                    <th>Uloga</th>
                    <th>Napomena</th>
                    <?php if ($isEditorRole): ?><th style="width:60px;"></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignments as $a): ?>
                <tr>
                    <td><strong><?= e($a['full_name']) ?></strong></td>
                    <td>
                        <span class="badge badge-<?= $a['role'] === 'reporter' ? 'primary' : ($a['role'] === 'photographer' ? 'success' : 'secondary') ?>">
                            <?= translateEventRole($a['role']) ?>
                        </span>
                    </td>
                    <td class="text-muted"><?= e($a['notes'] ?: '-') ?></td>
                    <?php if ($isEditorRole): ?>
                    <td>
                        <form method="POST" style="display: inline;">
                            <?= csrfField() ?>
                            <input type="hidden" name="form_action" value="remove_assignment">
                            <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger" title="Ukloni">×</button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
