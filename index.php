<?php
/**
 * Login stranica
 */

require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Ako je već ulogiran, preusmjeri na dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Obrada login forme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Unesite korisničko ime i lozinku';
    } elseif (login($username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Pogrešno korisničko ime ili lozinka';
    }
}

// Poruke
$msg = $_GET['msg'] ?? '';
$messages = [
    'login_required' => 'Molimo prijavite se za pristup',
    'logged_out' => 'Uspješno ste se odjavili'
];
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2563eb">
    <title>Prijava - Portal CMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-form {
            margin-top: 1.5rem;
        }
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-200);
            font-size: 0.85rem;
            color: var(--gray-500);
        }
    </style>
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary)">
                <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/>
                <path d="M18 14h-8"/>
                <path d="M15 18h-5"/>
                <path d="M10 6h8v4h-8V6Z"/>
            </svg>
            <h1>Portal CMS</h1>
            <p>Upravljanje sadržajem portala</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?= e($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($msg && isset($messages[$msg])): ?>
            <div class="alert alert-info">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 16v-4"/>
                    <path d="M12 8h.01"/>
                </svg>
                <?= e($messages[$msg]) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
            <div class="form-group">
                <label class="form-label" for="username">Korisničko ime</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       class="form-control" 
                       placeholder="Unesite korisničko ime"
                       value="<?= e($_POST['username'] ?? '') ?>"
                       autocomplete="username"
                       autofocus
                       required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Lozinka</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       class="form-control" 
                       placeholder="Unesite lozinku"
                       autocomplete="current-password"
                       required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                    <polyline points="10 17 15 12 10 7"/>
                    <line x1="15" y1="12" x2="3" y2="12"/>
                </svg>
                Prijava
            </button>
        </form>
        
        <div class="login-footer">
            <p>© <?= date('Y') ?> Portal CMS</p>
        </div>
    </div>
</body>
</html>
