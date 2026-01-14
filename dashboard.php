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
// RSS - zadnje vijesti
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

function parseRSS($xml, $limit = 8) {
    $items = [];
    if (!$xml) return $items;

    $feed = @simplexml_load_string($xml);
    if (!$feed) return $items;

    $count = 0;
    foreach ($feed->channel->item as $item) {
        if ($count >= $limit) break;

        // Odvoji nadnaslov od naslova (delimiter: – - | :)
        $fullTitle = (string)$item->title;
        $nadnaslov = '';
        $naslov = $fullTitle;

        // Probaj različite delimitere
        $delimiters = [' – ', ' - ', ' | ', ': '];
        foreach ($delimiters as $del) {
            if (strpos($fullTitle, $del) !== false) {
                $parts = explode($del, $fullTitle, 2);
                $nadnaslov = trim($parts[0]);
                $naslov = trim($parts[1]);
                break;
            }
        }

        $items[] = [
            'title' => $naslov,
            'nadnaslov' => $nadnaslov,
            'link' => (string)$item->link,
            'pubDate' => (string)$item->pubDate,
            'description' => strip_tags((string)$item->description)
        ];
        $count++;
    }
    return $items;
}

// Dohvati zadnje vijesti iz RSS-a (8 od svakog = 16 ukupno)
$zagorjeRSS = fetchRSS('https://www.zagorje-international.hr/index.php/feed/');
$zagorjeLatest = parseRSS($zagorjeRSS, 8);

$stubicaRSS = fetchRSS('https://radio-stubica.hr/feed/');
$stubicaLatest = parseRSS($stubicaRSS, 8);

// ============================================
// FEEDLY API - Općine i gradovi
// ============================================
define('FEEDLY_TOKEN', 'REDACTED_FEEDLY_TOKEN');

function feedlyFetch($streamId, $count = 10) {
    $url = 'https://cloud.feedly.com/v3/streams/contents?' . http_build_query([
        'streamId' => $streamId,
        'count' => $count
    ]);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . FEEDLY_TOKEN
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    return $data['items'] ?? [];
}

// Općine i gradovi
$opcineFeedlyItems = feedlyFetch('user/6e135c2a-75fe-4109-8be8-6b52fa6866e6/category/svetikri%C5%BE', 10);

// Taskovi za sve (assigned_to IS NULL)
$stmt = $db->query("
    SELECT t.*, u.full_name as creator_name
    FROM tasks t
    LEFT JOIN users u ON t.created_by = u.id
    WHERE t.assigned_to IS NULL AND t.status IN ('pending', 'in_progress')
    ORDER BY
        CASE t.priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END,
        t.due_date ASC
    LIMIT 5
");
$tasksForAll = $stmt->fetchAll();

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
");
$upcomingEvents = $stmt->fetchAll();

// Grupiraj evente po datumu
$eventsByDay = [];
foreach ($upcomingEvents as $evt) {
    $date = $evt['event_date'];
    if (!isset($eventsByDay[$date])) {
        $eventsByDay[$date] = ['shifts' => [], 'events' => []];
    }
    if ($evt['event_type'] === 'dezurstvo') {
        $eventsByDay[$date]['shifts'][] = $evt;
    } else {
        $eventsByDay[$date]['events'][] = $evt;
    }
}

// Hrvatski nazivi dana i mjeseci
$daysHr = [
    'Monday' => 'Ponedjeljak', 'Tuesday' => 'Utorak', 'Wednesday' => 'Srijeda',
    'Thursday' => 'Četvrtak', 'Friday' => 'Petak', 'Saturday' => 'Subota', 'Sunday' => 'Nedjelja'
];
$monthsHr = [
    1 => 'siječnja', 2 => 'veljače', 3 => 'ožujka', 4 => 'travnja',
    5 => 'svibnja', 6 => 'lipnja', 7 => 'srpnja', 8 => 'kolovoza',
    9 => 'rujna', 10 => 'listopada', 11 => 'studenog', 12 => 'prosinca'
];
$todayHr = $daysHr[date('l')] . ', ' . date('j') . '. ' . $monthsHr[intval(date('n'))] . ' ' . date('Y') . '.';

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

// Danas na dežurstvu - iz OBJE tablice (events i shifts)
$todayShifts = [];

// 1. Iz events tablice (event_type='dezurstvo')
$stmt = $db->query("
    SELECT e.*, GROUP_CONCAT(u.full_name SEPARATOR ', ') as assigned_people
    FROM events e
    LEFT JOIN event_assignments ea ON e.id = ea.event_id
    LEFT JOIN users u ON ea.user_id = u.id
    WHERE e.event_date = CURDATE() AND e.event_type = 'dezurstvo'
    GROUP BY e.id
    ORDER BY e.event_time
");
$todayShifts = $stmt->fetchAll();

// 2. Iz shifts tablice (ako postoji)
try {
    $stmtOldShifts = $db->query("
        SELECT s.*, u.full_name
        FROM shifts s
        JOIN users u ON s.user_id = u.id
        WHERE s.shift_date = CURDATE()
        ORDER BY s.shift_type
    ");
    $oldShifts = $stmtOldShifts->fetchAll();
    foreach ($oldShifts as $os) {
        $todayShifts[] = [
            'title' => ($os['shift_type'] === 'morning' ? '☀️ Jutarnja' : ($os['shift_type'] === 'afternoon' ? '🌤️ Popodnevna' : '🌙 Večernja')) . ' - ' . $os['full_name'],
            'assigned_people' => $os['full_name'],
            'shift_type_old' => $os['shift_type']
        ];
    }
} catch (Exception $e) {
    // Shifts tablica možda ne postoji - ignoriraj
}

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
<p class="text-muted mb-1"><?= $todayHr ?>
<?php
// Kompaktni prikaz današnjih dežurstava
$shiftsCompact = ['morning' => '', 'afternoon' => '', 'full' => ''];
foreach ($todayShifts as $s) {
    $title = $s['title'] ?? '';
    $firstName = '';
    if (!empty($s['assigned_people'])) {
        $firstName = explode(' ', $s['assigned_people'])[0];
    } elseif (strpos($title, ' - ') !== false) {
        $parts = explode(' - ', $title);
        $firstName = explode(' ', end($parts))[0];
    }

    // Odredi tip smjene - prvo iz shift_type_old (stara tablica), pa iz naslova
    $shiftType = '';
    if (!empty($s['shift_type_old'])) {
        $shiftType = $s['shift_type_old'];
    } elseif (stripos($title, 'jutarn') !== false) {
        $shiftType = 'morning';
    } elseif (stripos($title, 'popodnevn') !== false) {
        $shiftType = 'afternoon';
    } elseif (stripos($title, 'večern') !== false || stripos($title, 'vecern') !== false || stripos($title, 'cijeli') !== false) {
        $shiftType = 'full';
    }

    if ($shiftType && $firstName && !$shiftsCompact[$shiftType]) {
        $shiftsCompact[$shiftType] = $firstName;
    }
}
if ($shiftsCompact['morning'] || $shiftsCompact['afternoon'] || $shiftsCompact['full']):
?>
<span class="today-shifts">
    <?php if ($shiftsCompact['morning']): ?>J-<?= e($shiftsCompact['morning']) ?> <?php endif; ?>
    <?php if ($shiftsCompact['afternoon']): ?>P-<?= e($shiftsCompact['afternoon']) ?> <?php endif; ?>
    <?php if ($shiftsCompact['full']): ?>C-<?= e($shiftsCompact['full']) ?><?php endif; ?>
</span>
<?php endif; ?>
</p>


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


<!-- Taskovi za sve -->
<?php if (!empty($tasksForAll)): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">📢 Taskovi za sve</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="list-items">
            <?php foreach ($tasksForAll as $task): ?>
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
<?php if (!empty($eventsByDay)): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">🗓️ Nadolazeći eventi (7 dana)</h2>
        <a href="events.php" class="btn btn-sm btn-outline">Svi eventi</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php foreach ($eventsByDay as $date => $dayData):
            $dayName = $daysHr[date('l', strtotime($date))] ?? date('l', strtotime($date));
            $dateFormatted = date('j.n.', strtotime($date));

            // Kompaktna dežurstva za taj dan
            $shiftsCompact = '';
            foreach ($dayData['shifts'] as $s) {
                $title = $s['title'] ?? '';
                $firstName = '';
                if (!empty($s['assigned_people'])) {
                    $firstName = explode(' ', $s['assigned_people'])[0];
                } elseif (strpos($title, ' - ') !== false) {
                    $parts = explode(' - ', $title);
                    $firstName = explode(' ', end($parts))[0];
                }
                if (stripos($title, 'jutarn') !== false && $firstName) {
                    $shiftsCompact .= "J-$firstName ";
                } elseif (stripos($title, 'popodnevn') !== false && $firstName) {
                    $shiftsCompact .= "P-$firstName ";
                } elseif ((stripos($title, 'večern') !== false || stripos($title, 'vecern') !== false) && $firstName) {
                    $shiftsCompact .= "V-$firstName ";
                }
            }
        ?>
        <div class="upcoming-day-header">
            <span class="upcoming-day-name"><?= $dayName ?>, <?= $dateFormatted ?></span>
            <?php if ($shiftsCompact): ?>
            <span class="upcoming-shifts"><?= trim($shiftsCompact) ?></span>
            <?php endif; ?>
        </div>
        <?php if (!empty($dayData['events'])): ?>
        <div class="list-items">
            <?php foreach ($dayData['events'] as $event): ?>
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
                        <?php if ($event['event_time']): ?>
                        <span>🕐 <?= date('H:i', strtotime($event['event_time'])) ?></span>
                        <?php endif; ?>
                        <?php if ($event['location']): ?>
                        <span>📍 <?= e(truncate($event['location'], 20)) ?></span>
                        <?php endif; ?>
                        <?php if ($event['assigned_people']): ?>
                        <span>👥 <?= e($event['assigned_people']) ?></span>
                        <?php else: ?>
                        <span class="text-danger">⚠️ Nitko</span>
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
        <?php endforeach; ?>
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
            <div class="rss-list">
                <?php foreach ($zagorjeLatest as $item): ?>
                <a href="<?= e($item['link']) ?>" target="_blank" class="rss-item">
                    <?php if ($item['nadnaslov']): ?>
                    <span class="rss-kicker"><?= e($item['nadnaslov']) ?></span>
                    <?php endif; ?>
                    <span class="rss-title"><?= e($item['title']) ?></span>
                    <span class="rss-time"><?= $item['pubDate'] ? timeAgo(date('Y-m-d H:i:s', strtotime($item['pubDate']))) : '' ?></span>
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
            <div class="rss-list">
                <?php foreach ($stubicaLatest as $item): ?>
                <a href="<?= e($item['link']) ?>" target="_blank" class="rss-item">
                    <?php if ($item['nadnaslov']): ?>
                    <span class="rss-kicker"><?= e($item['nadnaslov']) ?></span>
                    <?php endif; ?>
                    <span class="rss-title"><?= e($item['title']) ?></span>
                    <span class="rss-time"><?= $item['pubDate'] ? timeAgo(date('Y-m-d H:i:s', strtotime($item['pubDate']))) : '' ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Općine i gradovi (Feedly) -->
<?php if (!empty($opcineFeedlyItems)): ?>
<div class="card mt-2">
    <div class="card-header" style="background: #7b1fa2; color: white;">
        <h2 class="card-title" style="color: white;">🏛️ Općine i gradovi</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="rss-list">
            <?php foreach ($opcineFeedlyItems as $item):
                $link = $item['alternate'][0]['href'] ?? $item['originId'] ?? '#';
                $title = $item['title'] ?? 'Bez naslova';
                $source = $item['origin']['title'] ?? '';
                $published = $item['published'] ?? 0;
            ?>
            <a href="<?= e($link) ?>" target="_blank" class="rss-item">
                <?php if ($source): ?>
                <span class="rss-kicker"><?= e($source) ?></span>
                <?php endif; ?>
                <span class="rss-title"><?= e($title) ?></span>
                <span class="rss-time"><?= $published ? timeAgo(date('Y-m-d H:i:s', $published/1000)) : '' ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

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
.today-shifts {
    font-size: 0.85rem;
    font-weight: 600;
    color: #0ca678;
    background: rgba(32, 201, 151, 0.15);
    padding: 3px 10px;
    border-radius: 4px;
    margin-left: 0.5rem;
    white-space: nowrap;
}
.upcoming-day-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 1rem;
    background: var(--gray-100);
    border-bottom: 1px solid var(--gray-200);
}
.upcoming-day-name {
    font-weight: 600;
    color: var(--gray-700);
}
.upcoming-shifts {
    font-size: 0.8rem;
    font-weight: 600;
    color: #0ca678;
    background: rgba(32, 201, 151, 0.15);
    padding: 2px 8px;
    border-radius: 4px;
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
/* Mobilna verzija - manje vijesti */
@media (max-width: 767px) {
    .rss-item {
        padding: 0.35rem 0.5rem;
        font-size: 0.75rem;
    }
    .rss-kicker {
        font-size: 0.6rem;
    }
    .rss-title {
        font-size: 0.75rem;
    }
    .list-item {
        padding: 0.5rem 0.75rem;
    }
    .list-item-title {
        font-size: 0.85rem;
    }
    .list-item-meta {
        font-size: 0.7rem;
    }
    .section-title {
        font-size: 0.95rem;
    }
    .upcoming-day-header {
        padding: 0.35rem 0.5rem;
        flex-wrap: wrap;
        gap: 0.25rem;
    }
    .upcoming-day-name {
        font-size: 0.8rem;
    }
    .upcoming-shifts {
        font-size: 0.7rem;
        padding: 1px 5px;
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

/* Kompaktni RSS prikaz */
.rss-list {
    display: flex;
    flex-direction: column;
}
.rss-item {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 0.35rem;
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid var(--gray-100);
    color: var(--dark);
    text-decoration: none;
    font-size: 0.85rem;
    line-height: 1.3;
}
.rss-item:hover {
    background: var(--gray-50);
}
.rss-item:last-child {
    border-bottom: none;
}
.rss-kicker {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    color: var(--primary);
    background: rgba(37, 99, 235, 0.1);
    padding: 1px 5px;
    border-radius: 3px;
}
.rss-title {
    font-weight: 500;
    flex: 1;
}
.rss-time {
    font-size: 0.7rem;
    color: var(--gray-400);
    white-space: nowrap;
}
</style>

<?php include 'includes/footer.php'; ?>
