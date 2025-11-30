<?php

namespace App\Repositories;

use PDO;

class FineRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(?string $status = null): array
    {
        $sql = 'SELECT fines.*, members.full_name AS member_name, loans.book_id, loans.due_on, books.title AS book_title
            FROM fines
            JOIN members ON members.id = fines.member_id
            JOIN loans ON loans.id = fines.loan_id
            LEFT JOIN books ON books.id = loans.book_id';
        $params = [];
        if ($status) {
            $sql .= ' WHERE fines.status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY fines.assessed_on DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fines WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO fines (loan_id, member_id, amount, reason, status, assessed_on, settled_on)
            VALUES (:loan_id, :member_id, :amount, :reason, :status, :assessed_on, :settled_on)');
        $stmt->execute([
            'loan_id' => $data['loan_id'],
            'member_id' => $data['member_id'],
            'amount' => $data['amount'],
            'reason' => $data['reason'] ?? null,
            'status' => $data['status'] ?? 'unpaid',
            'assessed_on' => $data['assessed_on'] ?? date('Y-m-d H:i:s'),
            'settled_on' => $data['settled_on'] ?? null,
        ]);

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): ?array
    {
        $allowed = ['loan_id', 'member_id', 'amount', 'reason', 'status', 'assessed_on', 'settled_on'];
        $parts = [];
        $params = ['id' => $id];
        foreach ($allowed as $column) {
            if (array_key_exists($column, $data)) {
                $parts[] = "$column = :$column";
                $params[$column] = $data[$column];
            }
        }

        if ($parts) {
            $sql = 'UPDATE fines SET ' . implode(', ', $parts) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        }

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM fines WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    public function markPaid(int $id, ?string $settledOn = null): ?array
    {
        return $this->update($id, [
            'status' => 'paid',
            'settled_on' => $settledOn ?? date('Y-m-d H:i:s'),
        ]);
    }

    public function unpaidForMember(int $memberId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fines WHERE member_id = :member AND status = "unpaid" ORDER BY assessed_on DESC');
        $stmt->execute(['member' => $memberId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function totalOutstandingForMember(int $memberId): float
    {
        $stmt = $this->pdo->prepare('SELECT SUM(amount) FROM fines WHERE member_id = :member AND status = "unpaid"');
        $stmt->execute(['member' => $memberId]);
        $total = $stmt->fetchColumn();

        return (float) ($total ?: 0);
    }
}
