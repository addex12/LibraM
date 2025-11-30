<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

use App\Services\PortalAuthenticator;

session_start();

require_once __DIR__ . '/includes/portal.php';

$pdo = require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/admin/includes/auth.php';

$authenticator = new PortalAuthenticator($pdo);
$error = null;
$accountHint = $_GET['account'] ?? null;
$accountHint = in_array($accountHint, ['member', 'staff', 'admin'], true) ? $accountHint : null;

if ($accountHint === null) {
    if (admin_logged_in()) {
        header('Location: /admin/index.php');
        exit;
    }
    if (staff_session()) {
        header('Location: /staff/dashboard.php');
        exit;
    }
    if (member_session()) {
        header('Location: /member/dashboard.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($identifier === '' || $password === '') {
            throw new RuntimeException('Identifier and password are required.');
        }

        $redirect = null;

        if (attempt_admin_login($identifier, $password)) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Welcome back, ' . admin_username() . '.'];
            $redirect = '/admin/index.php';
        } else {
            $normalized = normalize_identifier($identifier);
            $staff = $authenticator->authenticateStaff($normalized, $password);
            if ($staff) {
                $_SESSION['staff_auth'] = [
                    'id' => (int) $staff['id'],
                    'employee_id' => $staff['employee_id'],
                    'full_name' => $staff['full_name'],
                    'role' => $staff['role'],
                    'email' => $staff['email'] ?? null,
                    'branch_id' => $staff['branch_id'] ?? null,
                ];
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Signed in as ' . $staff['full_name'] . '.'];
                $redirect = '/staff/dashboard.php';
            } else {
                $member = $authenticator->authenticateMember($normalized, $password);
                if (! $member) {
                    throw new RuntimeException('We could not match those credentials to any account.');
                }
                $_SESSION['member_auth'] = [
                    'id' => (int) $member['id'],
                    'student_id' => $member['student_id'],
                    'full_name' => $member['full_name'],
                    'faculty' => $member['faculty'],
                    'email' => $member['email'],
                ];
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Welcome back, ' . $member['full_name'] . '.'];
                $redirect = '/member/dashboard.php';
            }
        }

        header('Location: ' . $redirect);
        exit;
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

$identifierLabel = 'Identifier';
$identifierPlaceholder = 'Enter your student ID, employee ID or admin username';
if ($accountHint === 'staff') {
    $identifierLabel = 'Employee ID';
    $identifierPlaceholder = 'e.g., STF-1001';
} elseif ($accountHint === 'admin') {
    $identifierLabel = 'Username';
    $identifierPlaceholder = 'e.g., librarian';
} elseif ($accountHint === 'member') {
    $identifierLabel = 'Student ID';
    $identifierPlaceholder = 'e.g., UGR/1234/13';
}

function normalize_identifier(string $value): string
{
    return strtoupper(trim($value));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LibraM Unified Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background: radial-gradient(circle at top, #1f61d1, #111c44);
            min-height: 100vh;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .login-card {
            max-width: 500px;
            margin: 4rem auto;
            border: none;
            border-radius: 1.5rem;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.35);
            overflow: hidden;
        }
        .login-card .card-header {
            background: linear-gradient(120deg, rgba(255,255,255,0.08), rgba(255,255,255,0));
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card login-card">
        <div class="card-header text-center">
            <p class="text-uppercase text-muted small mb-1">LibraM Access Portal</p>
            <h4 class="mb-0">Sign in to continue</h4>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label"><?php echo htmlspecialchars($identifierLabel); ?></label>
                    <input type="text" name="identifier" class="form-control" placeholder="<?php echo htmlspecialchars($identifierPlaceholder); ?>" required>
                    <small class="text-muted">Use the ID or username you normally sign in with. We will route you automatically.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Sign In</button>
            </form>
        </div>
        <div class="card-footer text-center text-muted small">
            Need help? Contact the ICT office or visit the service desk.
            <div class="mt-2"><a class="link-light" href="/">Return to LibraM home</a></div>
        </div>
    </div>
</div>
</body>
</html>
