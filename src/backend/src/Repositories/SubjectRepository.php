<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
 * Email: gizawadugna@gmail.com
 */

namespace App\Repositories;

use PDO;

class SubjectRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT * FROM subjects ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM subjects WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO subjects (name, description) VALUES (:name, :description)');
        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): ?array
    {
        $allowed = ['name', 'description'];
        $parts = [];
        $params = ['id' => $id];
        foreach ($allowed as $column) {
            if (array_key_exists($column, $data)) {
                $parts[] = "$column = :$column";
                $params[$column] = $data[$column];
            }
        }

        if ($parts) {
            $sql = 'UPDATE subjects SET ' . implode(', ', $parts) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        }

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM subjects WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }
}
