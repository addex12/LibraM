# Library Management System Graduation Project

This repository collects every asset required for a University-level Library Management System submission, including formal documentation and a runnable application that can be demonstrated entirely from a PHP stack (no external frameworks required on the client).

## What's Included

- Documentation sources in Markdown and LaTeX plus generated outputs in `docs/exports/` ready for DOCX/PDF submission.
- Composer-managed PHP backend (SQLite by default) that exposes JSON APIs and server-rendered portals for students and librarians.
- Bootstrap-based admin dashboard and public service desk rendered directly from `src/backend/public` so the system can be showcased with only PHP, HTML, CSS, and vanilla JS.

## Repository Layout

- `docs/` – Authoritative project documentation.
  - `markdown/` – Editable Markdown chapters, plans, and appendices.
  - `latex/` – LaTeX variant of the manuscript.
  - `exports/` – Generated DOCX/PDF files.
- `src/backend/` – PHP codebase (Composer, repositories, public entrypoints, SQLite storage).
  - `public/` – Student portal (`index.php`) plus admin pages under `admin/`.
  - `storage/` – SQLite database (`library.db`) and seed scripts.

## Running the Application

1. Install PHP 8.1+ and Composer, then fetch dependencies:
   ```bash
   cd src/backend
   composer install
   ```
2. Copy `.env.example` to `.env` (or create `.env`) and ensure `DB_PATH` points to `storage/library.db`.
3. Seed demo data:
   ```bash
   php scripts/seed.php
   ```
   This populates representative books, members, and a mix of active/returned loans for immediate testing.
4. Launch the PHP dev server from `src/backend/public`:
   ```bash
   php -S 127.0.0.1:8000 index.php
   ```
5. Open `http://127.0.0.1:8000/` for the student portal and `http://127.0.0.1:8000/admin/` for the librarian dashboard (books, members, and loans management).
   - Librarian credentials use `ADMIN_USER` / `ADMIN_PASSWORD` (defaults: `librarian` / `library123`).
   - A super administrator role is now available via `SUPER_ADMIN_USER` / `SUPER_ADMIN_PASSWORD` (defaults: `superadmin` / `superlibrary!23`) and unlocks the new **Operations** console plus the embedded database view.
   - For either account, you can supply hashed secrets by setting `*_PASSWORD_HASH` using `php -r "echo password_hash('NewPass', PASSWORD_DEFAULT);"`.
   - The repository bundles **Adminer 4.8.1** at `/admin/tools/adminer-iframe.php`, so leaving `PHPMYADMIN_URL` blank still gives you a secure, offline database console that works directly inside the dashboard. Override the variable if you want to embed an external phpMyAdmin/Adminer instance instead—just keep it on a restricted network.
6. (Optional) From `src/backend`, queue overdue reminder logs for follow-up emails/SMS by running:
   ```bash
   composer notify-overdue
   ```

## Documentation Workflow

All official text lives inside `docs/markdown/`. Use the provided Pandoc command (documented in `docs/README.md`) to regenerate DOCX/PDF deliverables whenever content changes.

## Support

- Database schema and sample API payloads are under `docs/exports/`.
- Troubleshooting steps, testing instructions, and architectural notes are embedded throughout the `docs/markdown` set and in inline code comments.
