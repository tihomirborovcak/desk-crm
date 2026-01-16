<?php
/**
 * Zagorski list - Upravljanje brojevima/izdanjima
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

define('PAGE_TITLE', 'Brojevi ZL');

$db = getDB();
$userId = $_SESSION['user_id'];
$isEditor = isEditor();

$error = null;
$success = null;

// Obrada forme - novi broj
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $issueNumber = (int)$_POST['issue_number'];
        $year = (int)$_POST['year'];
        $publishDate = $_POST['publish_date'];

        if (!$issueNumber || !$year || !$publishDate) {
            $error = 'Sva polja su obavezna';
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO zl_issues (issue_number, year, publish_date, created_by)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$issueNumber, $year, $publishDate, $userId]);
                $success = "Kreiran broj $issueNumber/$year";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    $error = "Broj $issueNumber/$year već postoji";
                } else {
                    $error = 'Greška: ' . $e->getMessage();
                }
            }
        }
    }

    if ($action === 'update_status' && $isEditor) {
        $issueId = (int)$_POST['issue_id'];
        $status = $_POST['status'];
        $stmt = $db->prepare("UPDATE zl_issues SET status = ? WHERE id = ?");
        $stmt->execute([$status, $issueId]);
        $success = 'Status ažuriran';
    }

    if ($action === 'delete' && $isEditor) {
        $issueId = (int)$_POST['issue_id'];
        // Provjeri ima li članaka
        $stmt = $db->prepare("SELECT COUNT(*) FROM zl_articles WHERE issue_id = ?");
        $stmt->execute([$issueId]);
        $articleCount = $stmt->fetchColumn();

        if ($articleCount > 0) {
            $error = "Ne možete obrisati broj koji ima $articleCount članaka";
        } else {
            $stmt = $db->prepare("DELETE FROM zl_issues WHERE id = ?");
            $stmt->execute([$issueId]);
            $success = 'Broj obrisan';
        }
    }
}

// Dohvati sve brojeve
$issues = $db->query("
    SELECT i.*,
           u.full_name as created_by_name,
           (SELECT COUNT(*) FROM zl_articles WHERE issue_id = i.id) as article_count,
           (SELECT SUM(char_count) FROM zl_articles WHERE issue_id = i.id) as total_chars
    FROM zl_issues i
    LEFT JOIN users u ON i.created_by = u.id
    ORDER BY i.year DESC, i.issue_number DESC
")->fetchAll();

// Izračunaj sljedeći broj
$nextNumber = 1;
$currentYear = date('Y');
if (!empty($issues)) {
    $latestIssue = $issues[0];
    if ($latestIssue['year'] == $currentYear) {
        $nextNumber = $latestIssue['issue_number'] + 1;
    }
}

// Izračunaj sljedeći utorak
$nextTuesday = new DateTime();
if ($nextTuesday->format('N') <= 2) {
    $nextTuesday->modify('tuesday this week');
} else {
    $nextTuesday->modify('next tuesday');
}

include 'includes/header.php';
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <h1>Brojevi Zagorskog lista</h1>
    <a href="zl-clanci.php" class="btn btn-outline">← Natrag na članke</a>
</div>

<?php if ($error): ?>
<div style="background: #fee2e2; border: 1px solid #fecaca; color: #dc2626; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <?= e($error) ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div style="background: #d1fae5; border: 1px solid #a7f3d0; color: #059669; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <?= e($success) ?>
</div>
<?php endif; ?>

<!-- Forma za novi broj -->
<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem;">
    <h2 style="margin: 0 0 1rem 0; font-size: 1.125rem;">Kreiraj novi broj</h2>
    <form method="POST" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="create">

        <div>
            <label style="display: block; font-size: 0.875rem; margin-bottom: 0.25rem;">Broj</label>
            <input type="number" name="issue_number" value="<?= $nextNumber ?>" min="1"
                   class="form-control" style="width: 100px;" required>
        </div>

        <div>
            <label style="display: block; font-size: 0.875rem; margin-bottom: 0.25rem;">Godina</label>
            <input type="number" name="year" value="<?= $currentYear ?>" min="2020" max="2030"
                   class="form-control" style="width: 100px;" required>
        </div>

        <div>
            <label style="display: block; font-size: 0.875rem; margin-bottom: 0.25rem;">Datum izlaska (utorak)</label>
            <input type="date" name="publish_date" value="<?= $nextTuesday->format('Y-m-d') ?>"
                   class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Kreiraj broj</button>
    </form>
</div>

<!-- Lista brojeva -->
<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f9fafb;">
                <th style="padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid #e5e7eb;">Broj</th>
                <th style="padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid #e5e7eb;">Datum</th>
                <th style="padding: 0.75rem 1rem; text-align: center; border-bottom: 1px solid #e5e7eb;">Članaka</th>
                <th style="padding: 0.75rem 1rem; text-align: center; border-bottom: 1px solid #e5e7eb;">Znakova</th>
                <th style="padding: 0.75rem 1rem; text-align: center; border-bottom: 1px solid #e5e7eb;">Status</th>
                <th style="padding: 0.75rem 1rem; text-align: right; border-bottom: 1px solid #e5e7eb;"></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($issues)): ?>
            <tr>
                <td colspan="6" style="padding: 2rem; text-align: center; color: #6b7280;">
                    Nema kreiranih brojeva
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($issues as $issue): ?>
                <tr>
                    <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6;">
                        <strong><?= $issue['issue_number'] ?>/<?= $issue['year'] ?></strong>
                    </td>
                    <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6;">
                        <?= formatDate($issue['publish_date']) ?>
                    </td>
                    <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6; text-align: center;">
                        <?= $issue['article_count'] ?>
                    </td>
                    <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6; text-align: center;">
                        <?= number_format($issue['total_chars'] ?? 0) ?>
                    </td>
                    <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6; text-align: center;">
                        <?php if ($isEditor): ?>
                        <form method="POST" style="display: inline;">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="issue_id" value="<?= $issue['id'] ?>">
                            <select name="status" onchange="this.form.submit()" style="padding: 0.25rem; border-radius: 4px; border: 1px solid #d1d5db; font-size: 0.875rem;">
                                <option value="priprema" <?= $issue['status'] === 'priprema' ? 'selected' : '' ?>>Priprema</option>
                                <option value="u_izradi" <?= $issue['status'] === 'u_izradi' ? 'selected' : '' ?>>U izradi</option>
                                <option value="zatvoren" <?= $issue['status'] === 'zatvoren' ? 'selected' : '' ?>>Zatvoren</option>
                            </select>
                        </form>
                        <?php else: ?>
                            <?php
                            $statusLabels = ['priprema' => 'Priprema', 'u_izradi' => 'U izradi', 'zatvoren' => 'Zatvoren'];
                            echo $statusLabels[$issue['status']] ?? $issue['status'];
                            ?>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6; text-align: right;">
                        <a href="zl-clanci.php?issue=<?= $issue['id'] ?>" class="btn btn-sm btn-outline">Članci</a>
                        <?php if ($isEditor && $issue['article_count'] == 0): ?>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Obrisati ovaj broj?')">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="issue_id" value="<?= $issue['id'] ?>">
                            <button type="submit" class="btn btn-sm" style="color: #dc2626;">Obriši</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
