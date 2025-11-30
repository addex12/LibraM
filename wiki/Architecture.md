# Architecture Summary

LibraM follows a layered PHP architecture backed by SQLite (or MySQL with environment overrides). This page documents the major components for contributors.

## Stack Overview
- **Language:** PHP 8+
- **Frameworks:** Native PHP with custom repositories and services
- **Database:** SQLite (`storage/library.db`) by default, switchable via `.env`
- **Templating:** PHP includes + Bootstrap 5 UI
- **Environment:** `.env` handled by `vlucas/phpdotenv`

## Directory Map
```
src/backend/
├── bootstrap.php          # Loads autoloader, env, database schema
├── public/                # Entry points for admin/member/staff portals
├── src/
│   ├── Database.php       # Connection factory (SQLite/MySQL)
│   ├── Schema.php         # Auto-migrates tables and seed data triggers
│   ├── Repositories/      # PDO repositories per aggregate
│   └── Services/          # Portal auth helpers, etc.
├── scripts/
│   ├── seed.php           # Idempotent sample data loader
│   └── notify-overdue.php # CLI notifier example
└── storage/
    └── logs/              # Runtime logs (gitignored)
```

## Request Lifecycle
1. Incoming request hits `public/*.php`.
2. `bootstrap.php` wires autoloading, env vars, database, and schema migrations.
3. Page-specific includes (e.g., `admin/includes/auth.php`) guard routes.
4. Repositories execute parameterized SQL; results pass to view templates (same file).

## Data Access Layer
- Repositories encapsulate CRUD. Example: `BookRepository::all()` handles keyword + relational filters.
- Each repository receives the shared PDO from `bootstrap.php`.
- Helper methods such as `normalizeId()` prevent accidental zero/empty inserts.

## Configuration
- `.env` defines DB credentials, admin logins, optional phpMyAdmin/Adminer URL.
- Sample `.env` shipped with SQLite defaults; copy to production and adjust to MySQL when needed.

## Deployment Notes
- PHP built-in server: `php -S localhost:8080 -t public public/index.php` (task already defined in VS Code workspace).
- For MySQL: set `DB_CONNECTION=mysql`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- Ensure `storage/` is writable for SQLite db and logs.

## Extensibility Tips
- Add new entities by creating a repository, schema migration, and admin page under `public/admin/`.
- Shared UI fragments live under `public/admin/includes/` and `public/includes/` for portals.
- Keep validation server-side; optional JS should only mirror behavior.

Use this page as the "Architecture" entry in the GitHub wiki.