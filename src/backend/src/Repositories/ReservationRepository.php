<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
 * Email: gizawadugna@gmail.com
 */

namespace App\Repositories;

use DateInterval;
use DateTimeImmutable;
use PDO;

class ReservationRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(?string $status = null): array
    {
        $sql = 'SELECT reservations.*, books.title AS book_title, members.full_name AS member_name
                FROM reservations
                JOIN books ON books.id = reservations.book_id
                JOIN members ON members.id = reservations.member_id';
        $params = [];
        if ($status) {
            $sql .= ' WHERE reservations.status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY reservations.status, reservations.queue_position';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM reservations WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(array $data): array
    {
        $position = $this->nextPosition((int) $data['book_id']);
        $stmt = $this->pdo->prepare('INSERT INTO reservations (book_id, member_id, status, reserved_on, queue_position)
            VALUES (:book_id, :member_id, :status, :reserved_on, :queue_position)');
        $stmt->execute([
            'book_id' => $data['book_id'],
            'member_id' => $data['member_id'],
            'status' => $data['status'] ?? 'pending',
            'reserved_on' => $data['reserved_on'] ?? date('Y-m-d H:i:s'),
            'queue_position' => $position,
        ]);

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function forMember(int $memberId): array
    {
        $sql = 'SELECT reservations.*, books.title AS book_title
                FROM reservations
                JOIN books ON books.id = reservations.book_id
                WHERE reservations.member_id = :member
                ORDER BY reservations.reserved_on DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['member' => $memberId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(int $id, array $data): ?array
    {
        $allowed = ['book_id', 'member_id', 'status', 'reserved_on', 'ready_on', 'expires_on', 'notified_on', 'queue_position'];
        $parts = [];
        $params = ['id' => $id];
        foreach ($allowed as $column) {
            if (array_key_exists($column, $data)) {
                $parts[] = "$column = :$column";
                $params[$column] = $data[$column];
            }
        }

        if ($parts) {
            $sql = 'UPDATE reservations SET ' . implode(', ', $parts) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        }

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM reservations WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    public function activateNextForBook(int $bookId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM reservations WHERE book_id = :book AND status = "pending" ORDER BY queue_position ASC, reserved_on ASC LIMIT 1');
        $stmt->execute(['book' => $bookId]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        if (! $reservation) {
            return null;
        }

        $readyOn = new DateTimeImmutable();
        $expiresOn = $readyOn->add(new DateInterval('P3D'));

        return $this->update((int) $reservation['id'], [
            'status' => 'ready',
            'ready_on' => $readyOn->format('Y-m-d H:i:s'),
            'expires_on' => $expiresOn->format('Y-m-d H:i:s'),
            'notified_on' => $readyOn->format('Y-m-d H:i:s'),
        ]);
    }

    public function expireReadyReservations(string $reference = 'now'): int
    {
        $cutoff = $reference === 'now' ? date('Y-m-d H:i:s') : $reference;
        $stmt = $this->pdo->prepare('UPDATE reservations SET status = "expired" WHERE status = "ready" AND expires_on IS NOT NULL AND expires_on < :cutoff');
        $stmt->execute(['cutoff' => $cutoff]);

        return $stmt->rowCount();
    }

    private function nextPosition(int $bookId): int
    {
        $stmt = $this->pdo->prepare('SELECT MAX(queue_position) AS max_pos FROM reservations WHERE book_id = :book_id');
        $stmt->execute(['book_id' => $bookId]);
        $max = $stmt->fetchColumn();

        return ((int) $max) + 1;
    }
}
