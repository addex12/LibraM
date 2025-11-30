<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
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
        $sql = 'SELECT staff.id, staff.employee_id, staff.full_name, staff.role, staff.email, staff.phone, staff.branch_id, staff.created_at, staff.updated_at, branches.name AS branch_name
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

    public function findByEmployeeId(string $employeeId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM staff WHERE employee_id = :employee_id');
        $stmt->execute(['employee_id' => strtoupper($employeeId)]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO staff (employee_id, full_name, role, email, phone, branch_id, password_hash)
            VALUES (:employee_id, :full_name, :role, :email, :phone, :branch_id, :password_hash)');
        $passwordHash = $data['password_hash'] ?? null;
        if (! $passwordHash && ! empty($data['password'])) {
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $stmt->execute([
            'employee_id' => strtoupper($data['employee_id']),
            'full_name' => $data['full_name'],
            'role' => $data['role'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'password_hash' => $passwordHash,
        ]);

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): ?array
    {
        $allowed = ['employee_id', 'full_name', 'role', 'email', 'phone', 'branch_id', 'password_hash'];
        $parts = [];
        $params = ['id' => $id];
        foreach ($allowed as $column) {
            if (array_key_exists($column, $data)) {
                $parts[] = "$column = :$column";
                $params[$column] = in_array($column, ['employee_id']) ? strtoupper((string) $data[$column]) : $data[$column];
            }
        }

        if (! isset($data['password_hash']) && isset($data['password'])) {
            $parts[] = 'password_hash = :password_hash';
            $params['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (! $parts) {
            return $this->find($id);
        }

        $sql = 'UPDATE staff SET ' . implode(', ', $parts) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM staff WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }
}
