<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144198
 * Email: gizawadugna@gmail.com
 */

namespace App\Repositories;

use PDO;

class StaffRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        $sql = 'SELECT staff.*, branches.name AS branch_name
                FROM staff
                LEFT JOIN branches ON branches.id = staff.branch_id
                ORDER BY staff.full_name';
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM staff WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO staff (employee_id, full_name, role, email, phone, branch_id)
            VALUES (:employee_id, :full_name, :role, :email, :phone, :branch_id)');
        $stmt->execute([
            'employee_id' => strtoupper($data['employee_id']),
            'full_name' => $data['full_name'],
            'role' => $data['role'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
        ]);

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): ?array
    {
        $allowed = ['employee_id', 'full_name', 'role', 'email', 'phone', 'branch_id'];
        $parts = [];
        $params = ['id' => $id];
        foreach ($allowed as $column) {
            if (array_key_exists($column, $data)) {
                $parts[] = "$column = :$column";
                $params[$column] = in_array($column, ['employee_id']) ? strtoupper((string) $data[$column]) : $data[$column];
            }
        }

        if ($parts) {
            $sql = 'UPDATE staff SET ' . implode(', ', $parts) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        }

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM staff WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }
}
