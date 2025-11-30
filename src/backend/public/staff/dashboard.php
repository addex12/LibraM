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
use App\Repositories\UserSessionRepository;

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

$userSessionRepository = new UserSessionRepository($pdo);
$colleagueSessions = $userSessionRepository->recent(4, ['Admin', 'Super Admin']);
$activeMemberSessions = $userSessionRepository->recent(4, ['Admin', 'Super Admin'], false);
$channelStats = $userSessionRepository->channelStats();

$taskSummary = [
    'overdue' => count($overdueLoans),
    'pending' => count($pendingReservations),
    'ready' => count($readyReservations),
    'fines' => count($unpaidFines),
];
$totalTaskCount = array_sum($taskSummary);
$completedTasks = $taskSummary['ready'];
$taskCompletion = $totalTaskCount === 0 ? 100 : (int) round(($completedTasks / $totalTaskCount) * 100);
$nextOverdue = $overdueLoans[0] ?? null;
$unpaidFineTotal = array_reduce($unpaidFines, static fn (float $carry, array $fine) => $carry + (float) ($fine['amount'] ?? 0), 0.0);

$quickActions = [
    ['label' => 'Issue a loan', 'href' => '/admin/loans.php', 'icon' => 'bi-arrow-left-right', 'hint' => 'Borrow/return desk'],
    ['label' => 'Register member', 'href' => '/admin/members.php', 'icon' => 'bi-person-plus', 'hint' => 'Enroll patrons quickly'],
    ['label' => 'Record fine', 'href' => '/admin/fines.php', 'icon' => 'bi-cash-coin', 'hint' => 'Adjust balances'],
    ['label' => 'Send notice', 'href' => '/admin/notifications.php', 'icon' => 'bi-send', 'hint' => 'Trigger reminders'],
];

$branchSnapshot = [
    'name' => $branch['name'] ?? 'All branches',
    'code' => $branch['code'] ?? 'MULTI',
    'overdue' => $taskSummary['overdue'],
    'pending' => $taskSummary['pending'],
    'ready' => $taskSummary['ready'],
    'fines' => $unpaidFineTotal,
];

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
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
        .quick-action-tile {
            border: 1px solid rgba(31, 97, 209, 0.2);
            border-radius: 1rem;
            padding: 1rem;
            background: #fff;
            display: flex;
            gap: 0.75rem;
            align-items: center;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
            transition: transform 0.15s ease, border-color 0.15s ease;
        }
        .quick-action-tile:hover {
            border-color: #1f61d1;
            transform: translateY(-2px);
        }
        .quick-action-tile small {
            color: #6b7280;
            display: block;
        }
        .quick-action-tile i {
            font-size: 1.5rem;
            color: #1f61d1;
        }
        .live-list {
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
        }
        .live-list li + li {
            margin-top: 0.75rem;
        }
        .live-pill {
            border-radius: 999px;
            padding: 0.1rem 0.75rem;
            font-size: 0.75rem;
            background: rgba(31, 97, 209, 0.1);
        }
        .channel-chip {
            border-radius: 0.75rem;
            background: rgba(99, 102, 241, 0.1);
            padding: 0.35rem 0.85rem;
            display: inline-flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .channel-chip span {
            font-size: 0.85rem;
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
    <div class="row g-3 mb-4">
        <?php foreach ($quickActions as $action): ?>
            <div class="col-6 col-md-3">
                <a class="quick-action-tile" href="<?php echo htmlspecialchars($action['href']); ?>">
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
                    <div>
                        <h5 class="mb-0">Shift checklist</h5>
                        <small class="text-muted">Keep the service desk current</small>
                    </div>
                    <span class="badge text-bg-success"><?php echo $taskCompletion; ?>% clear</span>
                </div>
                <div class="card-body">
                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar" role="progressbar" style="width: <?php echo $taskCompletion; ?>%" aria-valuenow="<?php echo $taskCompletion; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <p class="mb-1 small text-muted">Overdue follow-ups</p>
                            <h4 class="mb-0"><?php echo $taskSummary['overdue']; ?></h4>
                        </div>
                        <div class="col-sm-6">
                            <p class="mb-1 small text-muted">Ready pickups</p>
                            <h4 class="mb-0"><?php echo $taskSummary['ready']; ?></h4>
                        </div>
                        <div class="col-sm-6">
                            <p class="mb-1 small text-muted">Pending holds</p>
                            <h4 class="mb-0"><?php echo $taskSummary['pending']; ?></h4>
                        </div>
                        <div class="col-sm-6">
                            <p class="mb-1 small text-muted">Fines to review</p>
                            <h4 class="mb-0"><?php echo $taskSummary['fines']; ?></h4>
                        </div>
                    </div>
                    <?php if ($nextOverdue): ?>
                        <div class="alert alert-warning mt-3 mb-0 p-2">
                            <strong>Next urgent case:</strong> <?php echo htmlspecialchars($nextOverdue['member_name']); ?> • due <?php echo htmlspecialchars($nextOverdue['due_on']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Branch snapshot</h6>
                        <small class="text-muted"><?php echo htmlspecialchars($branchSnapshot['name']); ?> · <?php echo htmlspecialchars($branchSnapshot['code']); ?></small>
                    </div>
                    <span class="badge text-bg-light text-dark">Today</span>
                </div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-6">
                            <p class="text-uppercase small mb-1">Overdue</p>
                            <h4 class="mb-0"><?php echo $branchSnapshot['overdue']; ?></h4>
                        </div>
                        <div class="col-6">
                            <p class="text-uppercase small mb-1">Ready</p>
                            <h4 class="mb-0"><?php echo $branchSnapshot['ready']; ?></h4>
                        </div>
                        <div class="col-6">
                            <p class="text-uppercase small mb-1">Pending</p>
                            <h4 class="mb-0"><?php echo $branchSnapshot['pending']; ?></h4>
                        </div>
                        <div class="col-6">
                            <p class="text-uppercase small mb-1">Fines (Br)</p>
                            <h4 class="mb-0"><?php echo number_format($branchSnapshot['fines'], 2); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card table-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Live desk</h6>
                    <span class="live-pill">Real-time</span>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2">Colleagues online</p>
                    <?php if (! $colleagueSessions): ?>
                        <p class="text-muted">No recent staff sign-ins.</p>
                    <?php else: ?>
                        <ul class="live-list">
                            <?php foreach ($colleagueSessions as $session): ?>
                                <li class="d-flex justify-content-between">
                                    <div>
                                        <strong><?php echo htmlspecialchars($session['full_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($session['role']); ?></small>
                                    </div>
                                    <small class="text-muted"><?php echo htmlspecialchars($session['channel']); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <hr>
                    <p class="text-muted small mb-2">Patrons active</p>
                    <?php if (! $activeMemberSessions): ?>
                        <p class="text-muted mb-0">No sessions from members yet today.</p>
                    <?php else: ?>
                        <ul class="live-list">
                            <?php foreach ($activeMemberSessions as $session): ?>
                                <li class="d-flex justify-content-between">
                                    <div>
                                        <strong><?php echo htmlspecialchars($session['full_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($session['identifier']); ?></small>
                                    </div>
                                    <small class="text-muted"><?php echo htmlspecialchars($session['channel']); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card table-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Channel mix</h6>
                    <small class="text-muted">Last 50 sessions</small>
                </div>
                <div class="card-body">
                    <?php if (! $channelStats): ?>
                        <p class="text-muted mb-0">No session telemetry captured yet.</p>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($channelStats as $channel): ?>
                                <div class="channel-chip">
                                    <strong><?php echo htmlspecialchars($channel['channel']); ?></strong>
                                    <span><?php echo (int) $channel['total']; ?> sign-ins</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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
