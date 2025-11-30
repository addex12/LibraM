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
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background-color: #f5f7fb; }
            .navbar-brand span { font-weight: 600; }
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
