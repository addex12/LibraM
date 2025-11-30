<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

use App\Repositories\BookRepository;
use App\Repositories\BranchRepository;
use App\Repositories\LoanRepository;
use App\Repositories\MemberRepository;
use App\Repositories\ShelfRepository;
use App\Repositories\StaffRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\UserSessionRepository;

$pdo = require __DIR__ . '/../bootstrap.php';

$bookRepo = new BookRepository($pdo);
$branchRepo = new BranchRepository($pdo);
$memberRepo = new MemberRepository($pdo);
$shelfRepo = new ShelfRepository($pdo);
$staffRepo = new StaffRepository($pdo);
$subjectRepo = new SubjectRepository($pdo);
$loanRepo = new LoanRepository($pdo);
$sessionRepo = new UserSessionRepository($pdo);

function hashed(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    return trim((string) $value, '-');
}

if (! $branchRepo->all()) {
    $branches = [
        [
            'code' => 'CEN',
            'name' => 'Central Library',
            'location' => 'King George VI St., Addis Ababa',
            'contact_email' => 'central@libram.edu',
            'contact_phone' => '+251-11-000-2000',
            'hours' => 'Mon-Sat 8:00-22:00',
        ],
        [
            'code' => 'SCI',
            'name' => 'Science & Engineering Hub',
            'location' => 'STEM Campus, Block B',
            'contact_email' => 'science@libram.edu',
            'contact_phone' => '+251-11-000-2100',
            'hours' => 'Mon-Fri 8:00-20:00',
        ],
        [
            'code' => 'HLT',
            'name' => 'Health Sciences Knowledge Center',
            'location' => 'Black Lion Hospital Wing',
            'contact_email' => 'health@libram.edu',
            'contact_phone' => '+251-11-000-2200',
            'hours' => 'Mon-Sun 7:00-21:00',
        ],
    ];
    foreach ($branches as $branch) {
        $branchRepo->create($branch);
    }
}

$branchMap = [];
foreach ($branchRepo->all() as $branch) {
    $branchMap[$branch['code']] = $branch;
}

if (! $subjectRepo->all()) {
    $subjects = [
        ['name' => 'Software Engineering', 'description' => 'Architecture, design patterns, and delivery.'],
        ['name' => 'Data & Databases', 'description' => 'Warehouse design, SQL, and distributed storage.'],
        ['name' => 'Operations & Reliability', 'description' => 'SRE, incident response, automation.'],
        ['name' => 'Artificial Intelligence', 'description' => 'Machine learning foundations and practice.'],
        ['name' => 'Business & Economics', 'description' => 'Leadership, entrepreneurship, finance.'],
        ['name' => 'Research Methods', 'description' => 'Qualitative and quantitative methods for academia.'],
    ];
    foreach ($subjects as $subject) {
        $subjectRepo->create($subject);
    }
}

$subjectSlugMap = [];
foreach ($subjectRepo->all() as $subject) {
    $subjectSlugMap[slugify($subject['name'])] = $subject['id'];
}

if (! $shelfRepo->all()) {
    $shelfSeeds = [
        ['branch_code' => 'CEN', 'code' => 'CEN-A1', 'label' => 'Stacks A1', 'floor' => 'Level 1', 'capacity' => 120],
        ['branch_code' => 'CEN', 'code' => 'CEN-B2', 'label' => 'Stacks B2', 'floor' => 'Level 2', 'capacity' => 100],
        ['branch_code' => 'SCI', 'code' => 'SCI-ML1', 'label' => 'Machine Learning Bay', 'floor' => 'Innovation Loft', 'capacity' => 80],
        ['branch_code' => 'SCI', 'code' => 'SCI-NET2', 'label' => 'Networks Pod', 'floor' => 'Innovation Loft', 'capacity' => 70],
        ['branch_code' => 'HLT', 'code' => 'HLT-REF1', 'label' => 'Clinical Reference', 'floor' => 'East Wing', 'capacity' => 60],
    ];
    foreach ($shelfSeeds as $shelf) {
        $branchId = $branchMap[$shelf['branch_code']]['id'] ?? null;
        if (! $branchId) {
            continue;
        }
        $shelfRepo->create([
            'branch_id' => $branchId,
            'code' => $shelf['code'],
            'label' => $shelf['label'],
            'floor' => $shelf['floor'],
            'capacity' => $shelf['capacity'],
        ]);
    }
}

$shelfMap = [];
foreach ($shelfRepo->all() as $shelf) {
    $shelfMap[$shelf['code']] = $shelf;
}

{
    $books = [
        [
            'isbn' => '978-0132350884',
            'title' => 'Clean Code',
            'author' => 'Robert C. Martin',
            'publisher' => 'Prentice Hall',
            'publication_year' => 2008,
            'copies_total' => 6,
            'copies_available' => 6,
            'subjects' => 'Software Engineering, Best Practices',
            'branch_code' => 'CEN',
            'shelf_code' => 'CEN-A1',
            'subject_slug' => 'software-engineering',
        ],
        [
            'isbn' => '978-1492078005',
            'title' => 'Designing Data-Intensive Applications',
            'author' => 'Martin Kleppmann',
            'publisher' => "O'Reilly",
            'publication_year' => 2017,
            'copies_total' => 4,
            'copies_available' => 4,
            'subjects' => 'Databases, Distributed Systems',
            'branch_code' => 'SCI',
            'shelf_code' => 'SCI-ML1',
            'subject_slug' => 'data-databases',
        ],
        [
            'isbn' => '978-1430219484',
            'title' => 'Pro PHP and jQuery',
            'author' => 'Jason Lengstorf',
            'publisher' => 'Apress',
            'publication_year' => 2009,
            'copies_total' => 5,
            'copies_available' => 5,
            'subjects' => 'PHP, JavaScript',
            'branch_code' => 'CEN',
            'shelf_code' => 'CEN-B2',
            'subject_slug' => 'software-engineering',
        ],
        [
            'isbn' => '978-0596517748',
            'title' => 'Head First SQL',
            'author' => 'Lynn Beighley',
            'publisher' => "O'Reilly",
            'publication_year' => 2007,
            'copies_total' => 3,
            'copies_available' => 3,
            'subjects' => 'Databases, SQL',
            'branch_code' => 'CEN',
            'shelf_code' => 'CEN-B2',
            'subject_slug' => 'data-databases',
        ],
        [
            'isbn' => '978-0262033848',
            'title' => 'Introduction to Algorithms',
            'author' => 'Thomas H. Cormen',
            'publisher' => 'MIT Press',
            'publication_year' => 2009,
            'copies_total' => 8,
            'copies_available' => 8,
            'subjects' => 'Algorithms, Computer Science',
            'branch_code' => 'SCI',
            'shelf_code' => 'SCI-NET2',
            'subject_slug' => 'research-methods',
        ],
        [
            'isbn' => '978-0201616224',
            'title' => 'The Pragmatic Programmer',
            'author' => 'Andrew Hunt',
            'publisher' => 'Addison-Wesley',
            'publication_year' => 1999,
            'copies_total' => 4,
            'copies_available' => 4,
            'subjects' => 'Software Engineering, Craftsmanship',
            'branch_code' => 'CEN',
            'shelf_code' => 'CEN-A1',
            'subject_slug' => 'software-engineering',
        ],
        [
            'isbn' => '978-1491929124',
            'title' => 'Site Reliability Engineering',
            'author' => 'Betsy Beyer',
            'publisher' => "O'Reilly",
            'publication_year' => 2016,
            'copies_total' => 3,
            'copies_available' => 3,
            'subjects' => 'Operations, Reliability',
            'branch_code' => 'SCI',
            'shelf_code' => 'SCI-ML1',
            'subject_slug' => 'operations-reliability',
        ],
        [
            'isbn' => '978-1491950357',
            'title' => 'Building Microservices',
            'author' => 'Sam Newman',
            'publisher' => "O'Reilly",
            'publication_year' => 2021,
            'copies_total' => 5,
            'copies_available' => 5,
            'subjects' => 'Microservices, Architecture, DevOps',
            'branch_code' => 'SCI',
            'shelf_code' => 'SCI-NET2',
            'subject_slug' => 'software-engineering',
        ],
        [
            'isbn' => '978-0262035613',
            'title' => 'Deep Learning',
            'author' => 'Ian Goodfellow',
            'publisher' => 'MIT Press',
            'publication_year' => 2016,
            'copies_total' => 5,
            'copies_available' => 5,
            'subjects' => 'Machine Learning, AI',
            'branch_code' => 'SCI',
            'shelf_code' => 'SCI-ML1',
            'subject_slug' => 'artificial-intelligence',
        ],
        [
            'isbn' => '978-0136042594',
            'title' => 'Artificial Intelligence: A Modern Approach',
            'author' => 'Stuart Russell',
            'publisher' => 'Pearson',
            'publication_year' => 2010,
            'copies_total' => 4,
            'copies_available' => 4,
            'subjects' => 'Artificial Intelligence',
            'branch_code' => 'SCI',
            'shelf_code' => 'SCI-ML1',
            'subject_slug' => 'artificial-intelligence',
        ],
        [
            'isbn' => '978-0134494166',
            'title' => 'Clean Architecture',
            'author' => 'Robert C. Martin',
            'publisher' => 'Prentice Hall',
            'publication_year' => 2017,
            'copies_total' => 5,
            'copies_available' => 5,
            'subjects' => 'Software Architecture',
            'branch_code' => 'CEN',
            'shelf_code' => 'CEN-A1',
            'subject_slug' => 'software-engineering',
        ],
        [
            'isbn' => '978-0134757599',
            'title' => 'Refactoring',
            'author' => 'Martin Fowler',
            'publisher' => 'Addison-Wesley',
            'publication_year' => 2018,
            'copies_total' => 4,
            'copies_available' => 4,
            'subjects' => 'Refactoring, Code Quality',
            'branch_code' => 'CEN',
            'shelf_code' => 'CEN-A1',
            'subject_slug' => 'software-engineering',
        ],
        [
            'isbn' => '978-0321125217',
            'title' => 'Domain-Driven Design',
            'author' => 'Eric Evans',
            'publisher' => 'Addison-Wesley',
            'publication_year' => 2003,
            'copies_total' => 3,
            'copies_available' => 3,
            'subjects' => 'Architecture, Business Modeling',
            'branch_code' => 'CEN',
            'shelf_code' => 'CEN-B2',
            'subject_slug' => 'business-economics',
        ],
    ];
    foreach ($books as $book) {
        if ($bookRepo->findByIsbn($book['isbn'])) {
            continue;
        }

        $branchId = isset($book['branch_code'], $branchMap[$book['branch_code']]) ? $branchMap[$book['branch_code']]['id'] : null;
        $shelfId = isset($book['shelf_code'], $shelfMap[$book['shelf_code']]) ? $shelfMap[$book['shelf_code']]['id'] : null;
        $subjectId = isset($book['subject_slug'], $subjectSlugMap[$book['subject_slug']]) ? $subjectSlugMap[$book['subject_slug']] : null;
        $payload = $book;
        unset($payload['branch_code'], $payload['shelf_code'], $payload['subject_slug']);
        $payload['branch_id'] = $branchId;
        $payload['shelf_id'] = $shelfId;
        $payload['subject_id'] = $subjectId;
        $bookRepo->create($payload);
    }
}

if (! $memberRepo->all()) {
    $members = [
        [
            'student_id' => 'UGR/1234/13',
            'full_name' => 'Sara Mekonnen',
            'faculty' => 'Engineering',
            'email' => 'sara@example.edu',
            'password_hash' => hashed('Sara@Lib123'),
        ],
        [
            'student_id' => 'UGR/5678/13',
            'full_name' => 'Yonatan Bekele',
            'faculty' => 'Computer Science',
            'email' => 'yonatan@example.edu',
            'password_hash' => hashed('Yonatan#Data'),
        ],
        [
            'student_id' => 'UGR/9012/13',
            'full_name' => 'Hanna Girma',
            'faculty' => 'Business and Economics',
            'email' => 'hanna@example.edu',
            'password_hash' => hashed('HannaBiz!2024'),
        ],
        [
            'student_id' => 'UGR/4455/14',
            'full_name' => 'Mikiyas Tadesse',
            'faculty' => 'Electrical Engineering',
            'email' => 'mikiyas@example.edu',
            'password_hash' => hashed('MikiVolt#19'),
        ],
        [
            'student_id' => 'UGR/7788/12',
            'full_name' => 'Rahel Alemu',
            'faculty' => 'Health Sciences',
            'email' => 'rahel@example.edu',
            'password_hash' => hashed('RahelCare+'),
        ],
        [
            'student_id' => 'ALU/3322/11',
            'full_name' => 'Fikir Abay',
            'faculty' => 'Alumni Relations',
            'email' => 'fikir@example.edu',
            'password_hash' => hashed('FikirMentor!'),
        ],
        [
            'student_id' => 'STA/2201/10',
            'full_name' => 'Dr. Liya Bekalu',
            'faculty' => 'Faculty of Computing',
            'email' => 'liya@example.edu',
            'password_hash' => hashed('DeanLiya#90'),
        ],
        [
            'student_id' => 'UGR/0199/15',
            'full_name' => 'Kaleb Fekadu',
            'faculty' => 'Information Systems',
            'email' => 'kaleb@example.edu',
            'password_hash' => hashed('Kaleb.Cloud!'),
        ],
        [
            'student_id' => 'UGR/2210/14',
            'full_name' => 'Meron Assefa',
            'faculty' => 'Architecture',
            'email' => 'meron@example.edu',
            'password_hash' => hashed('MeronSketch7'),
        ],
        [
            'student_id' => 'UGR/3511/12',
            'full_name' => 'Nahom Bekri',
            'faculty' => 'Mathematics',
            'email' => 'nahom@example.edu',
            'password_hash' => hashed('NahomPi#314'),
        ],
        [
            'student_id' => 'PGC/1105/24',
            'full_name' => 'Selam Tesfaye',
            'faculty' => 'Graduate Studies',
            'email' => 'selam@example.edu',
            'password_hash' => hashed('SelamGrad+24'),
        ],
        [
            'student_id' => 'PGC/2207/23',
            'full_name' => 'Abebe Lemma',
            'faculty' => 'Public Policy',
            'email' => 'abebe@example.edu',
            'password_hash' => hashed('AbebePolicy@1'),
        ],
    ];
    foreach ($members as $member) {
        $memberRepo->create($member);
    }
}

if (! $staffRepo->all()) {
    $staffMembers = [
        [
            'employee_id' => 'STF-1001',
            'full_name' => 'Abel Kebede',
            'role' => 'Circulation Lead',
            'email' => 'abel.kebede@libram.edu',
            'phone' => '+251-11-000-1001',
            'password_hash' => hashed('AbelCirculation!23'),
        ],
        [
            'employee_id' => 'STF-1002',
            'full_name' => 'Martha Tulu',
            'role' => 'Digital Services Librarian',
            'email' => 'martha.tulu@libram.edu',
            'phone' => '+251-11-000-1002',
            'password_hash' => hashed('MarthaDigital#'),
        ],
        [
            'employee_id' => 'STF-1003',
            'full_name' => 'Samuel Hailu',
            'role' => 'Reference Specialist',
            'email' => 'samuel.hailu@libram.edu',
            'phone' => '+251-11-000-1003',
            'password_hash' => hashed('SamuelReference8'),
        ],
        [
            'employee_id' => 'STF-1004',
            'full_name' => 'Eden Worku',
            'role' => 'Branch Coordinator',
            'email' => 'eden.worku@libram.edu',
            'phone' => '+251-11-000-1004',
            'password_hash' => hashed('EdenBranch@45'),
        ],
        [
            'employee_id' => 'STF-1005',
            'full_name' => 'Kalkidan Lemlem',
            'role' => 'Collections Analyst',
            'email' => 'kalkidan.lemlem@libram.edu',
            'phone' => '+251-11-000-1005',
            'password_hash' => hashed('KalkidanStacks%'),
        ],
    ];

    foreach ($staffMembers as $staff) {
        $staffRepo->create($staff);
    }
}

if (! $loanRepo->all()) {
    $books = $bookRepo->all();
    $members = $memberRepo->all();

    if ($books && $members) {
        $bookMap = [];
        foreach ($books as $book) {
            $bookMap[$book['isbn']] = $book;
        }

        $memberMap = [];
        foreach ($members as $member) {
            $memberMap[$member['student_id']] = $member;
        }

        $loanSeeds = [
            [
                'book_isbn' => '978-0132350884',
                'student_id' => 'UGR/1234/13',
                'borrowed_on' => '-5 days',
                'due_on' => '+2 days',
                'status' => 'borrowed',
            ],
            [
                'book_isbn' => '978-1492078005',
                'student_id' => 'UGR/5678/13',
                'borrowed_on' => '-15 days',
                'due_on' => '-5 days',
                'status' => 'returned',
                'returned_on' => '-3 days',
            ],
            [
                'book_isbn' => '978-0262033848',
                'student_id' => 'UGR/9012/13',
                'borrowed_on' => '-12 days',
                'due_on' => '-2 days',
                'status' => 'borrowed',
            ],
            [
                'book_isbn' => '978-0201616224',
                'student_id' => 'UGR/4455/14',
                'borrowed_on' => '-3 days',
                'due_on' => '+11 days',
                'status' => 'borrowed',
            ],
            [
                'book_isbn' => '978-1491929124',
                'student_id' => 'STA/2201/10',
                'borrowed_on' => '-7 days',
                'due_on' => '+1 day',
                'status' => 'borrowed',
            ],
            [
                'book_isbn' => '978-0262035613',
                'student_id' => 'ALU/3322/11',
                'borrowed_on' => '-20 days',
                'due_on' => '-6 days',
                'status' => 'overdue',
            ],
            [
                'book_isbn' => '978-0136042594',
                'student_id' => 'UGR/0199/15',
                'borrowed_on' => '-2 days',
                'due_on' => '+12 days',
                'status' => 'borrowed',
            ],
            [
                'book_isbn' => '978-0134757599',
                'student_id' => 'UGR/2210/14',
                'borrowed_on' => '-9 days',
                'due_on' => '+5 days',
                'status' => 'borrowed',
            ],
            [
                'book_isbn' => '978-0321125217',
                'student_id' => 'PGC/1105/24',
                'borrowed_on' => '-1 day',
                'due_on' => '+13 days',
                'status' => 'borrowed',
            ],
        ];

        foreach ($loanSeeds as $seed) {
            if (! isset($bookMap[$seed['book_isbn']], $memberMap[$seed['student_id']])) {
                continue;
            }

            $loan = $loanRepo->create([
                'book_id' => $bookMap[$seed['book_isbn']]['id'],
                'member_id' => $memberMap[$seed['student_id']]['id'],
                'borrowed_on' => date('Y-m-d', strtotime($seed['borrowed_on'])),
                'due_on' => date('Y-m-d', strtotime($seed['due_on'])),
                'status' => $seed['status'] === 'returned' ? 'borrowed' : $seed['status'],
            ]);

            if ($loan && isset($seed['returned_on'])) {
                $loanRepo->update($loan['id'], [
                    'status' => 'returned',
                    'returned_on' => date('Y-m-d', strtotime($seed['returned_on'])),
                ]);
            }
        }
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
        [
            'full_name' => 'Mikiyas Tadesse',
            'identifier' => 'UGR/4455/14',
            'role' => 'Member',
            'channel' => 'Self-Service Kiosk',
            'usage_summary' => 'Issued a Pragmatic Programmer checkout for Capstone prep.',
            'last_login_at' => date('c', strtotime('today 10:40')),
        ],
        [
            'full_name' => 'Rahel Alemu',
            'identifier' => 'UGR/7788/12',
            'role' => 'Clinical Fellow',
            'channel' => 'Health Sciences Wing',
            'usage_summary' => 'Downloaded evidence-based nursing briefings.',
            'last_login_at' => date('c', strtotime('yesterday 18:05')),
        ],
        [
            'full_name' => 'Dr. Liya Bekalu',
            'identifier' => 'STA/2201/10',
            'role' => 'Faculty',
            'channel' => 'Faculty Portal',
            'usage_summary' => 'Extended SRE handbook access for course pack.',
            'last_login_at' => date('c', strtotime('today 07:55')),
        ],
        [
            'full_name' => 'Fikir Abay',
            'identifier' => 'ALU/3322/11',
            'role' => 'Alumni',
            'channel' => 'Alumni Portal',
            'usage_summary' => 'Reserved research commons pod for startup mentoring.',
            'last_login_at' => date('c', strtotime('today 12:25')),
        ],
        [
            'full_name' => 'Abel Kebede',
            'identifier' => 'STF-1001',
            'role' => 'Staff',
            'channel' => 'Circulation Desk',
            'usage_summary' => 'Processed seven pickups and reconciled two damaged items.',
            'last_login_at' => date('c', strtotime('today 10:10')),
        ],
        [
            'full_name' => 'Martha Tulu',
            'identifier' => 'STF-1002',
            'role' => 'Staff',
            'channel' => 'Digital Services',
            'usage_summary' => 'Published new digitization queue and synced e-resource stats.',
            'last_login_at' => date('c', strtotime('today 09:50')),
        ],
        [
            'full_name' => 'Samuel Hailu',
            'identifier' => 'STF-1003',
            'role' => 'Staff',
            'channel' => 'Research Support',
            'usage_summary' => 'Guided postgraduate cohort through citation management workshop.',
            'last_login_at' => date('c', strtotime('yesterday 15:20')),
        ],
    ];

    foreach ($sessions as $session) {
        $sessionRepo->create($session);
    }
}

echo "Seed complete\n";
