<?php
/**
 * Gmail API OAuth 2.0 konfiguracija
 *
 * Upute za postavljanje:
 * 1. Idi na Google Cloud Console: https://console.cloud.google.com
 * 2. Kreiraj OAuth 2.0 credentials (Web application tip)
 * 3. Dodaj OBA redirect URI-ja u Google Console:
 *    - http://localhost/desk-crm/gmail-callback.php
 *    - https://zagorje-promocija.com/desk-crm/gmail-callback.php
 * 4. Omoguci Gmail API u API Library
 * 5. Kopiraj Client ID i Client Secret ovdje
 */

// OAuth 2.0 Web Application Credentials
define('GMAIL_CLIENT_ID', 'YOUR_CLIENT_ID.apps.googleusercontent.com');
define('GMAIL_CLIENT_SECRET', 'YOUR_CLIENT_SECRET');

// Automatski odaberi redirect URI prema okruzenju
if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0) {
    define('GMAIL_REDIRECT_URI', 'http://localhost/desk-crm/gmail-callback.php');
} else {
    define('GMAIL_REDIRECT_URI', 'https://zagorje-promocija.com/desk-crm/gmail-callback.php');
}

// Gmail API Scopes (read-only pristup)
define('GMAIL_SCOPES', 'https://www.googleapis.com/auth/gmail.readonly');

// Maksimalan broj poruka za dohvat po sync-u
define('GMAIL_FETCH_LIMIT', 50);

/**
 * Generiraj Gmail OAuth URL za autorizaciju
 */
function getGmailAuthUrl() {
    $params = [
        'client_id' => GMAIL_CLIENT_ID,
        'redirect_uri' => GMAIL_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => GMAIL_SCOPES,
        'access_type' => 'offline',
        'prompt' => 'consent'
    ];

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

/**
 * Razmijeni authorization code za tokene
 */
function exchangeGmailCode($code) {
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'client_id' => GMAIL_CLIENT_ID,
            'client_secret' => GMAIL_CLIENT_SECRET,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => GMAIL_REDIRECT_URI
        ])
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

/**
 * Osvjezi access token koristeci refresh token
 */
function refreshGmailToken($refreshToken) {
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'client_id' => GMAIL_CLIENT_ID,
            'client_secret' => GMAIL_CLIENT_SECRET,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token'
        ])
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

/**
 * Dohvati vazeci access token (osvjezi ako je istekao)
 */
function getValidGmailToken($db, $accountId) {
    $stmt = $db->prepare("SELECT * FROM gmail_oauth_tokens WHERE id = ?");
    $stmt->execute([$accountId]);
    $account = $stmt->fetch();

    if (!$account) {
        return ['error' => 'Gmail racun nije pronadjen'];
    }

    // Provjeri je li token istekao (s 5 min bufferom)
    if (strtotime($account['token_expires_at']) < time() + 300) {
        // Osvjezi token
        $newTokens = refreshGmailToken($account['refresh_token']);

        if (isset($newTokens['error'])) {
            return ['error' => $newTokens['error_description'] ?? 'Osvjezavanje tokena neuspjesno'];
        }

        // Azuriraj bazu
        $expiresAt = date('Y-m-d H:i:s', time() + $newTokens['expires_in']);
        $stmt = $db->prepare("
            UPDATE gmail_oauth_tokens
            SET access_token = ?, token_expires_at = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$newTokens['access_token'], $expiresAt, $accountId]);

        return ['token' => $newTokens['access_token'], 'email' => $account['email']];
    }

    return ['token' => $account['access_token'], 'email' => $account['email']];
}

/**
 * Dohvati Gmail profil korisnika (za email adresu)
 */
function getGmailProfile($accessToken) {
    $ch = curl_init('https://www.googleapis.com/gmail/v1/users/me/profile');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken
        ]
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
