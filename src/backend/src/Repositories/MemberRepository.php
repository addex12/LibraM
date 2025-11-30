<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144198
 * Email: gizawadugna@gmail.com
 */

namespace App\Repositories;

use PDO;

class MemberRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT * FROM members ORDER BY full_name')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM members WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findByStudentId(string $studentId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM members WHERE student_id = :student_id');
        $stmt->execute(['student_id' => $studentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO members (student_id, full_name, faculty, email)
            VALUES (:student_id, :full_name, :faculty, :email)');
        $stmt->execute([
            'student_id' => $data['student_id'],
            'full_name' => $data['full_name'],
            'faculty' => $data['faculty'],
            'email' => $data['email'],
        ]);
        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): ?array
    {
        $allowed = ['student_id', 'full_name', 'faculty', 'email'];
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
        $sql = 'UPDATE members SET ' . implode(', ', $fields) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM members WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
