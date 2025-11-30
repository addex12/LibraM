<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
 * Email: gizawadugna@gmail.com
 */

namespace App;

use PDO;

class Schema
{
    public static function ensure(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS books (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            isbn TEXT NOT NULL,
            title TEXT NOT NULL,
            author TEXT NOT NULL,
            publisher TEXT,
            publication_year INTEGER,
            copies_total INTEGER DEFAULT 1,
            copies_available INTEGER DEFAULT 1,
            subjects TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS members (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id TEXT NOT NULL UNIQUE,
            full_name TEXT NOT NULL,
            faculty TEXT NOT NULL,
            email TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS loans (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            book_id INTEGER NOT NULL,
            member_id INTEGER NOT NULL,
            borrowed_on TEXT NOT NULL,
            due_on TEXT NOT NULL,
            returned_on TEXT,
            status TEXT NOT NULL DEFAULT "borrowed",
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(book_id) REFERENCES books(id),
            FOREIGN KEY(member_id) REFERENCES members(id)
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS branches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT NOT NULL UNIQUE,
            name TEXT NOT NULL,
            location TEXT,
            contact_email TEXT,
            contact_phone TEXT,
            hours TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS shelves (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            branch_id INTEGER NOT NULL,
            code TEXT NOT NULL,
            label TEXT NOT NULL,
            floor TEXT,
            capacity INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(branch_id) REFERENCES branches(id)
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS subjects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            description TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS staff (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id TEXT NOT NULL UNIQUE,
            full_name TEXT NOT NULL,
            role TEXT NOT NULL,
            email TEXT,
            phone TEXT,
            branch_id INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(branch_id) REFERENCES branches(id)
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS reservations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            book_id INTEGER NOT NULL,
            member_id INTEGER NOT NULL,
            status TEXT NOT NULL DEFAULT "pending",
            reserved_on TEXT DEFAULT CURRENT_TIMESTAMP,
            ready_on TEXT,
            expires_on TEXT,
            notified_on TEXT,
            queue_position INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(book_id, member_id),
            FOREIGN KEY(book_id) REFERENCES books(id),
            FOREIGN KEY(member_id) REFERENCES members(id)
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS fines (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            loan_id INTEGER NOT NULL,
            member_id INTEGER NOT NULL,
            amount REAL NOT NULL,
            reason TEXT,
            status TEXT NOT NULL DEFAULT "unpaid",
            assessed_on TEXT DEFAULT CURRENT_TIMESTAMP,
            settled_on TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(loan_id) REFERENCES loans(id),
            FOREIGN KEY(member_id) REFERENCES members(id)
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            member_id INTEGER,
            reservation_id INTEGER,
            channel TEXT NOT NULL,
            type TEXT NOT NULL,
            payload TEXT,
            status TEXT NOT NULL DEFAULT "pending",
            sent_at TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(member_id) REFERENCES members(id),
            FOREIGN KEY(reservation_id) REFERENCES reservations(id)
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS user_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            identifier TEXT NOT NULL,
            role TEXT NOT NULL,
            channel TEXT NOT NULL,
            usage_summary TEXT,
            last_login_at TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        self::addColumn($pdo, 'books', 'branch_id INTEGER REFERENCES branches(id)');
        self::addColumn($pdo, 'books', 'shelf_id INTEGER REFERENCES shelves(id)');
        self::addColumn($pdo, 'books', 'subject_id INTEGER REFERENCES subjects(id)');

        self::addColumn($pdo, 'members', 'phone TEXT');
        self::addColumn($pdo, 'members', 'address TEXT');
        self::addColumn($pdo, 'members', 'status TEXT NOT NULL DEFAULT "active"');
        self::addColumn($pdo, 'members', 'password_hash TEXT');
        self::addColumn($pdo, 'staff', 'password_hash TEXT');
    }

    private static function addColumn(PDO $pdo, string $table, string $definition): void
    {
        [$column] = explode(' ', trim($definition), 2);
        if (self::columnExists($pdo, $table, $column)) {
            return;
        }

        $pdo->exec(sprintf('ALTER TABLE %s ADD COLUMN %s', $table, $definition));
    }

    private static function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->query('PRAGMA table_info(' . $table . ')');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (strcasecmp($row['name'], $column) === 0) {
                return true;
            }
        }

        return false;
    }
}
