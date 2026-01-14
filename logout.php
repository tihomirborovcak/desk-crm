<?php
/**
 * Logout
 */

require_once 'includes/auth.php';

logout();

header('Location: index.php?msg=logged_out');
exit;
