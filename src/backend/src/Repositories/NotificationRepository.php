<?php

namespace App\Repositories;

use PDO;

class NotificationRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(?string $status = null, ?string $channel = null): array
    {
        $sql = 'SELECT notifications.*, members.full_name AS member_name, reservations.book_id
                FROM notifications
                LEFT JOIN members ON members.id = notifications.member_id
                LEFT JOIN reservations ON reservations.id = notifications.reservation_id';
        $params = [];
        $clauses = [];
        if ($status) {
            $clauses[] = 'notifications.status = :status';
            $params['status'] = $status;
        }
        if ($channel) {
            $clauses[] = 'notifications.channel = :channel';
            $params['channel'] = $channel;
        }
        if ($clauses) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }
        $sql .= ' ORDER BY notifications.created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM notifications WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function queue(array $data): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO notifications (member_id, reservation_id, channel, type, payload, status)
            VALUES (:member_id, :reservation_id, :channel, :type, :payload, :status)');
        $stmt->execute([
            'member_id' => $data['member_id'] ?? null,
            'reservation_id' => $data['reservation_id'] ?? null,
            'channel' => $data['channel'],
            'type' => $data['type'],
            'payload' => $data['payload'] ?? null,
            'status' => $data['status'] ?? 'pending',
        ]);

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): ?array
    {
        $allowed = ['member_id', 'reservation_id', 'channel', 'type', 'payload', 'status', 'sent_at'];
        $parts = [];
        $params = ['id' => $id];
        foreach ($allowed as $column) {
            if (array_key_exists($column, $data)) {
                $parts[] = "$column = :$column";
                $params[$column] = $data[$column];
            }
        }

        if ($parts) {
            $sql = 'UPDATE notifications SET ' . implode(', ', $parts) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        }

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM notifications WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    public function claimPending(string $channel, int $limit = 10): array
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM notifications WHERE status = "pending" AND channel = :channel ORDER BY created_at ASC LIMIT :limit');
            $stmt->bindValue(':channel', $channel);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $batch = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (! $batch) {
                $this->pdo->commit();
                return [];
            }

            $ids = array_column($batch, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $update = $this->pdo->prepare('UPDATE notifications SET status = "sending" WHERE id IN (' . $placeholders . ')');
            $update->execute($ids);
            $this->pdo->commit();

            return $batch;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function markSent(int $id, ?string $sentAt = null): ?array
    {
        return $this->update($id, [
            'status' => 'sent',
            'sent_at' => $sentAt ?? date('Y-m-d H:i:s'),
        ]);
    }

    public function pendingForMember(int $memberId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM notifications WHERE member_id = :member AND status IN ("pending", "sending")');
        $stmt->execute(['member' => $memberId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
