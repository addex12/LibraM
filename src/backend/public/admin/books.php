<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

use App\Repositories\BookRepository;
use App\Repositories\BranchRepository;
use App\Repositories\ShelfRepository;
use App\Repositories\SubjectRepository;

$pdo = require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_admin_login();
require __DIR__ . '/includes/layout.php';

$bookRepository = new BookRepository($pdo);
$branchRepository = new BranchRepository($pdo);
$shelfRepository = new ShelfRepository($pdo);
$subjectRepository = new SubjectRepository($pdo);
$message = null;
$error = null;
$keyword = $_GET['keyword'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    try {
        if ($action === 'create') {
            $bookRepository->create([
                'isbn' => trim($_POST['isbn'] ?? ''),
                'title' => trim($_POST['title'] ?? ''),
                'author' => trim($_POST['author'] ?? ''),
                'publisher' => trim($_POST['publisher'] ?? ''),
                'publication_year' => $_POST['publication_year'] ? (int) $_POST['publication_year'] : null,
                'copies_total' => $_POST['copies_total'] ? (int) $_POST['copies_total'] : 1,
                'copies_available' => $_POST['copies_available'] ? (int) $_POST['copies_available'] : ($_POST['copies_total'] ? (int) $_POST['copies_total'] : 1),
                'subjects' => trim($_POST['subjects'] ?? ''),
                'branch_id' => (isset($_POST['branch_id']) && $_POST['branch_id'] !== '') ? (int) $_POST['branch_id'] : null,
                'shelf_id' => (isset($_POST['shelf_id']) && $_POST['shelf_id'] !== '') ? (int) $_POST['shelf_id'] : null,
                'subject_id' => (isset($_POST['subject_id']) && $_POST['subject_id'] !== '') ? (int) $_POST['subject_id'] : null,
            ]);
            header('Location: /admin/books.php?message=' . urlencode('Book added successfully.'));
            exit;
        }

        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            $bookRepository->update($id, [
                'isbn' => trim($_POST['isbn'] ?? ''),
                'title' => trim($_POST['title'] ?? ''),
                'author' => trim($_POST['author'] ?? ''),
                'publisher' => trim($_POST['publisher'] ?? ''),
                'publication_year' => $_POST['publication_year'] ? (int) $_POST['publication_year'] : null,
                'copies_total' => $_POST['copies_total'] ? (int) $_POST['copies_total'] : null,
                'copies_available' => $_POST['copies_available'] ? (int) $_POST['copies_available'] : null,
                'subjects' => trim($_POST['subjects'] ?? ''),
                'branch_id' => (isset($_POST['branch_id']) && $_POST['branch_id'] !== '') ? (int) $_POST['branch_id'] : null,
                'shelf_id' => (isset($_POST['shelf_id']) && $_POST['shelf_id'] !== '') ? (int) $_POST['shelf_id'] : null,
                'subject_id' => (isset($_POST['subject_id']) && $_POST['subject_id'] !== '') ? (int) $_POST['subject_id'] : null,
            ]);
            header('Location: /admin/books.php?message=' . urlencode('Book updated successfully.'));
            exit;
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $bookRepository->delete($id);
            header('Location: /admin/books.php?message=' . urlencode('Book deleted.'));
            exit;
        }
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

$branches = $branchRepository->all();
$shelves = $shelfRepository->all();
$subjects = $subjectRepository->all();
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$bookToEdit = $editId ? $bookRepository->find($editId) : null;
$branchFilter = isset($_GET['branch']) && $_GET['branch'] !== '' ? (int) $_GET['branch'] : null;
$subjectFilter = isset($_GET['subject']) && $_GET['subject'] !== '' ? (int) $_GET['subject'] : null;
$shelfFilter = isset($_GET['shelf']) && $_GET['shelf'] !== '' ? (int) $_GET['shelf'] : null;
$books = $bookRepository->all($keyword, $branchFilter, $subjectFilter, $shelfFilter);

admin_header('Books', 'books');
admin_alert($_GET['message'] ?? $message);
admin_alert($_GET['error'] ?? $error, 'danger');
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><?php echo $bookToEdit ? 'Edit Book' : 'Add Book'; ?></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="<?php echo $bookToEdit ? 'update' : 'create'; ?>">
                    <?php if ($bookToEdit): ?>
                        <input type="hidden" name="id" value="<?php echo $bookToEdit['id']; ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">ISBN</label>
                        <input type="text" name="isbn" class="form-control" value="<?php echo htmlspecialchars($bookToEdit['isbn'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($bookToEdit['title'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Author</label>
                        <input type="text" name="author" class="form-control" value="<?php echo htmlspecialchars($bookToEdit['author'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Publisher</label>
                        <input type="text" name="publisher" class="form-control" value="<?php echo htmlspecialchars($bookToEdit['publisher'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Publication Year</label>
                        <input type="number" name="publication_year" class="form-control" value="<?php echo htmlspecialchars($bookToEdit['publication_year'] ?? ''); ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label class="form-label">Branch</label>
                            <select class="form-select" name="branch_id" id="book-branch" data-shelf-filter="#book-shelf">
                                <option value="">Unassigned</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>" <?php echo ((int) ($bookToEdit['branch_id'] ?? 0) === (int) $branch['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($branch['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col">
                            <label class="form-label">Shelf</label>
                            <select class="form-select" name="shelf_id" id="book-shelf">
                                <option value="">Unassigned</option>
                                <?php foreach ($shelves as $shelf): ?>
                                    <option value="<?php echo $shelf['id']; ?>" data-branch="<?php echo (int) $shelf['branch_id']; ?>" <?php echo ((int) ($bookToEdit['shelf_id'] ?? 0) === (int) $shelf['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(($shelf['branch_name'] ?? 'Branch') . ' · ' . $shelf['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Shelves filter automatically once you pick a branch.</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject Area</label>
                        <select class="form-select" name="subject_id">
                            <option value="">General</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>" <?php echo ((int) ($bookToEdit['subject_id'] ?? 0) === (int) $subject['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($subject['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label class="form-label">Total Copies</label>
                            <input type="number" min="1" name="copies_total" class="form-control" value="<?php echo htmlspecialchars($bookToEdit['copies_total'] ?? '1'); ?>">
                        </div>
                        <div class="col">
                            <label class="form-label">Available</label>
                            <input type="number" min="0" name="copies_available" class="form-control" value="<?php echo htmlspecialchars($bookToEdit['copies_available'] ?? '1'); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subjects / Keywords</label>
                        <input type="text" name="subjects" class="form-control" value="<?php echo htmlspecialchars($bookToEdit['subjects'] ?? ''); ?>">
                    </div>
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-primary" type="submit"><?php echo $bookToEdit ? 'Update Book' : 'Add Book'; ?></button>
                        <?php if ($bookToEdit): ?>
                            <a class="btn btn-outline-secondary" href="/admin/books.php">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-body">
                <form class="row g-3 align-items-end" method="get">
                    <div class="col-md-4">
                        <label class="form-label">Keyword</label>
                        <input type="text" name="keyword" class="form-control" placeholder="Search by title or author" value="<?php echo htmlspecialchars($keyword ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Branch</label>
                        <select class="form-select" name="branch" id="book-branch-filter" data-shelf-filter="#book-shelf-filter">
                            <option value="">All branches</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" <?php echo $branchFilter === (int) $branch['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($branch['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Subject</label>
                        <select class="form-select" name="subject">
                            <option value="">All subjects</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>" <?php echo $subjectFilter === (int) $subject['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($subject['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Shelf</label>
                        <select class="form-select" name="shelf" id="book-shelf-filter">
                            <option value="">Any shelf</option>
                            <?php foreach ($shelves as $shelf): ?>
                                <option value="<?php echo $shelf['id']; ?>" data-branch="<?php echo (int) $shelf['branch_id']; ?>" <?php echo $shelfFilter === (int) $shelf['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars(($shelf['branch_name'] ?? 'Branch') . ' · ' . $shelf['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-outline-secondary" type="submit">Apply Filters</button>
                        <a class="btn btn-link" href="/admin/books.php">Reset</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Book List</div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Year</th>
                        <th>Available</th>
                        <th>Location</th>
                        <th>Subject</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (! $books): ?>
                        <tr><td colspan="7" class="text-muted">No books match the selected filters.</td></tr>
                    <?php else: ?>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td>
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                                            <small class="text-muted">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></small>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a class="btn btn-sm btn-outline-primary" href="/admin/books.php?edit=<?php echo $book['id']; ?>">Edit</a>
                                            <form class="d-inline" method="post" onsubmit="return confirm('Delete this book?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $book['id']; ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo htmlspecialchars($book['publication_year']); ?></td>
                                <td><?php echo htmlspecialchars($book['copies_available']); ?> / <?php echo htmlspecialchars($book['copies_total']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($book['branch_name'] ?? '—'); ?><br>
                                    <small class="text-muted">
                                        <?php echo $book['shelf_label'] ? 'Shelf ' . htmlspecialchars($book['shelf_label']) : 'No shelf'; ?>
                                    </small>
                                </td>
                                <td><?php echo htmlspecialchars($book['subject_name'] ?? 'General'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-shelf-filter]').forEach((branchSelect) => {
        const targetSelector = branchSelect.getAttribute('data-shelf-filter');
        const shelfSelect = document.querySelector(targetSelector);
        if (! shelfSelect) {
            return;
        }
        const baseOptions = Array.from(shelfSelect.options);
        const refreshOptions = () => {
            const branchValue = branchSelect.value;
            const currentValue = shelfSelect.value;
            let activeStillVisible = false;
            shelfSelect.innerHTML = '';
            baseOptions.forEach((option) => {
                const optionNode = option.cloneNode(true);
                const optionBranch = option.dataset.branch ?? '';
                if (! branchValue || optionBranch === '' || optionBranch === branchValue) {
                    shelfSelect.appendChild(optionNode);
                    if (optionNode.value === currentValue) {
                        activeStillVisible = true;
                    }
                }
            });
            shelfSelect.value = activeStillVisible ? currentValue : '';
        };
        branchSelect.addEventListener('change', refreshOptions);
        refreshOptions();
    });
});
</script>
<?php admin_footer(); ?>
