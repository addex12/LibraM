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
$completedLoans = array_filter($loans, static fn ($loan) => ($loan['status'] ?? '') === 'returned');
$readingGoal = 12;
$readingProgress = $readingGoal === 0 ? 0 : (int) round((count($completedLoans) / $readingGoal) * 100);
$nextDueLoan = null;
foreach ($activeLoans as $loan) {
    if (! $nextDueLoan || strcmp($loan['due_on'], $nextDueLoan['due_on']) < 0) {
        $nextDueLoan = $loan;
    }
}
$readyReservations = array_values(array_filter($reservations, static fn ($reservation) => ($reservation['status'] ?? '') === 'ready'));
usort($readyReservations, static fn ($a, $b) => strcmp($a['ready_on'] ?? $a['reserved_on'] ?? '', $b['ready_on'] ?? $b['reserved_on'] ?? ''));
$nextReadyReservation = $readyReservations[0] ?? null;
$quickActions = [
    ['label' => 'Search catalog', 'href' => '/', 'icon' => 'bi-search', 'hint' => 'Find new books'],
    ['label' => 'Place hold', 'href' => '/member/dashboard.php#reservations', 'icon' => 'bi-bookmark-plus', 'hint' => 'Reserve a title'],
    ['label' => 'Renew loan', 'href' => '/member/dashboard.php#active-loans', 'icon' => 'bi-arrow-repeat', 'hint' => 'Request extension'],
    ['label' => 'Contact desk', 'href' => 'mailto:service@libram.edu', 'icon' => 'bi-chat-dots', 'hint' => 'Ask a librarian'],
];
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
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
        .digital-card {
            border-radius: 1.25rem;
            background: #fff;
            padding: 1.5rem;
            box-shadow: 0 12px 35px rgba(15, 23, 42, 0.12);
            position: relative;
            overflow: hidden;
        }
        .digital-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top right, rgba(255,255,255,0.4), transparent 45%);
            pointer-events: none;
        }
        .library-id {
            font-size: 1.25rem;
            letter-spacing: 0.1em;
        }
        .quick-action-btn {
            display: flex;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            border-radius: 1rem;
            background: #fff;
            text-decoration: none;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
            color: inherit;
            border: 1px solid rgba(31, 97, 209, 0.2);
            transition: transform 0.15s ease, border-color 0.15s ease;
        }
        .quick-action-btn:hover {
            transform: translateY(-3px);
            border-color: #1f61d1;
        }
        .quick-action-btn small {
            color: #6c7684;
            display: block;
        }
        .quick-action-btn i {
            font-size: 1.35rem;
            color: #1f61d1;
        }
        .support-card {
            border-left: 4px solid #1f61d1;
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
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="hero-card h-100">
                <div class="row g-3 align-items-center">
                    <div class="col-md-8">
                        <p class="text-uppercase small mb-1">Welcome back</p>
                        <h2 class="fw-semibold mb-2"><?php echo htmlspecialchars($member['full_name']); ?></h2>
                        <p class="mb-0"><?php echo htmlspecialchars($member['faculty']); ?> · ID <?php echo htmlspecialchars($member['student_id']); ?></p>
                        <?php if ($nextDueLoan): ?>
                            <div class="mt-3">
                                <small class="text-uppercase">Next due</small>
                                <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($nextDueLoan['book_title']); ?> · <?php echo htmlspecialchars($nextDueLoan['due_on']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <div class="row g-3 text-center">
                            <div class="col-4">
                                <p class="display-6 fw-semibold mb-0"><?php echo count($activeLoans); ?></p>
                                <p class="text-uppercase small mb-0">Active loans</p>
                            </div>
                            <div class="col-4">
                                <p class="display-6 fw-semibold mb-0"><?php echo count($reservations); ?></p>
                                <p class="text-uppercase small mb-0">Reservations</p>
                            </div>
                            <div class="col-4">
                                <p class="display-6 fw-semibold mb-0"><?php echo $totalFine > 0 ? 'Br ' . number_format($totalFine, 2) : '0'; ?></p>
                                <p class="text-uppercase small mb-0">Fines due</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="digital-card h-100">
                <p class="text-uppercase small text-muted mb-1">Digital library card</p>
                <div class="library-id fw-semibold mb-2"><?php echo htmlspecialchars(str_replace('/', '·', $member['student_id'])); ?></div>
                <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($member['full_name']); ?></p>
                <small class="text-muted"><?php echo htmlspecialchars($member['faculty']); ?></small>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Membership</span>
                    <span class="badge text-bg-primary">Active</span>
                </div>
                <?php if ($nextReadyReservation): ?>
                    <div>
                        <small class="text-muted">Ready for pickup</small>
                        <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($nextReadyReservation['book_title']); ?></p>
                        <?php if (! empty($nextReadyReservation['expires_on'])): ?>
                            <small class="text-muted">Hold until <?php echo htmlspecialchars($nextReadyReservation['expires_on']); ?></small>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-0">Track pickups here once a hold is ready.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="row g-3 mb-4">
        <?php foreach ($quickActions as $action): ?>
            <div class="col-6 col-md-3">
                <a class="quick-action-btn" href="<?php echo htmlspecialchars($action['href']); ?>">
                    <i class="<?php echo htmlspecialchars($action['icon']); ?>"></i>
                    <div>
                        <span class="fw-semibold d-block"><?php echo htmlspecialchars($action['label']); ?></span>
                        <small><?php echo htmlspecialchars($action['hint']); ?></small>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card table-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Reading journey</h5>
                    <span class="badge text-bg-light text-dark"><?php echo $readingProgress; ?>% of goal</span>
                </div>
                <div class="card-body">
                    <div class="progress mb-3" style="height: 8px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $readingProgress; ?>%" aria-valuenow="<?php echo $readingProgress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <p class="mb-0 text-muted">Goal: <?php echo $readingGoal; ?> books this term · Completed <?php echo count($completedLoans); ?></p>
                </div>
            </div>
            <div class="card table-card mb-4" id="active-loans">
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
                    <h6 class="mb-0">Upcoming reminders</h6>
                </div>
                <div class="card-body">
                    <?php if (! $nextDueLoan && ! $nextReadyReservation && $totalFine <= 0): ?>
                        <p class="text-muted mb-0">You're all caught up. We'll notify you if anything changes.</p>
                    <?php else: ?>
                        <?php if ($nextDueLoan): ?>
                            <div class="mb-3">
                                <small class="text-uppercase text-muted">Due soon</small>
                                <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($nextDueLoan['book_title']); ?></p>
                                <span class="badge text-bg-warning text-dark">Due <?php echo htmlspecialchars($nextDueLoan['due_on']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($nextReadyReservation): ?>
                            <div class="mb-3">
                                <small class="text-uppercase text-muted">Pickup window</small>
                                <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($nextReadyReservation['book_title']); ?></p>
                                <?php if (! empty($nextReadyReservation['expires_on'])): ?>
                                    <span class="badge text-bg-info text-dark">Hold until <?php echo htmlspecialchars($nextReadyReservation['expires_on']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($totalFine > 0): ?>
                            <div>
                                <small class="text-uppercase text-muted">Balance</small>
                                <p class="mb-0 fw-semibold">Br <?php echo number_format($totalFine, 2); ?></p>
                                <small class="text-muted">Visit the desk to settle fines.</small>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card table-card mb-4" id="reservations">
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
            <div class="card table-card support-card mt-4">
                <div class="card-body">
                    <h6 class="fw-semibold">Need assistance?</h6>
                    <p class="text-muted mb-2">Our librarians can help renew loans, locate materials, or unblock your account.</p>
                    <ul class="list-unstyled mb-3">
                        <li class="mb-1"><i class="bi bi-envelope me-2"></i>service@libram.edu</li>
                        <li><i class="bi bi-telephone me-2"></i>+251-11-000-2000</li>
                    </ul>
                    <a class="btn btn-outline-primary w-100" href="mailto:service@libram.edu?subject=LibraM%20Help">Email support</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
