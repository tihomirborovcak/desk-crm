<?php
/**
 * Facebook API konfiguracija
 */

define('FB_APP_ID', '1400449617617009');
define('FB_APP_SECRET', '865941be7c689b85f4de5f6f8bc61b5b');
define('FB_PAGE_ACCESS_TOKEN', 'EAAT5s5X74HEBQqt88FOf4Y1VptB04op7kpcc6ZBZCZBtHs2qZCm6WuRuPZCiZAG4wkp83bDnHycPVgyrfeGSjs8AIz80tHll2z3WBaftUkb2v8cFnDuQ0KeQ2cXN2g277kJU4ongl8fcZA83XjNhigqKeTrDAvk9AvO631I3C8RxkvAS0vEa6zUXmYEMeZAvZCaVJGViBt6iRLwHJo0k0tywoTYwMIrRF2t0rv4w7hd01byZBSG65ZCAygZD');
define('FB_PAGE_ID', '170346612635');

/**
 * Objavi link na Facebook stranicu
 */
function postToFacebook($url, $message = '') {
    // Koristi hardkodirani Page ID i token
    $pageId = FB_PAGE_ID;
    $pageToken = FB_PAGE_ACCESS_TOKEN;

    // Dodaj UTM parametre
    $separator = strpos($url, '?') !== false ? '&' : '?';
    $urlWithUtm = $url . $separator . 'utm_source=facebook&utm_medium=social&utm_campaign=post';

    // Objavi na Facebook
    $postUrl = "https://graph.facebook.com/v24.0/{$pageId}/feed";

    $postData = [
        'link' => $urlWithUtm,
        'access_token' => $pageToken
    ];

    if (!empty($message)) {
        $postData['message'] = $message;
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
        return ['success' => true, 'post_id' => $result['id'], 'page_name' => $pageInfo['name']];
    }

    return ['success' => false, 'error' => $result['error']['message'] ?? 'Nepoznata greška', 'debug' => $result];
}

/**
 * Dohvati objave s Facebook stranice
 */
function getFacebookPosts($limit = 20, $debug = false) {
    $pageId = FB_PAGE_ID;
    $token = FB_PAGE_ACCESS_TOKEN;

    // S pages_read_engagement možemo dohvatiti i reakcije/komentare/dijeljenja
    $url = "https://graph.facebook.com/v24.0/{$pageId}/feed?fields=id,message,created_time,permalink_url,full_picture,likes.summary(true),comments.summary(true),shares&limit={$limit}&access_token={$token}";

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
