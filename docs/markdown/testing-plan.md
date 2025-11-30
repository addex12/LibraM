# Testing Plan

## Objectives

- Verify that all high-priority functional requirements operate as specified.
- Validate non-functional targets such as response time and localization.
- Encourage user acceptance through scenario-driven walkthroughs.

## Test Levels

1. **Unit Testing:** Focus on utility functions (due date calculator, fine computation).
2. **Integration Testing:** Ensure API endpoints interact correctly with the database.
3. **User Acceptance Testing:** Librarians execute scripted scenarios covering cataloging and circulation.

## Tools

- `PHPUnit` (or Pest) targeting repository classes with an in-memory SQLite database to validate business rules such as availability checks and renewal limits.
- Built-in PHP server plus browser-based scripts for end-to-end and UAT walkthroughs covering OPAC search, member registration, and circulation tasks.
- Manual acceptance checklist stored in shared spreadsheets for librarian sign-off and screenshot evidence.

## Schedule

| Phase | Duration | Deliverables |
| --- | --- | --- |
| Unit Tests | Week 9 | PHPUnit summary & SQLite fixtures |
| Integration Tests | Week 10 | API/portal walkthrough log |
| UAT | Week 11 | Signed librarian acceptance sheet |

## Exit Criteria

- 100% pass rate on critical test cases (TC01–TC10).
- No open high-severity defects.
- User acceptance forms signed by lead librarian and ICT officer.
