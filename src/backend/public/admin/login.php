<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144198
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/includes/auth.php';

if (admin_logged_in()) {
    header('Location: /admin/index.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (! $username || ! $password) {
        $error = 'Username and password are required.';
    } elseif (attempt_admin_login($username, $password)) {
        header('Location: /admin/index.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Library Admin Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f5f6f8; }
        .login-card { max-width: 420px; margin: 5rem auto; box-shadow: 0 1px 3px rgba(0,0,0,0.12); }
    </style>
</head>
<body>
<div class="container">
    <div class="card login-card">
        <div class="card-header text-center">
            <h4 class="mb-0">Library Admin Portal</h4>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Sign In</button>
            </form>
        </div>
        <div class="card-footer text-center text-muted small">
            Need help? Contact the ICT office to reset your credentials.
        </div>
    </div>
</div>
</body>
</html>
