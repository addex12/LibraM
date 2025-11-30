<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

$pdo = require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_super_admin();
require __DIR__ . '/includes/layout.php';

$maintenanceTasks = [
    'notify-overdue' => [
        'label' => 'Queue overdue reminders',
        'script' => realpath(__DIR__ . '/../../scripts/notify-overdue.php') ?: __DIR__ . '/../../scripts/notify-overdue.php',
        'summary' => 'Scans active loans and enqueues reminder notifications for overdue borrowers.',
        'command' => 'composer notify-overdue',
    ],
    'seed-demo' => [
        'label' => 'Seed demo catalog + patrons',
        'script' => realpath(__DIR__ . '/../../scripts/seed.php') ?: __DIR__ . '/../../scripts/seed.php',
        'summary' => 'Populates books, members, and baseline loans for test environments.',
        'command' => 'php src/backend/scripts/seed.php',
    ],
];

$taskFeedback = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maintenance_task'])) {
    $taskKey = (string) $_POST['maintenance_task'];
    $taskFeedback = run_maintenance_task($taskKey, $maintenanceTasks);
}

$counts = [
    'books' => (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn(),
    'members' => (int) $pdo->query('SELECT COUNT(*) FROM members')->fetchColumn(),
    'loans' => (int) $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'borrowed'")->fetchColumn(),
];

$counts['branches'] = (int) $pdo->query('SELECT COUNT(*) FROM branches')->fetchColumn();
$counts['shelves'] = (int) $pdo->query('SELECT COUNT(*) FROM shelves')->fetchColumn();
$counts['staff'] = (int) $pdo->query('SELECT COUNT(*) FROM staff')->fetchColumn();
$counts['reservations_pending'] = (int) $pdo->query("SELECT COUNT(*) FROM reservations WHERE status IN ('pending','ready')")->fetchColumn();
$counts['reservations_ready'] = (int) $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'ready'")->fetchColumn();
$counts['fines_unpaid'] = (int) $pdo->query("SELECT COUNT(*) FROM fines WHERE status != 'paid'")->fetchColumn();
$counts['notifications_pending'] = (int) $pdo->query("SELECT COUNT(*) FROM notifications WHERE status = 'pending'")->fetchColumn();
$counts['notifications_failed'] = (int) $pdo->query("SELECT COUNT(*) FROM notifications WHERE status = 'failed'")->fetchColumn();

$fineTotalsRow = $pdo->query('SELECT
    COALESCE(SUM(CASE WHEN status = "unpaid" THEN amount ELSE 0 END), 0) AS outstanding,
    COALESCE(SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END), 0) AS collected
FROM fines')->fetch(PDO::FETCH_ASSOC) ?: ['outstanding' => 0, 'collected' => 0];
$fineTotals = [
    'outstanding' => isset($fineTotalsRow['outstanding']) ? (float) $fineTotalsRow['outstanding'] : 0.0,
    'collected' => isset($fineTotalsRow['collected']) ? (float) $fineTotalsRow['collected'] : 0.0,
];

$pendingReservations = fetch_records($pdo, 'SELECT r.id, b.title AS book_title, m.full_name AS member_name,
    r.status, r.queue_position, r.reserved_on, r.ready_on, r.expires_on
    FROM reservations r
    JOIN books b ON b.id = r.book_id
    JOIN members m ON m.id = r.member_id
    WHERE r.status IN ("pending","ready")
    ORDER BY CASE WHEN r.status = "ready" THEN 0 ELSE 1 END, r.queue_position ASC, r.reserved_on ASC
    LIMIT 5');

$fineWatchlist = fetch_records($pdo, 'SELECT f.id, m.full_name, f.amount, f.status, f.assessed_on
    FROM fines f
    JOIN members m ON m.id = f.member_id
    WHERE f.status != "paid"
    ORDER BY f.amount DESC, f.assessed_on ASC
    LIMIT 5');

$recentNotifications = fetch_records($pdo, 'SELECT n.id, m.full_name, n.type, n.channel, n.status, n.created_at
    FROM notifications n
    LEFT JOIN members m ON m.id = n.member_id
    ORDER BY n.created_at DESC
    LIMIT 5');

$primaryMetrics = [
    ['key' => 'books', 'label' => 'Catalog Records'],
    ['key' => 'members', 'label' => 'Registered Members'],
    ['key' => 'loans', 'label' => 'Active Loans'],
];

$infrastructureMetrics = [
    ['key' => 'branches', 'label' => 'Campus Branches'],
    ['key' => 'shelves', 'label' => 'Shelving Locations'],
    ['key' => 'staff', 'label' => 'Staff Accounts'],
];

$workflowMetrics = [
    ['key' => 'reservations_pending', 'label' => 'Reservations in Queue', 'hint' => sprintf('%d ready for pickup', $counts['reservations_ready'])],
    ['key' => 'fines_unpaid', 'label' => 'Open Fines', 'hint' => 'Outstanding ' . format_currency($fineTotals['outstanding'])],
    ['key' => 'notifications_pending', 'label' => 'Pending Notices', 'hint' => sprintf('%d failed deliveries', $counts['notifications_failed'])],
];

$logOptions = [
    'notifications' => 'notifications.log',
    'overdue' => 'overdue.log',
];
$logSelection = filter_input(INPUT_GET, 'log', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: null;
if (! $logSelection || ! isset($logOptions[$logSelection])) {
    $logSelection = is_file(__DIR__ . '/../../storage/logs/notifications.log') ? 'notifications' : 'overdue';
}
$logFileName = $logOptions[$logSelection];
$logPath = realpath(__DIR__ . '/../../storage/logs/' . $logFileName) ?: __DIR__ . '/../../storage/logs/' . $logFileName;
$logTail = tail_log($logPath, 12);
$phpMyAdminUrl = phpmyadmin_url();
$usingBundledAdminer = $phpMyAdminUrl === '/admin/tools/adminer-iframe.php';
$consoleLaunchUrl = null;
if ($phpMyAdminUrl) {
    $consoleLaunchUrl = $usingBundledAdminer ? '/admin/tools/adminer.php' : $phpMyAdminUrl;
}
$dbPath = realpath($_ENV['DB_DATABASE'] ?? __DIR__ . '/../../storage/library.db');
$dbSizeKb = ($dbPath && filesize($dbPath)) ? round(filesize($dbPath) / 1024, 1) : null;
$dbUpdatedAt = ($dbPath && file_exists($dbPath) && filemtime($dbPath)) ? date('Y-m-d H:i', filemtime($dbPath)) : null;

admin_header('Operations Center', 'operations');
?>
<?php if ($taskFeedback): ?>
    <div class="alert alert-<?php echo $taskFeedback['ok'] ? 'success' : 'danger'; ?> d-flex align-items-center justify-content-between" role="alert">
        <div>
            <strong><?php echo htmlspecialchars($taskFeedback['task']); ?>:</strong>
            <?php echo htmlspecialchars($taskFeedback['message']); ?>
        </div>
        <?php if (! $taskFeedback['ok'] && isset($taskFeedback['fallback'])): ?>
            <span class="small text-muted">Run manually: <code><?php echo htmlspecialchars($taskFeedback['fallback']); ?></code></span>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <?php foreach ($primaryMetrics as $metric): ?>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-uppercase text-muted small mb-1"><?php echo htmlspecialchars($metric['label']); ?></p>
                    <p class="display-6 fw-bold mb-0"><?php echo number_format($counts[$metric['key']] ?? 0); ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <?php foreach ($infrastructureMetrics as $metric): ?>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-uppercase text-muted small mb-1"><?php echo htmlspecialchars($metric['label']); ?></p>
                    <p class="display-6 fw-bold mb-0"><?php echo number_format($counts[$metric['key']] ?? 0); ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <?php foreach ($workflowMetrics as $metric): ?>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-uppercase text-muted small mb-1"><?php echo htmlspecialchars($metric['label']); ?></p>
                    <p class="display-6 fw-bold mb-0"><?php echo number_format($counts[$metric['key']] ?? 0); ?></p>
                    <?php if (! empty($metric['hint'])): ?>
                        <small class="text-muted"><?php echo htmlspecialchars($metric['hint']); ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Reservation Queue</h5>
                <a class="btn btn-sm btn-outline-primary" href="reservations.php">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if ($pendingReservations): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Member</th>
                                    <th>Status</th>
                                    <th class="text-end">Queue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingReservations as $reservation): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($reservation['book_title']); ?></div>
                                            <div class="text-muted small">Reserved <?php echo htmlspecialchars(friendly_date($reservation['reserved_on'])); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($reservation['member_name']); ?></td>
                                        <td>
                                            <span class="badge text-bg-<?php echo status_badge_class($reservation['status']); ?>">
                                                <?php echo htmlspecialchars(format_status($reservation['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <?php echo (int) ($reservation['queue_position'] ?? 1); ?>
                                            <?php if (! empty($reservation['ready_on'])): ?>
                                                <div class="text-muted small">Ready <?php echo htmlspecialchars(friendly_date($reservation['ready_on'])); ?></div>
                                            <?php elseif (! empty($reservation['expires_on'])): ?>
                                                <div class="text-muted small">Expires <?php echo htmlspecialchars(friendly_date($reservation['expires_on'])); ?></div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        No pending reservations.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Fines Watchlist</h5>
                <a class="btn btn-sm btn-outline-primary" href="fines.php">Review Fines</a>
            </div>
            <div class="card-body p-0">
                <?php if ($fineWatchlist): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fineWatchlist as $fine): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($fine['full_name']); ?></div>
                                            <div class="text-muted small">Assessed <?php echo htmlspecialchars(friendly_date($fine['assessed_on'])); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars(format_currency((float) $fine['amount'])); ?></td>
                                        <td>
                                            <span class="badge text-bg-<?php echo status_badge_class($fine['status']); ?>">
                                                <?php echo htmlspecialchars(format_status($fine['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        No unpaid fines detected.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Notification Activity</h5>
                <a class="btn btn-sm btn-outline-primary" href="notifications.php">Open Queue</a>
            </div>
            <div class="card-body p-0">
                <?php if ($recentNotifications): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Recipient</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentNotifications as $notification): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($notification['full_name'] ?? 'System'); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars(friendly_date($notification['created_at'], 'M j, H:i')); ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-capitalize"><?php echo htmlspecialchars(str_replace('-', ' ', $notification['type'])); ?></div>
                                            <div class="text-muted small text-uppercase">via <?php echo htmlspecialchars($notification['channel']); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge text-bg-<?php echo status_badge_class($notification['status']); ?>">
                                                <?php echo htmlspecialchars(format_status($notification['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        No notifications recorded yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Operational Logs</h5>
                <form method="get" class="d-flex gap-2 align-items-center">
                    <label for="log" class="text-muted small mb-0">File</label>
                    <select id="log" name="log" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach ($logOptions as $key => $filename): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $logSelection === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($filename); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="card-body">
                <?php if ($logTail): ?>
                    <pre class="bg-dark text-white small p-3 rounded overflow-auto" style="max-height: 300px;"><?php echo htmlspecialchars(implode("\n", $logTail)); ?></pre>
                <?php else: ?>
                    <p class="text-muted mb-0">No entries found inside <code><?php echo htmlspecialchars($logFileName); ?></code>. Run <code>composer notify-overdue</code> to generate fresh activity.</p>
                <?php endif; ?>
                </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Database Console</h5>
                <?php if ($consoleLaunchUrl): ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($consoleLaunchUrl); ?>" target="_blank" rel="noopener">Open in New Tab</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <p class="mb-0 text-muted small">
                    Launches the secured Adminer console in a dedicated tab. Embedded browsing is disabled to avoid CSP and mixed-content warnings—use the button above whenever you need direct SQL access.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Automation &amp; Maintenance</h5>
                <span class="badge text-bg-secondary">super admins only</span>
            </div>
            <div class="card-body">
                <p class="text-muted small">These shortcuts execute vetted CLI scripts using the same PHP runtime as the dashboard. Review output below for success or errors.</p>
                <?php foreach ($maintenanceTasks as $key => $task): ?>
                    <form method="post" class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($task['label']); ?></h6>
                                <p class="small text-muted mb-2"><?php echo htmlspecialchars($task['summary']); ?></p>
                                <code class="small">$ <?php echo htmlspecialchars($task['command']); ?></code>
                            </div>
                            <input type="hidden" name="maintenance_task" value="<?php echo htmlspecialchars($key); ?>">
                            <button type="submit" class="btn btn-sm btn-primary">Run</button>
                        </div>
                    </form>
                <?php endforeach; ?>
                <p class="small mb-0 text-muted">Scripts run from <code><?php echo htmlspecialchars(dirname(__DIR__)); ?></code>. Keep long-running jobs in the terminal when possible.</p>
            </div>
        </div>
    </div>
        <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Storage Snapshot</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Database file</dt>
                    <dd class="col-sm-7"><code><?php echo htmlspecialchars($dbPath ?: 'Not configured'); ?></code></dd>
                    <dt class="col-sm-5">Size</dt>
                    <dd class="col-sm-7"><?php echo $dbSizeKb ? htmlspecialchars($dbSizeKb . ' KB') : '—'; ?></dd>
                    <dt class="col-sm-5">Last updated</dt>
                    <dd class="col-sm-7"><?php echo $dbUpdatedAt ? htmlspecialchars($dbUpdatedAt) : '—'; ?></dd>
                    <dt class="col-sm-5">Notifications log</dt>
                    <dd class="col-sm-7"><code><?php echo htmlspecialchars($logFileName); ?></code></dd>
                    <dt class="col-sm-5">Outstanding fines</dt>
                    <dd class="col-sm-7"><?php echo htmlspecialchars(format_currency($fineTotals['outstanding'])); ?></dd>
                    <dt class="col-sm-5">Collected YTD</dt>
                    <dd class="col-sm-7"><?php echo htmlspecialchars(format_currency($fineTotals['collected'])); ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>
<?php admin_footer();

function tail_log(string $path, int $lines = 10): array
{
    if (! is_file($path)) {
        return [];
    }

    $buffer = file($path, FILE_IGNORE_NEW_LINES) ?: [];

    return array_slice($buffer, -1 * $lines);
}

function fetch_records(PDO $pdo, string $query): array
{
    $stmt = $pdo->query($query);
    if (! $stmt) {
        return [];
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function friendly_date(?string $value, string $format = 'M j, Y'): string
{
    if (! $value) {
        return '—';
    }

    $timestamp = strtotime($value);

    return $timestamp ? date($format, $timestamp) : $value;
}

function status_badge_class(?string $status): string
{
    return match (strtolower((string) $status)) {
        'ready', 'paid', 'sent', 'completed' => 'success',
        'pending', 'borrowed', 'queued', 'active' => 'info',
        'failed', 'unpaid', 'overdue', 'expired' => 'danger',
        default => 'secondary',
    };
}

function format_status(?string $status): string
{
    if (! $status) {
        return 'Unknown';
    }

    $label = str_replace(['_', '-'], ' ', strtolower($status));

    return ucwords($label);
}

function format_currency(float $value): string
{
    return 'ETB ' . number_format($value, 2);
}

function run_maintenance_task(string $taskKey, array $tasks): array
{
    if (! isset($tasks[$taskKey])) {
        return ['ok' => false, 'task' => 'Maintenance', 'message' => 'Unknown task requested.', 'fallback' => null];
    }

    $task = $tasks[$taskKey];
    if (empty($task['script']) || ! is_file($task['script'])) {
        return ['ok' => false, 'task' => $task['label'], 'message' => 'Script file is missing.', 'fallback' => $task['command']];
    }

    $command = sprintf('%s %s', escapeshellarg(PHP_BINARY), escapeshellarg($task['script']));

    if (! function_exists('proc_open')) {
        return ['ok' => false, 'task' => $task['label'], 'message' => 'proc_open is disabled on this host.', 'fallback' => $task['command']];
    }

    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes, dirname($task['script']));
    if (! is_resource($process)) {
        return ['ok' => false, 'task' => $task['label'], 'message' => 'Unable to start process.', 'fallback' => $task['command']];
    }

    fclose($pipes[0]);
    $output = trim(stream_get_contents($pipes[1]));
    fclose($pipes[1]);
    $errorOutput = trim(stream_get_contents($pipes[2]));
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    $message = $output;
    if ($errorOutput) {
        $message .= ($message ? ' | ' : '') . $errorOutput;
    }
    $message = $message ?: 'Completed without console output.';

    return [
        'ok' => $exitCode === 0,
        'task' => $task['label'],
        'message' => $message,
        'fallback' => $exitCode === 0 ? null : $task['command'],
    ];
}
