<?php

declare(strict_types=1);

use App\Repositories\MemberRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\ReservationRepository;
use Throwable;

$pdo = require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_admin_login();
require __DIR__ . '/includes/layout.php';

$notificationRepository = new NotificationRepository($pdo);
$memberRepository = new MemberRepository($pdo);
$reservationRepository = new ReservationRepository($pdo);
$message = null;
$error = null;

$statuses = [
    'pending' => 'Pending',
    'sending' => 'Sending',
    'sent' => 'Sent',
    'failed' => 'Failed',
];

$channels = [
    'email' => 'Email',
    'sms' => 'SMS',
    'webhook' => 'Webhook',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'queue';
    try {
        if ($action === 'queue') {
            $notificationRepository->queue([
                'member_id' => $_POST['member_id'] !== '' ? (int) $_POST['member_id'] : null,
                'reservation_id' => $_POST['reservation_id'] !== '' ? (int) $_POST['reservation_id'] : null,
                'channel' => $_POST['channel'] ?? 'email',
                'type' => trim($_POST['type'] ?? 'general'),
                'payload' => trim($_POST['payload'] ?? ''),
            ]);
            header('Location: /admin/notifications.php?message=' . urlencode('Notification queued.'));
            exit;
        }

        if ($action === 'update-status') {
            $id = (int) ($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? 'pending';
            $payload = ['status' => $status];
            if ($status === 'sent') {
                $payload['sent_at'] = $_POST['sent_at'] ?: date('Y-m-d H:i:s');
            } elseif ($status === 'pending') {
                $payload['sent_at'] = null;
            }
            $notificationRepository->update($id, $payload);
            header('Location: /admin/notifications.php?message=' . urlencode('Notification updated.'));
            exit;
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $notificationRepository->delete($id);
            header('Location: /admin/notifications.php?message=' . urlencode('Notification deleted.'));
            exit;
        }
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

$statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
$channelFilter = isset($_GET['channel']) && $_GET['channel'] !== '' ? $_GET['channel'] : null;
$notifications = $notificationRepository->all($statusFilter, $channelFilter);
$members = $memberRepository->all();
$reservations = $reservationRepository->all();

admin_header('Notifications', 'notifications');
admin_alert($_GET['message'] ?? $message);
admin_alert($_GET['error'] ?? $error, 'danger');
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Queue Notification</div>
            <div class="card-body">
                <?php if (! $members): ?>
                    <p class="text-muted">Members are required before sending notifications.</p>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="action" value="queue">
                        <div class="mb-3">
                            <label class="form-label">Channel</label>
                            <select class="form-select" name="channel">
                                <?php foreach ($channels as $value => $label): ?>
                                    <option value="<?php echo $value; ?>"><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <input type="text" name="type" class="form-control" value="general" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Member</label>
                            <select class="form-select" name="member_id" required>
                                <option value="">Select member</option>
                                <?php foreach ($members as $member): ?>
                                    <option value="<?php echo $member['id']; ?>"><?php echo htmlspecialchars($member['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reservation (optional)</label>
                            <select class="form-select" name="reservation_id">
                                <option value="">None</option>
                                <?php foreach ($reservations as $reservation): ?>
                                    <option value="<?php echo $reservation['id']; ?>">
                                        <?php echo htmlspecialchars(($reservation['book_title'] ?? 'Book') . ' - ' . ($reservation['member_name'] ?? 'Member')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payload / Message</label>
                            <textarea name="payload" class="form-control" rows="4" placeholder='{"subject":"Hold ready"}'></textarea>
                        </div>
                        <button class="btn btn-primary" type="submit">Queue Notification</button>
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
                        <select class="form-select" name="status">
                            <option value="">All statuses</option>
                            <?php foreach ($statuses as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo $statusFilter === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col">
                        <select class="form-select" name="channel">
                            <option value="">All channels</option>
                            <?php foreach ($channels as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo $channelFilter === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
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
            <div class="card-header">Notification Log</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Channel / Type</th>
                        <th>Member</th>
                        <th>Status</th>
                        <th>Timestamps</th>
                        <th>Payload</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (! $notifications): ?>
                        <tr><td colspan="6" class="text-muted">No notifications logged.</td></tr>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <tr>
                                <td>
                                    <strong><?php echo strtoupper(htmlspecialchars($notification['channel'])); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($notification['type']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($notification['member_name'] ?? 'Unknown member'); ?><br>
                                    <?php if ($notification['reservation_id']): ?>
                                        <small class="text-muted">Reservation #<?php echo $notification['reservation_id']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" class="d-flex gap-2">
                                        <input type="hidden" name="action" value="update-status">
                                        <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
                                        <input type="hidden" name="sent_at" value="<?php echo htmlspecialchars($notification['sent_at'] ?? ''); ?>">
                                        <select class="form-select form-select-sm" name="status">
                                            <?php foreach ($statuses as $value => $label): ?>
                                                <option value="<?php echo $value; ?>" <?php echo ($notification['status'] === $value) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                                    </form>
                                </td>
                                <td>
                                    <div><small class="text-muted">Created:</small> <?php echo htmlspecialchars($notification['created_at']); ?></div>
                                    <?php if ($notification['sent_at']): ?>
                                        <div><small class="text-muted">Sent:</small> <?php echo htmlspecialchars($notification['sent_at']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width:220px;">
                                    <pre class="small mb-0" style="white-space:pre-wrap;"><?php echo htmlspecialchars($notification['payload'] ?? ''); ?></pre>
                                </td>
                                <td class="table-actions">
                                    <form method="post" onsubmit="return confirm('Delete this notification?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
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
