<?php

declare(strict_types=1);

use App\Repositories\BranchRepository;
use App\Repositories\ShelfRepository;

$pdo = require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_admin_login();
require __DIR__ . '/includes/layout.php';

$branchRepository = new BranchRepository($pdo);
$shelfRepository = new ShelfRepository($pdo);
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    try {
        $payload = [
            'branch_id' => (int) ($_POST['branch_id'] ?? 0),
            'code' => strtoupper(trim($_POST['code'] ?? '')),
            'label' => trim($_POST['label'] ?? ''),
            'floor' => trim($_POST['floor'] ?? ''),
            'capacity' => $_POST['capacity'] !== '' ? (int) $_POST['capacity'] : null,
        ];
        if ($action === 'create') {
            $shelfRepository->create($payload);
            header('Location: /admin/shelves.php?message=' . urlencode('Shelf added successfully.'));
            exit;
        }
        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            $shelfRepository->update($id, $payload);
            header('Location: /admin/shelves.php?message=' . urlencode('Shelf updated successfully.'));
            exit;
        }
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $shelfRepository->delete($id);
            header('Location: /admin/shelves.php?message=' . urlencode('Shelf removed.'));
            exit;
        }
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

$branches = $branchRepository->all();
$branchFilter = isset($_GET['branch']) && $_GET['branch'] !== '' ? (int) $_GET['branch'] : null;
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$shelfToEdit = $editId ? $shelfRepository->find($editId) : null;
$allShelves = $shelfRepository->all();
if ($branchFilter) {
    $shelves = array_values(array_filter($allShelves, static fn(array $row) => (int) $row['branch_id'] === $branchFilter));
} else {
    $shelves = $allShelves;
}

admin_header('Shelves', 'shelves');
admin_alert($_GET['message'] ?? $message);
admin_alert($_GET['error'] ?? $error, 'danger');
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><?php echo $shelfToEdit ? 'Edit Shelf' : 'Add Shelf'; ?></div>
            <div class="card-body">
                <?php if (! $branches): ?>
                    <p class="text-muted">Create a branch first before adding shelves.</p>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="action" value="<?php echo $shelfToEdit ? 'update' : 'create'; ?>">
                        <?php if ($shelfToEdit): ?>
                            <input type="hidden" name="id" value="<?php echo $shelfToEdit['id']; ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select" required>
                                <option value="">Select branch</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>" <?php echo ((int) ($shelfToEdit['branch_id'] ?? 0) === (int) $branch['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($branch['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Shelf Code</label>
                            <input type="text" name="code" class="form-control" maxlength="10" value="<?php echo htmlspecialchars($shelfToEdit['code'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Label / Description</label>
                            <input type="text" name="label" class="form-control" value="<?php echo htmlspecialchars($shelfToEdit['label'] ?? ''); ?>" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col">
                                <label class="form-label">Floor</label>
                                <input type="text" name="floor" class="form-control" value="<?php echo htmlspecialchars($shelfToEdit['floor'] ?? ''); ?>">
                            </div>
                            <div class="col">
                                <label class="form-label">Capacity</label>
                                <input type="number" min="0" name="capacity" class="form-control" value="<?php echo htmlspecialchars((string) ($shelfToEdit['capacity'] ?? '')); ?>">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button class="btn btn-primary" type="submit"><?php echo $shelfToEdit ? 'Update Shelf' : 'Add Shelf'; ?></button>
                            <?php if ($shelfToEdit): ?>
                                <a class="btn btn-outline-secondary" href="/admin/shelves.php">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php endif; ?>
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
            <div class="card-header">Shelf Directory</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Shelf</th>
                        <th>Branch</th>
                        <th>Floor</th>
                        <th>Capacity</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (! $shelves): ?>
                        <tr><td colspan="5" class="text-muted">No shelves defined.</td></tr>
                    <?php else: ?>
                        <?php foreach ($shelves as $shelf): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($shelf['label']); ?></strong><br>
                                    <small class="text-muted">Code: <?php echo htmlspecialchars($shelf['code']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($shelf['branch_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($shelf['floor'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars((string) ($shelf['capacity'] ?? '')); ?></td>
                                <td class="table-actions">
                                    <a class="btn btn-sm btn-outline-primary" href="/admin/shelves.php?edit=<?php echo $shelf['id']; ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this shelf?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $shelf['id']; ?>">
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
