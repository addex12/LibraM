<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (! function_exists('member_session')) {
    function member_session(): ?array
    {
        return $_SESSION['member_auth'] ?? null;
    }
}

if (! function_exists('staff_session')) {
    function staff_session(): ?array
    {
        return $_SESSION['staff_auth'] ?? null;
    }
}

function require_member_login(): array
{
    $session = member_session();
    if ($session) {
        return $session;
    }

    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Please sign in as a member to continue.'];
    header('Location: /login.php?account=member');
    exit;
}

function require_staff_login(): array
{
    $session = staff_session();
    if ($session) {
        return $session;
    }

    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Please sign in with your staff credentials.'];
    header('Location: /login.php?account=staff');
    exit;
}

function consume_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    if ($flash) {
        unset($_SESSION['flash']);
    }

    return $flash;
}
