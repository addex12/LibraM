<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144198
 * Email: gizawadugna@gmail.com
 */

declare(strict_types=1);

use App\Repositories\BookRepository;

$pdo = require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/includes/auth.php';
require_admin_login();
require __DIR__ . '/includes/layout.php';

$bookRepository = new BookRepository($pdo);
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

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$bookToEdit = $editId ? $bookRepository->find($editId) : null;
$books = $bookRepository->all($keyword);

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
                <form class="row g-2" method="get">
                    <div class="col">
                        <input type="text" name="keyword" class="form-control" placeholder="Search books" value="<?php echo htmlspecialchars($keyword ?? ''); ?>">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
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
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (! $books): ?>
                        <tr><td colspan="5" class="text-muted">No books yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                                    <small class="text-muted">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo htmlspecialchars($book['publication_year']); ?></td>
                                <td><?php echo htmlspecialchars($book['copies_available']); ?> / <?php echo htmlspecialchars($book['copies_total']); ?></td>
                                <td class="table-actions">
                                    <a class="btn btn-sm btn-outline-primary" href="/admin/books.php?edit=<?php echo $book['id']; ?>">Edit</a>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this book?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $book['id']; ?>">
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
