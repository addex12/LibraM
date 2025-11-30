<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144198
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

use App\Repositories\BookRepository;
use App\Repositories\LoanRepository;
use App\Repositories\MemberRepository;

$pdo = require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_admin_login();
require __DIR__ . '/includes/layout.php';

$bookRepository = new BookRepository($pdo);
$memberRepository = new MemberRepository($pdo);
$loanRepository = new LoanRepository($pdo);
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    try {
        if ($action === 'create') {
            $loanRepository->create([
                'book_id' => (int) $_POST['book_id'],
                'member_id' => (int) $_POST['member_id'],
                'borrowed_on' => $_POST['borrowed_on'] ?: date('Y-m-d'),
                'due_on' => $_POST['due_on'] ?: date('Y-m-d', strtotime('+14 days')),
                'status' => 'borrowed',
            ]);
            header('Location: /admin/loans.php?message=' . urlencode('Loan issued successfully.'));
            exit;
        }

        if ($action === 'close') {
            $loanId = (int) ($_POST['id'] ?? 0);
            $loanRepository->update($loanId, [
                'status' => 'returned',
                'returned_on' => date('Y-m-d'),
            ]);
            header('Location: /admin/loans.php?message=' . urlencode('Loan marked as returned.'));
            exit;
        }

        if ($action === 'renew') {
            $loanId = (int) ($_POST['id'] ?? 0);
            $newDueOn = $_POST['new_due_on'] ?: date('Y-m-d', strtotime('+7 days'));
            $loanRepository->renew($loanId, $newDueOn);
            header('Location: /admin/loans.php?message=' . urlencode('Loan renewed successfully.'));
            exit;
        }
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

$books = $bookRepository->all();
$members = $memberRepository->all();
$loans = $loanRepository->all();

admin_header('Loans', 'loans');
admin_alert($_GET['message'] ?? $message);
admin_alert($_GET['error'] ?? $error, 'danger');
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Issue New Loan</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Book</label>
                        <select class="form-select" name="book_id" required>
                            <option value="">Choose a book</option>
                            <?php foreach ($books as $book): ?>
                                <option value="<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']); ?> (<?php echo $book['copies_available']; ?> available)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Member</label>
                        <select class="form-select" name="member_id" required>
                            <option value="">Choose a member</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?php echo $member['id']; ?>"><?php echo htmlspecialchars($member['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col">
                            <label class="form-label">Borrowed On</label>
                            <input type="date" class="form-control" name="borrowed_on" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col">
                            <label class="form-label">Due On</label>
                            <input type="date" class="form-control" name="due_on" value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>">
                        </div>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary w-100" type="submit">Issue Loan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Active Loans</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Member</th>
                        <th>Book</th>
                        <th>Borrowed</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (! $loans): ?>
                        <tr><td colspan="6" class="text-muted">No loans recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($loans as $loan): ?>
                            <tr class="align-middle">
                                <td><?php echo htmlspecialchars($loan['member_name']); ?></td>
                                <td><?php echo htmlspecialchars($loan['book_title']); ?></td>
                                <td><?php echo htmlspecialchars($loan['borrowed_on']); ?></td>
                                <td><?php echo htmlspecialchars($loan['due_on']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $loan['status'] === 'borrowed' ? 'warning text-dark' : 'success'; ?>"><?php echo htmlspecialchars($loan['status']); ?></span>
                                </td>
                                <td>
                                    <?php if ($loan['status'] === 'borrowed'): ?>
                                        <div class="d-flex flex-wrap gap-2">
                                            <form method="post" onsubmit="return confirm('Mark as returned?');">
                                                <input type="hidden" name="action" value="close">
                                                <input type="hidden" name="id" value="<?php echo $loan['id']; ?>">
                                                <button class="btn btn-sm btn-outline-success" type="submit">Mark Returned</button>
                                            </form>
                                            <form method="post" class="d-flex gap-1">
                                                <input type="hidden" name="action" value="renew">
                                                <input type="hidden" name="id" value="<?php echo $loan['id']; ?>">
                                                <input type="date" class="form-control form-control-sm" name="new_due_on" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($loan['due_on'] . ' +7 days'))); ?>">
                                                <button class="btn btn-sm btn-outline-primary" type="submit">Renew</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <small class="text-muted">Returned on <?php echo htmlspecialchars($loan['returned_on']); ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php admin_footer(); ?>
