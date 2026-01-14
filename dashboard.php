<?php
/**
 * Dashboard - Početna stranica
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

define('PAGE_TITLE', 'Početna');

$db = getDB();
$userId = $_SESSION['user_id'];

// ============================================
// 1. OBIČNI RSS - zadnje vijesti iz baze
// ============================================
function fetchRSS($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function parseRSS($xml, $limit = 5) {
    $items = [];
    if (!$xml) return $items;
    
    $feed = @simplexml_load_string($xml);
    if (!$feed) return $items;
    
    $count = 0;
    foreach ($feed->channel->item as $item) {
        if ($count >= $limit) break;
        $items[] = [
            'title' => (string)$item->title,
            'link' => (string)$item->link,
            'pubDate' => (string)$item->pubDate,
            'description' => strip_tags((string)$item->description)
        ];
        $count++;
    }
    return $items;
}

// Dohvati zadnje vijesti iz RSS-a
$zagorjeRSS = fetchRSS('https://www.zagorje-international.hr/index.php/feed/');
$zagorjeLatest = parseRSS($zagorjeRSS, 5);

$stubicaRSS = fetchRSS('https://radio-stubica.hr/feed/');
$stubicaLatest = parseRSS($stubicaRSS, 5);

// ============================================
// 2. FEEDLY API - trending po engagementu
// ============================================
define('FEEDLY_TOKEN', 'REDACTED_FEEDLY_TOKEN');
define('FEEDLY_USER_ID', '6e135c2a-75fe-4109-8be8-6b52fa6866e6');
define('FEEDLY_API_URL', 'https://cloud.feedly.com/v3');

function feedlyRequest($endpoint, $params = []) {
    $url = FEEDLY_API_URL . $endpoint;
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . FEEDLY_TOKEN,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200) return ['error' => "HTTP $httpCode"];
    return json_decode($response, true);
}

function getFeedlyArticles($streamId, $count = 5) {
    $params = [
        'streamId' => $streamId,
        'count' => $count,
        'ranked' => 'engagement'
    ];
    return feedlyRequest('/streams/contents', $params);
}

// Dohvati trending iz Feedlyja
$zagorjeStream = 'feed/https://www.zagorje-international.hr/index.php/feed/';
$stubicaStream = 'feed/https://radio-stubica.hr/feed/';

$zagorjeTrending = getFeedlyArticles($zagorjeStream, 5);
$zagorjeTrendingItems = $zagorjeTrending['items'] ?? [];

$stubicaTrending = getFeedlyArticles($stubicaStream, 5);
$stubicaTrendingItems = $stubicaTrending['items'] ?? [];

// Moji taskovi (pending i in_progress)
$stmt = $db->prepare("
    SELECT t.*, u.full_name as creator_name 
    FROM tasks t 
    LEFT JOIN users u ON t.created_by = u.id
    WHERE t.assigned_to = ? AND t.status IN ('pending', 'in_progress')
    ORDER BY 
        CASE t.priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END,
        t.due_date ASC
    LIMIT 5
");
$stmt->execute([$userId]);
$myTasks = $stmt->fetchAll();

// Nadolazeći eventi (7 dana)
$stmt = $db->query("
    SELECT e.*, 
           GROUP_CONCAT(u.full_name SEPARATOR ', ') as assigned_people
    FROM events e
    LEFT JOIN event_assignments ea ON e.id = ea.event_id
    LEFT JOIN users u ON ea.user_id = u.id
    WHERE e.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    GROUP BY e.id
    ORDER BY e.event_date, e.event_time
    LIMIT 5
");
$upcomingEvents = $stmt->fetchAll();

// Moji eventi
$stmt = $db->prepare("
    SELECT e.*, ea.role as my_role
    FROM events e
    JOIN event_assignments ea ON e.id = ea.event_id
    WHERE ea.user_id = ? AND e.event_date >= CURDATE()
    ORDER BY e.event_date, e.event_time
    LIMIT 5
");
$stmt->execute([$userId]);
$myEvents = $stmt->fetchAll();

// Danas na dežurstvu
$stmt = $db->query("
    SELECT s.*, u.full_name 
    FROM shifts s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.shift_date = CURDATE()
    ORDER BY s.shift_type
");
$todayShifts = $stmt->fetchAll();

// Statistike
$stmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status IN ('pending', 'in_progress')");
$stmt->execute([$userId]);
$taskCount = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM event_assignments ea JOIN events e ON ea.event_id = e.id WHERE ea.user_id = ? AND e.event_date >= CURDATE()");
$stmt->execute([$userId]);
$eventCount = $stmt->fetchColumn();

include 'includes/header.php';
?>

<h1 class="mb-2">Dobrodošli, <?= e($user['full_name']) ?>!</h1>
<p class="text-muted mb-2"><?= date('l, j. F Y.') ?></p>

<!-- Statistike -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-value"><?= $taskCount ?></div>
        <div class="stat-label">Aktivnih taskova</div>
    </div>
    <div class="stat-card success">
        <div class="stat-value"><?= $eventCount ?></div>
        <div class="stat-label">Mojih evenata</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-value"><?= count($todayShifts) ?></div>
        <div class="stat-label">Danas dežura</div>
    </div>
    <div class="stat-card info">
        <div class="stat-value"><?= count($zagorjeLatest) + count($stubicaLatest) ?></div>
        <div class="stat-label">Novih vijesti</div>
    </div>
</div>

<!-- Brze akcije -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Brze akcije</h2>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-1">
            <a href="task-edit.php" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 20h9"/>
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                </svg>
                Novi task
            </a>
            <a href="event-edit.php" class="btn btn-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                Novi event
            </a>
            <a href="rss.php" class="btn btn-info">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 11a9 9 0 0 1 9 9"/>
                    <path d="M4 4a16 16 0 0 1 16 16"/>
                    <circle cx="5" cy="19" r="1"/>
                </svg>
                RSS vijesti
            </a>
            <a href="photos.php" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <polyline points="21 15 16 10 5 21"/>
                </svg>
                Fotografije
            </a>
        </div>
    </div>
</div>

<!-- Danas na dežurstvu -->
<?php if (!empty($todayShifts)): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">🟢 Danas na dežurstvu</h2>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-1">
            <?php foreach ($todayShifts as $shift): ?>
            <span class="badge badge-<?= $shift['shift_type'] === 'morning' ? 'warning' : ($shift['shift_type'] === 'afternoon' ? 'info' : 'success') ?>">
                <?= e($shift['full_name']) ?> - <?= translateShift($shift['shift_type']) ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row-2-col">
    <!-- Moji taskovi -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📋 Moji taskovi</h2>
            <a href="tasks.php?assigned=<?= $userId ?>" class="btn btn-sm btn-outline">Svi</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($myTasks)): ?>
            <div class="empty-state" style="padding: 2rem;">
                <p class="text-muted">Nema aktivnih taskova 🎉</p>
            </div>
            <?php else: ?>
            <div class="list-items">
                <?php foreach ($myTasks as $task): ?>
                <a href="task-edit.php?id=<?= $task['id'] ?>" class="list-item">
                    <div class="list-item-content">
                        <div class="list-item-title">
                            <?php if ($task['priority'] === 'urgent'): ?>
                            <span class="badge badge-danger">!</span>
                            <?php elseif ($task['priority'] === 'high'): ?>
                            <span class="badge badge-warning">!</span>
                            <?php endif; ?>
                            <?= e($task['title']) ?>
                        </div>
                        <div class="list-item-meta">
                            <span class="badge badge-<?= taskStatusColor($task['status']) ?>">
                                <?= translateTaskStatus($task['status']) ?>
                            </span>
                            <?php if ($task['due_date']): ?>
                            <span><?= formatDate($task['due_date'], 'j.n.') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Moji eventi -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📅 Moji eventi</h2>
            <a href="events.php?my=1" class="btn btn-sm btn-outline">Svi</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($myEvents)): ?>
            <div class="empty-state" style="padding: 2rem;">
                <p class="text-muted">Niste dodijeljeni na evente</p>
            </div>
            <?php else: ?>
            <div class="list-items">
                <?php foreach ($myEvents as $event): ?>
                <a href="event-edit.php?id=<?= $event['id'] ?>" class="list-item">
                    <div class="list-item-content">
                        <div class="list-item-title"><?= e($event['title']) ?></div>
                        <div class="list-item-meta">
                            <span class="badge badge-<?= eventTypeColor($event['event_type']) ?>">
                                <?= translateEventType($event['event_type']) ?>
                            </span>
                            <span><?= formatDate($event['event_date'], 'j.n.') ?> <?= $event['event_time'] ? date('H:i', strtotime($event['event_time'])) : '' ?></span>
                            <span class="text-muted">(<?= translateEventRole($event['my_role']) ?>)</span>
                        </div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Nadolazeći eventi -->
<?php if (!empty($upcomingEvents)): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">🗓️ Nadolazeći eventi (7 dana)</h2>
        <a href="events.php" class="btn btn-sm btn-outline">Svi eventi</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="list-items">
            <?php foreach ($upcomingEvents as $event): ?>
            <a href="event-edit.php?id=<?= $event['id'] ?>" class="list-item">
                <div class="list-item-content">
                    <div class="list-item-title">
                        <?php if ($event['importance'] === 'must_cover'): ?>
                        <span class="badge badge-danger">OBAVEZNO</span>
                        <?php elseif ($event['importance'] === 'important'): ?>
                        <span class="badge badge-warning">VAŽNO</span>
                        <?php endif; ?>
                        <?= e($event['title']) ?>
                    </div>
                    <div class="list-item-meta">
                        <span class="badge badge-<?= eventTypeColor($event['event_type']) ?>">
                            <?= translateEventType($event['event_type']) ?>
                        </span>
                        <span>📍 <?= e($event['location'] ?: 'TBA') ?></span>
                        <span>📅 <?= formatDate($event['event_date'], 'D j.n.') ?> <?= $event['event_time'] ? date('H:i', strtotime($event['event_time'])) : '' ?></span>
                    </div>
                    <?php if ($event['assigned_people']): ?>
                    <div class="text-xs text-muted mt-1">👥 <?= e($event['assigned_people']) ?></div>
                    <?php else: ?>
                    <div class="text-xs text-danger mt-1">⚠️ Nitko nije dodijeljen!</div>
                    <?php endif; ?>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ZADNJE VIJESTI (obični RSS) -->
<h2 class="section-title">📰 Zadnje vijesti</h2>
<div class="row-2-col">
    <!-- Zagorje International - zadnje -->
    <div class="card">
        <div class="card-header" style="background: #2d5016; color: white;">
            <h2 class="card-title" style="color: white;">🌍 Zagorje International</h2>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($zagorjeLatest)): ?>
            <p class="text-muted text-center" style="padding: 1rem;">Nema vijesti</p>
            <?php else: ?>
            <div class="list-items">
                <?php foreach ($zagorjeLatest as $item): ?>
                <a href="<?= e($item['link']) ?>" target="_blank" class="list-item">
                    <div class="list-item-content">
                        <div class="list-item-title"><?= e($item['title']) ?></div>
                        <div class="list-item-meta">
                            <span><?= $item['pubDate'] ? timeAgo(date('Y-m-d H:i:s', strtotime($item['pubDate']))) : '' ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Radio Stubica - zadnje -->
    <div class="card">
        <div class="card-header" style="background: #1565c0; color: white;">
            <h2 class="card-title" style="color: white;">📻 Radio Stubica</h2>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($stubicaLatest)): ?>
            <p class="text-muted text-center" style="padding: 1rem;">Nema vijesti</p>
            <?php else: ?>
            <div class="list-items">
                <?php foreach ($stubicaLatest as $item): ?>
                <a href="<?= e($item['link']) ?>" target="_blank" class="list-item">
                    <div class="list-item-content">
                        <div class="list-item-title"><?= e($item['title']) ?></div>
                        <div class="list-item-meta">
                            <span><?= $item['pubDate'] ? timeAgo(date('Y-m-d H:i:s', strtotime($item['pubDate']))) : '' ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- TRENDING VIJESTI (Feedly) -->
<h2 class="section-title">🔥 Trending (najpopularnije)</h2>
<div class="row-2-col">
    <!-- Zagorje International - trending -->
    <div class="card">
        <div class="card-header" style="background: #c0392b; color: white;">
            <h2 class="card-title" style="color: white;">🌍 Zagorje International</h2>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($zagorjeTrendingItems)): ?>
            <p class="text-muted text-center" style="padding: 1rem;">Nema trending vijesti</p>
            <?php else: ?>
            <div class="list-items">
                <?php foreach ($zagorjeTrendingItems as $item): 
                    $link = $item['alternate'][0]['href'] ?? $item['originId'] ?? '#';
                    $title = $item['title'] ?? 'Bez naslova';
                    $published = $item['published'] ?? 0;
                    $engagement = $item['engagement'] ?? 0;
                ?>
                <a href="<?= e($link) ?>" target="_blank" class="list-item">
                    <div class="list-item-content">
                        <div class="list-item-title"><?= e($title) ?></div>
                        <div class="list-item-meta">
                            <span><?= $published ? timeAgo(date('Y-m-d H:i:s', $published/1000)) : '' ?></span>
                            <?php if ($engagement > 0): ?>
                            <span class="engagement-badge">🔥 <?= $engagement >= 1000 ? round($engagement/1000, 1).'K' : $engagement ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Radio Stubica - trending -->
    <div class="card">
        <div class="card-header" style="background: #e67e22; color: white;">
            <h2 class="card-title" style="color: white;">📻 Radio Stubica</h2>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($stubicaTrendingItems)): ?>
            <p class="text-muted text-center" style="padding: 1rem;">Nema trending vijesti</p>
            <?php else: ?>
            <div class="list-items">
                <?php foreach ($stubicaTrendingItems as $item): 
                    $link = $item['alternate'][0]['href'] ?? $item['originId'] ?? '#';
                    $title = $item['title'] ?? 'Bez naslova';
                    $published = $item['published'] ?? 0;
                    $engagement = $item['engagement'] ?? 0;
                ?>
                <a href="<?= e($link) ?>" target="_blank" class="list-item">
                    <div class="list-item-content">
                        <div class="list-item-title"><?= e($title) ?></div>
                        <div class="list-item-meta">
                            <span><?= $published ? timeAgo(date('Y-m-d H:i:s', $published/1000)) : '' ?></span>
                            <?php if ($engagement > 0): ?>
                            <span class="engagement-badge">🔥 <?= $engagement >= 1000 ? round($engagement/1000, 1).'K' : $engagement ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--gray-700);
    margin: 1.5rem 0 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--gray-200);
}
.engagement-badge {
    background: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 600;
}
.row-2-col {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    margin-top: 1rem;
}
@media (min-width: 768px) {
    .row-2-col {
        grid-template-columns: 1fr 1fr;
    }
}
.list-items {
    display: flex;
    flex-direction: column;
}
.list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1rem;
    border-bottom: 1px solid var(--gray-200);
    color: var(--dark);
    text-decoration: none;
    transition: background 0.15s;
}
.list-item:hover {
    background: var(--gray-50);
    text-decoration: none;
}
.list-item:last-child {
    border-bottom: none;
}
.list-item-content {
    flex: 1;
    min-width: 0;
}
.list-item-title {
    font-weight: 500;
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}
.list-item-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: var(--gray-500);
    align-items: center;
}
.mt-1 { margin-top: 0.25rem; }
</style>

<?php include 'includes/footer.php'; ?>
