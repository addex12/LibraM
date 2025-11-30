# Backend Service (PHP)

Stack: PHP 8.1+, SQLite (development), Composer autoloading, lightweight custom router.

## Prerequisites

- PHP 8.1 or later with `pdo_sqlite`.
- Composer 2.x.

## Setup & Run

```bash
cd src/backend
composer install
cp .env.example .env
php -S localhost:8000 -t public public/index.php
```

The API exposes `/api/books`, `/api/members`, and `/api/loans` endpoints. Data persists in `storage/library.db` (SQLite). Update `.env` to point to PostgreSQL or MySQL in production.
