<?php
/**
 * Praćenje najčitanijeg sadržaja na portalima
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = getDB();

// Provjeri postoje li tablice
try {
    $db->query("SELECT 1 FROM portali LIMIT 1");
} catch (PDOException $e) {
    header('Location: portali-setup.php');
    exit;
}

// Dodaj stupac objavljeno_at ako ne postoji
try {
    $db->query("SELECT objavljeno_at FROM portal_najcitanije LIMIT 1");
} catch (PDOException $e) {
    $db->exec("ALTER TABLE portal_najcitanije ADD COLUMN objavljeno_at DATETIME NULL AFTER url");
}

// Dodaj stupac sadrzaj ako ne postoji
try {
    $db->query("SELECT sadrzaj FROM portal_najcitanije LIMIT 1");
} catch (PDOException $e) {
    $db->exec("ALTER TABLE portal_najcitanije ADD COLUMN sadrzaj TEXT NULL AFTER objavljeno_at");
}

$message = null;
$error = null;

// Funkcija za dohvat HTML-a
function fetchUrl($url) {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 15,
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    return @file_get_contents($url, false, $ctx);
}

// Funkcija za parsiranje RSS feeda kao fallback
function parseRSS($rssUrl, $portalDomain, $limit = 10) {
    $xml = @fetchUrl($rssUrl);
    if (!$xml) return [];

    $results = [];
    libxml_use_internal_errors(true);
    $feed = @simplexml_load_string($xml);
    libxml_clear_errors();

    if (!$feed) return [];

    $items = [];
    // Standard RSS
    if (isset($feed->channel->item)) {
        $items = $feed->channel->item;
    }
    // Atom
    elseif (isset($feed->entry)) {
        $items = $feed->entry;
    }

    $position = 1;
    foreach ($items as $item) {
        $title = trim((string)($item->title ?? ''));
        $link = (string)($item->link ?? '');

        // Atom format
        if (empty($link) && isset($item->link['href'])) {
            $link = (string)$item->link['href'];
        }

        // Dohvati datum objave
        $pubDate = null;
        if (isset($item->pubDate)) {
            $pubDate = date('Y-m-d H:i:s', strtotime((string)$item->pubDate));
        } elseif (isset($item->published)) {
            $pubDate = date('Y-m-d H:i:s', strtotime((string)$item->published));
        } elseif (isset($item->updated)) {
            $pubDate = date('Y-m-d H:i:s', strtotime((string)$item->updated));
        }

        // Dohvati sadržaj članka
        $content = '';

        // Dohvati namespaces
        $namespaces = $feed->getNamespaces(true);

        // 1. Provjeri content:encoded (puni tekst) - koristi namespace
        if (isset($namespaces['content'])) {
            $contentNs = $item->children($namespaces['content']);
            if (isset($contentNs->encoded)) {
                $content = (string)$contentNs->encoded;
            }
        }

        // 2. Fallback na description
        if (empty($content) && isset($item->description)) {
            $content = (string)$item->description;
        }

        // 3. Atom content
        if (empty($content) && isset($item->content)) {
            $content = (string)$item->content;
        }

        // 4. Atom summary
        if (empty($content) && isset($item->summary)) {
            $content = (string)$item->summary;
        }

        // Očisti HTML tagove i whitespace
        $content = strip_tags($content);
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);

        if (empty($title) || strlen($title) < 10) continue;
        if (strpos($link, $portalDomain) === false && strpos($link, '/') !== 0) continue;

        $results[] = [
            'pozicija' => $position,
            'naslov' => $title,
            'url' => $link,
            'objavljeno_at' => $pubDate,
            'sadrzaj' => $content
        ];
        $position++;
        if ($position > $limit) break;
    }

    return $results;
}

// Scraperi za svaki portal - koriste RSS za datume objave
function scrapeIndex() {
    // RSS ima datume objave, pa ga koristimo kao primarni izvor
    return parseRSS('https://www.index.hr/rss', 'index.hr');
}

function scrape24sata() {
    return parseRSS('https://www.24sata.hr/feeds/aktualno.xml', '24sata.hr');
}

function scrapeJutarnji() {
    return parseRSS('https://www.jutarnji.hr/feed', 'jutarnji.hr');
}

function scrapeVecernji() {
    return parseRSS('https://www.vecernji.hr/feeds/latest', 'vecernji.hr');
}

// Osvježi podatke
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $portalId = (int)$_POST['portal_id'];

    // Dohvati portal
    $stmt = $db->prepare("SELECT * FROM portali WHERE id = ?");
    $stmt->execute([$portalId]);
    $portal = $stmt->fetch();

    if ($portal) {
        $results = [];

        // Pozovi odgovarajući scraper
        if (strpos($portal['url'], 'index.hr') !== false) {
            $results = scrapeIndex();
        } elseif (strpos($portal['url'], '24sata.hr') !== false) {
            $results = scrape24sata();
        } elseif (strpos($portal['url'], 'jutarnji.hr') !== false) {
            $results = scrapeJutarnji();
        } elseif (strpos($portal['url'], 'vecernji.hr') !== false) {
            $results = scrapeVecernji();
        }

        if (!empty($results)) {
            // Spremi rezultate
            $now = date('Y-m-d H:i:s');
            $stmt = $db->prepare("INSERT INTO portal_najcitanije (portal_id, pozicija, naslov, url, objavljeno_at, sadrzaj, dohvaceno_at) VALUES (?, ?, ?, ?, ?, ?, ?)");

            foreach ($results as $r) {
                $stmt->execute([$portalId, $r['pozicija'], $r['naslov'], $r['url'], $r['objavljeno_at'] ?? null, $r['sadrzaj'] ?? null, $now]);
            }

            $message = "Dohvaćeno " . count($results) . " članaka s portala " . $portal['naziv'];
        } else {
            $error = "Nije pronađeno članaka s portala " . $portal['naziv'] . ". Pokušano je HTML scraping i RSS feed.";
        }
    }
}

// Osvježi sve
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh_all']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $portali = $db->query("SELECT * FROM portali WHERE aktivan = 1")->fetchAll();
    $totalCount = 0;

    foreach ($portali as $portal) {
        $results = [];

        if (strpos($portal['url'], 'index.hr') !== false) {
            $results = scrapeIndex();
        } elseif (strpos($portal['url'], '24sata.hr') !== false) {
            $results = scrape24sata();
        } elseif (strpos($portal['url'], 'jutarnji.hr') !== false) {
            $results = scrapeJutarnji();
        } elseif (strpos($portal['url'], 'vecernji.hr') !== false) {
            $results = scrapeVecernji();
        }

        if (!empty($results)) {
            $now = date('Y-m-d H:i:s');
            $stmt = $db->prepare("INSERT INTO portal_najcitanije (portal_id, pozicija, naslov, url, objavljeno_at, sadrzaj, dohvaceno_at) VALUES (?, ?, ?, ?, ?, ?, ?)");

            foreach ($results as $r) {
                $stmt->execute([$portal['id'], $r['pozicija'], $r['naslov'], $r['url'], $r['objavljeno_at'] ?? null, $r['sadrzaj'] ?? null, $now]);
            }
            $totalCount += count($results);
        }

        usleep(500000); // Pauza 0.5 sec između portala
    }

    $message = "Dohvaćeno ukupno $totalCount članaka sa svih portala";
}

// Dohvati portale
$portali = $db->query("SELECT * FROM portali WHERE aktivan = 1 ORDER BY naziv")->fetchAll();

// Za svaki portal dohvati zadnje najčitanije
$najcitanije = [];
foreach ($portali as $portal) {
    // Pronađi zadnji timestamp za ovaj portal
    $stmt = $db->prepare("SELECT MAX(dohvaceno_at) FROM portal_najcitanije WHERE portal_id = ?");
    $stmt->execute([$portal['id']]);
    $lastFetch = $stmt->fetchColumn();

    if ($lastFetch) {
        $stmt = $db->prepare("
            SELECT * FROM portal_najcitanije
            WHERE portal_id = ? AND dohvaceno_at = ?
            ORDER BY pozicija
        ");
        $stmt->execute([$portal['id'], $lastFetch]);
        $najcitanije[$portal['id']] = [
            'vrijeme' => $lastFetch,
            'clanci' => $stmt->fetchAll()
        ];
    }
}

define('PAGE_TITLE', 'Praćenje portala');
include 'includes/header.php';
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1>Najčitanije na portalima</h1>
        <p style="color: #6b7280; margin: 0.25rem 0 0 0;">Praćenje najčitanijeg sadržaja na konkurentskim portalima</p>
    </div>
    <form method="POST" style="display: inline;">
        <?= csrfField() ?>
        <button type="submit" name="refresh_all" value="1" class="btn btn-primary">
            Osvježi sve portale
        </button>
    </form>
</div>

<?php if ($message): ?>
<div style="background: #d1fae5; border: 1px solid #a7f3d0; color: #059669; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <?= e($message) ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div style="background: #fee2e2; border: 1px solid #fecaca; color: #dc2626; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <?= e($error) ?>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1rem;">
    <?php foreach ($portali as $portal): ?>
    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
        <div style="background: #1e3a5f; color: white; padding: 0.75rem 1rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong><?= e($portal['naziv']) ?></strong>
                <?php if (isset($najcitanije[$portal['id']])): ?>
                <div style="font-size: 0.75rem; opacity: 0.8;">
                    Osvježeno: <?= date('d.m.Y H:i', strtotime($najcitanije[$portal['id']]['vrijeme'])) ?>
                </div>
                <?php endif; ?>
            </div>
            <form method="POST" style="display: inline;">
                <?= csrfField() ?>
                <input type="hidden" name="portal_id" value="<?= $portal['id'] ?>">
                <button type="submit" name="refresh" value="1" style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem;">
                    Osvježi
                </button>
            </form>
        </div>

        <div style="padding: 0;">
            <?php if (isset($najcitanije[$portal['id']]) && !empty($najcitanije[$portal['id']]['clanci'])): ?>
                <?php foreach ($najcitanije[$portal['id']]['clanci'] as $clanak): ?>
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6;">
                    <div style="display: flex; gap: 0.75rem;">
                        <span style="background: #f3f4f6; color: #6b7280; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 600; flex-shrink: 0;">
                            <?= $clanak['pozicija'] ?>
                        </span>
                        <div style="flex: 1; min-width: 0;">
                            <a href="<?= e($clanak['url']) ?>" target="_blank" style="font-size: 0.875rem; color: #374151; line-height: 1.4; text-decoration: none;">
                                <?= e($clanak['naslov']) ?>
                            </a>
                            <div style="display: flex; gap: 0.75rem; align-items: center; margin-top: 0.25rem;">
                                <?php if (!empty($clanak['objavljeno_at'])): ?>
                                <span style="font-size: 0.7rem; color: #9ca3af;">
                                    <?= date('d.m.Y H:i', strtotime($clanak['objavljeno_at'])) ?>
                                </span>
                                <?php endif; ?>
                                <button type="button" onclick="openRewrite(<?= $clanak['id'] ?>, '<?= e($clanak['url']) ?>')" style="font-size: 0.7rem; color: #059669; background: none; border: none; cursor: pointer; padding: 0;">
                                    Preradi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding: 2rem; text-align: center; color: #9ca3af;">
                    Nema podataka. Klikni "Osvježi" za dohvat.
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal za preradu -->
<div id="rewriteModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; padding: 1rem; overflow-y: auto;">
    <div style="background: white; max-width: 900px; margin: 2rem auto; border-radius: 12px; overflow: hidden;">
        <div style="background: #1e3a5f; color: white; padding: 1rem; display: flex; justify-content: space-between; align-items: center;">
            <strong>Preradi tekst</strong>
            <button onclick="closeModal()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div style="padding: 1rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label style="font-weight: 500; font-size: 0.875rem; color: #374151;">Originalni tekst</label>
                    <textarea id="modalOriginal" readonly style="width: 100%; height: 300px; margin-top: 0.5rem; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 0.875rem; resize: vertical;"></textarea>
                    <div id="modalOriginalCount" style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem;"></div>
                </div>
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <label style="font-weight: 500; font-size: 0.875rem; color: #374151;">Prerađeni tekst</label>
                        <button id="rewriteBtn" onclick="rewriteText()" class="btn btn-primary" style="padding: 0.25rem 0.75rem; font-size: 0.75rem;">
                            Preradi s AI
                        </button>
                    </div>
                    <textarea id="modalRewritten" style="width: 100%; height: 300px; margin-top: 0.5rem; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 0.875rem; resize: vertical;"></textarea>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.25rem;">
                        <span id="modalRewrittenCount" style="font-size: 0.75rem; color: #9ca3af;"></span>
                        <button onclick="copyRewritten()" class="btn btn-outline" style="padding: 0.25rem 0.75rem; font-size: 0.75rem;">
                            Kopiraj
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function openRewrite(id, url) {
    document.getElementById('modalOriginal').value = 'Dohvaćam članak...';
    document.getElementById('modalOriginalCount').textContent = '';
    document.getElementById('modalRewritten').value = '';
    document.getElementById('modalRewrittenCount').textContent = '';
    document.getElementById('rewriteModal').style.display = 'block';
    document.body.style.overflow = 'hidden';

    try {
        const response = await fetch('api/fetch-article.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({url: url})
        });

        const data = await response.json();

        if (data.success) {
            const fullText = data.title + "\n\n" + data.content;
            document.getElementById('modalOriginal').value = fullText;
            document.getElementById('modalOriginalCount').textContent = data.length.toLocaleString() + ' znakova';
        } else {
            document.getElementById('modalOriginal').value = 'Greška: ' + data.error;
        }
    } catch (e) {
        document.getElementById('modalOriginal').value = 'Greška pri dohvaćanju članka';
    }
}

function closeModal() {
    document.getElementById('rewriteModal').style.display = 'none';
    document.body.style.overflow = '';
}

async function rewriteText() {
    const btn = document.getElementById('rewriteBtn');
    const original = document.getElementById('modalOriginal').value;

    btn.disabled = true;
    btn.textContent = 'Prerađujem...';

    try {
        const response = await fetch('api/rewrite.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({text: original})
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById('modalRewritten').value = data.text;
            document.getElementById('modalRewrittenCount').textContent = data.text.length.toLocaleString() + ' znakova';
        } else {
            alert('Greška: ' + data.error);
        }
    } catch (e) {
        alert('Greška pri komunikaciji sa serverom');
    }

    btn.disabled = false;
    btn.textContent = 'Preradi s AI';
}

function copyRewritten() {
    const textarea = document.getElementById('modalRewritten');
    textarea.select();
    document.execCommand('copy');

    const btn = event.target;
    btn.textContent = 'Kopirano!';
    setTimeout(() => btn.textContent = 'Kopiraj', 1500);
}

// Zatvori modal na Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});
</script>

<?php include 'includes/footer.php'; ?>
