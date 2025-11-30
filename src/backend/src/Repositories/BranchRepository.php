<?php

namespace App\Repositories;

use PDO;

class BranchRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT * FROM branches ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM branches WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO branches (code, name, location, contact_email, contact_phone, hours)
            VALUES (:code, :name, :location, :contact_email, :contact_phone, :hours)');
        $stmt->execute([
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'location' => $data['location'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'hours' => $data['hours'] ?? null,
        ]);

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): ?array
    {
        $allowed = ['code', 'name', 'location', 'contact_email', 'contact_phone', 'hours'];
        $parts = [];
        $params = ['id' => $id];
        foreach ($allowed as $column) {
            if (array_key_exists($column, $data)) {
                $parts[] = "$column = :$column";
                $params[$column] = $column === 'code' ? strtoupper((string) $data[$column]) : $data[$column];
            }
        }

        if ($parts) {
            $sql = 'UPDATE branches SET ' . implode(', ', $parts) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        }

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM branches WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }
}
