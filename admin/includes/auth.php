<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Redirect to login if no valid session */
if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_logged_in'])) {
    $loginUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $loginUrl = preg_replace('#/includes$#', '', $loginUrl);
    header('Location: ' . $loginUrl . '/login.php?reason=session');
    exit;
}

/* Optional: session timeout after 2 hours of inactivity */
$timeout = 2 * 60 * 60;
if (isset($_SESSION['admin_last_active']) && (time() - $_SESSION['admin_last_active']) > $timeout) {
    session_unset();
    session_destroy();
    header('Location: login.php?reason=timeout');
    exit;
}
$_SESSION['admin_last_active'] = time();
