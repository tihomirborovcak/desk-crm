<?php
/**
 * Attachmenti - Fotografije i Dokumenti
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

define('PAGE_TITLE', 'Attachmenti');

$db = getDB();
$userId = $_SESSION['user_id'];
$isEditorRole = isEditor();

// Filteri
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 24;

// Obrada uploada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setMessage('danger', 'Nevažeći sigurnosni token');
    } else {
        $uploadedCount = 0;
        $errors = [];

        $files = $_FILES['files'];
        $caption = trim($_POST['caption'] ?? '');
        $credit = trim($_POST['credit'] ?? '');

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];

            try {
                $fileData = uploadFile($file);

                $stmt = $db->prepare("
                    INSERT INTO photos (filename, original_name, filepath, thumbnail, mime_type, file_size, width, height, caption, credit, uploaded_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $fileData['filename'],
                    $fileData['original_name'],
                    $fileData['filepath'],
                    $fileData['thumbnail'],
                    $fileData['mime_type'],
                    $fileData['file_size'],
                    $fileData['width'],
                    $fileData['height'],
                    $caption,
                    $credit,
                    $userId
                ]);

                $uploadedCount++;
                logActivity('file_upload', 'attachment', $db->lastInsertId());
            } catch (Exception $e) {
                $errors[] = $files['name'][$i] . ': ' . $e->getMessage();
            }
        }

        if ($uploadedCount > 0) {
            setMessage('success', "Uspješno uploadano $uploadedCount datoteka");
        }
        if (!empty($errors)) {
            setMessage('warning', implode(', ', $errors));
        }

        header('Location: photos.php');
        exit;
    }
}

// Brisanje datoteke
if (isset($_GET['delete'])) {
    $photoId = intval($_GET['delete']);

    $stmt = $db->prepare("SELECT * FROM photos WHERE id = ?");
    $stmt->execute([$photoId]);
    $photo = $stmt->fetch();

    if ($photo && ($photo['uploaded_by'] == $userId || $isEditorRole)) {
        // Obriši datoteke
        @unlink(UPLOAD_PATH . str_replace(UPLOAD_URL, '', $photo['filepath']));
        if ($photo['thumbnail']) {
            @unlink(UPLOAD_PATH . str_replace(UPLOAD_URL, '', $photo['thumbnail']));
        }

        $stmt = $db->prepare("DELETE FROM photos WHERE id = ?");
        $stmt->execute([$photoId]);

        logActivity('file_delete', 'attachment', $photoId);
        setMessage('success', 'Datoteka je obrisana');
    }

    header('Location: photos.php');
    exit;
}

// Gradnja upita
$where = [];
$params = [];

// Novinari vide samo svoje fotografije
if (!$isEditorRole) {
    $where[] = "p.uploaded_by = ?";
    $params[] = $userId;
}

if ($search) {
    $where[] = "(p.caption LIKE ? OR p.original_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Ukupan broj
$stmt = $db->prepare("SELECT COUNT(*) FROM photos p $whereClause");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Dohvati fotografije
$offset = ($page - 1) * $perPage;
$sql = "
    SELECT p.*, u.full_name as uploader_name
    FROM photos p
    LEFT JOIN users u ON p.uploaded_by = u.id
    $whereClause
    ORDER BY p.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$photos = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="d-flex" style="justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <h1>Attachmenti</h1>
    <button class="btn btn-primary" data-modal="uploadModal">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="17 8 12 3 7 8"/>
            <line x1="12" y1="3" x2="12" y2="15"/>
        </svg>
        Upload
    </button>
</div>

<!-- Filteri -->
<div class="card mt-2">
    <div class="card-body">
        <form method="GET" class="d-flex flex-wrap gap-1">
            <input type="text"
                   name="search"
                   class="form-control"
                   placeholder="Pretraži po opisu ili imenu..."
                   value="<?= e($search) ?>"
                   style="flex: 1; min-width: 200px;">

            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </button>

            <?php if ($search): ?>
            <a href="photos.php" class="btn btn-outline">Očisti</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Galerija -->
<?php if (empty($photos)): ?>
<div class="card mt-2">
    <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
        </svg>
        <h3>Nema datoteka</h3>
        <p>Uploadajte prvu datoteku</p>
        <button class="btn btn-primary mt-2" data-modal="uploadModal">Upload datoteka</button>
    </div>
</div>
<?php else: ?>

<div class="photo-grid mt-2">
    <?php foreach ($photos as $photo):
        $isImage = strpos($photo['mime_type'], 'image/') === 0;
        $ext = strtolower(pathinfo($photo['original_name'], PATHINFO_EXTENSION));
        $fileIcon = getFileIcon($photo['mime_type'], $ext);
    ?>
    <div class="photo-item">
        <?php if ($isImage): ?>
        <img src="<?= e($photo['thumbnail'] ?? $photo['filepath']) ?>"
             alt="<?= e($photo['caption'] ?: $photo['original_name']) ?>"
             loading="lazy">
        <?php else: ?>
        <div class="file-icon file-icon-<?= $fileIcon ?>">
            <?php if ($fileIcon === 'pdf'): ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <path d="M9 15h6"/>
                <path d="M9 11h6"/>
            </svg>
            <span>PDF</span>
            <?php elseif ($fileIcon === 'word'): ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <path d="M16 13H8"/>
                <path d="M16 17H8"/>
                <path d="M10 9H8"/>
            </svg>
            <span>DOC</span>
            <?php elseif ($fileIcon === 'excel'): ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <rect x="8" y="12" width="8" height="6"/>
            </svg>
            <span>XLS</span>
            <?php else: ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
            <span><?= strtoupper($ext) ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="photo-item-overlay">
            <div class="text-xs"><?= e(truncate($photo['original_name'], 20)) ?></div>
        </div>

        <div class="photo-item-meta">
            <div class="text-xs"><?= e($photo['uploader_name'] ?? 'Nepoznato') ?></div>
            <div class="text-xs text-muted"><?= date('d.m.Y H:i', strtotime($photo['created_at'])) ?></div>
        </div>

        <div class="photo-item-actions">
            <a href="<?= e($photo['filepath']) ?>"
               target="_blank"
               class="btn btn-sm btn-icon"
               style="background: rgba(255,255,255,0.9);"
               title="Otvori">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                    <polyline points="15 3 21 3 21 9"/>
                    <line x1="10" y1="14" x2="21" y2="3"/>
                </svg>
            </a>
            <a href="?delete=<?= $photo['id'] ?>"
               class="btn btn-sm btn-icon btn-danger"
               data-confirm="Jeste li sigurni da želite obrisati ovu datoteku?"
               title="Obriši">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Paginacija -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
    </a>
    <?php endif; ?>

    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
       class="<?= $i === $page ? 'active' : '' ?>">
        <?= $i ?>
    </a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="9 18 15 12 9 6"/>
        </svg>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<p class="text-muted text-sm text-center mt-2">Ukupno: <?= $total ?> datoteka</p>

<!-- Upload Modal -->
<div class="modal" id="uploadModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Upload datoteka</h3>
            <button class="modal-close">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <?= csrfField() ?>

                <div class="upload-area" id="mainUploadArea">
                    <input type="file" name="files[]" multiple style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    <p>Kliknite ili povucite datoteke ovdje</p>
                    <p class="text-xs text-muted">Slike, PDF, Word, Excel do 10MB</p>
                </div>

                <div class="form-group mt-2">
                    <label class="form-label" for="caption">Opis</label>
                    <input type="text" id="caption" name="caption" class="form-control" placeholder="Kratki opis datoteke...">
                </div>

                <div class="form-group">
                    <label class="form-label" for="credit">Autor/Izvor</label>
                    <input type="text" id="credit" name="credit" class="form-control" placeholder="Ime autora ili izvor...">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" data-modal-close>Odustani</button>
            <button type="submit" form="uploadForm" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                Upload
            </button>
        </div>
    </div>
</div>

<style>
.file-icon {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 120px;
    background: var(--gray-100);
    color: var(--gray-500);
}
.file-icon span {
    font-size: 0.7rem;
    font-weight: 600;
    margin-top: 0.25rem;
}
.file-icon-pdf { color: #dc2626; }
.file-icon-word { color: #2563eb; }
.file-icon-excel { color: #16a34a; }
.file-icon-powerpoint { color: #ea580c; }
.photo-item-meta {
    padding: 0.5rem;
    border-top: 1px solid var(--gray-200);
    background: var(--gray-50);
}
</style>

<?php include 'includes/footer.php'; ?>
