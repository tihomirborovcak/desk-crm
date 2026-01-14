<?php
/**
 * Autentikacija i upravljanje sesijama
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Provjera je li korisnik ulogiran
 */
function isLoggedIn() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        return false;
    }
    
    // Provjera timeout-a
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        logout();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Zahtijeva login - preusmjerava ako nije ulogiran
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php?msg=login_required');
        exit;
    }
}

/**
 * Zahtijeva određenu ulogu
 */
function requireRole($roles) {
    requireLogin();
    
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    if (!in_array($_SESSION['user_role'], $roles)) {
        header('Location: dashboard.php?msg=no_permission');
        exit;
    }
}

/**
 * Login korisnika
 */
function login($username, $password) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['last_activity'] = time();
        
        // Ažuriraj last_login
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Log aktivnosti
        logActivity('login', 'user', $user['id']);
        
        return true;
    }
    
    return false;
}

/**
 * Logout korisnika
 */
function logout() {
    if (isset($_SESSION['user_id'])) {
        logActivity('logout', 'user', $_SESSION['user_id']);
    }
    
    session_unset();
    session_destroy();
}

/**
 * Dohvati trenutnog korisnika
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Provjera ima li korisnik ulogu
 */
function hasRole($role) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    $roles = is_array($role) ? $role : [$role];
    return in_array($_SESSION['user_role'], $roles);
}

/**
 * Je li admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Je li urednik ili admin
 */
function isEditor() {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['urednik', 'admin']);
}

/**
 * Logiranje aktivnosti
 */
function logActivity($action, $entityType = null, $entityId = null, $details = null) {
    $db = getDB();
    
    $userId = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    
    $stmt = $db->prepare("INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $entityType, $entityId, $details, $ip]);
}

/**
 * Generiranje CSRF tokena
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Provjera CSRF tokena
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * CSRF hidden input
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}
