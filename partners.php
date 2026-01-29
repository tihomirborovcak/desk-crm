<?php
/**
 * Upravljanje partnerima - pregled i editiranje
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

if (!isEditor()) {
    header('Location: dashboard.php');
    exit;
}

define('PAGE_TITLE', 'Partneri');

$db = getDB();

// Provjeri postoji li tablica
try {
    $db->query("SELECT 1 FROM partners LIMIT 1");
} catch (PDOException $e) {
    header('Location: gmail-setup.php');
    exit;
}

// Obradi spremanje
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_partner'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setMessage('error', 'Nevazeci token');
    } else {
        $id = $_POST['id'] ?? null;
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($name) || empty($email)) {
            setMessage('error', 'Ime i email su obavezni');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setMessage('error', 'Nevazeca email adresa');
        } else {
            try {
                if ($id) {
                    $stmt = $db->prepare("
                        UPDATE partners SET name = ?, email = ?, phone = ?, company = ?, notes = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $email, $phone, $company, $notes, $id]);
                    setMessage('success', 'Partner azuriran');
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO partners (name, email, phone, company, notes, source)
                        VALUES (?, ?, ?, ?, ?, 'manual')
                    ");
                    $stmt->execute([$name, $email, $phone, $company, $notes]);
                    setMessage('success', 'Partner dodan');
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    setMessage('error', 'Email adresa vec postoji');
                } else {
                    setMessage('error', 'Greska: ' . $e->getMessage());
                }
            }
        }
    }
    header('Location: partners.php');
    exit;
}

// Obrisi partnera
if (isset($_GET['delete']) && isset($_GET['token']) && verifyCSRFToken($_GET['token'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM partners WHERE id = ?")->execute([$id]);
    // Postavi partner_id na NULL u threadovima
    $db->prepare("UPDATE gmail_threads SET partner_id = NULL WHERE partner_id = ?")->execute([$id]);
    setMessage('success', 'Partner obrisan');
    header('Location: partners.php');
    exit;
}

// Dohvati partnere s brojacima
$partners = $db->query("
    SELECT p.*,
           COUNT(DISTINCT t.id) as gmail_threads,
           SUM(t.unread_count) as unread_count
    FROM partners p
    LEFT JOIN gmail_threads t ON t.partner_id = p.id AND (t.deleted = 0 OR t.deleted IS NULL)
    GROUP BY p.id
    ORDER BY p.name
")->fetchAll();

// Partner za edit (ako je odabran)
$editPartner = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM partners WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editPartner = $stmt->fetch();
}

include 'includes/header.php';
?>

<div class="d-flex" style="justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <h1>Partneri</h1>
    <a href="gmail-messages.php" class="btn btn-outline">Gmail poruke</a>
</div>

<?php $flashMsg = getMessage(); if ($flashMsg): ?>
<div class="alert alert-<?= $flashMsg['type'] ?> mt-2"><?= e($flashMsg['text']) ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 350px; gap: 1rem; margin-top: 1rem;">
    <!-- Lista partnera -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Svi partneri (<?= count($partners) ?>)</h2>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ime</th>
                        <th>Email</th>
                        <th>Firma</th>
                        <th>Izvor</th>
                        <th style="text-align: center;">Threadovi</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($partners)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #6b7280;">Nema partnera</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($partners as $p): ?>
                    <tr>
                        <td>
                            <strong><?= e($p['name']) ?></strong>
                            <?php if ($p['unread_count'] > 0): ?>
                            <span class="badge badge-danger" style="font-size: 0.65rem;"><?= $p['unread_count'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="color: #6b7280; font-size: 0.9rem;"><?= e($p['email']) ?></td>
                        <td style="color: #6b7280;"><?= e($p['company'] ?: '-') ?></td>
                        <td>
                            <span style="background: <?= $p['source'] === 'gmail' ? '#fef2f2' : '#f0fdf4' ?>; color: <?= $p['source'] === 'gmail' ? '#ea4335' : '#059669' ?>; padding: 2px 6px; border-radius: 3px; font-size: 0.75rem;">
                                <?= $p['source'] ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($p['gmail_threads'] > 0): ?>
                            <a href="gmail-messages.php?partner=<?= $p['id'] ?>" style="color: #ea4335;"><?= $p['gmail_threads'] ?></a>
                            <?php else: ?>
                            0
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline">Uredi</a>
                            <a href="?delete=<?= $p['id'] ?>&token=<?= generateCSRFToken() ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Obrisati partnera?')">Obrisi</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Forma za dodavanje/editiranje -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= $editPartner ? 'Uredi partnera' : 'Dodaj partnera' ?></h2>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <?php if ($editPartner): ?>
                <input type="hidden" name="id" value="<?= $editPartner['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Ime *</label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= e($editPartner['name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" class="form-control" required
                           value="<?= e($editPartner['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Telefon</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= e($editPartner['phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Firma</label>
                    <input type="text" name="company" class="form-control"
                           value="<?= e($editPartner['company'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Napomene</label>
                    <textarea name="notes" class="form-control" rows="3"><?= e($editPartner['notes'] ?? '') ?></textarea>
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" name="save_partner" class="btn btn-primary">
                        <?= $editPartner ? 'Spremi' : 'Dodaj' ?>
                    </button>
                    <?php if ($editPartner): ?>
                    <a href="partners.php" class="btn btn-outline">Odustani</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 350px"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
