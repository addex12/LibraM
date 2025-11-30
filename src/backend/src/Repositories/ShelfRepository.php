<?php

namespace App\Repositories;

use PDO;

class ShelfRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        $sql = 'SELECT shelves.*, branches.name AS branch_name
                FROM shelves
                LEFT JOIN branches ON branches.id = shelves.branch_id
                ORDER BY branches.name, shelves.label';
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function forBranch(int $branchId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM shelves WHERE branch_id = :branch ORDER BY label');
        $stmt->execute(['branch' => $branchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM shelves WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO shelves (branch_id, code, label, floor, capacity)
            VALUES (:branch_id, :code, :label, :floor, :capacity)');
        $stmt->execute([
            'branch_id' => $data['branch_id'],
            'code' => strtoupper($data['code']),
            'label' => $data['label'],
            'floor' => $data['floor'] ?? null,
            'capacity' => $data['capacity'] ?? null,
        ]);

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): ?array
    {
        $allowed = ['branch_id', 'code', 'label', 'floor', 'capacity'];
        $parts = [];
        $params = ['id' => $id];
        foreach ($allowed as $column) {
            if (array_key_exists($column, $data)) {
                $parts[] = "$column = :$column";
                $params[$column] = $column === 'code' ? strtoupper((string) $data[$column]) : $data[$column];
            }
        }

        if ($parts) {
            $sql = 'UPDATE shelves SET ' . implode(', ', $parts) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        }

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM shelves WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }
}
