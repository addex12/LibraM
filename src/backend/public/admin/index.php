<?php

declare(strict_types=1);

use App\Repositories\LoanRepository;

$pdo = require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_admin_login();
require __DIR__ . '/includes/layout.php';

$bookCount = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$memberCount = (int) $pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
$loanCount = (int) $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'borrowed'")->fetchColumn();

$loanRepository = new LoanRepository($pdo);
$recentLoans = array_slice($loanRepository->all(), 0, 5);

admin_header('Library Admin Dashboard', 'dashboard');
?>
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase">Books</h6>
                <p class="display-6 fw-bold mb-0"><?php echo $bookCount; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase">Members</h6>
                <p class="display-6 fw-bold mb-0"><?php echo $memberCount; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase">Active Loans</h6>
                <p class="display-6 fw-bold mb-0"><?php echo $loanCount; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card">
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
<?php admin_footer(); ?>
