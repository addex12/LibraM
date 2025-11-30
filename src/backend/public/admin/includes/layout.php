<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
 * Email: gizawadugna@gmail.com
 */

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

        if (admin_is_staff_operator()) {
            $allowedKeys = staff_allowed_admin_nav_keys();
            $nav = array_filter(
                $nav,
                static fn ($item, $key) => in_array($key, $allowedKeys, true),
                ARRAY_FILTER_USE_BOTH
            );
        }

        $head = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
HTML;
        $head .= htmlspecialchars($title);
        $head .= <<<HTML
    </title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: #f3f5fb;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .navbar {
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.15);
            background: #0f172a !important;
        }
        .nav-link.active {
            font-weight: 600;
            color: #fff !important;
        }
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 12px 40px rgba(15, 23, 42, 0.08);
        }
        .card-metric {
            padding: 1.25rem;
        }
        .card-metric .metric-label {
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 0.75rem;
            color: #64748b;
        }
        .card-metric .metric-value {
            font-size: 2.25rem;
            font-weight: 600;
        }
        .table-actions {
            display: flex;
            gap: .5rem;
        }
        .book-table td,
        .book-table th {
            vertical-align: middle;
        }
        .book-table .book-title-cell,
        .book-table .book-location,
        .book-table .book-subject-cell {
            white-space: normal;
        }
        .book-table .book-availability {
            white-space: nowrap;
        }
        .book-table .book-location .badge {
            white-space: nowrap;
            font-weight: 500;
        }
        .book-table .book-location .text-muted span {
            display: inline-block;
            margin-right: .3rem;
        }
        .avatar-chip {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 600;
            background: #e0e7ff;
            color: #1d4ed8;
        }
        .activity-row {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #eef2ff;
        }
        .activity-row:last-child {
            border-bottom: none;
        }
        .badge-soft {
            background: #ecf0ff;
            color: #1d4ed8;
        }
        .quick-actions .btn {
            min-width: 150px;
        }
    </style>
</head>
<body>
HTML;
        echo $head;
        echo '<nav class="navbar navbar-expand-lg bg-dark navbar-dark mb-4"><div class="container-fluid"><a class="navbar-brand" href="/admin/index.php">LibraM Admin</a><div class="collapse navbar-collapse show"><ul class="navbar-nav me-auto mb-2 mb-lg-0">';
        foreach ($nav as $key => $item) {
            $activeClass = $key === $active ? ' active' : '';
            echo '<li class="nav-item"><a class="nav-link' . $activeClass . '" href="' . $item['href'] . '">' . htmlspecialchars($item['label']) . '</a></li>';
        }
        echo '</ul></div>';
        $operator = admin_operator();
        if ($operator) {
            if ($operator['role'] === 'super_admin') {
                $roleBadge = '<span class="badge text-bg-warning text-dark">Super Admin</span>';
            } elseif ($operator['role'] === 'staff') {
                $roleBadge = '<span class="badge text-bg-info text-dark">Staff</span>';
            } else {
                $roleBadge = '<span class="badge text-bg-secondary">Admin</span>';
            }
            echo '<div class="d-flex align-items-center gap-3"><span class="text-white-50 small">' . htmlspecialchars($operator['name']) . '</span>' . $roleBadge . '<a class="btn btn-sm btn-outline-light" href="/admin/logout.php">Logout</a></div>';
        } else {
            echo '<a class="btn btn-sm btn-outline-light" href="/login.php?account=admin">Login</a>';
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
