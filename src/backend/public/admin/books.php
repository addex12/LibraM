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

function validate_book_payload(array $input, array $branchIndex, array $shelfIndex, array $subjectIndex, BookRepository $bookRepository, ?int $editingId = null): array
{
    $errors = [];
    $payload = [
        'isbn' => trim((string) ($input['isbn'] ?? '')),
        'title' => trim((string) ($input['title'] ?? '')),
        'author' => trim((string) ($input['author'] ?? '')),
        'publisher' => trim((string) ($input['publisher'] ?? '')),
        'subjects' => trim((string) ($input['subjects'] ?? '')),
    ];

    if ($payload['isbn'] === '') {
        $errors[] = 'ISBN is required.';
    }

    if ($payload['title'] === '') {
        $errors[] = 'Title is required.';
    }

    if ($payload['author'] === '') {
        $errors[] = 'Author is required.';
    }

    if ($payload['publisher'] === '') {
        $payload['publisher'] = null;
    }

    $yearRaw = trim((string) ($input['publication_year'] ?? ''));
    if ($yearRaw === '') {
        $payload['publication_year'] = null;
    } elseif (! ctype_digit($yearRaw)) {
        $errors[] = 'Publication year must be a valid number.';
        $payload['publication_year'] = null;
    } else {
        $year = (int) $yearRaw;
        $currentYear = (int) date('Y') + 2;
        if ($year < 1400 || $year > $currentYear) {
            $errors[] = 'Publication year must be between 1400 and ' . $currentYear . '.';
        }
        $payload['publication_year'] = $year;
    }

    $totalRaw = (int) max(1, (int) ($input['copies_total'] ?? 1));
    $availableRaw = (int) max(0, (int) ($input['copies_available'] ?? $totalRaw));
    if ($availableRaw > $totalRaw) {
        $errors[] = 'Available copies cannot exceed the total copies.';
    }
    $payload['copies_total'] = $totalRaw;
    $payload['copies_available'] = min($availableRaw, $totalRaw);

    $payload['subjects'] = $payload['subjects'] !== '' ? $payload['subjects'] : null;

    $branchValue = trim((string) ($input['branch_id'] ?? ''));
    if ($branchValue === '') {
        $payload['branch_id'] = null;
    } else {
        $branchId = (int) $branchValue;
        if (! isset($branchIndex[$branchId])) {
            $errors[] = 'Selected branch is invalid.';
            $payload['branch_id'] = null;
        } else {
            $payload['branch_id'] = $branchId;
        }
    }

    $shelfValue = trim((string) ($input['shelf_id'] ?? ''));
    if ($shelfValue === '') {
        $payload['shelf_id'] = null;
    } else {
        $shelfId = (int) $shelfValue;
        if (! isset($shelfIndex[$shelfId])) {
            $errors[] = 'Selected shelf is invalid.';
            $payload['shelf_id'] = null;
        } else {
            $payload['shelf_id'] = $shelfId;
            if ($payload['branch_id'] !== null && (int) $shelfIndex[$shelfId]['branch_id'] !== $payload['branch_id']) {
                $errors[] = 'Selected shelf does not belong to the chosen branch.';
            }
        }
    }

    $subjectValue = trim((string) ($input['subject_id'] ?? ''));
    if ($subjectValue === '') {
        $payload['subject_id'] = null;
    } else {
        $subjectId = (int) $subjectValue;
        if (! isset($subjectIndex[$subjectId])) {
            $errors[] = 'Selected subject area is invalid.';
            $payload['subject_id'] = null;
        } else {
            $payload['subject_id'] = $subjectId;
        }
    }

    if ($payload['isbn'] !== '') {
        $existing = $bookRepository->findByIsbn($payload['isbn']);
        if ($existing && (int) $existing['id'] !== (int) $editingId) {
            $errors[] = 'Another book with this ISBN already exists.';
        }
    }

    return [$payload, $errors];
}

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
$keyword = isset($_GET['keyword']) ? trim((string) $_GET['keyword']) : null;

$branches = $branchRepository->all();
$shelves = $shelfRepository->all();
$subjects = $subjectRepository->all();
$branchIndex = [];
foreach ($branches as $branch) {
    $branchIndex[(int) $branch['id']] = $branch;
}
$shelfIndex = [];
foreach ($shelves as $shelf) {
    $shelfIndex[(int) $shelf['id']] = $shelf;
}
$subjectIndex = [];
foreach ($subjects as $subject) {
    $subjectIndex[(int) $subject['id']] = $subject;
}

$bookToEdit = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    try {
        if ($action === 'seed_sample') {
            if (! is_super_admin()) {
                throw new RuntimeException('Only super administrators can reload sample data.');
            }
            require __DIR__ . '/../../scripts/seed.php';
            header('Location: /admin/books.php?message=' . urlencode('Sample catalog ensured.'));
            exit;
        }
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $bookRepository->delete($id);
            header('Location: /admin/books.php?message=' . urlencode('Book deleted.'));
            exit;
        }

        $editingId = $action === 'update' ? (int) ($_POST['id'] ?? 0) : null;
        [$payload, $validationErrors] = validate_book_payload($_POST, $branchIndex, $shelfIndex, $subjectIndex, $bookRepository, $editingId);

        if ($validationErrors) {
            $error = implode('<br>', $validationErrors);
            $bookToEdit = $payload;
            if ($editingId) {
                $bookToEdit['id'] = $editingId;
            }
        } else {
            if ($action === 'create') {
                $bookRepository->create($payload);
                header('Location: /admin/books.php?message=' . urlencode('Book added successfully.'));
                exit;
            }

            if ($action === 'update' && $editingId) {
                $bookRepository->update($editingId, $payload);
                header('Location: /admin/books.php?message=' . urlencode('Book updated successfully.'));
                exit;
            }

            $error = 'Unknown action requested.';
        }
    } catch (Throwable $throwable) {
        $error = $throwable->getMessage();
    }
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
if (! $bookToEdit && $editId) {
    $bookToEdit = $bookRepository->find($editId);
}
$branchFilter = isset($_GET['branch']) && $_GET['branch'] !== '' ? (int) $_GET['branch'] : null;
$subjectFilter = isset($_GET['subject']) && $_GET['subject'] !== '' ? (int) $_GET['subject'] : null;
$shelfFilter = isset($_GET['shelf']) && $_GET['shelf'] !== '' ? (int) $_GET['shelf'] : null;
$books = $bookRepository->all($keyword ?: null, $branchFilter, $subjectFilter, $shelfFilter);
$bookCount = count($books);
$filtersApplied = ($keyword !== null && $keyword !== '') || $branchFilter !== null || $subjectFilter !== null || $shelfFilter !== null;
$dbPath = null;
try {
    $dbRow = $pdo->query('PRAGMA database_list')->fetch(PDO::FETCH_ASSOC);
    if ($dbRow && isset($dbRow['file'])) {
        $dbPath = $dbRow['file'];
    }
} catch (Throwable $ignored) {
}

admin_header('Books', 'books');
admin_alert($_GET['message'] ?? $message);
admin_alert($_GET['error'] ?? $error, 'danger');
if (! $filtersApplied && $bookCount <= 1) {
    admin_alert('Only ' . $bookCount . ' book currently stored. Run "php scripts/seed.php" from src/backend (or use the Reload Sample Data button) to load the full sample catalog.', 'info');
}
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header" id="book-form-title"><?php echo $bookToEdit ? 'Edit Book' : 'Add Book'; ?></div>
            <div class="card-body">
                <form method="post" id="book-form">
                    <input type="hidden" name="id" id="book-id-field" value="<?php echo htmlspecialchars((string) ($bookToEdit['id'] ?? '')); ?>">
                    <div class="mb-3">
                        <label class="form-label">ISBN</label>
                        <input type="text" name="isbn" id="book-isbn" class="form-control" value="<?php echo htmlspecialchars($bookToEdit['isbn'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" id="book-title" class="form-control" value="<?php echo htmlspecialchars($bookToEdit['title'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Author</label>
                        <input type="text" name="author" id="book-author" class="form-control" value="<?php echo htmlspecialchars($bookToEdit['author'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Publisher</label>
                        <input type="text" name="publisher" id="book-publisher" class="form-control" value="<?php echo htmlspecialchars($bookToEdit['publisher'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Publication Year</label>
                        <input type="number" name="publication_year" id="book-year" class="form-control" value="<?php echo htmlspecialchars($bookToEdit['publication_year'] ?? ''); ?>">
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
                        <select class="form-select" name="subject_id" id="book-subject">
                            <option value="">General</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>" <?php echo ((int) ($bookToEdit['subject_id'] ?? 0) === (int) $subject['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($subject['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label class="form-label">Total Copies</label>
                            <input type="number" min="1" name="copies_total" id="book-total" class="form-control" value="<?php echo htmlspecialchars($bookToEdit['copies_total'] ?? '1'); ?>">
                        </div>
                        <div class="col">
                            <label class="form-label">Available</label>
                            <input type="number" min="0" name="copies_available" id="book-available" class="form-control" value="<?php echo htmlspecialchars($bookToEdit['copies_available'] ?? '1'); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subjects / Keywords</label>
                        <input type="text" name="subjects" id="book-subjects" class="form-control" value="<?php echo htmlspecialchars($bookToEdit['subjects'] ?? ''); ?>">
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" type="submit" name="action" value="create" id="book-create-button">Add Book</button>
                        <button class="btn btn-success" type="submit" name="action" value="update" id="book-update-button" <?php echo $bookToEdit ? '' : 'disabled'; ?>>Update Book</button>
                        <button class="btn btn-outline-secondary" type="button" id="book-reset-button">Cancel</button>
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
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>Book List</div>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <small class="text-muted">Showing <?php echo $bookCount; ?> book(s)<?php echo $dbPath ? ' · DB: ' . htmlspecialchars(basename($dbPath)) : ''; ?></small>
                    <?php if (is_super_admin()): ?>
                        <form method="post" class="d-inline" onsubmit="return confirm('Re-run the data seeder? This operation is idempotent.');">
                            <input type="hidden" name="action" value="seed_sample">
                            <button class="btn btn-sm btn-outline-secondary" type="submit">Reload Sample Data</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped mb-0 book-table">
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
                        <tr><td colspan="6" class="text-muted">No books match the selected filters.</td></tr>
                    <?php else: ?>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td class="book-title-cell">
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <?php $bookPayload = [
            'id' => $book['id'],
            'isbn' => $book['isbn'],
            'title' => $book['title'],
            'author' => $book['author'],
            'publisher' => $book['publisher'],
            'publication_year' => $book['publication_year'],
            'copies_total' => $book['copies_total'],
            'copies_available' => $book['copies_available'],
            'subjects' => $book['subjects'],
            'branch_id' => $book['branch_id'],
            'shelf_id' => $book['shelf_id'],
            'subject_id' => $book['subject_id'],
        ]; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                                            <small class="text-muted">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></small>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary book-edit-button" data-book='<?php echo htmlspecialchars(json_encode($bookPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)); ?>'>Edit</button>
                                            <form class="d-inline" method="post" onsubmit="return confirm('Delete this book?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $book['id']; ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo $book['publication_year'] ? htmlspecialchars((string) $book['publication_year']) : '—'; ?></td>
                                <td class="book-availability">
                                    <?php if ($book['copies_total'] !== null): ?>
                                        <?php echo htmlspecialchars((string) $book['copies_available']); ?> / <?php echo htmlspecialchars((string) $book['copies_total']); ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td class="book-location">
                                    <div class="fw-semibold mb-1"><?php echo htmlspecialchars($book['branch_name'] ?? '—'); ?></div>
                                    <div class="text-muted small">
                                        <?php if ($book['shelf_label']): ?>
                                            <span class="badge text-bg-light">Shelf <?php echo htmlspecialchars($book['shelf_label']); ?></span>
                                        <?php else: ?>
                                            <span>No shelf</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="book-subject-cell"><?php echo htmlspecialchars($book['subject_name'] ?? 'General'); ?></td>
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
        branchSelect.__refreshShelves = refreshOptions;
        branchSelect.__shelfTarget = shelfSelect;
        refreshOptions();
    });

    const form = document.getElementById('book-form');
    if (! form) {
        return;
    }
    const elements = {
        id: document.getElementById('book-id-field'),
        isbn: document.getElementById('book-isbn'),
        title: document.getElementById('book-title'),
        author: document.getElementById('book-author'),
        publisher: document.getElementById('book-publisher'),
        year: document.getElementById('book-year'),
        branch: document.getElementById('book-branch'),
        shelf: document.getElementById('book-shelf'),
        subject: document.getElementById('book-subject'),
        total: document.getElementById('book-total'),
        available: document.getElementById('book-available'),
        subjects: document.getElementById('book-subjects'),
    };
    const formTitle = document.getElementById('book-form-title');
    const defaultTitle = formTitle ? formTitle.textContent : 'Add Book';
    const updateButton = document.getElementById('book-update-button');
    const createButton = document.getElementById('book-create-button');
    const resetButton = document.getElementById('book-reset-button');

    const applyBranchAndShelf = (branchId, shelfId) => {
        if (elements.branch) {
            elements.branch.value = branchId ?? '';
            if (typeof elements.branch.__refreshShelves === 'function') {
                elements.branch.__refreshShelves();
            }
        }
        if (elements.shelf) {
            elements.shelf.value = shelfId ?? '';
        }
    };

    const fillForm = (book) => {
        if (! book) {
            return;
        }
        if (elements.id) {
            elements.id.value = book.id ?? '';
        }
        if (elements.isbn) {
            elements.isbn.value = book.isbn ?? '';
        }
        if (elements.title) {
            elements.title.value = book.title ?? '';
        }
        if (elements.author) {
            elements.author.value = book.author ?? '';
        }
        if (elements.publisher) {
            elements.publisher.value = book.publisher ?? '';
        }
        if (elements.year) {
            elements.year.value = book.publication_year ?? '';
        }
        if (elements.total) {
            elements.total.value = book.copies_total ?? '1';
        }
        if (elements.available) {
            elements.available.value = book.copies_available ?? '1';
        }
        if (elements.subjects) {
            elements.subjects.value = book.subjects ?? '';
        }
        if (elements.subject) {
            elements.subject.value = book.subject_id ?? '';
        }
        applyBranchAndShelf(book.branch_id ?? '', book.shelf_id ?? '');
    };

    const setEditingState = (editing) => {
        if (updateButton) {
            updateButton.disabled = ! editing;
        }
        if (formTitle) {
            formTitle.textContent = editing ? 'Edit Book' : defaultTitle;
        }
    };

    document.querySelectorAll('.book-edit-button').forEach((button) => {
        button.addEventListener('click', () => {
            const payload = button.dataset.book ? JSON.parse(button.dataset.book) : null;
            fillForm(payload);
            setEditingState(true);
        });
    });

    if (resetButton) {
        resetButton.addEventListener('click', () => {
            form.reset();
            if (elements.id) {
                elements.id.value = '';
            }
            if (elements.branch && typeof elements.branch.__refreshShelves === 'function') {
                elements.branch.__refreshShelves();
            }
            setEditingState(false);
        });
    }

    if (elements.id && elements.id.value) {
        setEditingState(true);
    }
});
</script>
<?php admin_footer(); ?>
