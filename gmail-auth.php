<?php
/**
 * Gmail OAuth Start Page
 * 
 * Pokreće OAuth flow - redirecta korisnika na Google login.
 */

require_once __DIR__ . '/GmailClient.php';

session_start();

$gmail = new GmailClient();

// Ako je već autoriziran, redirect na dashboard
if ($gmail->isAuthorized()) {
    header('Location: gmail-dashboard.php');
    exit;
}

// Generiraj state za CSRF zaštitu
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Redirect na Google OAuth
$authUrl = $gmail->getAuthUrl($state);
header('Location: ' . $authUrl);
exit;
