<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144198
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

use App\Repositories\BookRepository;
use App\Repositories\MemberRepository;
use App\Repositories\ReservationRepository;
use DateInterval;
use DateTimeImmutable;

$pdo = require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_admin_login();
require __DIR__ . '/includes/layout.php';

$bookRepository = new BookRepository($pdo);
$memberRepository = new MemberRepository($pdo);
$reservationRepository = new ReservationRepository($pdo);
$message = null;
$error = null;

$statuses = [
    'pending' => 'Pending',
    'ready' => 'Ready for pickup',
    'fulfilled' => 'Fulfilled',
    'cancelled' => 'Cancelled',
    'expired' => 'Expired',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    try {
        if ($action === 'create') {
            $reservationRepository->create([
                'book_id' => (int) ($_POST['book_id'] ?? 0),
                'member_id' => (int) ($_POST['member_id'] ?? 0),
            ]);
            header('Location: /admin/reservations.php?message=' . urlencode('Reservation added to queue.'));
            exit;
        }

        if ($action === 'update-status') {
            $id = (int) ($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? 'pending';
            $payload = ['status' => $status];
            if ($status === 'ready') {
                $readyOn = new DateTimeImmutable();
                $payload['ready_on'] = $readyOn->format('Y-m-d H:i:s');
                $payload['expires_on'] = $readyOn->add(new DateInterval('P3D'))->format('Y-m-d H:i:s');
                $payload['notified_on'] = $readyOn->format('Y-m-d H:i:s');
            }
            if ($status === 'pending') {
                $payload['ready_on'] = null;
                $payload['expires_on'] = null;
                $payload['notified_on'] = null;
            }
            if (in_array($status, ['fulfilled', 'cancelled', 'expired'], true)) {
                $payload['expires_on'] = $payload['expires_on'] ?? null;
            }
            $reservationRepository->update($id, $payload);
            header('Location: /admin/reservations.php?message=' . urlencode('Reservation updated.'));
            exit;
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $reservationRepository->delete($id);
            header('Location: /admin/reservations.php?message=' . urlencode('Reservation removed.'));
            exit;
        }

        if ($action === 'expire-ready') {
            $count = $reservationRepository->expireReadyReservations();
            header('Location: /admin/reservations.php?message=' . urlencode(sprintf('%d ready holds expired.', $count)));
            exit;
        }
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

$statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
$reservations = $reservationRepository->all($statusFilter);
$books = $bookRepository->all();
$members = $memberRepository->all();

admin_header('Reservations', 'reservations');
admin_alert($_GET['message'] ?? $message);
admin_alert($_GET['error'] ?? $error, 'danger');
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Add Reservation</div>
            <div class="card-body">
                <?php if (! $books || ! $members): ?>
                    <p class="text-muted">You need at least one book and member before recording reservations.</p>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">Book</label>
                            <select class="form-select" name="book_id" required>
                                <option value="">Select a title</option>
                                <?php foreach ($books as $book): ?>
                                    <option value="<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
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
                        <button class="btn btn-primary" type="submit">Queue Reservation</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-body">
                <form method="post" onsubmit="return confirm('Expire ready reservations that passed their deadline?');">
                    <input type="hidden" name="action" value="expire-ready">
                    <button class="btn btn-outline-warning w-100" type="submit">Expire Ready Holds</button>
                </form>
                <small class="text-muted d-block mt-2">Applies to holds with an expiration earlier than now.</small>
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
                    <div class="col-auto">
                        <button class="btn btn-outline-secondary" type="submit">Filter</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Reservation Queue</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Book</th>
                        <th>Member</th>
                        <th>Status</th>
                        <th>Reserved / Ready</th>
                        <th>Queue</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (! $reservations): ?>
                        <tr><td colspan="6" class="text-muted">No reservations yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reservation['book_title']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['member_name']); ?></td>
                                <td>
                                    <form method="post" class="d-flex gap-2">
                                        <input type="hidden" name="action" value="update-status">
                                        <input type="hidden" name="id" value="<?php echo $reservation['id']; ?>">
                                        <select class="form-select form-select-sm" name="status">
                                            <?php foreach ($statuses as $value => $label): ?>
                                                <option value="<?php echo $value; ?>" <?php echo ($reservation['status'] === $value) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                                    </form>
                                </td>
                                <td>
                                    <div><small class="text-muted">Reserved:</small> <?php echo htmlspecialchars($reservation['reserved_on']); ?></div>
                                    <?php if ($reservation['ready_on']): ?>
                                        <div><small class="text-muted">Ready:</small> <?php echo htmlspecialchars($reservation['ready_on']); ?></div>
                                        <?php if ($reservation['expires_on']): ?>
                                            <div><small class="text-muted">Expires:</small> <?php echo htmlspecialchars($reservation['expires_on']); ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>#<?php echo htmlspecialchars($reservation['queue_position']); ?></td>
                                <td class="table-actions">
                                    <form method="post" onsubmit="return confirm('Delete this reservation?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $reservation['id']; ?>">
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
