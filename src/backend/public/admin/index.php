<?php
/**
 * Developer: Adugna Gizaw
 * Phone: +251911144198
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

use App\Repositories\LoanRepository;
use App\Repositories\UserSessionRepository;

$pdo = require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_admin_login();
require __DIR__ . '/includes/layout.php';
if (! function_exists('badge_initials')) {
    function badge_initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $parts = array_values(array_filter($parts, static fn ($part) => $part !== ''));
        if (! $parts) {
            return strtoupper(substr($name, 0, 2) ?: 'AD');
        }
        $first = substr($parts[0], 0, 1) ?: '';
        $last = substr($parts[count($parts) - 1], 0, 1) ?: '';
        return strtoupper($first . ($last ?: $first));
    }
}

if (! function_exists('format_session_time')) {
    function format_session_time(?string $timestamp): string
    {
        if (! $timestamp) {
            return '—';
        }

        $time = strtotime($timestamp);
        return $time ? date('M j · g:i A', $time) : $timestamp;
    }
}

$bookCount = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$memberCount = (int) $pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
$loanCount = (int) $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'borrowed'")->fetchColumn();
$overdueCount = (int) $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'borrowed' AND due_on < DATE('now')")->fetchColumn();
$engagementRate = $memberCount > 0 ? round(($loanCount / $memberCount) * 100) : 0;

$loanRepository = new LoanRepository($pdo);
$recentLoans = array_slice($loanRepository->all(), 0, 5);
$userSessionRepository = new UserSessionRepository($pdo);
$staffSamples = $userSessionRepository->recent(4, ['Admin', 'Super Admin']);
$studentSamples = $userSessionRepository->recent(4, ['Admin', 'Super Admin'], false);
$quickActions = [
    ['label' => 'Add Book', 'href' => '/admin/books.php', 'icon' => 'bi-book'],
    ['label' => 'Register Member', 'href' => '/admin/members.php', 'icon' => 'bi-person-plus'],
    ['label' => 'Approve Loans', 'href' => '/admin/loans.php', 'icon' => 'bi-arrow-left-right'],
    ['label' => 'View Reports', 'href' => '/admin/reports.php', 'icon' => 'bi-graph-up'],
];

admin_header('Library Admin Dashboard', 'dashboard');
?>
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card card-metric h-100">
            <div class="metric-label">Books</div>
            <div class="d-flex justify-content-between align-items-end">
                <div class="metric-value"><?php echo number_format($bookCount); ?></div>
                <i class="bi bi-book-half text-primary fs-3"></i>
            </div>
            <small class="text-muted">Catalog items across all branches</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-metric h-100">
            <div class="metric-label">Members</div>
            <div class="d-flex justify-content-between align-items-end">
                <div class="metric-value"><?php echo number_format($memberCount); ?></div>
                <i class="bi bi-people text-success fs-3"></i>
            </div>
            <small class="text-muted">Active students, staff, and alumni</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-metric h-100">
            <div class="metric-label">Active Loans</div>
            <div class="d-flex justify-content-between align-items-end">
                <div class="metric-value"><?php echo number_format($loanCount); ?></div>
                <i class="bi bi-arrow-left-right text-warning fs-3"></i>
            </div>
            <small class="text-muted"><?php echo $engagementRate; ?>% of members engaged</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-metric h-100">
            <div class="metric-label">Due Soon</div>
            <div class="d-flex justify-content-between align-items-end">
                <div class="metric-value"><?php echo number_format($overdueCount); ?></div>
                <i class="bi bi-alarm text-danger fs-3"></i>
            </div>
            <small class="text-muted">Loans past due today</small>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">User sign-ins</h5>
                    <small class="text-muted">Sample activity from staff and admins</small>
                </div>
                <span class="badge badge-soft">Live Preview</span>
            </div>
            <div class="card-body">
                <?php foreach ($staffSamples as $sample): ?>
                    <div class="activity-row">
                        <div class="avatar-chip"><?php echo htmlspecialchars(badge_initials($sample['full_name'])); ?></div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between flex-wrap gap-2">
                                <div>
                                    <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($sample['full_name']); ?></p>
                                    <small class="text-muted"><?php echo htmlspecialchars($sample['role']); ?> • <?php echo htmlspecialchars($sample['identifier']); ?></small>
                                </div>
                                <small class="text-muted"><?php echo htmlspecialchars(format_session_time($sample['last_login_at'] ?? null)); ?></small>
                            </div>
                            <p class="mb-0 text-body-secondary"><?php echo htmlspecialchars($sample['usage_summary']); ?></p>
                            <span class="badge text-bg-light text-dark mt-2"><?php echo htmlspecialchars($sample['channel']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card h-100 mb-4 mb-xl-0">
            <div class="card-header">
                <h5 class="mb-0">Command center</h5>
                <small class="text-muted">Launch common workflows</small>
            </div>
            <div class="card-body quick-actions">
                <div class="row g-3">
                    <?php foreach ($quickActions as $action): ?>
                        <div class="col-sm-6">
                            <a class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center gap-2" href="<?php echo $action['href']; ?>">
                                <i class="bi <?php echo $action['icon']; ?>"></i>
                                <span><?php echo htmlspecialchars($action['label']); ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Loans</h5>
                <a class="btn btn-sm btn-outline-primary" href="/admin/loans.php">Manage Loans</a>
            </div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Member</th>
                        <th>Book</th>
                        <th>Borrowed</th>
                        <th>Due</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (! $recentLoans): ?>
                        <tr><td colspan="5" class="text-muted">No loans yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentLoans as $loan): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($loan['member_name']); ?></td>
                                <td><?php echo htmlspecialchars($loan['book_title']); ?></td>
                                <td><?php echo htmlspecialchars($loan['borrowed_on']); ?></td>
                                <td><?php echo htmlspecialchars($loan['due_on']); ?></td>
                                <td><span class="badge bg-<?php echo $loan['status'] === 'borrowed' ? 'warning text-dark' : 'success'; ?>"><?php echo htmlspecialchars($loan['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Member spotlight</h5>
                    <small class="text-muted">Sample journeys from students</small>
                </div>
                <span class="badge text-bg-primary">Stories</span>
            </div>
            <div class="card-body">
                <?php foreach ($studentSamples as $sample): ?>
                    <div class="activity-row">
                        <div class="avatar-chip"><?php echo htmlspecialchars(badge_initials($sample['full_name'])); ?></div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between flex-wrap gap-2">
                                <div>
                                    <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($sample['full_name']); ?></p>
                                    <small class="text-muted"><?php echo htmlspecialchars($sample['identifier']); ?></small>
                                </div>
                                <small class="text-muted"><?php echo htmlspecialchars(format_session_time($sample['last_login_at'] ?? null)); ?></small>
                            </div>
                            <p class="mb-0 text-body-secondary"><?php echo htmlspecialchars($sample['usage_summary']); ?></p>
                            <span class="badge text-bg-light text-dark mt-2"><?php echo htmlspecialchars($sample['channel']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php admin_footer(); ?>
