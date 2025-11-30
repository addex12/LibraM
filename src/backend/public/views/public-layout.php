<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144198
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

function public_header(string $title): void
{
    ?><!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo htmlspecialchars($title); ?></title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            :root {
                --hero-gradient: linear-gradient(135deg, #1f61d1, #5b8dee);
            }
            body {
                background-color: #eef2fb;
                font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            }
            .navbar {
                box-shadow: 0 4px 14px rgba(31, 97, 209, 0.15);
            }
            .navbar-brand span {
                font-weight: 600;
                letter-spacing: 0.5px;
            }
            .hero-panel {
                background: var(--hero-gradient);
                border-radius: 1.25rem;
                padding: 2rem;
                color: #fff;
                box-shadow: 0 20px 45px rgba(31, 97, 209, 0.25);
                overflow: hidden;
            }
            .hero-panel .text-white-75 {
                color: rgba(255, 255, 255, 0.75) !important;
            }
            .stat-pill {
                background: rgba(255, 255, 255, 0.13);
                border-radius: 1rem;
                padding: 1rem;
                min-height: 110px;
                color: #fff;
            }
            .stat-value {
                font-size: 1.65rem;
                font-weight: 600;
            }
            .stat-label {
                text-transform: uppercase;
                letter-spacing: 0.08em;
                font-size: 0.75rem;
            }
            .stat-meta {
                font-size: 0.8rem;
                color: rgba(255, 255, 255, 0.75);
            }
            .card {
                border: none;
                box-shadow: 0 8px 25px rgba(15, 23, 42, 0.08);
            }
            .activity-row {
                display: flex;
                gap: 1rem;
                padding: 1rem 0;
                border-bottom: 1px solid #f0f2f8;
            }
            .activity-row:last-child {
                border-bottom: none;
            }
            .avatar-chip {
                width: 48px;
                height: 48px;
                border-radius: 50%;
                background: #edf2ff;
                color: #2f4ea2;
                display: grid;
                place-items: center;
                font-weight: 600;
            }
            .activity-pill {
                display: inline-block;
                margin-top: 0.5rem;
                background: #eef2ff;
                color: #1f61d1;
                font-size: 0.75rem;
                padding: 0.25rem 0.75rem;
                border-radius: 999px;
                font-weight: 600;
            }
        </style>
    </head>
    <body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                <span>University Library</span>
            </a>
            <div class="d-flex gap-2">
                <a class="btn btn-sm btn-light" href="/admin/index.php">Admin Portal</a>
                <a class="btn btn-sm btn-outline-light" href="/docs/README.html" target="_blank">Docs</a>
            </div>
        </div>
    </nav>
    <main class="container py-4">
        <div class="mb-4 text-center">
            <h1 class="h3 mb-1">Library Service Desk</h1>
            <p class="text-muted mb-0">Browse the catalog, register as a member, borrow books, or track your loans.</p>
        </div>
    <?php
}

function public_flash(?array $flash): void
{
    if (! $flash) {
        return;
    }
    ?>
    <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php
}

function public_footer(): void
{
    ?>
    </main>
    <footer class="bg-white border-top py-3 mt-4">
        <div class="container text-center text-muted small">
            &copy; <?php echo date('Y'); ?> University Library. All rights reserved.
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
