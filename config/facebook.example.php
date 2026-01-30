<?php
/**
 * Facebook API konfiguracija - više stranica
 *
 * UPUTE: Kopirajte ovu datoteku u facebook.php i unesite prave vrijednosti
 */

define('FB_APP_ID', 'YOUR_APP_ID');
define('FB_APP_SECRET', 'YOUR_APP_SECRET');

// Konfiguracija stranica (tokeni s messaging permisijom)
$FB_PAGES = [
    'zagorje' => [
        'id' => 'YOUR_PAGE_ID',
        'name' => 'Zagorje.com',
        'token' => 'YOUR_PAGE_ACCESS_TOKEN'
    ]
];

// Za kompatibilnost sa starim kodom
define('FB_PAGE_ACCESS_TOKEN', $FB_PAGES['zagorje']['token']);
define('FB_PAGE_ID', $FB_PAGES['zagorje']['id']);

// Instagram Business Account (povezan sa Zagorje.com stranicom)
define('INSTAGRAM_ACCOUNT_ID', 'YOUR_INSTAGRAM_ACCOUNT_ID');

/**
 * Dohvati sve FB stranice
 */
function getFBPages() {
    global $FB_PAGES;
    return $FB_PAGES;
}

/**
 * Objavi link na Facebook stranicu/e
 * @param string $url URL članka
 * @param string $message Tekst objave
 * @param array $pages Stranice na koje objaviti
 * @param int|null $scheduledTime Unix timestamp za zakazivanje (null = odmah)
 */
function postToFacebook($url, $message = '', $pages = ['zagorje'], $scheduledTime = null) {
    global $FB_PAGES;
    $results = [];

    // Dodaj UTM parametre
    $separator = strpos($url, '?') !== false ? '&' : '?';
    $urlWithUtm = $url . $separator . 'utm_source=facebook&utm_medium=social&utm_campaign=post';

    foreach ($pages as $pageKey) {
        if (!isset($FB_PAGES[$pageKey])) continue;

        $page = $FB_PAGES[$pageKey];
        $postUrl = "https://graph.facebook.com/v24.0/{$page['id']}/feed";

        $postData = [
            'link' => $urlWithUtm,
            'access_token' => $page['token']
        ];

        if (!empty($message)) {
            $postData['message'] = $message;
        }

        // Ako je zakazano, dodaj scheduled_publish_time
        if ($scheduledTime !== null) {
            // FB zahtijeva da vrijeme bude između 10 min i 6 mjeseci od sad
            $minTime = time() + 600; // 10 minuta
            $maxTime = time() + (6 * 30 * 24 * 60 * 60); // ~6 mjeseci

            if ($scheduledTime < $minTime) {
                $scheduledTime = $minTime;
            } elseif ($scheduledTime > $maxTime) {
                $scheduledTime = $maxTime;
            }

            $postData['scheduled_publish_time'] = $scheduledTime;
            $postData['published'] = 'false';
        }

        $ch = curl_init($postUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['id'])) {
            $results[$pageKey] = ['success' => true, 'post_id' => $result['id'], 'page_name' => $page['name']];
        } else {
            $results[$pageKey] = ['success' => false, 'error' => $result['error']['message'] ?? 'Nepoznata greška', 'page_name' => $page['name']];
        }
    }

    // Za kompatibilnost - ako samo jedna stranica, vrati kao prije
    if (count($pages) === 1) {
        return $results[$pages[0]] ?? ['success' => false, 'error' => 'Stranica nije pronađena'];
    }

    return $results;
}

/**
 * Dohvati objave s Facebook stranice
 */
function getFacebookPosts($limit = 20, $debug = false) {
    $pageId = FB_PAGE_ID;
    $token = FB_PAGE_ACCESS_TOKEN;

    // S pages_read_engagement možemo dohvatiti i reakcije/komentare/dijeljenja + attachments za naslov
    $url = "https://graph.facebook.com/v24.0/{$pageId}/feed?fields=id,message,created_time,permalink_url,full_picture,attachments{title,url,description},likes.summary(true),comments.summary(true),shares&limit={$limit}&access_token={$token}";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($debug) {
        return ['posts' => $data['data'] ?? [], 'raw' => $data, 'error' => $data['error'] ?? null];
    }

    return $data['data'] ?? [];
}

/**
 * Dohvati Facebook Page ID i info
 */
function getFacebookPageId() {
    // Prvo probaj dohvatiti stranice koje korisnik administrira
    $url = "https://graph.facebook.com/v24.0/me/accounts?access_token=" . FB_PAGE_ACCESS_TOKEN;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    // Ako ima stranica, uzmi prvu
    if (isset($data['data']) && !empty($data['data'])) {
        return [
            'id' => $data['data'][0]['id'],
            'token' => $data['data'][0]['access_token'],
            'name' => $data['data'][0]['name']
        ];
    }

    // Ako je već Page token, probaj /me
    $url = "https://graph.facebook.com/v24.0/me?access_token=" . FB_PAGE_ACCESS_TOKEN;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (isset($data['id'])) {
        return [
            'id' => $data['id'],
            'token' => FB_PAGE_ACCESS_TOKEN,
            'name' => $data['name'] ?? 'Unknown'
        ];
    }

    return null;
}

/**
 * Dohvati zakazane objave s Facebook stranice
 */
function getFacebookScheduledPosts() {
    $pageId = FB_PAGE_ID;
    $token = FB_PAGE_ACCESS_TOKEN;

    $url = "https://graph.facebook.com/v24.0/{$pageId}/scheduled_posts?fields=id,message,scheduled_publish_time,attachments{title,url},full_picture&access_token={$token}";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    return [
        'posts' => $data['data'] ?? [],
        'error' => $data['error'] ?? null
    ];
}

/**
 * Debug - prikaži info o tokenu
 */
function debugFacebookToken() {
    $url = "https://graph.facebook.com/v24.0/me?access_token=" . FB_PAGE_ACCESS_TOKEN;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
