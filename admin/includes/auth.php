<?php
declare(strict_types=1);
/**
 * Simple session-based admin guard. No tokens, no registration —
 * just "is there a logged-in admin in the session?".
 * Every protected admin page starts with:
 *   require_once __DIR__ . '/../includes/auth.php';
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        header('Location: ' . admin_base_url() . 'login.php');
        exit;
    }
}

/**
 * Base URL of the /admin folder, works whether the app is at the
 * webroot or in a sub-folder.
 */
function admin_base_url(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (preg_match('#^(.*/admin/)#', $script, $m)) {
        return $m[1];
    }
    return '/admin/';
}