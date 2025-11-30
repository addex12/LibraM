<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

require __DIR__ . '/includes/auth.php';

admin_logout();
header('Location: /login.php?account=admin');
exit;
