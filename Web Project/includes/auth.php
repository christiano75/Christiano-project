<?php
/**
 * Authentication & authorisation guards.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function require_login(string $loginPath = 'login.php'): void
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'account.php';
        set_flash('warning', 'Please sign in to continue.');
        redirect($loginPath);
    }
}

function require_admin(string $loginPath = '../login.php', string $accountPath = '../account.php'): void
{
    require_login($loginPath);

    if (!is_admin()) {
        http_response_code(403);
        set_flash('danger', 'That area is reserved for store administrators.');
        redirect($accountPath);
    }
}
