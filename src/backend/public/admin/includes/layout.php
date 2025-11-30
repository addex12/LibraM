<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if (! function_exists('admin_header')) {
    function admin_header(string $title, string $active = 'dashboard'): void
    {
        $nav = [
            'dashboard' => ['label' => 'Dashboard', 'href' => '/admin/index.php'],
            'books' => ['label' => 'Books', 'href' => '/admin/books.php'],
            'branches' => ['label' => 'Branches', 'href' => '/admin/branches.php'],
            'shelves' => ['label' => 'Shelves', 'href' => '/admin/shelves.php'],
            'subjects' => ['label' => 'Subjects', 'href' => '/admin/subjects.php'],
            'members' => ['label' => 'Members', 'href' => '/admin/members.php'],
            'staff' => ['label' => 'Staff', 'href' => '/admin/staff.php'],
            'loans' => ['label' => 'Loans', 'href' => '/admin/loans.php'],
            'reservations' => ['label' => 'Reservations', 'href' => '/admin/reservations.php'],
            'fines' => ['label' => 'Fines', 'href' => '/admin/fines.php'],
            'notifications' => ['label' => 'Notifications', 'href' => '/admin/notifications.php'],
            'reports' => ['label' => 'Reports', 'href' => '/admin/reports.php'],
        ];

        if (is_super_admin()) {
            $nav['operations'] = ['label' => 'Operations', 'href' => '/admin/operations.php'];
        }
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' .
            htmlspecialchars($title) .
            '</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"><style>body{background:#f5f6f8;} .nav-link.active{font-weight:bold;} .card{box-shadow:0 1px 2px rgba(0,0,0,0.08);} .table-actions{display:flex;gap:.5rem;}</style></head><body>';
        echo '<nav class="navbar navbar-expand-lg bg-dark navbar-dark mb-4"><div class="container-fluid"><a class="navbar-brand" href="/admin/index.php">Library Admin</a><div class="collapse navbar-collapse show"><ul class="navbar-nav me-auto mb-2 mb-lg-0">';
        foreach ($nav as $key => $item) {
            $activeClass = $key === $active ? ' active' : '';
            echo '<li class="nav-item"><a class="nav-link' . $activeClass . '" href="' . $item['href'] . '">' . htmlspecialchars($item['label']) . '</a></li>';
        }
        echo '</ul></div>';
        if (admin_logged_in()) {
            $roleBadge = is_super_admin() ? '<span class="badge text-bg-warning text-dark">Super Admin</span>' : '<span class="badge text-bg-secondary">Admin</span>';
            echo '<div class="d-flex align-items-center gap-3"><span class="text-white-50 small">' . htmlspecialchars(admin_username()) . '</span>' . $roleBadge . '<a class="btn btn-sm btn-outline-light" href="/admin/logout.php">Logout</a></div>';
        } else {
            echo '<a class="btn btn-sm btn-outline-light" href="/admin/login.php">Login</a>';
        }
        echo '</div></nav><main class="container pb-5">';
    }
}

if (! function_exists('admin_footer')) {
    function admin_footer(): void
    {
        echo '</main><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>';
    }
}

if (! function_exists('admin_alert')) {
    function admin_alert(?string $message, string $type = 'success'): void
    {
        if (! $message) {
            return;
        }
        echo '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">' . htmlspecialchars($message) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
}
