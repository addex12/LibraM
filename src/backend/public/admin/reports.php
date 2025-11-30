<?php

declare(strict_types=1);


$pdo = require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_admin_login();
require __DIR__ . '/includes/layout.php';

$monthParam = $_GET['month'] ?? date('Y-m');
$month = DateTimeImmutable::createFromFormat('Y-m', $monthParam) ?: new DateTimeImmutable('first day of this month');
$periodStart = $month->modify('first day of this month')->format('Y-m-01');
$periodEnd = $month->modify('first day of next month')->format('Y-m-01');
$today = date('Y-m-d');

$scalar = static function (string $sql, array $params = []) use ($pdo): int {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
};

$loansThisMonth = $scalar('SELECT COUNT(*) FROM loans WHERE borrowed_on >= :start AND borrowed_on < :end', [
    'start' => $periodStart,
    'end' => $periodEnd,
]);
$returnsThisMonth = $scalar('SELECT COUNT(*) FROM loans WHERE returned_on IS NOT NULL AND returned_on >= :start AND returned_on < :end', [
    'start' => $periodStart,
    'end' => $periodEnd,
]);
$activeLoans = $scalar('SELECT COUNT(*) FROM loans WHERE status = "borrowed"');
$overdueLoans = $scalar('SELECT COUNT(*) FROM loans WHERE status = "borrowed" AND due_on < :today', ['today' => $today]);
$totalBooks = $scalar('SELECT COUNT(*) FROM books');
$totalMembers = $scalar('SELECT COUNT(*) FROM members');

$topBooksStmt = $pdo->prepare('SELECT books.title, COUNT(loans.id) AS total
    FROM loans
    JOIN books ON books.id = loans.book_id
    WHERE loans.borrowed_on >= :start AND loans.borrowed_on < :end
    GROUP BY books.id
    ORDER BY total DESC
    LIMIT 5');
$topBooksStmt->execute(['start' => $periodStart, 'end' => $periodEnd]);
$topBooks = $topBooksStmt->fetchAll(PDO::FETCH_ASSOC);

$dailyTrendStmt = $pdo->prepare('SELECT borrowed_on AS day, COUNT(*) AS total
    FROM loans
    WHERE borrowed_on >= :start AND borrowed_on < :end
    GROUP BY borrowed_on
    ORDER BY borrowed_on');
$dailyTrendStmt->execute(['start' => $periodStart, 'end' => $periodEnd]);
$dailyTrend = $dailyTrendStmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="monthly-report-' . $month->format('Y-m') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Month', $month->format('F Y')]);
    fputcsv($output, ['Loans Issued', $loansThisMonth]);
    fputcsv($output, ['Returns Logged', $returnsThisMonth]);
    fputcsv($output, ['Active Loans', $activeLoans]);
    fputcsv($output, ['Overdue Loans', $overdueLoans]);
    fputcsv($output, ['Registered Books', $totalBooks]);
    fputcsv($output, ['Registered Members', $totalMembers]);
    fputcsv($output, []);
    fputcsv($output, ['Top Books']);
    foreach ($topBooks as $book) {
        fputcsv($output, [$book['title'], $book['total']]);
    }
    fclose($output);
    exit;
}

admin_header('Reports', 'reports');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Monthly Circulation Report</h1>
    <form class="d-flex gap-2" method="get">
        <input type="month" class="form-control" name="month" value="<?php echo htmlspecialchars($month->format('Y-m')); ?>">
        <button class="btn btn-outline-primary" type="submit">Filter</button>
        <a class="btn btn-primary" href="/admin/reports.php?month=<?php echo htmlspecialchars($month->format('Y-m')); ?>&export=csv">Export CSV</a>
    </form>
</div>
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['label' => 'Loans Issued', 'value' => $loansThisMonth],
        ['label' => 'Returns Logged', 'value' => $returnsThisMonth],
        ['label' => 'Active Loans', 'value' => $activeLoans],
        ['label' => 'Overdue Loans', 'value' => $overdueLoans],
        ['label' => 'Registered Books', 'value' => $totalBooks],
        ['label' => 'Registered Members', 'value' => $totalMembers],
    ];
    foreach ($cards as $card): ?>
        <div class="col-md-4 col-xl-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <p class="text-muted mb-1"><?php echo htmlspecialchars($card['label']); ?></p>
                    <h3 class="mb-0"><?php echo htmlspecialchars((string) $card['value']); ?></h3>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Top Books This Month</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Title</th>
                        <th>Loans</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (! $topBooks): ?>
                        <tr><td colspan="2" class="text-muted">No circulation data for this month.</td></tr>
                    <?php else: ?>
                        <?php foreach ($topBooks as $book): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['total']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Daily Issuance Trend</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Day</th>
                        <th>Total Loans</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (! $dailyTrend): ?>
                        <tr><td colspan="2" class="text-muted">No activity captured.</td></tr>
                    <?php else: ?>
                        <?php foreach ($dailyTrend as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['day']); ?></td>
                                <td><?php echo htmlspecialchars($row['total']); ?></td>
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
