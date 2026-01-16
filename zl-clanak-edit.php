<?php
/**
 * Zagorski list - Uredi/dodaj članak
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = getDB();
$isEditor = isEditor();
$userId = $_SESSION['user_id'];

$id = (int)($_GET['id'] ?? 0);
$issueId = (int)($_GET['issue'] ?? 0);
$reviewMode = isset($_GET['review']) && $isEditor;

$article = null;
$error = null;
$success = null;
$articleImages = [];

// Direktorij za slike članaka
$imageUploadDir = UPLOAD_PATH . 'zl-clanci/' . date('Y/m/');

// Dohvati članak ako uređujemo
if ($id) {
    $stmt = $db->prepare("SELECT * FROM zl_articles WHERE id = ?");
    $stmt->execute([$id]);
    $article = $stmt->fetch();

    if (!$article) {
        header('Location: zl-clanci.php');
        exit;
    }
    $issueId = $article['issue_id'];

    // Dohvati slike članka
    $imgStmt = $db->prepare("SELECT * FROM zl_article_images WHERE article_id = ? ORDER BY is_main DESC, sort_order, id");
    $imgStmt->execute([$id]);
    $articleImages = $imgStmt->fetchAll();
}

// Dohvati izdanje
$currentIssue = null;
if ($issueId) {
    $stmt = $db->prepare("SELECT * FROM zl_issues WHERE id = ?");
    $stmt->execute([$issueId]);
    $currentIssue = $stmt->fetch();
}

// Dohvati sva izdanja za dropdown
$issuesStmt = $db->query("SELECT * FROM zl_issues ORDER BY year DESC, issue_number DESC LIMIT 20");
$issues = $issuesStmt->fetchAll();

// Dohvati rubrike
$sectionsStmt = $db->query("SELECT * FROM zl_sections WHERE active = 1 ORDER BY sort_order");
$sections = $sectionsStmt->fetchAll();

// Dohvati autore (korisnike)
$usersStmt = $db->query("SELECT id, full_name FROM users WHERE active = 1 ORDER BY full_name");
$users = $usersStmt->fetchAll();

// Obrada forme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'save' || $action === 'submit') {
        $data = [
            'issue_id' => (int)$_POST['issue_id'] ?: null,
            'section_id' => (int)$_POST['section_id'] ?: null,
            'supertitle' => trim($_POST['supertitle'] ?? ''),
            'title' => trim($_POST['title'] ?? ''),
            'subtitle' => trim($_POST['subtitle'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'author_id' => (int)$_POST['author_id'] ?: null,
            'author_text' => trim($_POST['author_text'] ?? ''),
            'page_number' => (int)$_POST['page_number'] ?: null,
        ];

        // Izračunaj broj znakova (samo tekst, bez HTML-a)
        $plainText = strip_tags($data['content']);
        $data['char_count'] = mb_strlen($plainText);
        $data['word_count'] = str_word_count($plainText, 0, 'ČčĆćŽžŠšĐđ');

        // Status
        if ($action === 'submit') {
            $data['status'] = 'za_pregled';
        } elseif ($article) {
            $data['status'] = $article['status']; // Zadrži trenutni
        } else {
            $data['status'] = 'nacrt';
        }

        if (empty($data['title'])) {
            $error = 'Naslov je obavezan';
        } else {
            try {
                if ($id) {
                    // Update
                    $stmt = $db->prepare("
                        UPDATE zl_articles SET
                            issue_id = ?, section_id = ?, supertitle = ?, title = ?,
                            subtitle = ?, content = ?, author_id = ?, author_text = ?,
                            page_number = ?, char_count = ?, word_count = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $data['issue_id'], $data['section_id'], $data['supertitle'], $data['title'],
                        $data['subtitle'], $data['content'], $data['author_id'], $data['author_text'],
                        $data['page_number'], $data['char_count'], $data['word_count'], $data['status'],
                        $id
                    ]);
                    $success = 'Članak spremljen!';
                } else {
                    // Insert
                    $stmt = $db->prepare("
                        INSERT INTO zl_articles
                            (issue_id, section_id, supertitle, title, subtitle, content,
                             author_id, author_text, page_number, char_count, word_count, status, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $data['issue_id'], $data['section_id'], $data['supertitle'], $data['title'],
                        $data['subtitle'], $data['content'], $data['author_id'], $data['author_text'],
                        $data['page_number'], $data['char_count'], $data['word_count'], $data['status'],
                        $userId
                    ]);
                    $id = $db->lastInsertId();
                    $success = 'Članak kreiran!';

                    if ($action === 'submit') {
                        header('Location: zl-clanci.php?issue=' . $data['issue_id']);
                        exit;
                    }
                }

                // Reload article
                $stmt = $db->prepare("SELECT * FROM zl_articles WHERE id = ?");
                $stmt->execute([$id]);
                $article = $stmt->fetch();
            } catch (PDOException $e) {
                $error = 'Greška pri spremanju: ' . $e->getMessage();
            }
        }
    }

    // Uredničke akcije
    if ($action === 'approve' && $isEditor && $id) {
        $stmt = $db->prepare("UPDATE zl_articles SET status = 'odobreno', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        $stmt->execute([$userId, $id]);
        $success = 'Članak odobren!';
        header('Location: zl-clanci.php?issue=' . $article['issue_id']);
        exit;
    }

    if ($action === 'reject' && $isEditor && $id) {
        $notes = trim($_POST['review_notes'] ?? '');
        $stmt = $db->prepare("UPDATE zl_articles SET status = 'odbijeno', reviewed_by = ?, reviewed_at = NOW(), review_notes = ? WHERE id = ?");
        $stmt->execute([$userId, $notes, $id]);
        $success = 'Članak vraćen na doradu.';
        header('Location: zl-clanci.php?issue=' . $article['issue_id']);
        exit;
    }

    if ($action === 'delete' && $id) {
        // Samo kreator ili urednik može brisati
        if ($article['created_by'] == $userId || $isEditor) {
            // Obriši slike
            $imgStmt = $db->prepare("SELECT filepath FROM zl_article_images WHERE article_id = ?");
            $imgStmt->execute([$id]);
            while ($img = $imgStmt->fetch()) {
                if ($img['filepath'] && file_exists(UPLOAD_PATH . $img['filepath'])) {
                    unlink(UPLOAD_PATH . $img['filepath']);
                }
            }
            $stmt = $db->prepare("DELETE FROM zl_articles WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: zl-clanci.php?issue=' . $article['issue_id']);
            exit;
        }
    }

    // Upload slike
    if ($action === 'upload_image' && $id) {
        if (!empty($_FILES['image']['tmp_name'])) {
            $file = $_FILES['image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($ext, $allowedExts)) {
                $error = 'Nedozvoljeni format slike';
            } elseif ($file['size'] > 10 * 1024 * 1024) {
                $error = 'Slika je prevelika (max 10MB)';
            } else {
                if (!is_dir($imageUploadDir)) {
                    mkdir($imageUploadDir, 0755, true);
                }

                $filename = date('Y-m-d_His_') . bin2hex(random_bytes(4)) . '.' . $ext;
                $filepath = 'zl-clanci/' . date('Y/m/') . $filename;

                if (move_uploaded_file($file['tmp_name'], UPLOAD_PATH . $filepath)) {
                    $caption = trim($_POST['caption'] ?? '');
                    $credit = trim($_POST['credit'] ?? '');
                    $isMain = isset($_POST['is_main']) ? 1 : 0;

                    // Ako je glavna, makni oznaku s drugih
                    if ($isMain) {
                        $db->prepare("UPDATE zl_article_images SET is_main = 0 WHERE article_id = ?")->execute([$id]);
                    }

                    $stmt = $db->prepare("
                        INSERT INTO zl_article_images (article_id, filename, original_name, filepath, caption, credit, is_main)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$id, $filename, $file['name'], $filepath, $caption, $credit, $isMain]);
                    $success = 'Slika dodana!';

                    // Reload images
                    $imgStmt = $db->prepare("SELECT * FROM zl_article_images WHERE article_id = ? ORDER BY is_main DESC, sort_order, id");
                    $imgStmt->execute([$id]);
                    $articleImages = $imgStmt->fetchAll();
                } else {
                    $error = 'Greška pri uploadu slike';
                }
            }
        }
    }

    // Brisanje slike
    if ($action === 'delete_image' && $id) {
        $imageId = (int)($_POST['image_id'] ?? 0);
        if ($imageId) {
            $stmt = $db->prepare("SELECT filepath FROM zl_article_images WHERE id = ? AND article_id = ?");
            $stmt->execute([$imageId, $id]);
            $img = $stmt->fetch();

            if ($img) {
                if ($img['filepath'] && file_exists(UPLOAD_PATH . $img['filepath'])) {
                    unlink(UPLOAD_PATH . $img['filepath']);
                }
                $db->prepare("DELETE FROM zl_article_images WHERE id = ?")->execute([$imageId]);
                $success = 'Slika obrisana!';

                // Reload images
                $imgStmt = $db->prepare("SELECT * FROM zl_article_images WHERE article_id = ? ORDER BY is_main DESC, sort_order, id");
                $imgStmt->execute([$id]);
                $articleImages = $imgStmt->fetchAll();
            }
        }
    }
}

define('PAGE_TITLE', $id ? 'Uredi članak' : 'Novi članak');
include 'includes/header.php';
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <h1><?= $id ? 'Uredi članak' : 'Novi članak' ?></h1>
    <a href="zl-clanci.php<?= $issueId ? '?issue=' . $issueId : '' ?>" class="btn btn-outline">← Natrag</a>
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

<!-- Brojač znakova - sticky -->
<div id="charCounter" style="position: sticky; top: 60px; z-index: 100; background: #1e3a5f; color: white; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <span style="font-size: 1.5rem; font-weight: 600;" id="charCount">0</span>
        <span style="opacity: 0.8;">znakova</span>
        <span style="margin-left: 1rem; opacity: 0.6;" id="wordCount">0 riječi</span>
    </div>
    <div style="font-size: 0.875rem; opacity: 0.8;">
        <?php if ($currentIssue): ?>
        Broj <?= $currentIssue['issue_number'] ?>/<?= $currentIssue['year'] ?>
        <?php endif; ?>
    </div>
</div>

<form method="POST" id="articleForm">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="save" id="formAction">

    <div style="display: grid; grid-template-columns: 1fr 300px; gap: 1rem;">
        <!-- Lijeva kolona - sadržaj -->
        <div>
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem;">
                <!-- Nadnaslov -->
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Nadnaslov</label>
                    <input type="text" name="supertitle" value="<?= e($article['supertitle'] ?? '') ?>"
                           class="form-control" placeholder="Opciono..." style="font-size: 0.875rem;">
                </div>

                <!-- Naslov -->
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Naslov *</label>
                    <input type="text" name="title" value="<?= e($article['title'] ?? '') ?>"
                           class="form-control" required style="font-size: 1.25rem; font-weight: 600;"
                           placeholder="Glavni naslov članka">
                </div>

                <!-- Podnaslov -->
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Podnaslov</label>
                    <textarea name="subtitle" rows="2" class="form-control"
                              placeholder="Kratki sažetak ili lead..."><?= e($article['subtitle'] ?? '') ?></textarea>
                </div>

                <!-- Tekst -->
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Tekst članka *</label>
                    <textarea name="content" id="content" rows="20" class="form-control"
                              style="font-family: Georgia, serif; font-size: 1rem; line-height: 1.8;"
                              placeholder="Unesite tekst članka..."><?= e($article['content'] ?? '') ?></textarea>
                </div>
            </div>

            <?php if (!$id): ?>
            <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; margin-top: 1rem; text-align: center; color: #6b7280;">
                <p style="margin: 0;">Spremite članak da biste mogli dodati fotografije.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Desna kolona - meta podaci -->
        <div>
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; position: sticky; top: 130px;">
                <!-- Izdanje -->
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Broj ZL</label>
                    <select name="issue_id" class="form-control">
                        <option value="">-- Odaberi --</option>
                        <?php foreach ($issues as $issue): ?>
                        <option value="<?= $issue['id'] ?>" <?= (($article ? $article['issue_id'] : null) ?? $issueId) == $issue['id'] ? 'selected' : '' ?>>
                            <?= $issue['issue_number'] ?>/<?= $issue['year'] ?>
                            (<?= formatDate($issue['publish_date']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Rubrika -->
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Rubrika</label>
                    <select name="section_id" class="form-control">
                        <option value="">-- Odaberi --</option>
                        <?php foreach ($sections as $section): ?>
                        <option value="<?= $section['id'] ?>" <?= (($article ? $article['section_id'] : null) ?? '') == $section['id'] ? 'selected' : '' ?>>
                            <?= e($section['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Autor -->
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Autor</label>
                    <select name="author_id" class="form-control" style="margin-bottom: 0.5rem;">
                        <option value="">-- Odaberi korisnika --</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>" <?= (($article ? $article['author_id'] : null) ?? $userId) == $user['id'] ? 'selected' : '' ?>>
                            <?= e($user['full_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="author_text" value="<?= e(($article['author_text'] ?? '')) ?>"
                           class="form-control" placeholder="Ili upiši ime..." style="font-size: 0.875rem;">
                </div>

                <!-- Stranica -->
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Stranica</label>
                    <input type="number" name="page_number" value="<?= ($article['page_number'] ?? '') ?>"
                           class="form-control" min="1" max="100" placeholder="Br. stranice">
                </div>

                <!-- Status -->
                <?php if ($article): ?>
                <div style="margin-bottom: 1rem; padding: 0.75rem; background: #f3f4f6; border-radius: 4px;">
                    <div style="font-size: 0.75rem; color: #6b7280;">Status</div>
                    <div style="font-weight: 500;"><?= translateArticleStatus($article['status']) ?></div>
                </div>
                <?php endif; ?>

                <!-- Gumbi -->
                <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Spremi nacrt
                    </button>

                    <?php if (!$article || $article['status'] === 'nacrt' || $article['status'] === 'odbijeno'): ?>
                    <button type="button" onclick="submitForReview()" class="btn btn-success" style="width: 100%;">
                        Pošalji na pregled
                    </button>
                    <?php endif; ?>

                    <?php if ($reviewMode && $article && $article['status'] === 'za_pregled'): ?>
                    <div style="border-top: 1px solid #e5e7eb; padding-top: 1rem; margin-top: 0.5rem;">
                        <div style="font-weight: 500; margin-bottom: 0.5rem;">Uredničke akcije:</div>
                        <button type="button" onclick="approveArticle()" class="btn btn-success" style="width: 100%; margin-bottom: 0.5rem;">
                            ✓ Odobri
                        </button>
                        <button type="button" onclick="showRejectModal()" class="btn btn-danger" style="width: 100%;">
                            ✗ Vrati na doradu
                        </button>
                    </div>
                    <?php endif; ?>

                    <?php if ($article && ($article['created_by'] == $userId || $isEditor)): ?>
                    <button type="button" onclick="deleteArticle()" class="btn btn-outline" style="width: 100%; margin-top: 1rem; color: #dc2626; border-color: #dc2626;">
                        Obriši članak
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Fotografije - izvan glavne forme -->
<?php if ($id): ?>
<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; margin-top: 1rem; max-width: calc(100% - 316px);">
    <h3 style="margin: 0 0 1rem 0; font-size: 1rem;">Fotografije (<?= count($articleImages) ?>)</h3>

    <!-- Postojeće slike -->
    <?php if (!empty($articleImages)): ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
        <?php foreach ($articleImages as $img): ?>
        <div style="position: relative; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
            <img src="<?= UPLOAD_URL . e($img['filepath']) ?>" alt=""
                 style="width: 100%; height: 120px; object-fit: cover;">
            <?php if ($img['is_main']): ?>
            <span style="position: absolute; top: 4px; left: 4px; background: #10b981; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.625rem;">GLAVNA</span>
            <?php endif; ?>
            <div style="padding: 0.5rem;">
                <?php if ($img['caption']): ?>
                <div style="font-size: 0.75rem; color: #374151; margin-bottom: 0.25rem;"><?= e(truncate($img['caption'], 50)) ?></div>
                <?php endif; ?>
                <?php if ($img['credit']): ?>
                <div style="font-size: 0.625rem; color: #9ca3af;">Foto: <?= e($img['credit']) ?></div>
                <?php endif; ?>
                <form method="POST" style="margin-top: 0.5rem;" onsubmit="return confirm('Obrisati ovu sliku?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="delete_image">
                    <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                    <button type="submit" style="background: none; border: none; color: #dc2626; cursor: pointer; font-size: 0.75rem; padding: 0;">Obriši</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Upload nove slike -->
    <div style="border: 2px dashed #d1d5db; border-radius: 8px; padding: 1rem;">
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="upload_image">

            <div style="margin-bottom: 0.75rem;">
                <input type="file" name="image" accept="image/*" required class="form-control">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 0.75rem;">
                <input type="text" name="caption" placeholder="Opis slike..." class="form-control" style="font-size: 0.875rem;">
                <input type="text" name="credit" placeholder="Autor foto..." class="form-control" style="font-size: 0.875rem;">
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <label style="font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" name="is_main" value="1">
                    Glavna slika
                </label>
                <button type="submit" class="btn btn-sm btn-primary">Dodaj sliku</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal za odbijanje -->
<div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 1.5rem; border-radius: 8px; max-width: 400px; width: 90%;">
        <h3 style="margin: 0 0 1rem 0;">Vrati na doradu</h3>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="reject">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Napomena za autora:</label>
                <textarea name="review_notes" rows="3" class="form-control" placeholder="Što treba ispraviti..."></textarea>
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button type="button" onclick="hideRejectModal()" class="btn btn-outline">Odustani</button>
                <button type="submit" class="btn btn-danger">Vrati na doradu</button>
            </div>
        </form>
    </div>
</div>

<script>
const contentEl = document.getElementById('content');
const charCountEl = document.getElementById('charCount');
const wordCountEl = document.getElementById('wordCount');

function updateCounter() {
    const text = contentEl.value;
    const chars = text.length;
    const words = text.trim() ? text.trim().split(/\s+/).length : 0;

    charCountEl.textContent = chars.toLocaleString('hr-HR');
    wordCountEl.textContent = words.toLocaleString('hr-HR') + ' riječi';
}

contentEl.addEventListener('input', updateCounter);
updateCounter(); // Initial

function submitForReview() {
    if (confirm('Poslati članak uredniku na pregled?')) {
        document.getElementById('formAction').value = 'submit';
        document.getElementById('articleForm').submit();
    }
}

function approveArticle() {
    if (confirm('Odobriti ovaj članak?')) {
        document.getElementById('formAction').value = 'approve';
        document.getElementById('articleForm').submit();
    }
}

function deleteArticle() {
    if (confirm('Jeste li sigurni da želite obrisati ovaj članak?')) {
        document.getElementById('formAction').value = 'delete';
        document.getElementById('articleForm').submit();
    }
}

function showRejectModal() {
    document.getElementById('rejectModal').style.display = 'flex';
}

function hideRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
</script>

<?php include 'includes/footer.php'; ?>
