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

session_start();

$pdo = require __DIR__ . '/../bootstrap.php';

$bookRepository = new BookRepository($pdo);
$memberRepository = new MemberRepository($pdo);
$loanRepository = new LoanRepository($pdo);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . $path;
    if ($path !== '/' && $path !== '' && is_file($file) && $file !== __FILE__) {
        return false;
    }
}

if ($path === '/health' || str_starts_with($path, '/api')) {
    handle_api($path, $method, $bookRepository, $memberRepository, $loanRepository);
    return;
}

require __DIR__ . '/views/public-layout.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'register_member':
                $studentId = trim($_POST['student_id'] ?? '');
                $fullName = trim($_POST['full_name'] ?? '');
                $faculty = trim($_POST['faculty'] ?? '');
                $email = trim($_POST['email'] ?? '');
                if (! $studentId || ! $fullName || ! $faculty || ! $email) {
                    throw new RuntimeException('All member fields are required.');
                }
                if ($memberRepository->findByStudentId($studentId)) {
                    throw new RuntimeException('Student ID already registered.');
                }
                $memberRepository->create([
                    'student_id' => $studentId,
                    'full_name' => $fullName,
                    'faculty' => $faculty,
                    'email' => $email,
                ]);
                set_flash('success', 'You are now registered as a library member.');
                header('Location: /');
                exit;

            case 'borrow_book':
                $bookId = (int) ($_POST['book_id'] ?? 0);
                $studentId = trim($_POST['student_id'] ?? '');
                $borrowedOn = $_POST['borrowed_on'] ?: date('Y-m-d');
                $dueOn = $_POST['due_on'] ?: date('Y-m-d', strtotime('+7 days'));
                if (! $bookId || ! $studentId) {
                    throw new RuntimeException('Please pick a book and provide your student ID.');
                }
                $member = $memberRepository->findByStudentId($studentId);
                if (! $member) {
                    throw new RuntimeException('Student ID not found. Register first.');
                }
                $loan = $loanRepository->create([
                    'book_id' => $bookId,
                    'member_id' => (int) $member['id'],
                    'borrowed_on' => $borrowedOn,
                    'due_on' => $dueOn,
                    'status' => 'borrowed',
                ]);
                if (! $loan) {
                    throw new RuntimeException('Book unavailable.');
                }
                set_flash('success', 'Borrow request recorded. Please collect your book from the circulation desk.');
                header('Location: /');
                exit;
        }
    } catch (Throwable $throwable) {
        set_flash('danger', $throwable->getMessage());
        header('Location: /');
        exit;
    }
}

$keyword = $_GET['keyword'] ?? null;
$books = $bookRepository->all($keyword);
$availableBooks = array_filter($bookRepository->all(), fn ($book) => (int) $book['copies_available'] > 0);

$lookupStudent = trim($_GET['lookup_student'] ?? '');
$lookupMember = null;
$lookupLoans = [];
if ($lookupStudent !== '') {
    $lookupMember = $memberRepository->findByStudentId($lookupStudent);
    if ($lookupMember) {
        $lookupLoans = $loanRepository->forMember((int) $lookupMember['id']);
    }
}

public_header('Library Service Portal');
public_flash($flash);
?>
<section class="py-3">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <form class="row gy-2 gx-2 align-items-center" method="get">
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="keyword" placeholder="Search by title, author or subject" value="<?php echo htmlspecialchars($keyword ?? ''); ?>">
                        </div>
                        <div class="col-sm-auto">
                            <button class="btn btn-primary" type="submit">Search Catalog</button>
                        </div>
                        <div class="col-sm-auto">
                            <a class="btn btn-outline-secondary" href="/">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Available Books</h5>
                    <span class="text-muted small"><?php echo count($books); ?> record(s)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Year</th>
                            <th>Subjects</th>
                            <th>Available</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (! $books): ?>
                            <tr><td colspan="5" class="text-muted">No books match your search.</td></tr>
                        <?php else: ?>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($book['title']); ?></strong><br><small class="text-muted">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></small></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td><?php echo htmlspecialchars($book['publication_year'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($book['subjects'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($book['copies_available']); ?> / <?php echo htmlspecialchars($book['copies_total']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">Become a Member</div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="register_member">
                        <div class="mb-3">
                            <label class="form-label">Student ID</label>
                            <input type="text" name="student_id" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Faculty / Department</label>
                            <input type="text" name="faculty" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <button class="btn btn-success w-100" type="submit">Register</button>
                    </form>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header">Borrow a Book</div>
                <div class="card-body">
                    <?php if (! $availableBooks): ?>
                        <p class="text-muted">No copies are currently available.</p>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="action" value="borrow_book">
                            <div class="mb-3">
                                <label class="form-label">Select Book</label>
                                <select name="book_id" class="form-select" required>
                                    <option value="">-- choose --</option>
                                    <?php foreach ($availableBooks as $book): ?>
                                        <option value="<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']); ?> (<?php echo htmlspecialchars($book['copies_available']); ?> left)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Your Student ID</label>
                                <input type="text" name="student_id" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Borrowed On</label>
                                <input type="date" name="borrowed_on" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Due On</label>
                                <input type="date" name="due_on" class="form-control" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Submit Request</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Check Your Loans</div>
                <div class="card-body">
                    <form method="get">
                        <div class="mb-3">
                            <label class="form-label">Student ID</label>
                            <input type="text" name="lookup_student" class="form-control" value="<?php echo htmlspecialchars($lookupStudent); ?>" required>
                        </div>
                        <button class="btn btn-outline-primary w-100" type="submit">Search Loans</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($lookupStudent !== ''): ?>
        <div class="card mt-4">
            <div class="card-header">Loan History for <?php echo htmlspecialchars($lookupStudent); ?></div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Book</th>
                        <th>Borrowed On</th>
                        <th>Due On</th>
                        <th>Returned</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (! $lookupMember): ?>
                        <tr><td colspan="5" class="text-muted">No member found with this student ID.</td></tr>
                    <?php elseif (! $lookupLoans): ?>
                        <tr><td colspan="5" class="text-muted">No loans found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($lookupLoans as $loan): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($loan['book_title']); ?></td>
                                <td><?php echo htmlspecialchars($loan['borrowed_on']); ?></td>
                                <td><?php echo htmlspecialchars($loan['due_on']); ?></td>
                                <td><?php echo htmlspecialchars($loan['returned_on'] ?? '-'); ?></td>
                                <td><span class="badge bg-<?php echo $loan['status'] === 'borrowed' ? 'warning text-dark' : 'success'; ?>"><?php echo htmlspecialchars($loan['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
<?php public_footer(); ?>
<?php

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function handle_api(string $path, string $method, BookRepository $bookRepository, MemberRepository $memberRepository, LoanRepository $loanRepository): void
{
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($method === 'OPTIONS') {
        http_response_code(204);
        return;
    }

    switch (true) {
        case $path === '/health' && $method === 'GET':
            respond(['status' => 'ok', 'timestamp' => date(DATE_ATOM)]);
            break;

        case $path === '/api/books' && $method === 'GET':
            $keyword = $_GET['keyword'] ?? null;
            respond($bookRepository->all($keyword));
            break;

        case $path === '/api/books' && $method === 'POST':
            $payload = json_input();
            respond($bookRepository->create($payload), 201);
            break;

        case preg_match('#^/api/books/(\d+)$#', $path, $matches) === 1 && $method === 'PUT':
            $id = (int) $matches[1];
            $payload = json_input();
            $updated = $bookRepository->update($id, $payload);
            $updated ? respond($updated) : respond(['message' => 'Book not found'], 404);
            break;

        case preg_match('#^/api/books/(\d+)$#', $path, $matches) === 1 && $method === 'DELETE':
            $id = (int) $matches[1];
            $deleted = $bookRepository->delete($id);
            $deleted ? respond(['message' => 'Deleted']) : respond(['message' => 'Book not found'], 404);
            break;

        case $path === '/api/members' && $method === 'GET':
            respond($memberRepository->all());
            break;

        case $path === '/api/members' && $method === 'POST':
            $payload = json_input();
            respond($memberRepository->create($payload), 201);
            break;

        case preg_match('#^/api/members/(\d+)$#', $path, $matches) === 1 && $method === 'PUT':
            $id = (int) $matches[1];
            $payload = json_input();
            $updated = $memberRepository->update($id, $payload);
            $updated ? respond($updated) : respond(['message' => 'Member not found'], 404);
            break;

        case preg_match('#^/api/members/(\d+)$#', $path, $matches) === 1 && $method === 'DELETE':
            $id = (int) $matches[1];
            $deleted = $memberRepository->delete($id);
            $deleted ? respond(['message' => 'Deleted']) : respond(['message' => 'Member not found'], 404);
            break;

        case $path === '/api/loans' && $method === 'GET':
            respond($loanRepository->all());
            break;

        case $path === '/api/loans' && $method === 'POST':
            $payload = json_input();
            $loan = $loanRepository->create($payload);
            $loan ? respond($loan, 201) : respond(['message' => 'Book unavailable'], 400);
            break;

        case preg_match('#^/api/loans/(\d+)$#', $path, $matches) === 1 && $method === 'PUT':
            $id = (int) $matches[1];
            $payload = json_input();
            $loan = $loanRepository->update($id, $payload);
            $loan ? respond($loan) : respond(['message' => 'Loan not found'], 404);
            break;

        default:
            respond(['message' => 'Route not found'], 404);
    }
}

function json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (json_last_error() != 0) {
        respond(['message' => 'Invalid JSON payload'], 400);
    }
    return $data ?? [];
}

function respond(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
