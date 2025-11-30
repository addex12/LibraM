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

require_once dirname(__DIR__, 2) . '/includes/portal.php';

if (! function_exists('staff_allowed_admin_routes')) {
    function staff_allowed_admin_routes(): array
    {
        return [
            '/admin/index.php',
            '/admin/loans.php',
            '/admin/members.php',
            '/admin/fines.php',
            '/admin/notifications.php',
            '/admin/logout.php',
        ];
    }
}

if (! function_exists('staff_allowed_admin_nav_keys')) {
    function staff_allowed_admin_nav_keys(): array
    {
        return ['dashboard', 'members', 'loans', 'fines', 'notifications'];
    }
}

function admin_accounts(): array
{
    $accounts = [];

    $defaultAdminUser = trim((string) ($_ENV['ADMIN_USER'] ?? 'librarian'));
    if ($defaultAdminUser !== '') {
        $accounts[$defaultAdminUser] = [
            'username' => $defaultAdminUser,
            'password' => normalize_secret($_ENV['ADMIN_PASSWORD'] ?? 'library123'),
            'hash' => normalize_secret($_ENV['ADMIN_PASSWORD_HASH'] ?? null),
            'role' => 'admin',
            'display' => $_ENV['ADMIN_DISPLAY_NAME'] ?? 'Librarian',
        ];
    }

    $superAdminUser = trim((string) ($_ENV['SUPER_ADMIN_USER'] ?? 'superadmin'));
    if ($superAdminUser !== '') {
        $accounts[$superAdminUser] = [
            'username' => $superAdminUser,
            'password' => normalize_secret($_ENV['SUPER_ADMIN_PASSWORD'] ?? 'superlibrary!23'),
            'hash' => normalize_secret($_ENV['SUPER_ADMIN_PASSWORD_HASH'] ?? null),
            'role' => 'super_admin',
            'display' => $_ENV['SUPER_ADMIN_DISPLAY_NAME'] ?? 'Super Admin',
        ];
    }

    return array_values($accounts);
}

function normalize_secret(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $trimmed = trim($value);

    return $trimmed === '' ? null : $trimmed;
}

function find_admin_account(string $username): ?array
{
    foreach (admin_accounts() as $account) {
        if (hash_equals($account['username'], $username)) {
            return $account;
        }
    }

    return null;
}

function admin_logged_in(): bool
{
    return ($_SESSION['admin_authenticated'] ?? false) === true;
}

function staff_has_portal_session(): bool
{
    return function_exists('staff_session') && staff_session() !== null;
}

function admin_operator(): ?array
{
    if (admin_logged_in()) {
        return [
            'type' => 'admin',
            'name' => $_SESSION['admin_username'] ?? 'Admin',
            'role' => $_SESSION['admin_role'] ?? 'admin',
        ];
    }

    if (staff_has_portal_session()) {
        $staff = staff_session();

        return [
            'type' => 'staff',
            'name' => $staff['full_name'] ?? 'Staff',
            'role' => 'staff',
        ];
    }

    return null;
}

function admin_is_staff_operator(): bool
{
    $operator = admin_operator();

    return ($operator['role'] ?? null) === 'staff';
}

function admin_username(): string
{
    $operator = admin_operator();

    return $operator['name'] ?? 'Admin';
}

function admin_role(): string
{
    $operator = admin_operator();

    return $operator['role'] ?? 'guest';
}

function is_super_admin(): bool
{
    return admin_logged_in() && admin_role() === 'super_admin';
}

function require_admin_login(): void
{
    if (admin_operator() !== null) {
        return;
    }

    header('Location: /login.php?account=admin');
    exit;
}

function enforce_admin_route_permissions(): void
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if ($script === '' || strpos($script, '/admin/') !== 0) {
        return;
    }

    // Skip internal helper scripts
    if (preg_match('#^/admin/(includes|tools)/#', $script)) {
        return;
    }

    if (! admin_is_staff_operator()) {
        return;
    }

    if (! in_array($script, staff_allowed_admin_routes(), true)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Access limited</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head><body class="bg-light"><div class="container py-5"><div class="alert alert-warning"><h4 class="alert-heading">Restricted area</h4><p>Your staff session only has access to specific service-desk tools.</p><hr><a class="btn btn-primary" href="/staff/dashboard.php">Return to staff workspace</a></div></div></body></html>';
        exit;
    }
}

enforce_admin_route_permissions();

function require_super_admin(): void
{
    require_admin_login();
    if (! is_super_admin()) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>403 Forbidden</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head><body class="bg-light"><div class="container py-5"><div class="alert alert-danger"><h4 class="alert-heading">Access denied</h4><p>You must be a super administrator to view this page.</p><hr><a class="btn btn-outline-primary" href="/admin/index.php">Return to dashboard</a></div></div></body></html>';
        exit;
    }
}

function attempt_admin_login(string $username, string $password): bool
{
    $account = find_admin_account($username);
    if ($account === null) {
        return false;
    }

    if ($account['hash']) {
        if (! password_verify($password, $account['hash'])) {
            return false;
        }
    } elseif ($account['password'] === null || ! hash_equals($account['password'], $password)) {
        return false;
    }

    $_SESSION['admin_authenticated'] = true;
    $_SESSION['admin_username'] = $account['display'] ?? $account['username'];
    $_SESSION['admin_role'] = $account['role'] ?? 'admin';
    $_SESSION['admin_username_raw'] = $account['username'];

    return true;
}

function admin_logout(): void
{
    unset(
        $_SESSION['admin_authenticated'],
        $_SESSION['admin_username'],
        $_SESSION['admin_username_raw'],
        $_SESSION['admin_role']
    );
}

function phpmyadmin_url(): ?string
{
    $url = trim((string) ($_ENV['PHPMYADMIN_URL'] ?? ''));
    if ($url !== '') {
        return $url;
    }

    $bundledPath = dirname(__DIR__) . '/tools/adminer-iframe.php';
    if (is_file($bundledPath)) {
        return '/admin/tools/adminer-iframe.php';
    }

    return null;
}
