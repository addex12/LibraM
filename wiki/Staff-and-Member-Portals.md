# Staff & Member Portals

This document summarizes the non-admin portals exposed under `/member` and `/staff`.

## Member Portal (`/member/dashboard.php`)
- **Dashboard:** Shows checked-out items, reservations, fines, and notifications relevant to the logged-in student.
- **Authentication:** Uses `App\Services\PortalAuthenticator` with session state stored via `UserSessionRepository`.
- **Features:**
  - View due dates and overdue status
  - Request reservations
  - Receive automated notifications (driven by `scripts/notify-overdue.php`)

## Staff Portal (`/staff/dashboard.php`)
- **Audience:** Circulation desk and branch operators.
- **Capabilities:**
  - Quick lookup of members and books
  - Loan check-in/check-out shortcuts
  - Access to internal notices and logs

## Shared Components
- **Includes:** `public/includes/portal.php` defines shared layout, nav, and helper functions.
- **Sessions:** Staff and member sessions store user id, role, and branch assignments for authorization.
- **Repositories Used:** `MemberRepository`, `LoanRepository`, `ReservationRepository`, `NotificationRepository`.

## Extending Portals
1. Add new cards/modules inside the relevant dashboard PHP file.
2. Reuse repository methods for data retrieval.
3. Guard new endpoints with role checks (e.g., `require_member_login`).
4. Update the Screenshot Checklist with the new UI section you want to document.

This write-up can become the "Staff & Member Portals" page in your GitHub wiki.