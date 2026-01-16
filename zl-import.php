<?php
/**
 * Import članaka iz StoryEditor XML formata
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

if (!isEditor()) {
    die('Samo urednici mogu importirati članke.');
}

$db = getDB();
$userId = $_SESSION['user_id'];

$results = [];
$imported = 0;
$errors = [];
$processed = false;

// Dohvati mapiranje rubrika
$sectionsStmt = $db->query("SELECT id, slug, name FROM zl_sections");
$sectionsMap = [];
while ($row = $sectionsStmt->fetch()) {
    $sectionsMap[strtolower($row['slug'])] = $row['id'];
    $sectionsMap[strtolower($row['name'])] = $row['id'];
}

// Funkcija za čišćenje HTML-a iz naslova
function cleanHeadline($html) {
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = trim($text);
    return $text;
}

// Funkcija za čišćenje teksta članka
function cleanArticleText($html) {
    $text = preg_replace('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/is', "\n\n$1\n\n", $html);
    $text = preg_replace('/<p[^>]*>(.*?)<\/p>/is', "$1\n\n", $text);
    $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace("/\n{3,}/", "\n\n", $text);
    $text = trim($text);
    return $text;
}

// Obrada uploada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $processed = true;

    if (empty($_FILES['zipfiles']['name'][0])) {
        $errors[] = "Niste odabrali nijednu datoteku.";
    } else {
        $fileCount = count($_FILES['zipfiles']['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['zipfiles']['error'][$i] !== UPLOAD_ERR_OK) {
                $errors[] = "Greška pri uploadu: " . $_FILES['zipfiles']['name'][$i];
                continue;
            }

            $zipFile = $_FILES['zipfiles']['tmp_name'][$i];
            $zipName = pathinfo($_FILES['zipfiles']['name'][$i], PATHINFO_FILENAME);

            // Ekstrahiraj ZIP
            $tempDir = sys_get_temp_dir() . '/zl_import_' . uniqid();
            mkdir($tempDir, 0755, true);

            // Pokušaj s ZipArchive ili shell unzip
            $extracted = false;
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($zipFile) === true) {
                    $zip->extractTo($tempDir);
                    $zip->close();
                    $extracted = true;
                }
            }

            if (!$extracted) {
                // Fallback na shell unzip (Windows/Linux)
                $escapedZip = escapeshellarg($zipFile);
                $escapedDir = escapeshellarg($tempDir);
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    // Windows - koristi tar (dostupan od Win10)
                    exec("tar -xf $escapedZip -C $escapedDir 2>&1", $output, $returnCode);
                } else {
                    exec("unzip -o $escapedZip -d $escapedDir 2>&1", $output, $returnCode);
                }
                $extracted = ($returnCode === 0);
            }

            if (!$extracted) {
                $errors[] = "Ne mogu ekstrahirati: " . $_FILES['zipfiles']['name'][$i];
                @rmdir($tempDir);
                continue;
            }

            // Pronađi XML datoteku
            $xmlFiles = glob($tempDir . '/*.xml');
            if (empty($xmlFiles)) {
                $xmlFiles = glob($tempDir . '/*/*.xml');
            }

            if (empty($xmlFiles)) {
                $errors[] = "Nema XML datoteke u: " . $_FILES['zipfiles']['name'][$i];
                // Cleanup
                array_map('unlink', glob("$tempDir/*.*") ?: []);
                array_map('unlink', glob("$tempDir/images/*.*") ?: []);
                @rmdir("$tempDir/images");
                @rmdir($tempDir);
                continue;
            }

            $xmlFile = $xmlFiles[0];
            $xmlContent = file_get_contents($xmlFile);

            // Parsiraj XML
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlContent);

            if ($xml === false) {
                $errors[] = "Greška pri parsiranju XML: " . $_FILES['zipfiles']['name'][$i];
                continue;
            }

            $article = $xml->Article;
            if (!$article) {
                $errors[] = "Nema Article elementa: " . $_FILES['zipfiles']['name'][$i];
                continue;
            }

            // Ekstrahiraj podatke
            $storyEditorCode = (string)$article->Code;
            $issueNumber = (int)$article->Issue;
            $publishDate = (string)$article->ProjectPublishDate;
            $sectionSlug = strtolower(trim((string)$article->Section));
            $pageNumber = (int)$article->Pages->Page;

            $supertitle = cleanHeadline((string)$article->Superscript_headline);
            $title = cleanHeadline((string)$article->Main_headline);
            $subtitle = cleanHeadline((string)$article->Subtitle);
            $content = cleanArticleText((string)$article->Text);
            $charCount = mb_strlen($content);
            $wordCount = str_word_count($content, 0, 'ČčĆćŽžŠšĐđ');

            // Autor
            $authorText = '';
            if ($article->ContributorList->Contributor) {
                $authorText = (string)$article->ContributorList->Contributor->ContributorName;
            }
            if (empty($authorText)) {
                $authorText = cleanHeadline((string)$article->Author);
            }

            // Provjeri/kreiraj issue
            $stmt = $db->prepare("SELECT id FROM zl_issues WHERE issue_number = ? AND year = ?");
            $year = date('Y', strtotime($publishDate));
            $stmt->execute([$issueNumber, $year]);
            $issueId = $stmt->fetchColumn();

            if (!$issueId) {
                $stmt = $db->prepare("INSERT INTO zl_issues (issue_number, year, publish_date, created_by) VALUES (?, ?, ?, ?)");
                $stmt->execute([$issueNumber, $year, $publishDate, $userId]);
                $issueId = $db->lastInsertId();
                $results[] = "Kreiran broj $issueNumber/$year";
            }

            // Pronađi section_id
            $sectionId = $sectionsMap[$sectionSlug] ?? $sectionsMap['ostalo'] ?? null;

            // Provjeri postoji li već članak s istim naslovom u istom broju
            $stmt = $db->prepare("SELECT id FROM zl_articles WHERE title = ? AND issue_id = ?");
            $stmt->execute([$title, $issueId]);
            if ($stmt->fetchColumn()) {
                $results[] = "Preskočen (već postoji): $title";
                // Cleanup
                array_map('unlink', glob("$tempDir/*.*") ?: []);
                array_map('unlink', glob("$tempDir/images/*.*") ?: []);
                @rmdir("$tempDir/images");
                @rmdir($tempDir);
                continue;
            }

            // Unesi članak
            try {
                $stmt = $db->prepare("
                    INSERT INTO zl_articles
                    (issue_id, section_id, supertitle, title, subtitle, content, author_text, page_number, char_count, word_count, status, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'odobreno', ?)
                ");
                $stmt->execute([
                    $issueId,
                    $sectionId,
                    $supertitle,
                    $title ?: "Članak $storyEditorCode",
                    $subtitle,
                    $content,
                    $authorText,
                    $pageNumber ?: null,
                    $charCount,
                    $wordCount,
                    $userId
                ]);
                $articleId = $db->lastInsertId();

                // Importiraj slike
                $imageCount = 0;
                if ($article->MediaList->Media) {
                    $imageDir = UPLOAD_PATH . 'zl-clanci/' . date('Y/m/');
                    if (!is_dir($imageDir)) {
                        mkdir($imageDir, 0755, true);
                    }

                    $isFirst = true;
                    foreach ($article->MediaList->Media as $media) {
                        $mediaFilename = (string)$media->MediaFilename;
                        $sourceImagePath = dirname($xmlFile) . '/' . $mediaFilename;

                        if (!file_exists($sourceImagePath)) {
                            continue;
                        }

                        $ext = strtolower(pathinfo($mediaFilename, PATHINFO_EXTENSION));
                        $newFilename = date('Y-m-d_His_') . bin2hex(random_bytes(4)) . '.' . $ext;
                        $destPath = $imageDir . $newFilename;
                        $filepath = 'zl-clanci/' . date('Y/m/') . $newFilename;

                        if (copy($sourceImagePath, $destPath)) {
                            $caption = cleanHeadline((string)$media->WEB_MediaCaption);
                            $credit = (string)$media->MediaCredit;
                            $credit = str_replace('_', ' ', $credit);

                            $stmt = $db->prepare("
                                INSERT INTO zl_article_images (article_id, filename, original_name, filepath, caption, credit, is_main)
                                VALUES (?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $articleId,
                                $newFilename,
                                basename($mediaFilename),
                                $filepath,
                                $caption,
                                $credit,
                                $isFirst ? 1 : 0
                            ]);
                            $imageCount++;
                            $isFirst = false;
                        }
                    }
                }

                $imported++;
                $results[] = "Importiran: $title" . ($imageCount ? " ($imageCount slika)" : "");

            } catch (PDOException $e) {
                $errors[] = "Greška za '$title': " . $e->getMessage();
            }

            // Očisti temp direktorij
            array_map('unlink', glob("$tempDir/*.*") ?: []);
            array_map('unlink', glob("$tempDir/images/*.*") ?: []);
            @rmdir("$tempDir/images");
            @rmdir($tempDir);
        }
    }
}

define('PAGE_TITLE', 'Import članaka');
include 'includes/header.php';
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <h1>Import članaka iz StoryEditora</h1>
    <a href="zl-clanci.php" class="btn btn-outline">← Natrag na članke</a>
</div>

<?php if ($processed): ?>
<!-- Rezultati importa -->
<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
    <h2 style="margin: 0 0 1rem 0; font-size: 1.125rem;">Rezultat importa</h2>

    <div style="background: #d1fae5; border: 1px solid #a7f3d0; color: #059669; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <strong>Uspješno importirano: <?= $imported ?> članaka</strong>
    </div>

    <?php if (!empty($errors)): ?>
    <div style="background: #fee2e2; border: 1px solid #fecaca; color: #dc2626; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <strong>Greške (<?= count($errors) ?>):</strong>
        <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
            <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($results)): ?>
    <div style="max-height: 400px; overflow-y: auto; background: #f9fafb; padding: 1rem; border-radius: 4px; font-size: 0.875rem;">
        <?php foreach ($results as $r): ?>
        <div style="padding: 0.25rem 0; border-bottom: 1px solid #e5e7eb;"><?= e($r) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div style="margin-top: 1rem;">
        <a href="zl-clanci.php" class="btn btn-primary">Idi na članke</a>
        <a href="zl-import.php" class="btn btn-outline">Importiraj još</a>
    </div>
</div>

<?php else: ?>
<!-- Upload forma -->
<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem;">
    <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>

        <div style="border: 2px dashed #d1d5db; border-radius: 8px; padding: 2rem; text-align: center; margin-bottom: 1rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5" style="margin-bottom: 1rem;">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            <div style="margin-bottom: 1rem;">
                <label style="cursor: pointer;">
                    <span class="btn btn-primary">Odaberi ZIP datoteke</span>
                    <input type="file" name="zipfiles[]" multiple accept=".zip" style="display: none;" onchange="updateFileList(this)">
                </label>
            </div>
            <p style="color: #6b7280; margin: 0; font-size: 0.875rem;">
                Odaberite jednu ili više ZIP datoteka iz StoryEditora (art_*.zip)
            </p>
            <div id="fileList" style="margin-top: 1rem; text-align: left; display: none;">
                <strong>Odabrane datoteke:</strong>
                <ul id="fileListItems" style="margin: 0.5rem 0 0 1.5rem; padding: 0; color: #374151;"></ul>
            </div>
        </div>

        <div style="background: #f3f4f6; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 0.875rem;">Import će:</h3>
            <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.875rem; color: #4b5563;">
                <li>Kreirati broj ZL ako ne postoji</li>
                <li>Importirati članke s nadnaslovom, naslovom, podnaslovom i tekstom</li>
                <li>Kopirati sve slike iz ZIP-a</li>
                <li>Postaviti rubriku prema podacima iz XML-a</li>
                <li>Postaviti status na "odobreno"</li>
                <li>Preskočiti članke koji već postoje (isti naslov u istom broju)</li>
            </ul>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">
            Pokreni import
        </button>
    </form>
</div>

<script>
function updateFileList(input) {
    const list = document.getElementById('fileList');
    const items = document.getElementById('fileListItems');
    items.innerHTML = '';

    if (input.files.length > 0) {
        list.style.display = 'block';
        for (let i = 0; i < input.files.length; i++) {
            const li = document.createElement('li');
            li.textContent = input.files[i].name;
            items.appendChild(li);
        }
    } else {
        list.style.display = 'none';
    }
}
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
