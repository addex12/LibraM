<?php

declare(strict_types=1);

use App\Repositories\BookRepository;
use App\Repositories\LoanRepository;
use App\Repositories\MemberRepository;

$pdo = require __DIR__ . '/../bootstrap.php';

$bookRepo = new BookRepository($pdo);
$memberRepo = new MemberRepository($pdo);
$loanRepo = new LoanRepository($pdo);

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

echo "Seed complete\n";
