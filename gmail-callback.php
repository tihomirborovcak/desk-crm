<?php
/**
 * Gmail OAuth Callback Handler
 * 
 * Google će redirectati ovdje nakon što korisnik odobri pristup.
 * URL: http://localhost/desk-crm/gmail-callback.php
 */

require_once __DIR__ . '/GmailClient.php';

session_start();

// Error handling
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
    $description = htmlspecialchars($_GET['error_description'] ?? 'Unknown error');
    die("
        <h1>Authorization Error</h1>
        <p><strong>Error:</strong> {$error}</p>
        <p><strong>Description:</strong> {$description}</p>
        <p><a href='gmail-auth.php'>Try again</a></p>
    ");
}

// Provjeri code
if (!isset($_GET['code'])) {
    die("
        <h1>Missing Authorization Code</h1>
        <p>No authorization code received from Google.</p>
        <p><a href='gmail-auth.php'>Start authorization</a></p>
    ");
}

$code = $_GET['code'];

try {
    $gmail = new GmailClient();
    $gmail->handleCallback($code);
    
    // Dohvati info o korisniku
    $profile = $gmail->getProfile();
    $email = $profile['emailAddress'] ?? 'Unknown';
    
    // Spremi u session
    $_SESSION['gmail_connected'] = true;
    $_SESSION['gmail_email'] = $email;
    
    // Redirect na success stranicu ili dashboard
    header('Location: gmail-dashboard.php');
    exit;
    
} catch (Exception $e) {
    die("
        <h1>Authorization Failed</h1>
        <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <p><a href='gmail-auth.php'>Try again</a></p>
    ");
}
