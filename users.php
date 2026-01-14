<?php
/**
 * Upravljanje korisnicima - Admin
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireRole('admin');

define('PAGE_TITLE', 'Korisnici');

$db = getDB();

// Obrada forme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setMessage('danger', 'Nevažeći sigurnosni token');
    } else {
        $action = $_POST['action'] ?? '';
        $editId = intval($_POST['user_id'] ?? 0);
        
        $username = trim($_POST['username'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? 'novinar';
        $password = $_POST['password'] ?? '';
        $active = isset($_POST['active']) ? 1 : 0;
        
        $errors = [];
        
        if (empty($username)) $errors[] = 'Korisničko ime je obavezno';
        if (empty($fullName)) $errors[] = 'Ime i prezime su obavezni';
        if (empty($email)) $errors[] = 'Email je obavezan';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Nevažeća email adresa';
        
        $stmt = $db->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $editId]);
        if ($stmt->fetch()) {
            $errors[] = 'Korisničko ime ili email već postoje';
        }
        
        if ($action === 'add' && empty($password)) {
            $errors[] = 'Lozinka je obavezna za novog korisnika';
        }
        
        if (empty($errors)) {
            try {
                if ($action === 'add') {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("INSERT INTO users (username, password, full_name, email, phone, role, active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $hashedPassword, $fullName, $email, $phone, $role, $active]);
                    logActivity('user_create', 'user', $db->lastInsertId());
                    setMessage('success', 'Korisnik je kreiran');
                } else {
                    if (!empty($password)) {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE users SET username = ?, password = ?, full_name = ?, email = ?, phone = ?, role = ?, active = ? WHERE id = ?");
                        $stmt->execute([$username, $hashedPassword, $fullName, $email, $phone, $role, $active, $editId]);
                    } else {
                        $stmt = $db->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, phone = ?, role = ?, active = ? WHERE id = ?");
                        $stmt->execute([$username, $fullName, $email, $phone, $role, $active, $editId]);
                    }
                    logActivity('user_update', 'user', $editId);
                    setMessage('success', 'Korisnik je ažuriran');
                }
                header('Location: users.php');
                exit;
            } catch (Exception $e) {
                $errors[] = 'Greška: ' . $e->getMessage();
            }
        }
    }
}

// Brisanje korisnika
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    if ($deleteId == $_SESSION['user_id']) {
        setMessage('danger', 'Ne možete obrisati svoj račun');
    } else {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$deleteId]);
        logActivity('user_delete', 'user', $deleteId);
        setMessage('success', 'Korisnik je obrisan');
    }
    header('Location: users.php');
    exit;
}

// Dohvati sve korisnike
$stmt = $db->query("SELECT * FROM users ORDER BY full_name");
$users = $stmt->fetchAll();

// Za uređivanje
$editUser = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch();
}

include 'includes/header.php';
?>

<div class="d-flex" style="justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <h1>Korisnici</h1>
    <button class="btn btn-primary" data-modal="userModal">+ Novi korisnik</button>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mt-2">
    <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card mt-2">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Ime</th>
                    <th>Email</th>
                    <th>Telefon</th>
                    <th>Uloga</th>
                    <th>Status</th>
                    <th style="width: 120px;">Akcije</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <strong><?= e($u['full_name']) ?></strong>
                        <div class="text-xs text-muted">@<?= e($u['username']) ?></div>
                    </td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e($u['phone'] ?: '-') ?></td>
                    <td>
                        <span class="badge badge-<?= $u['role'] === 'admin' ? 'danger' : ($u['role'] === 'urednik' ? 'warning' : 'info') ?>">
                            <?= translateRole($u['role']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($u['active']): ?>
                            <span class="badge badge-success">Aktivan</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Neaktivan</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?edit=<?= $u['id'] ?>" class="btn btn-sm btn-outline">Uredi</a>
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <a href="?delete=<?= $u['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Obrisati korisnika?">×</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal <?= $editUser ? 'active' : '' ?>" id="userModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><?= $editUser ? 'Uredi korisnika' : 'Novi korisnik' ?></h3>
            <a href="users.php" class="modal-close">&times;</a>
        </div>
        <div class="modal-body">
            <form method="POST" id="userForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="<?= $editUser ? 'edit' : 'add' ?>">
                <input type="hidden" name="user_id" value="<?= $editUser['id'] ?? 0 ?>">
                
                <div class="form-group">
                    <label class="form-label">Korisničko ime *</label>
                    <input type="text" name="username" class="form-control" value="<?= e($editUser['username'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Ime i prezime *</label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($editUser['full_name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" value="<?= e($editUser['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Telefon</label>
                    <input type="tel" name="phone" class="form-control" value="<?= e($editUser['phone'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Uloga *</label>
                    <select name="role" class="form-control" required>
                        <option value="novinar" <?= ($editUser['role'] ?? '') === 'novinar' ? 'selected' : '' ?>>Novinar</option>
                        <option value="urednik" <?= ($editUser['role'] ?? '') === 'urednik' ? 'selected' : '' ?>>Urednik</option>
                        <option value="admin" <?= ($editUser['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrator</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Lozinka <?= $editUser ? '(prazno = zadrži)' : '*' ?></label>
                    <input type="password" name="password" class="form-control" <?= $editUser ? '' : 'required' ?>>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" id="active" name="active" <?= ($editUser['active'] ?? 1) ? 'checked' : '' ?>>
                    <label for="active">Aktivan</label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <a href="users.php" class="btn btn-outline">Odustani</a>
            <button type="submit" form="userForm" class="btn btn-primary">Spremi</button>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
