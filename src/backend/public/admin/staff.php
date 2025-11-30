<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144198
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

use App\Repositories\BranchRepository;
use App\Repositories\StaffRepository;

$pdo = require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_admin_login();
require __DIR__ . '/includes/layout.php';

$branchRepository = new BranchRepository($pdo);
$staffRepository = new StaffRepository($pdo);
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    try {
        $payload = [
            'employee_id' => strtoupper(trim($_POST['employee_id'] ?? '')),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'role' => trim($_POST['role'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'branch_id' => $_POST['branch_id'] !== '' ? (int) $_POST['branch_id'] : null,
        ];
        if ($action === 'create') {
            $staffRepository->create($payload);
            header('Location: /admin/staff.php?message=' . urlencode('Staff member added.'));
            exit;
        }
        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            $staffRepository->update($id, $payload);
            header('Location: /admin/staff.php?message=' . urlencode('Staff member updated.'));
            exit;
        }
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $staffRepository->delete($id);
            header('Location: /admin/staff.php?message=' . urlencode('Staff record removed.'));
            exit;
        }
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

$branches = $branchRepository->all();
$branchFilter = isset($_GET['branch']) && $_GET['branch'] !== '' ? (int) $_GET['branch'] : null;
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$staffToEdit = $editId ? $staffRepository->find($editId) : null;
$directory = $staffRepository->all();
if ($branchFilter) {
    $staff = array_values(array_filter($directory, static fn(array $row) => (int) ($row['branch_id'] ?? 0) === $branchFilter));
} else {
    $staff = $directory;
}

admin_header('Staff', 'staff');
admin_alert($_GET['message'] ?? $message);
admin_alert($_GET['error'] ?? $error, 'danger');
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><?php echo $staffToEdit ? 'Edit Staff' : 'Add Staff'; ?></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="<?php echo $staffToEdit ? 'update' : 'create'; ?>">
                    <?php if ($staffToEdit): ?>
                        <input type="hidden" name="id" value="<?php echo $staffToEdit['id']; ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Employee ID</label>
                        <input type="text" name="employee_id" class="form-control" maxlength="12" value="<?php echo htmlspecialchars($staffToEdit['employee_id'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($staffToEdit['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role / Title</label>
                        <input type="text" name="role" class="form-control" value="<?php echo htmlspecialchars($staffToEdit['role'] ?? ''); ?>" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($staffToEdit['email'] ?? ''); ?>">
                        </div>
                        <div class="col">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($staffToEdit['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Branch</label>
                        <select class="form-select" name="branch_id">
                            <option value="">Unassigned</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" <?php echo ((int) ($staffToEdit['branch_id'] ?? 0) === (int) $branch['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($branch['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-primary" type="submit"><?php echo $staffToEdit ? 'Update Staff' : 'Add Staff'; ?></button>
                        <?php if ($staffToEdit): ?>
                            <a class="btn btn-outline-secondary" href="/admin/staff.php">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-body">
                <form class="row g-2" method="get">
                    <div class="col">
                        <select class="form-select" name="branch">
                            <option value="">All branches</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" <?php echo $branchFilter === (int) $branch['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($branch['name']); ?></option>
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
            <div class="card-header">Staff Directory</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Employee ID</th>
                        <th>Branch</th>
                        <th>Contact</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (! $staff): ?>
                        <tr><td colspan="5" class="text-muted">No staff members yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($staff as $person): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($person['full_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($person['role']); ?></small>
                                </td>
                                <td><span class="badge text-bg-secondary"><?php echo htmlspecialchars($person['employee_id']); ?></span></td>
                                <td><?php echo htmlspecialchars($person['branch_name'] ?? 'Unassigned'); ?></td>
                                <td>
                                    <?php if ($person['email']): ?>
                                        <div><?php echo htmlspecialchars($person['email']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($person['phone']): ?>
                                        <div><?php echo htmlspecialchars($person['phone']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <a class="btn btn-sm btn-outline-primary" href="/admin/staff.php?edit=<?php echo $person['id']; ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this staff record?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $person['id']; ?>">
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
