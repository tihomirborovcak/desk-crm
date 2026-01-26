<?php
/**
 * Header template
 */

if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Portal CMS');
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$user = getCurrentUser();
$userInitials = mb_substr($user['full_name'] ?? 'U', 0, 1);
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Portal CMS">
    <title><?= e(PAGE_TITLE) ?> - Portal CMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icons/icon-192.png">
</head>
<body>
    <div class="app-container">
        <!-- Mobile Header -->
        <header class="app-header">
            <div class="header-content">
                <button class="menu-toggle" aria-label="Otvori izbornik">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <span class="logo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/>
                    </svg>
                    CMS
                </span>
                <div class="nav-avatar"><?= e($userInitials) ?></div>
            </div>
        </header>
        
        <!-- Navigation Overlay -->
        <div class="nav-overlay"></div>
        
        <!-- Sidebar Navigation -->
        <nav class="mobile-nav">
            <div class="nav-header">
                <div class="nav-user">
                    <div class="nav-avatar"><?= e($userInitials) ?></div>
                    <div class="nav-user-details">
                        <div class="nav-user-name"><?= e($user['full_name'] ?? 'Korisnik') ?></div>
                        <div class="nav-user-role"><?= translateRole($user['role'] ?? '') ?></div>
                    </div>
                </div>
                <button class="nav-close" aria-label="Zatvori izbornik">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    Početna
                </a>
                
                <?php if (isAdmin()): ?>
                <a href="tasks.php" class="nav-item <?= $currentPage === 'tasks' || $currentPage === 'task-edit' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    Taskovi
                </a>
                <?php endif; ?>
                
                <a href="themes.php" class="nav-item <?= $currentPage === 'themes' || $currentPage === 'theme-edit' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                    </svg>
                    Teme ZL
                </a>

                <a href="zl-clanci.php" class="nav-item <?= in_array($currentPage, ['zl-clanci', 'zl-clanak-edit', 'zl-issue']) ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                    Članci ZL
                </a>

                <a href="events.php" class="nav-item <?= $currentPage === 'events' || $currentPage === 'event-edit' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    Događaji
                </a>
                
                <a href="photos.php" class="nav-item <?= $currentPage === 'photos' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                    </svg>
                    Attachmenti
                </a>

                <a href="slike-ai.php" class="nav-item <?= $currentPage === 'slike-ai' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                    </svg>
                    AI Slike
                </a>

                <a href="transkripcija.php" class="nav-item <?= $currentPage === 'transkripcija' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                        <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                        <line x1="12" y1="19" x2="12" y2="23"/>
                        <line x1="8" y1="23" x2="16" y2="23"/>
                    </svg>
                    Transkripcija
                </a>

                <a href="tekst-ai.php" class="nav-item <?= $currentPage === 'tekst-ai' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 20h9"/>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                    </svg>
                    AI Tekst
                </a>

                <a href="portali.php" class="nav-item <?= $currentPage === 'portali' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                    Portali
                </a>

                <a href="skini-tekst.php" class="nav-item <?= $currentPage === 'skini-tekst' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="12" y1="18" x2="12" y2="12"/>
                        <line x1="9" y1="15" x2="15" y2="15"/>
                    </svg>
                    Skini tekst
                </a>

                <?php if (isEditor()): ?>
                <div class="nav-divider"></div>

                <a href="statistike.php" class="nav-item <?= $currentPage === 'statistike' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    Statistike
                </a>
                <?php endif; ?>

                <?php if (isAdmin()): ?>
                <a href="users.php" class="nav-item <?= $currentPage === 'users' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Korisnici
                </a>
                
                <a href="activity.php" class="nav-item <?= $currentPage === 'activity' ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                    </svg>
                    Aktivnosti
                </a>
                <?php endif; ?>
            </div>
            
            <div class="nav-footer">
                <a href="logout.php" class="btn btn-outline btn-block">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Odjava
                </a>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="main-content">
            <?php 
            $flashMessage = getMessage();
            if ($flashMessage): 
            ?>
            <div class="alert alert-<?= e($flashMessage['type']) ?>" data-dismiss="5000">
                <?= e($flashMessage['text']) ?>
            </div>
            <?php endif; ?>
