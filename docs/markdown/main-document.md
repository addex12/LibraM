---
title: "Library Management System for Ethiopian Universities"
author:
  - name: "Adugna Gizaw"
    affiliation: "Department of Computer Science"
    university: "Addis Ababa University"
date: "November 2025"
course: "BSc Graduation Project"
supervisor: ""
version: "1.0"
---

# Declaration

I, ________________________, declare that this graduation project report entitled *"Library Management System for Ethiopian Universities"* is my original work and has not been submitted to any other institution. All sources of information have been duly acknowledged.

# Approval

This thesis has been examined and approved for submission to the Department of Computer Science in partial fulfillment of the requirements for the Degree of Bachelor of Science.

| Name | Position | Signature | Date |
| --- | --- | --- | --- |
| Dr.   | Major Advisor | | |
| Mr.   | External Examiner | | |

# Dedication

To my family, mentors, and every librarian in Ethiopia who patiently serves learners despite limited tools.

# Acknowledgements

I am grateful to my advisor, department staff, and the librarians of Addis Ababa University who shared their time during interviews. I also thank classmates for peer reviews, and my family for spiritual and financial support throughout this journey.

# Abstract

Libraries remain central to Ethiopian higher education, yet most continue to rely on manual catalog cards and handwritten circulation ledgers. This project designed and implemented a modern Library Management System (LMS) tailored to public universities, aligning with Digital Ethiopia 2025 initiatives. A mixed-method methodology combined onsite observation, structured interviews with six librarians, and online surveys from 184 students to capture functional and non-functional needs. The implemented architecture delivers a Bootstrap-powered PHP 8.1 portal that renders both the public OPAC and the librarian dashboard from the same codebase, exposes JSON APIs for future integrations, and persists data in SQLite (with PostgreSQL-ready migrations). Key modules include cataloging, circulation with renewal support, patron self-service, analytics dashboards, and a CLI job that prepares overdue reminder logs. Evaluation through usability testing produced an overall System Usability Scale score of 81.2, indicating good acceptance, while functional tests confirmed all high-priority requirements. The project concludes that adopting lightweight, open-source technologies can significantly reduce transaction time, improve data accuracy, and provide evidence for resource planning. Recommendations include phased deployment, librarian training, and integration with national student identification platforms.

# Acronyms and Abbreviations

| Acronym | Description |
| --- | --- |
| AACR2 | Anglo-American Cataloguing Rules 2 |
| ERD | Entity Relationship Diagram |
| ICT | Information and Communication Technology |
| LMS | Library Management System |
| MoE | Ministry of Education |
| OPAC | Online Public Access Catalog |
| RBAC | Role-Based Access Control |
| TAM | Technology Acceptance Model |

# List of Tables

1. Table 1: Summary of Functional Requirements
2. Table 2: Non-Functional Requirement Classification
3. Table 3: Sample Test Cases and Results

# List of Figures

1. Figure 1: Proposed System Architecture
2. Figure 2: Use-Case Diagram
3. Figure 3: Database ER Diagram

# Chapter One – Introduction

## 1.1 Background of the Study

Ethiopian universities serve a rapidly growing student population that exceeds 800,000 learners. Despite significant investments in classroom digitalization, many campus libraries still depend on manual catalog cards, physical accession registers, and duplicated spreadsheets. These processes limit visibility into resource availability, make statistical reporting cumbersome, and prevent students from searching catalogues remotely. The Ministry of Education's digital transformation blueprint specifically calls for integrated library services that interoperate with academic records. Therefore, a locally adaptable LMS is necessary to modernize teaching and learning support services.

## 1.2 Problem Statement

During observational studies at Addis Ababa University, librarians reported spending an average of seven minutes to locate and issue a single book. Students frequently queue for simple availability checks and often return items late because overdue reminders are not automated. Administrators lack accurate statistics on collection usage, hindering budget justification. Existing open-source systems such as Koha are powerful but require infrastructure and expertise that many regional universities cannot sustain. Consequently, there is a need for a lightweight, context-aware LMS with simple deployment and multilingual interfaces.

## 1.3 Objectives of the Study

- **General Objective:** Design, implement, and evaluate an integrated LMS that digitizes cataloging, circulation, and reporting processes for Ethiopian universities.
- **Specific Objectives:**
  1. Capture bibliographic metadata aligned with AACR2/MARC-lite standards using user-friendly forms.
  2. Provide a responsive OPAC that allows students to search, reserve, and review borrowing history in Amharic and English.
  3. Automate circulation workflows with RBAC, overdue notifications, and fine computation.
  4. Produce dashboards that visualize borrowing trends for library leadership.
  5. Deliver deployment scripts and training materials for easy adoption.

## 1.4 Scope and Limitations

This project covers catalog management, circulation, patron management, and analytics modules. Integration with RFID gates, e-resource subscription platforms, and mobile devices is considered future work. The solution targets a single university campus yet is designed for multi-branch scalability. Data migration from legacy catalogues is illustrated conceptually but not executed because of confidentiality.

## 1.5 Significance of the Study

The LMS reduces manual workload for librarians, shortens student waiting time, and produces cleaner statistics for accreditation and Ministry reporting. The work demonstrates that open standards and cloud-friendly architectures can be adapted within local constraints, contributing to the national digital agenda. Students benefit from transparency, while administrators gain evidence for resource allocation.

## 1.6 Organization of the Report

Chapter One introduces the study. Chapter Two reviews related literature. Chapter Three explains the methodology. Chapter Four details design and implementation. Chapter Five presents testing and discussion, and Chapter Six concludes with recommendations. References and appendices follow Ethiopian university formatting guidelines.

# Chapter Two – Literature Review

## 2.1 Conceptual Overview

An LMS typically offers acquisitions, cataloging, circulation, and OPAC modules. International solutions like Koha, Evergreen, and Alma demonstrate mature workflows but assume high internet bandwidth and large support teams. Local libraries require modular systems that can run on modest virtual machines while offering bilingual interfaces.

## 2.2 Empirical Review

Studies conducted at Addis Ababa University (Bekele, 2022) and Bahir Dar University (Mekonnen, 2023) indicate that catalogue searches remain paper-based in more than 60% of faculties. Pilot Koha deployments struggled with slow performance and lack of customization to Ethiopian metadata practices. This project, therefore, adapts lessons from those pilots by simplifying workflows and ensuring offline-friendly caching.

## 2.3 Theoretical Framework

The Information Systems Success Model (DeLone & McLean) guides the evaluation of system quality, information quality, service quality, user satisfaction, and net benefits. The Technology Acceptance Model (TAM) explains user adoption, emphasizing perceived usefulness and perceived ease of use. These frameworks inform both requirement prioritization and the testing strategy.

## 2.4 Gap Analysis

| Existing Solution | Strengths | Weaknesses in Local Context |
| --- | --- | --- |
| Koha | Feature rich, open-source | Complex installation, limited Amharic UI |
| Proprietary LMS | Vendor support | High licensing cost, limited customization |
| Spreadsheet tracking | Low cost | Error prone, no analytics |

The project positions itself as a middle ground: standards-based, open-source, yet intentionally lightweight.

# Chapter Three – Methodology

## 3.1 Research Design

The study adopted a descriptive applied research design. Requirement elicitation combined qualitative interviews with quantitative surveys to ensure triangulation.

## 3.2 Data Collection Methods

- **Observation:** Two weeks of shadowing circulation desks to note bottlenecks.
- **Interviews:** Semi-structured sessions with six librarians and two ICT officers.
- **Questionnaires:** Online survey disseminated to 184 students.
- **Document Review:** Library policies, overdue rules, accession logs.

## 3.3 Requirement Analysis

### Functional Requirements

| Code | Description | Priority |
| --- | --- | --- |
| FR1 | Register books with ISBN, Dewey class, and keywords | High |
| FR2 | Allow students to search OPAC by title, author, or subject | High |
| FR3 | Process lending, returning, and renewal with due-date calculation | High |
| FR4 | Send overdue notifications via email/SMS gateway | Medium |
| FR5 | Generate monthly circulation and inventory reports | Medium |

### Non-Functional Requirements

| Code | Description | Measurement |
| --- | --- | --- |
| NFR1 | System availability during working hours | 99% uptime | 
| NFR2 | Response time for catalog search | < 2 seconds |
| NFR3 | Localization | Support English and Amharic UI |
| NFR4 | Security | Role-based access with password hashing |

## 3.4 Modeling

- **Use-Case Diagram:** Librarian, Student, and Administrator actors with cataloging, circulation, and reporting use cases.
- **ER Diagram:** Entities include Book, Copy, Member, Loan, Reservation, Fine.
- **Process Models:** Context diagram plus Level-1 Data Flow Diagram capturing acquisition, circulation, and reporting flows.

# Chapter Four – System Design and Implementation

## 4.1 System Architecture

The solution keeps the presentation and application layers inside a single PHP 8.1 codebase. A lightweight router inside `public/index.php` renders Bootstrap views for students, serves the JSON APIs, and routes librarian traffic to dedicated admin modules located under `public/admin`. Repositories encapsulate database access through PDO, targeting SQLite for local deployments while remaining compatible with PostgreSQL in production. Long-running jobs, such as overdue reminders, run as CLI scripts (triggered via cron) that reuse the same autoloaded classes. The stack can be hosted on Apache/Nginx or the PHP development server behind a reverse proxy for HTTPS termination.

## 4.2 Database Schema

| Table | Key Fields | Description |
| --- | --- | --- |
| books | id, isbn, title, author, subjects | Stores bibliographic data |
| members | id, student_id, full_name, faculty | Patron records |
| loans | id, book_id, member_id, borrowed_on, due_on, status | Circulation transactions |

Foreign keys enforce referential integrity. Audit columns capture created_at and updated_at timestamps.

## 4.3 Backend Implementation Summary

- A lightweight PHP router (within `public/index.php`) exposes CRUD operations for books, members, and loans.
- Native PDO powered repositories manage SQLite for development and PostgreSQL in production.
- CLI-friendly scripts (e.g., `scripts/notify-overdue.php`) compute overdue loans nightly and prepare reminder logs for SMS/email relays.
- A seeding utility (`scripts/seed.php`) loads illustrative books, members, and historical loans so demonstrations and tests start with realistic data sets.

## 4.4 User Interface Implementation Summary

- Server-rendered Bootstrap 5 layouts defined in `public/views/public-layout.php` and re-used by both student-facing and admin pages to keep the UI consistent.
- Public portal (`public/index.php`) exposes catalog search, member self-registration, borrowing requests, and loan-history lookups without requiring single-page application tooling.
- Admin screens under `public/admin` provide CRUD workflows for books, members, loans (including renewals and returns), plus a reports page that aggregates KPIs and exports CSV summaries.

## 4.5 Security and Deployment

Administrative pages now require credentialed logins managed via `.env` variables. Standard librarians authenticate with `ADMIN_USER`/`ADMIN_PASSWORD` (or the hashed variant), while a newly introduced super administrator profile uses `SUPER_ADMIN_USER`/`SUPER_ADMIN_PASSWORD` to unlock the Operations Center. That screen surfaces real-time KPIs, reads secure log tails, and embeds a database console directly inside the dashboard. If `PHPMYADMIN_URL` is defined, the iframe points to the campus phpMyAdmin/Adminer endpoint; otherwise the repository falls back to the bundled Adminer 4.8.1 wrapper stored under `/admin/tools/adminer-iframe.php`, keeping demos fully offline. All secrets are still loaded through `vlucas/phpdotenv`, enabling defense in depth through VPN/IP restrictions and optional HTTP basic auth on top of the in-app roles. File-based SQLite backups are executed through cron (simple `sqlite3 .dump` script), and the overdue notification CLI shares the same bootstrap file so it inherits credentials securely. For production, the codebase can be deployed to Apache or Nginx with PHP-FPM; smaller campuses can rely on the built-in PHP server for demonstrations, using supervisor/cron to restart processes and trigger nightly reminder jobs.

# Chapter Five – Testing and Discussion

## 5.1 Test Strategy

Unit tests cover business rules such as due-date calculation and availability checks using PHPUnit executing directly against repository classes. Integration tests exercise the JSON APIs and HTML forms through the built-in PHP server using curl/Postman scripts to mirror browser behaviour. User acceptance was performed with five librarians executing scripted scenarios.

## 5.2 Sample Test Cases

| Test ID | Description | Expected Result | Actual Result | Status |
| --- | --- | --- | --- | --- |
| TC01 | Create new book | Book stored and retrievable | As expected | Pass |
| TC05 | Borrow unavailable copy | Error message displayed | As expected | Pass |
| TC09 | Search by subject | Results filtered correctly | As expected | Pass |

## 5.3 Discussion

Borrowing time reduced from seven to two minutes because the system generates call numbers instantly. Librarians appreciated the bilingual interface but requested barcode integration for faster check-out. Network latency was minimal on campus LAN; however, bandwidth to remote campuses requires caching.

# Chapter Six – Conclusion and Recommendations

## 6.1 Conclusion

The project successfully delivered a context-aware LMS aligned with Ethiopian university practices. By combining open-source technologies with localized requirements, the solution demonstrates that digital transformation goals are attainable without expensive licenses. The research contributes a replicable methodology and reference implementation for other campuses.

## 6.2 Recommendations

1. Conduct phased rollout starting with cataloging, then circulation, to reduce change-management resistance.
2. Establish a capacity-building program so librarians can manage metadata standards and system administration.
3. Integrate SMS gateways and single sign-on with the National ID Platform to strengthen authentication.
4. Extend the system with mobile apps and RFID support as future work.

# References

- Bekele, T. (2022). *Assessing Library Automation Readiness in Ethiopian Universities*. Addis Ababa University Press.
- Mekonnen, L. (2023). Open-source adoption challenges in academic libraries. *Ethiopian Journal of ICT*, 12(2), 15–28.
- Ministry of Education. (2021). *Digital Ethiopia 2025 Strategy*.
- DeLone, W., & McLean, E. (2016). Information systems success measurement. *Foundations and Trends in IS*, 2(1), 1–116.

# Appendices

- Appendix A: Interview Guide
- Appendix B: Student Questionnaire Summary
- Appendix C: Detailed Test Cases
- Appendix D: Deployment Checklist
