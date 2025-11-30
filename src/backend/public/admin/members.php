<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

use App\Repositories\MemberRepository;
use App\Services\PortalAuthenticator;

$pdo = require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_admin_login();
require __DIR__ . '/includes/layout.php';

$memberRepository = new MemberRepository($pdo);
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    try {
        if ($action === 'create') {
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            if ($password === '' || $confirmPassword === '') {
                throw new RuntimeException('Password and confirmation are required.');
            }
            if (strlen($password) < 8) {
                throw new RuntimeException('Password must be at least 8 characters.');
            }
            if ($password !== $confirmPassword) {
                throw new RuntimeException('Passwords do not match.');
            }
            $memberRepository->create([
                'student_id' => trim($_POST['student_id'] ?? ''),
                'full_name' => trim($_POST['full_name'] ?? ''),
                'faculty' => trim($_POST['faculty'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'password_hash' => PortalAuthenticator::hashPassword($password),
            ]);
            header('Location: /admin/members.php?message=' . urlencode('Member added successfully.'));
            exit;
        }

        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $payload = [
                'student_id' => trim($_POST['student_id'] ?? ''),
                'full_name' => trim($_POST['full_name'] ?? ''),
                'faculty' => trim($_POST['faculty'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
            ];
            if ($password !== '' || $confirmPassword !== '') {
                if (strlen($password) < 8) {
                    throw new RuntimeException('Password must be at least 8 characters.');
                }
                if ($password !== $confirmPassword) {
                    throw new RuntimeException('Passwords do not match.');
                }
                $payload['password_hash'] = PortalAuthenticator::hashPassword($password);
            }
            $memberRepository->update($id, $payload);
            header('Location: /admin/members.php?message=' . urlencode('Member updated successfully.'));
            exit;
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $memberRepository->delete($id);
            header('Location: /admin/members.php?message=' . urlencode('Member deleted.'));
            exit;
        }
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$memberToEdit = $editId ? $memberRepository->find($editId) : null;
$members = $memberRepository->all();

admin_header('Members', 'members');
admin_alert($_GET['message'] ?? $message);
admin_alert($_GET['error'] ?? $error, 'danger');
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><?php echo $memberToEdit ? 'Edit Member' : 'Add Member'; ?></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="<?php echo $memberToEdit ? 'update' : 'create'; ?>">
                    <?php if ($memberToEdit): ?>
                        <input type="hidden" name="id" value="<?php echo $memberToEdit['id']; ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Student ID</label>
                        <input type="text" name="student_id" class="form-control" value="<?php echo htmlspecialchars($memberToEdit['student_id'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($memberToEdit['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Faculty</label>
                        <input type="text" name="faculty" class="form-control" value="<?php echo htmlspecialchars($memberToEdit['faculty'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($memberToEdit['email'] ?? ''); ?>" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label class="form-label">Portal Password</label>
                            <input type="password" name="password" class="form-control" minlength="8" <?php echo $memberToEdit ? '' : 'required'; ?>>
                            <small class="text-muted"><?php echo $memberToEdit ? 'Leave blank to keep the current password.' : 'Required for new members.'; ?></small>
                        </div>
                        <div class="col">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" minlength="8" <?php echo $memberToEdit ? '' : 'required'; ?>>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-primary" type="submit"><?php echo $memberToEdit ? 'Update Member' : 'Add Member'; ?></button>
                        <?php if ($memberToEdit): ?>
                            <a class="btn btn-outline-secondary" href="/admin/members.php">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Members List</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Student ID</th>
                        <th>Faculty</th>
                        <th>Email</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (! $members): ?>
                        <tr><td colspan="5" class="text-muted">No members yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($member['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($member['faculty']); ?></td>
                                <td><a href="mailto:<?php echo htmlspecialchars($member['email']); ?>"><?php echo htmlspecialchars($member['email']); ?></a></td>
                                <td class="table-actions">
                                    <a class="btn btn-sm btn-outline-primary" href="/admin/members.php?edit=<?php echo $member['id']; ?>">Edit</a>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this member?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
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
