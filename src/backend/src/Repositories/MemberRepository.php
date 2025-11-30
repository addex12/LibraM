<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
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
        $columns = 'id, student_id, full_name, faculty, email, phone, address, status, created_at, updated_at';
        return $this->pdo->query('SELECT ' . $columns . ' FROM members ORDER BY full_name')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function recent(int $limit = 5): array
    {
        $columns = 'id, student_id, full_name, faculty, email, created_at';
        $stmt = $this->pdo->prepare('SELECT ' . $columns . ' FROM members ORDER BY datetime(created_at) DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        $stmt->execute(['student_id' => strtoupper($studentId)]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO members (student_id, full_name, faculty, email, password_hash)
            VALUES (:student_id, :full_name, :faculty, :email, :password_hash)');
        $passwordHash = $data['password_hash'] ?? null;
        if (! $passwordHash && ! empty($data['password'])) {
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $stmt->execute([
            'student_id' => strtoupper($data['student_id']),
            'full_name' => $data['full_name'],
            'faculty' => $data['faculty'],
            'email' => $data['email'],
            'password_hash' => $passwordHash,
        ]);
        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): ?array
    {
        $allowed = ['student_id', 'full_name', 'faculty', 'email', 'password_hash'];
        $fields = [];
        $params = ['id' => $id];
        foreach ($allowed as $column) {
            if (array_key_exists($column, $data)) {
                $fields[] = "$column = :$column";
                $params[$column] = $column === 'student_id' ? strtoupper($data[$column]) : $data[$column];
            }
        }
        if (! isset($data['password_hash']) && isset($data['password'])) {
            $params['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $fields[] = 'password_hash = :password_hash';
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
