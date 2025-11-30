<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144198
 * Email: gizawadugna@gmail.com
 */

namespace App\Repositories;

use PDO;

class UserSessionRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO user_sessions (full_name, identifier, role, channel, usage_summary, last_login_at)
            VALUES (:full_name, :identifier, :role, :channel, :usage_summary, :last_login_at)');
        $stmt->execute([
            'full_name' => $data['full_name'],
            'identifier' => $data['identifier'],
            'role' => $data['role'],
            'channel' => $data['channel'],
            'usage_summary' => $data['usage_summary'] ?? null,
            'last_login_at' => $data['last_login_at'],
        ]);

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM user_sessions WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function count(?string $role = null): int
    {
        if ($role === null) {
            return (int) $this->pdo->query('SELECT COUNT(*) FROM user_sessions')->fetchColumn();
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM user_sessions WHERE role = :role');
        $stmt->execute(['role' => $role]);
        return (int) $stmt->fetchColumn();
    }

    public function recent(int $limit = 5, ?array $roles = null, bool $includeRoles = true): array
    {
        $sql = 'SELECT * FROM user_sessions';
        $params = [];

        if ($roles && $roles !== []) {
            $placeholders = implode(', ', array_fill(0, count($roles), '?'));
            $operator = $includeRoles ? 'IN' : 'NOT IN';
            $sql .= ' WHERE role ' . $operator . ' (' . $placeholders . ')';
            foreach ($roles as $role) {
                $params[] = $role;
            }
        }

        $sql .= ' ORDER BY datetime(last_login_at) DESC, id DESC LIMIT ?';
        $params[] = $limit;

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $index => $value) {
            $paramType = $index === count($params) - 1 ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($index + 1, $value, $paramType);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function channelStats(): array
    {
        $stmt = $this->pdo->query('SELECT channel, COUNT(*) AS total FROM user_sessions GROUP BY channel ORDER BY total DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
