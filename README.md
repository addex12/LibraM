# LibraM Library Management System

LibraM is the production-ready Library Management System experience packaged with end-to-end documentation, an auditable PHP backend, and runnable portals that operate entirely from a PHP stack (no external frameworks required on the client).

## Achievement Badges

### GitHub ([@addex12](https://github.com/addex12))
<p align="left">
   <a href="https://github.com/addex12" title="Quickdraw">
      <img src="https://github.githubassets.com/images/modules/profile/achievements/quickdraw-default.png" alt="GitHub Quickdraw badge" height="96" />
   </a>
   <a href="https://github.com/addex12" title="Pull Shark">
      <img src="https://github.githubassets.com/images/modules/profile/achievements/pull-shark-default.png" alt="GitHub Pull Shark badge" height="96" />
   </a>
   <a href="https://github.com/addex12" title="YOLO">
      <img src="https://github.githubassets.com/images/modules/profile/achievements/yolo-default.png" alt="GitHub YOLO badge" height="96" />
   </a>
</p>

### Credly Highlights ([profile](https://www.credly.com/users/adugnagizaw))
<p align="left">
   <a href="https://www.credly.com/users/adugnagizaw" title="IBM Project Management Essentials">
      <img src="https://upload.wikimedia.org/wikipedia/commons/5/51/IBM_logo.svg" alt="IBM badge" height="44" />
   </a>
   <a href="https://www.credly.com/users/adugnagizaw" title="Google professional certificates: AI Essentials, Advanced Data Analytics, Data Analytics, Project Management, Cybersecurity, IT Support, Business Intelligence">
      <img src="https://upload.wikimedia.org/wikipedia/commons/2/2f/Google_2015_logo.svg" alt="Google badge" height="44" />
   </a>
   <a href="https://www.credly.com/users/adugnagizaw" title="Cisco Qualified Data Science Blue Belt, Computer Hardware Basics">
      <img src="https://img.shields.io/badge/Cisco%20Blue%20Belt-1BA0D7?logo=cisco&logoColor=white" alt="Cisco badge" height="28" />
   </a>
   <a href="https://www.credly.com/users/adugnagizaw" title="ISC2 Candidate">
      <img src="https://img.shields.io/badge/ISC2%20Candidate-006241?logo=isc2&logoColor=white" alt="ISC2 badge" height="28" />
   </a>
   <a href="https://www.credly.com/users/adugnagizaw" title="Applied Data Science Lab (WorldQuant University)">
      <img src="https://img.shields.io/badge/WorldQuant%20University-222222?logoColor=white" alt="WorldQuant University badge" height="28" />
   </a>
   <a href="https://www.credly.com/users/adugnagizaw" title="Microsoft UX Design Professional Certificate">
      <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" alt="Microsoft badge" height="44" />
   </a>
   <a href="https://www.credly.com/users/adugnagizaw" title="Lifelong Learning 2025 (CertiProf)">
      <img src="https://upload.wikimedia.org/wikipedia/commons/9/9e/CertiProf_logo.png" alt="CertiProf badge" height="44" />
   </a>
</p>

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
3. Seed operational data:
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
   - The public portal now exposes dedicated **Member Workspace** and **Staff Workspace** cards so non-admin users can sign in with their own credentials.
6. (Optional) From `src/backend`, queue overdue reminder logs for follow-up emails/SMS by running:
   ```bash
   composer notify-overdue
   ```

## Operational Sign-ins & Activity Data

- The admin portal ships with curated accounts so reviewers can log in immediately:
   - **Librarian (default admin)** — username `librarian`, password `library123`
   - **Operations Desk (super admin)** — username `superadmin`, password `superlibrary!23`
- Staff workspace logins (employee ID + password) seeded for frontline teams:
   - Abel Kebede — `STF-1001` / `AbelCirculation!23`
   - Martha Tulu — `STF-1002` / `MarthaDigital#`
   - Samuel Hailu — `STF-1003` / `SamuelReference8`
- Member workspace logins (student ID + password) seeded for demos:
   - Sara Mekonnen — `UGR/1234/13` / `Sara@Lib123`
   - Yonatan Bekele — `UGR/5678/13` / `Yonatan#Data`
   - Hanna Girma — `UGR/9012/13` / `HannaBiz!2024`
   - Meron Assefa — `UGR/2210/14` / `MeronSketch7`
   - Selam Tesfaye — `PGC/1105/24` / `SelamGrad+24`
- The `user_sessions` table stores representative librarian and student journeys (Sara Mekonnen, Yonatan Bekele, Hanna Girma, etc.) that power the refreshed dashboards. They illustrate how real users sign in (kiosk, mobile, research commons) and what they do next (reserve seats, renew books, clear fines). Update `scripts/seed.php` or insert records directly through the admin tools to showcase your own pilot data.
- The public portal now highlights these journeys on the homepage so stakeholders can see live-inspired usage, while the admin dashboard renders the same feed inside “User sign-ins” and “Member spotlight” cards for quick demos.

## Documentation Workflow

All official text lives inside `docs/markdown/`. Use the provided Pandoc command (documented in `docs/README.md`) to regenerate DOCX/PDF deliverables whenever content changes.

## Support

- Database schema and sample API payloads are under `docs/exports/`.
- Troubleshooting steps, testing instructions, and architectural notes are embedded throughout the `docs/markdown` set and in inline code comments.
