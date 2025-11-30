# Requirements Traceability Matrix

| Requirement ID | Description | Source | Design Artifact | Test Case |
| --- | --- | --- | --- | --- |
| FR1 | Register books with ISBN, Dewey class, keywords | Librarian interviews | `BookRepository`, `admin/books.php` forms | TC01 Book CRUD (manual) |
| FR2 | Search OPAC by title/author/subject | Student survey | `public/index.php` catalog search + `/api/books` | TC09 Catalog Search |
| FR3 | Process lending/return/renewal | Observation | `LoanRepository`, `admin/loans.php`, public borrow form | TC05 Circulation Flow |
| FR4 | Send overdue notifications | Policy review | `scripts/notify-overdue.php`, `LoanRepository::overdue()` | TC12 Overdue Reminder Log |
| FR5 | Monthly circulation reports | Admin request | `admin/reports.php` (dashboard + CSV) | TC15 Report Generation |
| NFR1 | 99% availability | ICT office | Docker deployment plan | Recovery drill RD01 |
| NFR4 | Secure admin access | Policy review | `.env`-driven login + `/admin/login.php` | Security checklist SC02 |
| NFR3 | Localization support | Librarian interviews | i18n resource files | UI localization test |

> Status updated: November 2025
