<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

use App\Repositories\BranchRepository;
use App\Repositories\FineRepository;
use App\Repositories\LoanRepository;
use App\Repositories\MemberRepository;
use App\Repositories\ReservationRepository;

$pdo = require __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

$staff = require_staff_login();

$loanRepository = new LoanRepository($pdo);
$reservationRepository = new ReservationRepository($pdo);
$fineRepository = new FineRepository($pdo);
$memberRepository = new MemberRepository($pdo);
$branchRepository = new BranchRepository($pdo);

$branch = null;
if (! empty($staff['branch_id'])) {
    $branch = $branchRepository->find((int) $staff['branch_id']);
}

$overdueLoans = array_slice($loanRepository->overdue(), 0, 8);
$pendingReservations = array_slice($reservationRepository->all('pending'), 0, 5);
$readyReservations = array_slice($reservationRepository->all('ready'), 0, 5);
$unpaidFines = array_slice($fineRepository->all('unpaid'), 0, 5);
$newMembers = $memberRepository->recent(5);

$flash = consume_flash();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff Workspace | <?php echo htmlspecialchars($staff['full_name']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #f4f6fc;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .workspace-hero {
            background: linear-gradient(135deg, #101828, #1f61d1);
            border-radius: 1.25rem;
            padding: 2rem;
            color: #fff;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.25);
        }
        .stat-card {
            border-radius: 1rem;
            padding: 1.25rem;
            background: #fff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }
        .table-card {
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.08);
            border: none;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="/staff/dashboard.php">LibraM Staff Workspace</a>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-light" href="/admin/index.php">Admin Console</a>
            <a class="btn btn-sm btn-light" href="/">Public Portal</a>
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
    <div class="workspace-hero mb-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-8">
                <p class="text-uppercase small mb-1">Signed in as</p>
                <h2 class="fw-semibold mb-2"><?php echo htmlspecialchars($staff['full_name']); ?></h2>
                <p class="mb-0"><?php echo htmlspecialchars($staff['role']); ?><?php if ($branch): ?> · <?php echo htmlspecialchars($branch['name']); ?><?php endif; ?></p>
            </div>
            <div class="col-md-4">
                <div class="row g-3 text-center">
                    <div class="col-4">
                        <p class="display-6 fw-semibold mb-0"><?php echo count($overdueLoans); ?></p>
                        <small class="text-uppercase">Overdue</small>
                    </div>
                    <div class="col-4">
                        <p class="display-6 fw-semibold mb-0"><?php echo count($pendingReservations); ?></p>
                        <small class="text-uppercase">Pending holds</small>
                    </div>
                    <div class="col-4">
                        <p class="display-6 fw-semibold mb-0"><?php echo count($unpaidFines); ?></p>
                        <small class="text-uppercase">Unpaid fines</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card table-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Overdue follow-ups</h5>
                    <span class="text-muted small">Top <?php echo count($overdueLoans); ?> cases</span>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                        <tr>
                            <th>Member</th>
                            <th>Book</th>
                            <th>Due on</th>
                            <th>Contact</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (! $overdueLoans): ?>
                            <tr><td colspan="4" class="text-muted">No overdue loans at the moment.</td></tr>
                        <?php else: ?>
                            <?php foreach ($overdueLoans as $loan): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($loan['member_name']); ?><br><small class="text-muted"><?php echo htmlspecialchars($loan['student_id']); ?></small></td>
                                    <td><?php echo htmlspecialchars($loan['book_title']); ?></td>
                                    <td><span class="badge text-bg-danger"><?php echo htmlspecialchars($loan['due_on']); ?></span></td>
                                    <td><?php echo htmlspecialchars($loan['member_email']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card table-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Reservations queue</h5>
                    <span class="text-muted small"><?php echo count($pendingReservations); ?> waiting</span>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                        <tr>
                            <th>Book</th>
                            <th>Member</th>
                            <th>Position</th>
                            <th>Reserved on</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (! $pendingReservations): ?>
                            <tr><td colspan="4" class="text-muted">No pending reservations.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pendingReservations as $reservation): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reservation['book_title']); ?></td>
                                    <td><?php echo htmlspecialchars($reservation['member_name']); ?></td>
                                    <td><?php echo (int) $reservation['queue_position']; ?></td>
                                    <td><?php echo htmlspecialchars($reservation['reserved_on']); ?></td>
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
                    <h6 class="mb-0">Ready for pickup</h6>
                </div>
                <div class="card-body">
                    <?php if (! $readyReservations): ?>
                        <p class="text-muted">No ready notifications waiting.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($readyReservations as $reservation): ?>
                                <li class="list-group-item px-0">
                                    <strong><?php echo htmlspecialchars($reservation['book_title']); ?></strong><br>
                                    <small class="text-muted">Expires <?php echo htmlspecialchars($reservation['expires_on'] ?? 'soon'); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card table-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Unpaid fines</h6>
                    <a class="btn btn-sm btn-link" href="/admin/fines.php">Manage</a>
                </div>
                <div class="card-body">
                    <?php if (! $unpaidFines): ?>
                        <p class="text-muted mb-0">All clear.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($unpaidFines as $fine): ?>
                                <li class="list-group-item px-0 d-flex justify-content-between">
                                    <div>
                                        <strong><?php echo htmlspecialchars($fine['member_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($fine['reason'] ?? 'Fine'); ?></small>
                                    </div>
                                    <span class="badge text-bg-warning text-dark">Br <?php echo number_format((float) $fine['amount'], 2); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card table-card">
                <div class="card-header">
                    <h6 class="mb-0">Latest registrations</h6>
                </div>
                <div class="card-body">
                    <?php if (! $newMembers): ?>
                        <p class="text-muted mb-0">No recent registrations.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($newMembers as $member): ?>
                                <li class="list-group-item px-0">
                                    <strong><?php echo htmlspecialchars($member['full_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($member['faculty']); ?> · <?php echo htmlspecialchars($member['student_id']); ?></small>
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
