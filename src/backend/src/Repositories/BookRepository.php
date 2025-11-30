<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
 * Email: gizawadugna@gmail.com
 */

namespace App\Repositories;

use PDO;

class BookRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(?string $keyword = null, ?int $branchId = null, ?int $subjectId = null, ?int $shelfId = null): array
    {
        $sql = 'SELECT books.*, branches.name AS branch_name, shelves.label AS shelf_label, subjects.name AS subject_name
            FROM books
            LEFT JOIN branches ON branches.id = books.branch_id
            LEFT JOIN shelves ON shelves.id = books.shelf_id
            LEFT JOIN subjects ON subjects.id = books.subject_id
            WHERE 1 = 1';
        $params = [];

        if ($keyword) {
            $sql .= ' AND (lower(books.title) LIKE :kw OR lower(books.author) LIKE :kw OR lower(books.subjects) LIKE :kw)';
            $params['kw'] = '%' . strtolower($keyword) . '%';
        }

        if ($branchId !== null) {
            $sql .= ' AND books.branch_id = :branch_id';
            $params['branch_id'] = $branchId;
        }

        if ($subjectId !== null) {
            $sql .= ' AND books.subject_id = :subject_id';
            $params['subject_id'] = $subjectId;
        }

        if ($shelfId !== null) {
            $sql .= ' AND books.shelf_id = :shelf_id';
            $params['shelf_id'] = $shelfId;
        }

        $sql .= ' ORDER BY books.title';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM books WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findByIsbn(string $isbn): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM books WHERE isbn = :isbn');
        $stmt->execute(['isbn' => $isbn]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO books (isbn, title, author, publisher, publication_year, copies_total, copies_available, subjects, branch_id, shelf_id, subject_id)
            VALUES (:isbn, :title, :author, :publisher, :publication_year, :copies_total, :copies_available, :subjects, :branch_id, :shelf_id, :subject_id)');
        $stmt->execute([
            'isbn' => $data['isbn'],
            'title' => $data['title'],
            'author' => $data['author'],
            'publisher' => $data['publisher'] ?? null,
            'publication_year' => $data['publication_year'] ?? null,
            'copies_total' => $data['copies_total'] ?? 1,
            'copies_available' => $data['copies_available'] ?? ($data['copies_total'] ?? 1),
            'subjects' => $data['subjects'] ?? null,
            'branch_id' => $this->normalizeId($data['branch_id'] ?? null),
            'shelf_id' => $this->normalizeId($data['shelf_id'] ?? null),
            'subject_id' => $this->normalizeId($data['subject_id'] ?? null),
        ]);
        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): ?array
    {
        $allowed = ['isbn', 'title', 'author', 'publisher', 'publication_year', 'copies_total', 'copies_available', 'subjects', 'branch_id', 'shelf_id', 'subject_id'];
        $fields = [];
        $params = ['id' => $id];
        foreach ($allowed as $column) {
            if (array_key_exists($column, $data)) {
                $fields[] = "$column = :$column";
                $params[$column] = in_array($column, ['branch_id', 'shelf_id', 'subject_id'], true)
                    ? $this->normalizeId($data[$column])
                    : $data[$column];
            }
        }
        if (! $fields) {
            return $this->find($id);
        }
        $sql = 'UPDATE books SET ' . implode(', ', $fields) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM books WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    private function normalizeId(mixed $value): ?int
    {
        if ($value === null || $value === '' || (is_numeric($value) && (int) $value === 0)) {
            return null;
        }

        return (int) $value;
    }
}
