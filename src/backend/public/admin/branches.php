<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

use App\Repositories\BranchRepository;

$pdo = require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_admin_login();
require __DIR__ . '/includes/layout.php';

$branchRepository = new BranchRepository($pdo);
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    try {
        if ($action === 'create') {
            $branchRepository->create([
                'code' => strtoupper(trim($_POST['code'] ?? '')),
                'name' => trim($_POST['name'] ?? ''),
                'location' => trim($_POST['location'] ?? ''),
                'contact_email' => trim($_POST['contact_email'] ?? ''),
                'contact_phone' => trim($_POST['contact_phone'] ?? ''),
                'hours' => trim($_POST['hours'] ?? ''),
            ]);
            header('Location: /admin/branches.php?message=' . urlencode('Branch added successfully.'));
            exit;
        }

        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            $branchRepository->update($id, [
                'code' => strtoupper(trim($_POST['code'] ?? '')),
                'name' => trim($_POST['name'] ?? ''),
                'location' => trim($_POST['location'] ?? ''),
                'contact_email' => trim($_POST['contact_email'] ?? ''),
                'contact_phone' => trim($_POST['contact_phone'] ?? ''),
                'hours' => trim($_POST['hours'] ?? ''),
            ]);
            header('Location: /admin/branches.php?message=' . urlencode('Branch updated successfully.'));
            exit;
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $branchRepository->delete($id);
            header('Location: /admin/branches.php?message=' . urlencode('Branch deleted.'));
            exit;
        }
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$branchToEdit = $editId ? $branchRepository->find($editId) : null;
$branches = $branchRepository->all();

admin_header('Branches', 'branches');
admin_alert($_GET['message'] ?? $message);
admin_alert($_GET['error'] ?? $error, 'danger');
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><?php echo $branchToEdit ? 'Edit Branch' : 'Add Branch'; ?></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="<?php echo $branchToEdit ? 'update' : 'create'; ?>">
                    <?php if ($branchToEdit): ?>
                        <input type="hidden" name="id" value="<?php echo $branchToEdit['id']; ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" class="form-control" maxlength="10" value="<?php echo htmlspecialchars($branchToEdit['code'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($branchToEdit['name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location / Address</label>
                        <textarea name="location" class="form-control" rows="2"><?php echo htmlspecialchars($branchToEdit['location'] ?? ''); ?></textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label class="form-label">Contact Email</label>
                            <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($branchToEdit['contact_email'] ?? ''); ?>">
                        </div>
                        <div class="col">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($branchToEdit['contact_phone'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Opening Hours</label>
                        <textarea name="hours" class="form-control" rows="2" placeholder="Mon-Fri 8am-6pm"><?php echo htmlspecialchars($branchToEdit['hours'] ?? ''); ?></textarea>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-primary" type="submit"><?php echo $branchToEdit ? 'Update Branch' : 'Add Branch'; ?></button>
                        <?php if ($branchToEdit): ?>
                            <a class="btn btn-outline-secondary" href="/admin/branches.php">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Branch Directory</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Contact</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (! $branches): ?>
                        <tr><td colspan="4" class="text-muted">No branches yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($branches as $branch): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($branch['name']); ?></strong><br>
                                    <small class="text-muted"><?php echo nl2br(htmlspecialchars($branch['location'] ?? '')); ?></small>
                                </td>
                                <td><span class="badge text-bg-primary"><?php echo htmlspecialchars($branch['code']); ?></span></td>
                                <td>
                                    <?php if ($branch['contact_email']): ?>
                                        <div><?php echo htmlspecialchars($branch['contact_email']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($branch['contact_phone']): ?>
                                        <div><?php echo htmlspecialchars($branch['contact_phone']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <a class="btn btn-sm btn-outline-primary" href="/admin/branches.php?edit=<?php echo $branch['id']; ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this branch?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $branch['id']; ?>">
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
