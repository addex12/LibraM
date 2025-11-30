<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

use App\Repositories\FineRepository;
use App\Repositories\LoanRepository;
use RuntimeException;
use Throwable;

$pdo = require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_admin_login();
require __DIR__ . '/includes/layout.php';

$fineRepository = new FineRepository($pdo);
$loanRepository = new LoanRepository($pdo);
$message = null;
$error = null;

$statusLabels = [
    'unpaid' => 'Unpaid',
    'paid' => 'Paid',
    'waived' => 'Waived',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    try {
        if ($action === 'create') {
            $loanId = (int) ($_POST['loan_id'] ?? 0);
            $loan = $loanRepository->find($loanId);
            if (! $loan) {
                throw new RuntimeException('Loan not found.');
            }
            $status = $_POST['status'] ?? 'unpaid';
            $payload = [
                'loan_id' => $loanId,
                'member_id' => (int) $loan['member_id'],
                'amount' => (float) ($_POST['amount'] ?? 0),
                'reason' => trim($_POST['reason'] ?? ''),
                'status' => $status,
            ];
            if ($status === 'paid') {
                $payload['settled_on'] = date('Y-m-d H:i:s');
            }
            $fineRepository->create($payload);
            header('Location: /admin/fines.php?message=' . urlencode('Fine recorded.'));
            exit;
        }

        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? 'unpaid';
            $payload = [
                'amount' => (float) ($_POST['amount'] ?? 0),
                'reason' => trim($_POST['reason'] ?? ''),
                'status' => $status,
            ];
            if ($status === 'paid') {
                $payload['settled_on'] = $_POST['settled_on'] ?: date('Y-m-d H:i:s');
            } elseif ($status === 'unpaid') {
                $payload['settled_on'] = null;
            }
            $fineRepository->update($id, $payload);
            header('Location: /admin/fines.php?message=' . urlencode('Fine updated.'));
            exit;
        }

        if ($action === 'mark-paid') {
            $id = (int) ($_POST['id'] ?? 0);
            $fineRepository->markPaid($id);
            header('Location: /admin/fines.php?message=' . urlencode('Fine marked as paid.'));
            exit;
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $fineRepository->delete($id);
            header('Location: /admin/fines.php?message=' . urlencode('Fine removed.'));
            exit;
        }
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

$loanOptions = [];
foreach ($loanRepository->all() as $loan) {
    $label = sprintf(
        '%s (%s) - due %s',
        $loan['book_title'] ?? ('Loan #' . $loan['id']),
        $loan['member_name'] ?? ('Member #' . $loan['member_id']),
        $loan['due_on'] ?? 'n/a'
    );
    $loanOptions[$loan['id']] = $label;
}

$statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
$fines = $fineRepository->all($statusFilter);
$unpaidTotal = array_reduce($fineRepository->all('unpaid'), static function (float $carry, array $fine): float {
    return $carry + (float) $fine['amount'];
}, 0.0);

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$fineToEdit = $editId ? $fineRepository->find($editId) : null;

admin_header('Fines', 'fines');
admin_alert($_GET['message'] ?? $message);
admin_alert($_GET['error'] ?? $error, 'danger');
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><?php echo $fineToEdit ? 'Edit Fine' : 'Add Fine'; ?></div>
            <div class="card-body">
                <?php if (! $loanOptions && ! $fineToEdit): ?>
                    <p class="text-muted">Loans are required before assessing fines.</p>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="action" value="<?php echo $fineToEdit ? 'update' : 'create'; ?>">
                        <?php if ($fineToEdit): ?>
                            <input type="hidden" name="id" value="<?php echo $fineToEdit['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Loan</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($loanOptions[$fineToEdit['loan_id']] ?? ('Loan #' . $fineToEdit['loan_id'])); ?>" disabled>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <label class="form-label">Loan</label>
                                <select class="form-select" name="loan_id" required>
                                    <option value="">Select loan</option>
                                    <?php foreach ($loanOptions as $loanId => $label): ?>
                                        <option value="<?php echo $loanId; ?>"><?php echo htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Amount (USD)</label>
                            <input type="number" step="0.01" min="0" name="amount" class="form-control" value="<?php echo htmlspecialchars((string) ($fineToEdit['amount'] ?? '0.00')); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <textarea name="reason" class="form-control" rows="3"><?php echo htmlspecialchars($fineToEdit['reason'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <?php foreach ($statusLabels as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo (($fineToEdit['status'] ?? 'unpaid') === $value) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($fineToEdit): ?>
                            <input type="hidden" name="settled_on" value="<?php echo htmlspecialchars($fineToEdit['settled_on'] ?? ''); ?>">
                        <?php endif; ?>
                        <div class="d-flex justify-content-between">
                            <button class="btn btn-primary" type="submit"><?php echo $fineToEdit ? 'Update Fine' : 'Add Fine'; ?></button>
                            <?php if ($fineToEdit): ?>
                                <a class="btn btn-outline-secondary" href="/admin/fines.php">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Outstanding fines</strong>
                        <div class="text-muted small">Unpaid total across all members.</div>
                    </div>
                    <span class="fs-5">$<?php echo number_format($unpaidTotal, 2); ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-body">
                <form class="row g-2" method="get">
                    <div class="col">
                        <select class="form-select" name="status">
                            <option value="">All statuses</option>
                            <?php foreach ($statusLabels as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo $statusFilter === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-secondary" type="submit">Filter</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Fine Ledger</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Book / Member</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Timeline</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (! $fines): ?>
                        <tr><td colspan="5" class="text-muted">No fines recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($fines as $fine): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($fine['book_title'] ?? ('Loan #' . $fine['loan_id'])); ?></strong><br>
                                    <small class="text-muted">Member: <?php echo htmlspecialchars($fine['member_name'] ?? ('ID ' . $fine['member_id'])); ?></small>
                                </td>
                                <td>$<?php echo number_format((float) $fine['amount'], 2); ?></td>
                                <td><span class="badge text-bg-<?php echo $fine['status'] === 'paid' ? 'success' : ($fine['status'] === 'waived' ? 'secondary' : 'warning'); ?>"><?php echo htmlspecialchars($statusLabels[$fine['status']] ?? $fine['status']); ?></span></td>
                                <td>
                                    <div><small class="text-muted">Assessed:</small> <?php echo htmlspecialchars($fine['assessed_on']); ?></div>
                                    <?php if ($fine['settled_on']): ?>
                                        <div><small class="text-muted">Settled:</small> <?php echo htmlspecialchars($fine['settled_on']); ?></div>
                                    <?php elseif ($fine['status'] === 'unpaid' && $fine['due_on']): ?>
                                        <div><small class="text-muted">Loan due:</small> <?php echo htmlspecialchars($fine['due_on']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <a class="btn btn-sm btn-outline-primary" href="/admin/fines.php?edit=<?php echo $fine['id']; ?>">Edit</a>
                                    <?php if ($fine['status'] === 'unpaid'): ?>
                                        <form method="post" style="display:inline" onsubmit="return confirm('Mark this fine as paid?');">
                                            <input type="hidden" name="action" value="mark-paid">
                                            <input type="hidden" name="id" value="<?php echo $fine['id']; ?>">
                                            <button class="btn btn-sm btn-outline-success" type="submit">Mark Paid</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this fine?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $fine['id']; ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
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
