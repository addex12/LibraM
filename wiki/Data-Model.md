# Data Model

LibraM uses a relational schema provisioned automatically by `Schema::ensure()`. This page captures the key tables, relationships, and repository mappings.

## Core Tables
| Table | Description | Primary Relationships |
| ----- | ----------- | --------------------- |
| `branches` | Library locations | One-to-many with `shelves`, `staff`, `books` |
| `shelves` | Physical shelving units | Belongs to `branches`; optional link to `books` |
| `subjects` | Academic domains | Linked to `books` |
| `books` | Catalog entries | Optional foreign keys to `branches`, `shelves`, `subjects` |
| `members` | Students/patrons | Linked to `loans`, `reservations`, `fines`, `notifications` |
| `staff` | Employees/admins | Associated with `branches` |
| `loans` | Active or historical checkouts | References `books` + `members` + `staff`
| `reservations` | Hold requests | References `books` + `members`
| `fines` | Monetary penalties | References `members` and optionally `loans`
| `notifications` | Messages sent to patrons | Linked to `members` or staff
| `user_sessions` | Portal login state | Ties session tokens to member records

## Repository Mapping
| Repository | File | Notes |
| ---------- | ---- | ----- |
| `BookRepository` | `src/Repositories/BookRepository.php` | Provides filtered listings and ISBN uniqueness checks |
| `BranchRepository` | `src/Repositories/BranchRepository.php` | CRUD for location metadata |
| `ShelfRepository` | `src/Repositories/ShelfRepository.php` | Ensures branch linkage |
| `SubjectRepository` | `src/Repositories/SubjectRepository.php` | Plain CRUD |
| `MemberRepository` | `src/Repositories/MemberRepository.php` | Handles hashed passwords |
| `StaffRepository` | `src/Repositories/StaffRepository.php` | Stores roles and contact info |
| `LoanRepository` | `src/Repositories/LoanRepository.php` | Complex joins for status cards |
| `ReservationRepository` | `src/Repositories/ReservationRepository.php` | Queue tracking |
| `FineRepository` | `src/Repositories/FineRepository.php` | Assessment + payment tracking |
| `NotificationRepository` | `src/Repositories/NotificationRepository.php` | Message templates |
| `UserSessionRepository` | `src/Repositories/UserSessionRepository.php` | Session persistence for portals |

## Schema Bootstrap
- `Schema::ensure()` creates tables, indexes, and default admin credentials.
- `scripts/seed.php` loads demo branches, shelves, subjects, books, members, staff, and sessions. The script is idempotent thanks to lookups like `findByIsbn()`.

## ER Notes
- Foreign keys are nullable to allow unassigned books or orphan records during migration.
- Composite indexes on `books` (ISBN) and `loans` (member + status) optimize dashboard queries.
- Timestamps (`created_at`, `updated_at`) are managed via triggers in `Schema::ensure()` when the DB supports them.

Keep this file synced with actual schema changes so your wiki stays accurate.