<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144198
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

use App\Repositories\SubjectRepository;

$pdo = require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_admin_login();
require __DIR__ . '/includes/layout.php';

$subjectRepository = new SubjectRepository($pdo);
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    try {
        $payload = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
        ];
        if ($action === 'create') {
            $subjectRepository->create($payload);
            header('Location: /admin/subjects.php?message=' . urlencode('Subject added.'));
            exit;
        }
        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            $subjectRepository->update($id, $payload);
            header('Location: /admin/subjects.php?message=' . urlencode('Subject updated.'));
            exit;
        }
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $subjectRepository->delete($id);
            header('Location: /admin/subjects.php?message=' . urlencode('Subject removed.'));
            exit;
        }
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$subjectToEdit = $editId ? $subjectRepository->find($editId) : null;
$subjects = $subjectRepository->all();

admin_header('Subjects', 'subjects');
admin_alert($_GET['message'] ?? $message);
admin_alert($_GET['error'] ?? $error, 'danger');
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><?php echo $subjectToEdit ? 'Edit Subject' : 'Add Subject'; ?></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="<?php echo $subjectToEdit ? 'update' : 'create'; ?>">
                    <?php if ($subjectToEdit): ?>
                        <input type="hidden" name="id" value="<?php echo $subjectToEdit['id']; ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($subjectToEdit['name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($subjectToEdit['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-primary" type="submit"><?php echo $subjectToEdit ? 'Update Subject' : 'Add Subject'; ?></button>
                        <?php if ($subjectToEdit): ?>
                            <a class="btn btn-outline-secondary" href="/admin/subjects.php">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Subject List</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (! $subjects): ?>
                        <tr><td colspan="3" class="text-muted">No subjects yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($subjects as $subject): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($subject['description'] ?? '')); ?></td>
                                <td class="table-actions">
                                    <a class="btn btn-sm btn-outline-primary" href="/admin/subjects.php?edit=<?php echo $subject['id']; ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this subject?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $subject['id']; ?>">
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
