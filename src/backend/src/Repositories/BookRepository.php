<?php

namespace App\Repositories;

use PDO;

class BookRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(?string $keyword = null): array
    {
        if ($keyword) {
            $stmt = $this->pdo->prepare('SELECT * FROM books WHERE lower(title) LIKE :kw OR lower(author) LIKE :kw OR lower(subjects) LIKE :kw ORDER BY title');
            $stmt->execute(['kw' => '%' . strtolower($keyword) . '%']);
        } else {
            $stmt = $this->pdo->query('SELECT * FROM books ORDER BY title');
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM books WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO books (isbn, title, author, publisher, publication_year, copies_total, copies_available, subjects)
            VALUES (:isbn, :title, :author, :publisher, :publication_year, :copies_total, :copies_available, :subjects)');
        $stmt->execute([
            'isbn' => $data['isbn'],
            'title' => $data['title'],
            'author' => $data['author'],
            'publisher' => $data['publisher'] ?? null,
            'publication_year' => $data['publication_year'] ?? null,
            'copies_total' => $data['copies_total'] ?? 1,
            'copies_available' => $data['copies_available'] ?? ($data['copies_total'] ?? 1),
            'subjects' => $data['subjects'] ?? null,
        ]);
        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): ?array
    {
        $allowed = ['isbn', 'title', 'author', 'publisher', 'publication_year', 'copies_total', 'copies_available', 'subjects'];
        $fields = [];
        $params = ['id' => $id];
        foreach ($allowed as $column) {
            if (array_key_exists($column, $data)) {
                $fields[] = "$column = :$column";
                $params[$column] = $data[$column];
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
}
