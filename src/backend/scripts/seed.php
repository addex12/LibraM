<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144198
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

use App\Repositories\BookRepository;
use App\Repositories\LoanRepository;
use App\Repositories\MemberRepository;
use App\Repositories\UserSessionRepository;

$pdo = require __DIR__ . '/../bootstrap.php';

$bookRepo = new BookRepository($pdo);
$memberRepo = new MemberRepository($pdo);
$loanRepo = new LoanRepository($pdo);
$sessionRepo = new UserSessionRepository($pdo);

if (! $bookRepo->all()) {
    $books = [
        [
            'isbn' => '978-0132350884',
            'title' => 'Clean Code',
            'author' => 'Robert C. Martin',
            'publisher' => 'Prentice Hall',
            'publication_year' => 2008,
            'copies_total' => 5,
            'copies_available' => 5,
            'subjects' => 'Software Engineering, Best Practices',
        ],
        [
            'isbn' => '978-1492078005',
            'title' => 'Designing Data-Intensive Applications',
            'author' => 'Martin Kleppmann',
            'publisher' => "O'Reilly",
            'publication_year' => 2017,
            'copies_total' => 3,
            'copies_available' => 3,
            'subjects' => 'Databases, Distributed Systems',
        ],
        [
            'isbn' => '978-1430219484',
            'title' => 'Pro PHP and jQuery',
            'author' => 'Jason Lengstorf',
            'publisher' => 'Apress',
            'publication_year' => 2009,
            'copies_total' => 4,
            'copies_available' => 4,
            'subjects' => 'PHP, JavaScript',
        ],
        [
            'isbn' => '978-0596517748',
            'title' => 'Head First SQL',
            'author' => 'Lynn Beighley',
            'publisher' => "O'Reilly",
            'publication_year' => 2007,
            'copies_total' => 2,
            'copies_available' => 2,
            'subjects' => 'Databases, SQL',
        ],
        [
            'isbn' => '978-0262033848',
            'title' => 'Introduction to Algorithms',
            'author' => 'Thomas H. Cormen',
            'publisher' => 'MIT Press',
            'publication_year' => 2009,
            'copies_total' => 6,
            'copies_available' => 6,
            'subjects' => 'Algorithms, Computer Science',
        ],
    ];
    foreach ($books as $book) {
        $bookRepo->create($book);
    }
}

if (! $memberRepo->all()) {
    $members = [
        [
            'student_id' => 'UGR/1234/13',
            'full_name' => 'Sara Mekonnen',
            'faculty' => 'Engineering',
            'email' => 'sara@example.edu',
        ],
        [
            'student_id' => 'UGR/5678/13',
            'full_name' => 'Yonatan Bekele',
            'faculty' => 'Computer Science',
            'email' => 'yonatan@example.edu',
        ],
        [
            'student_id' => 'UGR/9012/13',
            'full_name' => 'Hanna Girma',
            'faculty' => 'Business and Economics',
            'email' => 'hanna@example.edu',
        ],
    ];
    foreach ($members as $member) {
        $memberRepo->create($member);
    }
}

if (! $loanRepo->all()) {
    $books = $bookRepo->all();
    $members = $memberRepo->all();

    if ($books && $members) {
        $loanRepo->create([
            'book_id' => $books[0]['id'],
            'member_id' => $members[0]['id'],
            'borrowed_on' => date('Y-m-d', strtotime('-5 days')),
            'due_on' => date('Y-m-d', strtotime('+2 days')),
            'status' => 'borrowed',
        ]);

        $returned = $loanRepo->create([
            'book_id' => $books[1]['id'],
            'member_id' => $members[1]['id'],
            'borrowed_on' => date('Y-m-d', strtotime('-15 days')),
            'due_on' => date('Y-m-d', strtotime('-5 days')),
            'status' => 'borrowed',
        ]);
        if ($returned) {
            $loanRepo->update($returned['id'], [
                'status' => 'returned',
                'returned_on' => date('Y-m-d', strtotime('-3 days')),
            ]);
        }

        $loanRepo->create([
            'book_id' => $books[2]['id'],
            'member_id' => $members[2]['id'],
            'borrowed_on' => date('Y-m-d', strtotime('-10 days')),
            'due_on' => date('Y-m-d', strtotime('-1 day')),
            'status' => 'borrowed',
        ]);
    }
}

if ($sessionRepo->count() === 0) {
    $sessions = [
        [
            'full_name' => 'Librarian Team',
            'identifier' => 'librarian',
            'role' => 'Admin',
            'channel' => 'Web Console',
            'usage_summary' => 'Reviewed reservations and approved new student accounts.',
            'last_login_at' => date('c', strtotime('today 09:15')),
        ],
        [
            'full_name' => 'Operations Desk',
            'identifier' => 'superadmin',
            'role' => 'Super Admin',
            'channel' => 'Admin Ops',
            'usage_summary' => 'Ran nightly data sync and cleared system health alerts.',
            'last_login_at' => date('c', strtotime('today 08:42')),
        ],
        [
            'full_name' => 'Sara Mekonnen',
            'identifier' => 'UGR/1234/13',
            'role' => 'Member',
            'channel' => 'Service Desk',
            'usage_summary' => 'Borrowed “Clean Code” and set a due-date reminder.',
            'last_login_at' => date('c', strtotime('today 09:02')),
        ],
        [
            'full_name' => 'Yonatan Bekele',
            'identifier' => 'UGR/5678/13',
            'role' => 'Member',
            'channel' => 'Mobile Portal',
            'usage_summary' => 'Checked fines and renewed “Head First SQL”.',
            'last_login_at' => date('c', strtotime('yesterday 16:35')),
        ],
        [
            'full_name' => 'Hanna Girma',
            'identifier' => 'UGR/9012/13',
            'role' => 'Research Fellow',
            'channel' => 'Research Commons',
            'usage_summary' => 'Reserved a collaboration pod and exported analytics.',
            'last_login_at' => date('c', strtotime('yesterday 11:10')),
        ],
    ];

    foreach ($sessions as $session) {
        $sessionRepo->create($session);
    }
}

echo "Seed complete\n";
