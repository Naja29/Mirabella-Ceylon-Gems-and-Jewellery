<?php

if (session_status() === PHP_SESSION_NONE) session_start();

function customer_logged_in(): bool {
    return !empty($_SESSION['customer_id']);
}

function customer_session(): array {
    return [
        'id'         => $_SESSION['customer_id']    ?? null,
        'first_name' => $_SESSION['customer_fname'] ?? '',
        'last_name'  => $_SESSION['customer_lname'] ?? '',
        'email'      => $_SESSION['customer_email'] ?? '',
    ];
}

function require_customer_login(string $redirect = 'login.php'): void {
    if (!customer_logged_in()) {
        header('Location: ' . $redirect . '?next=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function set_customer_session(array $customer): void {
    session_regenerate_id(true);
    $_SESSION['customer_id']    = $customer['id'];
    $_SESSION['customer_fname'] = $customer['first_name'];
    $_SESSION['customer_lname'] = $customer['last_name'];
    $_SESSION['customer_email'] = $customer['email'];
}

function destroy_customer_session(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
