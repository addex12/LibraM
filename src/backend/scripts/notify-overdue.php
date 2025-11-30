<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

use App\Repositories\LoanRepository;
use App\Repositories\NotificationRepository;

require __DIR__ . '/../vendor/autoload.php';
$pdo = require __DIR__ . '/../bootstrap.php';

$loanRepository = new LoanRepository($pdo);
$notificationRepository = new NotificationRepository($pdo);
$overdueLoans = $loanRepository->overdue();

$logDir = dirname(__DIR__) . '/storage/logs';
if (! is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

$logFile = $logDir . '/notifications.log';
$now = date('Y-m-d H:i:s');

if (! $overdueLoans) {
    $message = sprintf('[%s] No overdue loans detected.', $now);
    file_put_contents($logFile, $message . PHP_EOL, FILE_APPEND);
    echo $message . PHP_EOL;
    exit(0);
}

foreach ($overdueLoans as $loan) {
    $payload = json_encode([
        'subject' => sprintf('Overdue notice for %s', $loan['book_title']),
        'member_email' => $loan['member_email'] ?? null,
        'due_on' => $loan['due_on'],
        'book_title' => $loan['book_title'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $notificationRepository->queue([
        'member_id' => (int) $loan['member_id'],
        'channel' => 'email',
        'type' => 'overdue-reminder',
        'payload' => $payload,
    ]);

    $entry = sprintf(
        '[%s] Reminder queued for %s (%s) about "%s" due on %s',
        $now,
        $loan['member_name'],
        $loan['member_email'],
        $loan['book_title'],
        $loan['due_on']
    );
    file_put_contents($logFile, $entry . PHP_EOL, FILE_APPEND);
    echo $entry . PHP_EOL;
}

printf("%d reminder(s) recorded in %s\n", count($overdueLoans), $logFile);
