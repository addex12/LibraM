<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144198
 * Email: gizawadugna@gmail.com
 */

namespace App\Repositories;

use DateTimeImmutable;
use PDO;
use RuntimeException;

class LoanRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        $sql = 'SELECT loans.*, books.title AS book_title, members.full_name AS member_name
                FROM loans
                JOIN books ON books.id = loans.book_id
                JOIN members ON members.id = loans.member_id
                ORDER BY loans.borrowed_on DESC';
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function forMember(int $memberId): array
    {
        $sql = 'SELECT loans.*, books.title AS book_title
                FROM loans
                JOIN books ON books.id = loans.book_id
                WHERE loans.member_id = :member_id
                ORDER BY loans.borrowed_on DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['member_id' => $memberId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): ?array
    {
        $book = $this->pdo->prepare('SELECT * FROM books WHERE id = :id');
        $book->execute(['id' => $data['book_id']]);
        $bookRow = $book->fetch(PDO::FETCH_ASSOC);
        if (! $bookRow || (int) $bookRow['copies_available'] <= 0) {
            return null;
        }

        $this->pdo->beginTransaction();
        try {
            $loanStmt = $this->pdo->prepare('INSERT INTO loans (book_id, member_id, borrowed_on, due_on, returned_on, status)
                VALUES (:book_id, :member_id, :borrowed_on, :due_on, :returned_on, :status)');
            $loanStmt->execute([
                'book_id' => $data['book_id'],
                'member_id' => $data['member_id'],
                'borrowed_on' => $data['borrowed_on'],
                'due_on' => $data['due_on'],
                'returned_on' => $data['returned_on'] ?? null,
                'status' => $data['status'] ?? 'borrowed',
            ]);

            $updateBook = $this->pdo->prepare('UPDATE books SET copies_available = copies_available - 1 WHERE id = :id');
            $updateBook->execute(['id' => $data['book_id']]);

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM loans WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function update(int $id, array $data): ?array
    {
        $allowed = ['book_id', 'member_id', 'borrowed_on', 'due_on', 'returned_on', 'status'];
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
        $sql = 'UPDATE loans SET ' . implode(', ', $fields) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        if (isset($data['status']) && in_array($data['status'], ['returned', 'overdue'], true)) {
            $loan = $this->find($id);
            if ($loan) {
                $bookStmt = $this->pdo->prepare('UPDATE books SET copies_available = copies_available + 1 WHERE id = :id');
                $bookStmt->execute(['id' => $loan['book_id']]);
            }
        }

        return $this->find($id);
    }

    public function renew(int $id, string $newDueOn): ?array
    {
        $loan = $this->find($id);
        if (! $loan) {
            throw new RuntimeException('Loan not found.');
        }
        if (($loan['status'] ?? 'borrowed') !== 'borrowed') {
            throw new RuntimeException('Only active loans can be renewed.');
        }

        $currentDue = new DateTimeImmutable($loan['due_on']);
        $candidate = new DateTimeImmutable($newDueOn);
        if ($candidate <= $currentDue) {
            throw new RuntimeException('New due date must be later than the current due date.');
        }

        return $this->update($id, ['due_on' => $candidate->format('Y-m-d')]);
    }

    public function overdue(string $referenceDate = 'today'): array
    {
        $date = $referenceDate === 'today' ? (new DateTimeImmutable())->format('Y-m-d') : $referenceDate;
        $sql = 'SELECT loans.*, books.title AS book_title, members.full_name AS member_name, members.email AS member_email, members.student_id
                FROM loans
                JOIN books ON books.id = loans.book_id
                JOIN members ON members.id = loans.member_id
                WHERE loans.status = "borrowed" AND loans.due_on < :today
                ORDER BY loans.due_on ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['today' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
