<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

use App\Repositories\BookRepository;
use App\Repositories\FineRepository;
use App\Repositories\LoanRepository;
use App\Repositories\ReservationRepository;

$pdo = require __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

$member = require_member_login();

$loanRepository = new LoanRepository($pdo);
$reservationRepository = new ReservationRepository($pdo);
$fineRepository = new FineRepository($pdo);
$bookRepository = new BookRepository($pdo);

$loans = $loanRepository->forMember((int) $member['id']);
$activeLoans = array_filter($loans, static fn ($loan) => in_array($loan['status'] ?? 'borrowed', ['borrowed', 'overdue'], true));
$recentLoans = array_slice($loans, 0, 5);
$reservations = $reservationRepository->forMember((int) $member['id']);
$fines = $fineRepository->unpaidForMember((int) $member['id']);
$totalFine = $fineRepository->totalOutstandingForMember((int) $member['id']);
$recommended = array_slice($bookRepository->all(strtolower($member['faculty'])), 0, 4);
$flash = consume_flash();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Library | <?php echo htmlspecialchars($member['full_name']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #eef2fb;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
        }
        .hero-card {
            background: linear-gradient(135deg, #1f61d1, #5b8dee);
            border-radius: 1.25rem;
            padding: 2rem;
            color: #fff;
            box-shadow: 0 20px 45px rgba(31, 97, 209, 0.25);
        }
        .stat-tile {
            border-radius: 1rem;
            padding: 1.25rem;
            background: #fff;
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.08);
        }
        .table-card {
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.08);
            border: none;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="/">LibraM Service Desk</a>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-light" href="/">Catalog</a>
            <a class="btn btn-sm btn-light" href="/login.php?account=member">Switch account</a>
        </div>
    </div>
</nav>
<div class="container py-4">
    <?php if ($flash): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="hero-card mb-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-8">
                <p class="text-uppercase small mb-1">Welcome back</p>
                <h2 class="fw-semibold mb-2"><?php echo htmlspecialchars($member['full_name']); ?></h2>
                <p class="mb-0"><?php echo htmlspecialchars($member['faculty']); ?> · ID <?php echo htmlspecialchars($member['student_id']); ?></p>
            </div>
            <div class="col-md-4">
                <div class="row g-3">
                    <div class="col-4 text-center">
                        <p class="display-6 fw-semibold mb-0"><?php echo count($activeLoans); ?></p>
                        <p class="text-uppercase small mb-0">Active loans</p>
                    </div>
                    <div class="col-4 text-center">
                        <p class="display-6 fw-semibold mb-0"><?php echo count($reservations); ?></p>
                        <p class="text-uppercase small mb-0">Reservations</p>
                    </div>
                    <div class="col-4 text-center">
                        <p class="display-6 fw-semibold mb-0"><?php echo $totalFine > 0 ? 'Br ' . number_format($totalFine, 2) : '0'; ?></p>
                        <p class="text-uppercase small mb-0">Fines due</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card table-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Active loans</h5>
                    <span class="text-muted small"><?php echo count($activeLoans); ?> item(s)</span>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                        <tr>
                            <th>Book</th>
                            <th>Borrowed</th>
                            <th>Due</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (! $activeLoans): ?>
                            <tr><td colspan="4" class="text-muted">No active loans right now.</td></tr>
                        <?php else: ?>
                            <?php foreach ($activeLoans as $loan): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($loan['book_title']); ?></strong><br>
                                        <small class="text-muted">Loan #<?php echo (int) $loan['id']; ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($loan['borrowed_on']); ?></td>
                                    <td><?php echo htmlspecialchars($loan['due_on']); ?></td>
                                    <td>
                                        <span class="badge text-bg-<?php echo ($loan['status'] === 'overdue') ? 'danger' : 'primary'; ?>">
                                            <?php echo htmlspecialchars($loan['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card table-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent activity</h5>
                    <span class="text-muted small">Last <?php echo count($recentLoans); ?> loans</span>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                        <tr>
                            <th>Book</th>
                            <th>Borrowed</th>
                            <th>Returned</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (! $recentLoans): ?>
                            <tr><td colspan="4" class="text-muted">You have not borrowed any books yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentLoans as $loan): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($loan['book_title']); ?></td>
                                    <td><?php echo htmlspecialchars($loan['borrowed_on']); ?></td>
                                    <td><?php echo htmlspecialchars($loan['returned_on'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($loan['status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card table-card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Reservations</h6>
                </div>
                <div class="card-body">
                    <?php if (! $reservations): ?>
                        <p class="text-muted">No reservations pending. Browse the catalog to place a hold.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($reservations as $reservation): ?>
                                <li class="list-group-item px-0 d-flex justify-content-between">
                                    <div>
                                        <strong><?php echo htmlspecialchars($reservation['book_title']); ?></strong><br>
                                        <small class="text-muted">Queue position <?php echo (int) $reservation['queue_position']; ?></small>
                                    </div>
                                    <span class="badge text-bg-secondary text-capitalize"><?php echo htmlspecialchars($reservation['status']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card table-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Fines</h6>
                    <?php if ($totalFine > 0): ?>
                        <span class="badge text-bg-danger">Br <?php echo number_format($totalFine, 2); ?> due</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (! $fines): ?>
                        <p class="text-muted mb-0">No outstanding fines.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($fines as $fine): ?>
                                <li class="list-group-item px-0">
                                    <strong>Br <?php echo number_format((float) $fine['amount'], 2); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($fine['reason'] ?? 'Library fine'); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="text-muted small mt-2 mb-0">Visit the service desk to settle balances.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card table-card">
                <div class="card-header">
                    <h6 class="mb-0">Recommended for you</h6>
                </div>
                <div class="card-body">
                    <?php if (! $recommended): ?>
                        <p class="text-muted mb-0">Recommendations will appear after your first loan.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($recommended as $book): ?>
                                <li class="list-group-item px-0">
                                    <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($book['author']); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
