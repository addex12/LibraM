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
            max-width: 460px;
            margin: 4rem auto;
            border: none;
            border-radius: 1.5rem;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.35);
            overflow: hidden;
        }
        .login-card .card-header {
            background: linear-gradient(120deg, rgba(255,255,255,0.08), rgba(255,255,255,0));
        }
        .sample-credentials div + div {
            margin-top: 0.75rem;
            border-top: 1px dashed #dbe1ff;
            padding-top: 0.75rem;
        }
        .credential-tag {
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
        }
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
