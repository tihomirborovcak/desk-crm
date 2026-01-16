<?php
/**
 * Zagorski list - Članci
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

define('PAGE_TITLE', 'Članci - Zagorski list');

$db = getDB();
$isEditor = isEditor();

// Inicijalizacija tablica ako ne postoje
try {
    $db->query("SELECT 1 FROM zl_sections LIMIT 1");
} catch (PDOException $e) {
    // Tablice ne postoje, preusmjeri na setup
    header('Location: zl-setup.php');
    exit;
}

// Trenutno izdanje (ili najnovije)
$issueId = (int)($_GET['issue'] ?? 0);

// Dohvati sva izdanja
$issuesStmt = $db->query("
    SELECT * FROM zl_issues
    ORDER BY year DESC, issue_number DESC
    LIMIT 20
");
$issues = $issuesStmt->fetchAll();

// Ako nije odabrano izdanje, uzmi najnovije ili kreiraj novo
if (!$issueId && !empty($issues)) {
    $issueId = $issues[0]['id'];
}

$currentIssue = null;
if ($issueId) {
    $stmt = $db->prepare("SELECT * FROM zl_issues WHERE id = ?");
    $stmt->execute([$issueId]);
    $currentIssue = $stmt->fetch();
}

// Dohvati rubrike
$sectionsStmt = $db->query("SELECT * FROM zl_sections WHERE active = 1 ORDER BY sort_order");
$sections = $sectionsStmt->fetchAll();

// Filtriranje
$filterStatus = $_GET['status'] ?? '';
$filterSection = (int)($_GET['section'] ?? 0);
$viewMode = $_GET['view'] ?? 'stranice'; // 'stranice' ili 'rubrike'

// Dohvati članke za trenutno izdanje
$articles = [];
if ($currentIssue) {
    $sql = "
        SELECT a.*,
               s.name as section_name,
               u.full_name as author_name,
               creator.full_name as created_by_name,
               reviewer.full_name as reviewed_by_name,
               (SELECT COUNT(*) FROM zl_article_images WHERE article_id = a.id) as image_count,
               (SELECT filepath FROM zl_article_images WHERE article_id = a.id ORDER BY is_main DESC, id ASC LIMIT 1) as thumbnail
        FROM zl_articles a
        LEFT JOIN zl_sections s ON a.section_id = s.id
        LEFT JOIN users u ON a.author_id = u.id
        LEFT JOIN users creator ON a.created_by = creator.id
        LEFT JOIN users reviewer ON a.reviewed_by = reviewer.id
        WHERE a.issue_id = ?
    ";
    $params = [$issueId];

    if ($filterStatus) {
        $sql .= " AND a.status = ?";
        $params[] = $filterStatus;
    }
    if ($filterSection) {
        $sql .= " AND a.section_id = ?";
        $params[] = $filterSection;
    }

    $sql .= " ORDER BY s.sort_order, a.page_number, a.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $articles = $stmt->fetchAll();
}

// Statistike
$stats = [
    'total' => 0,
    'nacrt' => 0,
    'za_pregled' => 0,
    'odobreno' => 0,
    'objavljeno' => 0,
    'total_chars' => 0
];
foreach ($articles as $a) {
    $stats['total']++;
    $stats[$a['status']]++;
    $stats['total_chars'] += $a['char_count'];
}

include 'includes/header.php';
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1>Zagorski list - Članci</h1>
        <?php if ($currentIssue): ?>
        <p style="color: #6b7280; margin: 0.25rem 0 0 0;">
            Broj <?= $currentIssue['issue_number'] ?>/<?= $currentIssue['year'] ?>
            · Izlazi <?= formatDate($currentIssue['publish_date']) ?>
        </p>
        <?php endif; ?>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="zl-import.php" class="btn btn-outline">Import</a>
        <a href="zl-issue.php" class="btn btn-outline">Brojevi</a>
        <a href="zl-clanak-edit.php<?= $issueId ? '?issue=' . $issueId : '' ?>" class="btn btn-primary">+ Novi članak</a>
    </div>
</div>

<!-- Izbor broja i prikaza -->
<?php if (!empty($issues)): ?>
<div style="background: #f3f4f6; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
    <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; justify-content: space-between;">
        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <label style="font-weight: 500;">Broj:</label>
            <select onchange="location.href='?issue='+this.value+'&view=<?= $viewMode ?>'" style="padding: 0.5rem; border-radius: 4px; border: 1px solid #d1d5db;">
                <?php foreach ($issues as $issue): ?>
                <option value="<?= $issue['id'] ?>" <?= $issue['id'] == $issueId ? 'selected' : '' ?>>
                    <?= $issue['issue_number'] ?>/<?= $issue['year'] ?>
                    (<?= formatDate($issue['publish_date']) ?>)
                    <?= $issue['status'] === 'zatvoren' ? '✓' : '' ?>
                </option>
                <?php endforeach; ?>
            </select>

            <?php if ($filterStatus || $filterSection): ?>
            <a href="?issue=<?= $issueId ?>&view=<?= $viewMode ?>" style="color: #6b7280; font-size: 0.875rem;">Očisti filtere</a>
            <?php endif; ?>
        </div>

        <!-- Toggle prikaza -->
        <div style="display: flex; gap: 0; border: 1px solid #d1d5db; border-radius: 6px; overflow: hidden;">
            <a href="?issue=<?= $issueId ?>&view=stranice"
               style="padding: 0.5rem 1rem; text-decoration: none; font-size: 0.875rem; <?= $viewMode === 'stranice' ? 'background: #1e3a5f; color: white;' : 'background: white; color: #374151;' ?>">
                Po stranicama
            </a>
            <a href="?issue=<?= $issueId ?>&view=rubrike"
               style="padding: 0.5rem 1rem; text-decoration: none; font-size: 0.875rem; border-left: 1px solid #d1d5db; <?= $viewMode === 'rubrike' ? 'background: #1e3a5f; color: white;' : 'background: white; color: #374151;' ?>">
                Po rubrikama
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Statistike -->
<?php if ($currentIssue): ?>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; text-align: center;">
        <div style="font-size: 1.5rem; font-weight: 600;"><?= $stats['total'] ?></div>
        <div style="color: #6b7280; font-size: 0.875rem;">Ukupno</div>
    </div>
    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; text-align: center;">
        <div style="font-size: 1.5rem; font-weight: 600; color: #6b7280;"><?= $stats['nacrt'] ?></div>
        <div style="color: #6b7280; font-size: 0.875rem;">Nacrt</div>
    </div>
    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; text-align: center;">
        <div style="font-size: 1.5rem; font-weight: 600; color: #f59e0b;"><?= $stats['za_pregled'] ?></div>
        <div style="color: #6b7280; font-size: 0.875rem;">Za pregled</div>
    </div>
    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; text-align: center;">
        <div style="font-size: 1.5rem; font-weight: 600; color: #10b981;"><?= $stats['odobreno'] ?></div>
        <div style="color: #6b7280; font-size: 0.875rem;">Odobreno</div>
    </div>
    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; text-align: center;">
        <div style="font-size: 1.5rem; font-weight: 600;"><?= number_format($stats['total_chars']) ?></div>
        <div style="color: #6b7280; font-size: 0.875rem;">Znakova</div>
    </div>
</div>
<?php endif; ?>

<!-- Članci po rubrikama -->
<?php if ($currentIssue): ?>
    <?php if (empty($articles)): ?>
    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 3rem; text-align: center;">
        <p style="color: #6b7280; margin: 0;">Nema članaka za ovaj broj.</p>
        <a href="zl-clanak-edit.php?issue=<?= $issueId ?>" class="btn btn-primary" style="margin-top: 1rem;">Dodaj prvi članak</a>
    </div>
    <?php else: ?>
        <?php
        // Grupiraj ovisno o načinu prikaza
        $grouped = [];
        $groupChars = [];

        if ($viewMode === 'stranice') {
            // Grupiraj po facing pages (2-3, 4-5, 6-7...)
            foreach ($articles as $a) {
                $page = $a['page_number'] ?: 0;
                if ($page == 0) {
                    $groupKey = 999; // Bez stranice ide na kraj
                } elseif ($page == 1) {
                    $groupKey = 1; // Naslovnica sama
                } else {
                    // Facing pages: 2-3, 4-5, 6-7...
                    // Parne stranice su lijeve, neparne desne
                    if ($page % 2 == 0) {
                        $groupKey = $page; // 2, 4, 6...
                    } else {
                        $groupKey = $page - 1; // 3→2, 5→4, 7→6...
                    }
                }
                $grouped[$groupKey][] = $a;
                $groupChars[$groupKey] = ($groupChars[$groupKey] ?? 0) + $a['char_count'];
            }
            ksort($grouped); // Sortiraj po broju stranice
        } else {
            // Grupiraj po rubrikama
            foreach ($articles as $a) {
                $secName = $a['section_name'] ?: 'Bez rubrike';
                $grouped[$secName][] = $a;
                $groupChars[$secName] = ($groupChars[$secName] ?? 0) + $a['char_count'];
            }
        }
        ?>
        <?php if ($viewMode === 'stranice'): ?>
            <!-- FACING PAGES PRIKAZ -->
            <?php foreach ($grouped as $groupKey => $groupArticles):
                $leftPage = ($groupKey == 1) ? 1 : $groupKey;
                $rightPage = ($groupKey == 1) ? null : $groupKey + 1;

                // Podijeli članke na lijevu i desnu stranicu
                $leftArticles = [];
                $rightArticles = [];
                $leftChars = 0;
                $rightChars = 0;

                foreach ($groupArticles as $a) {
                    if ($groupKey == 1 || $a['page_number'] == $leftPage) {
                        $leftArticles[] = $a;
                        $leftChars += $a['char_count'];
                    } else {
                        $rightArticles[] = $a;
                        $rightChars += $a['char_count'];
                    }
                }
            ?>
            <div style="background: #e5e7eb; border-radius: 8px; padding: 4px; margin-bottom: 1.5rem;">
                <div style="display: grid; grid-template-columns: <?= $groupKey == 1 ? '1fr' : '1fr 1fr' ?>; gap: 4px;">
                    <!-- Lijeva stranica -->
                    <div style="background: white; border-radius: 6px; min-height: 200px;">
                        <div style="background: #1e3a5f; color: white; padding: 0.5rem 1rem; border-radius: 6px 6px 0 0; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 600;"><?= $groupKey == 1 ? 'Naslovnica' : $leftPage ?></span>
                            <span style="font-size: 0.75rem; opacity: 0.8;"><?= number_format($leftChars) ?> zn.</span>
                        </div>
                        <div style="padding: 0.5rem;">
                            <?php if (empty($leftArticles)): ?>
                            <div style="padding: 2rem; text-align: center; color: #9ca3af; font-size: 0.875rem;">Nema članaka</div>
                            <?php else: ?>
                                <?php foreach ($leftArticles as $article): ?>
                                <a href="zl-clanak-edit.php?id=<?= $article['id'] ?>" style="display: block; padding: 0.5rem; margin-bottom: 0.5rem; background: #f9fafb; border-radius: 4px; text-decoration: none; color: inherit; border-left: 3px solid <?= articleStatusColor($article['status']) ?>;">
                                    <div style="font-weight: 600; font-size: 0.875rem; color: #111827; margin-bottom: 0.25rem;"><?= e(truncate($article['title'], 60)) ?></div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #6b7280;">
                                        <span><?= e($article['section_name'] ?: 'Bez rubrike') ?></span>
                                        <span style="font-weight: 600; color: #1e3a5f;"><?= number_format($article['char_count']) ?></span>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($groupKey != 1): ?>
                    <!-- Desna stranica -->
                    <div style="background: white; border-radius: 6px; min-height: 200px;">
                        <div style="background: #1e3a5f; color: white; padding: 0.5rem 1rem; border-radius: 6px 6px 0 0; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 600;"><?= $rightPage ?></span>
                            <span style="font-size: 0.75rem; opacity: 0.8;"><?= number_format($rightChars) ?> zn.</span>
                        </div>
                        <div style="padding: 0.5rem;">
                            <?php if (empty($rightArticles)): ?>
                            <div style="padding: 2rem; text-align: center; color: #9ca3af; font-size: 0.875rem;">Nema članaka</div>
                            <?php else: ?>
                                <?php foreach ($rightArticles as $article): ?>
                                <a href="zl-clanak-edit.php?id=<?= $article['id'] ?>" style="display: block; padding: 0.5rem; margin-bottom: 0.5rem; background: #f9fafb; border-radius: 4px; text-decoration: none; color: inherit; border-left: 3px solid <?= articleStatusColor($article['status']) ?>;">
                                    <div style="font-weight: 600; font-size: 0.875rem; color: #111827; margin-bottom: 0.25rem;"><?= e(truncate($article['title'], 60)) ?></div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #6b7280;">
                                        <span><?= e($article['section_name'] ?: 'Bez rubrike') ?></span>
                                        <span style="font-weight: 600; color: #1e3a5f;"><?= number_format($article['char_count']) ?></span>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

        <?php else: ?>
            <!-- RUBRIKE PRIKAZ -->
            <?php foreach ($grouped as $groupKey => $groupArticles): ?>
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 1rem; overflow: hidden;">
                <div style="background: #f9fafb; padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: 1rem;"><?= e($groupKey) ?> (<?= count($groupArticles) ?>)</h3>
                    <span style="font-size: 0.875rem; color: #1e3a5f; font-weight: 600;"><?= number_format($groupChars[$groupKey]) ?> zn.</span>
                </div>
                <div style="padding: 0;">
                    <?php foreach ($groupArticles as $article): ?>
                    <a href="zl-clanak-edit.php?id=<?= $article['id'] ?>" style="display: block; padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6; text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: flex-start; gap: 1rem;">
                            <!-- Thumbnail -->
                            <?php if ($article['thumbnail']): ?>
                            <div style="flex-shrink: 0; width: 80px; height: 60px; border-radius: 4px; overflow: hidden; background: #f3f4f6;">
                                <img src="<?= UPLOAD_URL . e($article['thumbnail']) ?>" alt=""
                                     style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <?php else: ?>
                            <div style="flex-shrink: 0; width: 80px; height: 60px; border-radius: 4px; background: #f3f4f6; display: flex; align-items: center; justify-content: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <polyline points="21 15 16 10 5 21"/>
                                </svg>
                            </div>
                            <?php endif; ?>

                            <!-- Sadržaj -->
                            <div style="flex: 1; min-width: 0;">
                                <?php if ($article['supertitle']): ?>
                                <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.125rem;">
                                    <?= e($article['supertitle']) ?>
                                </div>
                                <?php endif; ?>
                                <div style="font-weight: 600; color: #111827; margin-bottom: 0.25rem;">
                                    <?= e($article['title']) ?>
                                </div>
                                <?php if ($article['subtitle']): ?>
                                <div style="font-size: 0.875rem; color: #4b5563; margin-bottom: 0.25rem; line-height: 1.3;">
                                    <?= e(truncate($article['subtitle'], 120)) ?>
                                </div>
                                <?php endif; ?>
                                <div style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.5rem;">
                                    <?= e($article['author_text'] ?: $article['author_name'] ?: $article['created_by_name']) ?>
                                    <?php if ($article['page_number']): ?>
                                    · str. <?= $article['page_number'] ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Status i brojači -->
                            <div style="flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-end; gap: 0.25rem;">
                                <span style="background: <?= articleStatusColor($article['status']) ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">
                                    <?= translateArticleStatus($article['status']) ?>
                                </span>
                                <span style="font-size: 0.875rem; font-weight: 600; color: #1e3a5f;">
                                    <?= number_format($article['char_count']) ?> zn.
                                </span>
                                <?php if ($article['image_count'] > 1): ?>
                                <span style="font-size: 0.75rem; color: #6b7280;">
                                    +<?= $article['image_count'] - 1 ?> foto
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
<?php else: ?>
<div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 2rem; text-align: center;">
    <p style="color: #92400e; margin: 0 0 1rem 0;">Nema kreiranog broja Zagorskog lista.</p>
    <a href="zl-issue.php" class="btn btn-primary">Kreiraj prvi broj</a>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
