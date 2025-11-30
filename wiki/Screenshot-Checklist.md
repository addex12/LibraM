# Screenshot Checklist

Use this list to capture every screen needed for the public showcase. Store the PNG files under `wiki/images/` (create the folder if it does not exist) and reference them from the wiki pages.

## Quick Automation
Run the Playwright helper in `tools/screenshots/` to batch-capture the login pages and admin module shots:

```bash
cd tools/screenshots
npm install            # first run only
npx playwright install # first run only
npm run capture        # creates PNGs in ../wiki/images
```

Environment variables:
- `LIBRAM_BASE_URL` (default `http://127.0.0.1:8081`)
- `LIBRAM_ADMIN_USER` / `LIBRAM_ADMIN_PASSWORD` if you changed `.env`
- `LIBRAM_SHOT_DIR` to override the output folder

The script covers the login page plus the admin, staff, and member dashboards when the required credentials are supplied via environment variables (see `tools/screenshots/README.md`). Capture any additional workflows manually following the checklist below.

## Admin Portal
| File Name | Page | Notes |
| --------- | ---- | ----- |
| `admin-dashboard.png` | Dashboard | Include KPIs and quick actions visible after login |
| `admin-books-list.png` | Books list | Show filters, inline Edit/Delete buttons, multiple rows |
| `admin-books-edit.png` | Books form | Capture Add/Update buttons with fields populated |
| `admin-branches.png` | Branch management | Display branch grid and create form |
| `admin-shelves.png` | Shelves | Show branch filter and shelf table |
| `admin-subjects.png` | Subjects | Include add form + list |
| `admin-members.png` | Members module | Highlight search, CRUD actions |
| `admin-staff.png` | Staff module | Include role indicators |
| `admin-loans.png` | Loans module | Show status badges |
| `admin-reservations.png` | Reservations | Demonstrate queue view |
| `admin-fines.png` | Fines | Show assessment + payment status |
| `admin-notifications.png` | Notifications | Include template editor or log |
| `admin-reports.png` | Reports dashboard | Capture charts/tables |
| `admin-operations.png` | Operations (super admin) | Show maintenance tools and seed button |

## Staff Portal
| File Name | Page | Notes |
| --------- | ---- | ----- |
| `staff-dashboard.png` | `/staff/dashboard.php` | Include quick actions and alerts |
| `staff-loan-flow.png` | Loan management | Show check-in/out widget |

## Member Portal
| File Name | Page | Notes |
| --------- | ---- | ----- |
| `member-dashboard.png` | `/member/dashboard.php` | Highlight loans, reservations, fines cards |
| `member-reservation.png` | Reservation request | Capture form/modal |

## Public Site & Auth
| File Name | Page | Notes |
| --------- | ---- | ----- |
| `landing-page.png` | Public home | Show hero and navigation |
| `login-admin.png` | Admin login | Include role toggle if present |
| `login-member.png` | Member login | Show credential form |
| `login-staff.png` | Staff login | Show credential form |

## Tips
1. Use a consistent resolution (e.g., 1440px wide) and light mode.
2. Include the browser address bar only when demonstrating URLs.
3. Save as PNG for clarity; keep filenames lower_snake_case.
4. After adding images, reference them in the relevant wiki pages using Markdown: `![Books List](images/admin-books-list.png)`.
5. Update this checklist whenever you add new modules or flows.