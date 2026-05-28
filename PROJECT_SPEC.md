# Olympics Run 2026 — Project Specification

**Owner:** Kerala Olympic Association (KOA)
**Event:** Olympics Run 2026 — Inter-School Sports Quiz
**Domain:** olympicsrun2026.in
**Document version:** 1.0
**Last updated:** 2026-05-28

---

## 1. Purpose

Olympics Run 2026 is an online quiz platform that runs a two-round inter-school
sports quiz on behalf of the Kerala Olympic Association. The platform handles
the full lifecycle:

1. Expert panelists submit candidate questions.
2. Admin / Association curates the question bank and assembles quiz sets.
3. Schools register teams and book time slots.
4. Teams take Round 1 (qualifier) and Round 2 (final) at their assigned slots.
5. Results are auto-scored, ranked, and published as printable reports.

This document is the source of truth for system roles, workflow, data model,
configurable settings, and tech stack. It is written so a new developer can
read this single file and understand what to build.

---

## 2. Roles

The platform has four roles. Each role has its own login and its own
dashboard.

### 2.1 Admin
The super-user. Owns the system end-to-end.

Capabilities:
- Manage all other users (create / suspend / reset password).
- Manage Association Users, Expert Panelists, and School accounts.
- Manage master data: categories, sports, difficulty levels, regions.
- Curate the question bank (approve / reject / edit panelist submissions).
- Assemble Round 1 and Round 2 question sets via drag-and-drop (SortableJS).
- Configure all system settings (see §5).
- View, export, and print all reports.
- Override results in exceptional cases (with audit trail).

### 2.2 Association User
Operational user from Kerala Olympic Association. Runs the event day-to-day.

Capabilities:
- Approve / verify school registrations.
- Open, close, and reschedule quiz slots.
- Assign schools to slots (drag-and-drop).
- Monitor live quiz progress (who is in, who has finished).
- Publish results for each round.
- Generate and print round-level and school-level reports.

Association users **cannot** modify global settings or create other admins.

### 2.3 Expert Panelist
Subject-matter expert (typically a coach, sports academic, or KOA appointee).

Capabilities:
- Submit questions to the question bank.
  - Multiple choice (4 options), one correct answer.
  - Category, sport, difficulty, explanation, source/reference.
- Edit own questions while they are still in `pending` state.
- See status of own submissions: `pending`, `approved`, `rejected`, `needs_revision`.
- View aggregate stats on their own contributions (counts only — no PII).

Expert panelists **cannot** see other panelists' submissions, schools, or
results.

### 2.4 School Team
The team account that takes the quiz. One account per school per event.

Capabilities:
- Complete school profile (school name, region, coach, team members).
- View assigned slot(s) for Round 1 and Round 2.
- Launch the quiz at the start of their slot window.
- See their score / rank after results are published.
- Download / print their result card.

A school sees only its own data. Round 2 is visible only if the school
qualifies from Round 1.

---

## 3. End-to-end workflow

```
   Expert Panelist            Admin / Association            School Team
   ───────────────            ───────────────────            ───────────
        │                            │                            │
        │  submit question           │                            │
        ├───────────────────────────>│                            │
        │                            │  curate (approve / edit)   │
        │                            │  build Round 1 & 2 sets    │
        │                            │  (drag-and-drop)           │
        │                            │                            │
        │                            │  open registrations        │
        │                            │<───────────────────────────┤
        │                            │  approve school            │
        │                            ├──────────┐                 │
        │                            │          │                 │
        │                            │          v                 │
        │                            │  assign to slot            │
        │                            │  (drag-and-drop)           │
        │                            │───────────────────────────>│
        │                            │                            │
        │                            │             ┌──────────────┤
        │                            │             │  Round 1     │
        │                            │             │  take quiz   │
        │                            │             └──────────────┤
        │                            │  auto-score & publish      │
        │                            │───────────────────────────>│
        │                            │                            │
        │                            │  qualifiers → Round 2 slot │
        │                            │───────────────────────────>│
        │                            │             ┌──────────────┤
        │                            │             │  Round 2     │
        │                            │             │  take quiz   │
        │                            │             └──────────────┤
        │                            │  final results + reports   │
        │                            │───────────────────────────>│
        v                            v                            v
```

### 3.1 Question collection
- Panelists submit MCQ questions tagged by `sport`, `category`, `difficulty`.
- Submissions land in the bank with status `pending`.
- A panelist can edit / withdraw a question only while it is `pending`.

### 3.2 Curation
- Admin and Association Users see the pending queue.
- For each question they can: `approve`, `reject` (with reason),
  `needs_revision` (sent back to panelist).
- Approved questions enter the **active pool**, taggable by round.
- Admin builds Round 1 and Round 2 sets by drag-and-drop from the active pool
  into a round slot. Order within a set is also drag-and-drop.

### 3.3 Two-round quiz
- **Round 1 (Qualifier):** Open to all approved schools. Auto-scored. The top
  N schools (configurable) advance.
- **Round 2 (Final):** Only qualifying schools. Different (harder) question
  set assembled by Admin.

Slot mechanics:
- A **slot** is a fixed wall-clock window (default **30 minutes**).
- Within a slot the team has a **quiz timer** (default **15 minutes**) that
  starts when they hit "Start Quiz".
- A slot has a capacity (number of teams that can be assigned to it).
- Teams that miss their slot are marked `no_show` and forfeit.
- Each quiz delivers a fixed number of questions (default **30**) drawn from
  the round's question set.

### 3.4 Results
- Auto-scored on submission. Marks per question and negative marking are
  configurable per round.
- Tiebreaks (in order): higher score → fewer wrong → faster completion time
  → earlier submission timestamp.
- Round 1 results gate Round 2 access.
- Final results published by Association User. Once published, scores
  become visible to school accounts.

### 3.5 Reports
All reports are **printable HTML pages opened in a new tab** with a print
stylesheet. No PDF library required.

Report inventory:
- Round-level leaderboard (Round 1, Round 2).
- School result card (per school, per round).
- Slot manifest (who is assigned to which slot).
- Panelist contribution report.
- Question bank export (CSV alternative).
- Audit log (admin overrides).

---

## 4. Tech stack

| Layer        | Choice                                              |
|--------------|-----------------------------------------------------|
| Language     | PHP 8.x                                             |
| Database     | MySQL 8.x (utf8mb4)                                 |
| Server       | Apache / Nginx with PHP-FPM                         |
| Frontend     | HTML5 + Tailwind CSS (mobile-first responsive)      |
| Scripting    | Vanilla JS + jQuery (3.x), AJAX for all dynamic UI  |
| Drag-and-drop| SortableJS                                          |
| Reports      | Printable HTML opened in a new tab (`window.open`)  |
| Sessions     | PHP native sessions, secure cookies                 |
| Auth         | Password hashing via `password_hash` (bcrypt)       |
| CSRF         | Per-session token, validated on every POST          |

Hard constraints:
- **Mobile-first.** Every page must work on a 360px-wide screen first.
  Tablet and desktop are progressive enhancements.
- **No SPA framework.** jQuery + AJAX only. No React / Vue / Svelte.
- **No PDF library.** Reports use the browser's native print.

---

## 5. Configurable settings

All settings live in a `settings` table and are editable by Admin via the
Settings screen. Defaults shown below.

### 5.1 Slot & quiz timing
| Key                       | Default | Description                                 |
|---------------------------|---------|---------------------------------------------|
| `slot_duration_minutes`   | 30      | Wall-clock length of a slot window.         |
| `quiz_duration_minutes`   | 15      | Countdown timer once the team starts.       |
| `questions_per_quiz`      | 30      | Number of questions delivered per attempt.  |
| `slot_grace_minutes`      | 5       | How late a team can start within a slot.    |

### 5.2 Scoring
| Key                       | Default | Description                                 |
|---------------------------|---------|---------------------------------------------|
| `marks_correct_r1`        | 1       | Marks per correct answer, Round 1.          |
| `marks_wrong_r1`          | 0       | Negative marks per wrong answer, Round 1.   |
| `marks_correct_r2`        | 2       | Marks per correct answer, Round 2.          |
| `marks_wrong_r2`          | -0.5    | Negative marks per wrong answer, Round 2.   |
| `marks_unanswered`        | 0       | Marks for skipped questions.                |

### 5.3 Round 1 → Round 2 cut-off
| Key                       | Default | Description                                 |
|---------------------------|---------|---------------------------------------------|
| `r1_qualifiers_count`     | 50      | Top N schools that advance from Round 1.    |
| `r1_qualifiers_mode`      | `topN`  | `topN` or `percentage` or `min_score`.      |
| `r1_min_score`            | 0       | Used when `r1_qualifiers_mode = min_score`. |

### 5.4 Registration & event windows
| Key                       | Default     | Description                                |
|---------------------------|-------------|--------------------------------------------|
| `registration_open_at`    | (datetime)  | When schools can start registering.        |
| `registration_close_at`   | (datetime)  | Registration deadline.                     |
| `r1_window_start`         | (datetime)  | Earliest a Round 1 slot may begin.         |
| `r1_window_end`           | (datetime)  | Latest a Round 1 slot may begin.           |
| `r2_window_start`         | (datetime)  | Earliest a Round 2 slot may begin.         |
| `r2_window_end`           | (datetime)  | Latest a Round 2 slot may begin.           |

### 5.5 Display & branding
| Key                       | Default                  | Description                   |
|---------------------------|--------------------------|-------------------------------|
| `site_name`               | Olympics Run 2026        | Header / browser title.       |
| `organising_body`         | Kerala Olympic Association | Footer & report header.     |
| `support_email`           | support@olympicsrun2026.in | Contact link.               |
| `result_publish_round1`   | `false`                  | Publish R1 results to schools.|
| `result_publish_round2`   | `false`                  | Publish R2 results to schools.|

All values are typed at the application layer (int / float / bool / datetime /
string). No setting is hard-coded in PHP — every tunable above is read from
the `settings` table.

---

## 6. Data model (overview)

The schema lives in `database/schema.sql`. Tables (in dependency order):

- `users` — single auth table; column `role` ∈ {`admin`, `association`,
  `panelist`, `school`}.
- `settings` — key/value/type, single row per key.
- `regions` — KOA regions (for grouping schools).
- `sports`, `categories`, `difficulty_levels` — question taxonomy.
- `schools` — extends `users` for school accounts (school name, region,
  coach, principal, address).
- `team_members` — students on a school's team (name, class, dob).
- `panelists` — extends `users` for panelist profiles.
- `questions` — the bank. Columns: stem, 4 options, correct_index,
  explanation, sport_id, category_id, difficulty_id, submitted_by,
  status, reviewed_by, reviewed_at, reject_reason.
- `question_sets` — a curated set per round (Round 1, Round 2).
- `question_set_items` — join with `position` for drag-and-drop ordering.
- `slots` — round, starts_at, capacity, status.
- `slot_assignments` — school_id, slot_id, status.
- `quiz_attempts` — slot_assignment_id, started_at, submitted_at, score,
  correct, wrong, unanswered, time_taken_seconds, status.
- `quiz_answers` — attempt_id, question_id, chosen_index, is_correct,
  answered_at.
- `audit_log` — actor_id, action, target, payload_json, at.

Indexes and FKs are defined in `sql/schema.sql`. Reads use `utf8mb4_unicode_ci`.

---

## 7. URL & routing convention

A single front controller (`public/index.php`) handles all requests. URLs
follow `/<role>/<resource>/<action>`:

- `/auth/login`, `/auth/logout`
- `/admin/dashboard`, `/admin/users`, `/admin/settings`,
  `/admin/questions`, `/admin/sets`, `/admin/slots`, `/admin/reports`
- `/association/schools`, `/association/slots`, `/association/results`
- `/panelist/questions`, `/panelist/questions/new`
- `/school/profile`, `/school/slot`, `/school/quiz`, `/school/result`
- `/api/*` — AJAX endpoints; respond JSON; require CSRF token.
- `/reports/print/<report>?...` — printable HTML, opened in a new tab.

Apache rewrites `/*` to `public/index.php` via `public/.htaccess`.

---

## 8. Folder structure

See `README.md` for the on-disk layout. Summary:

```
/config        env-specific config (db creds, app constants)
/includes      bootstrap, helpers, auth guard, CSRF, db connector
/models        one class per domain entity, talks to MySQL
/controllers   one class per route group, calls models, picks a view
/views         PHP templates; sub-folders by role; layouts + partials
/public        web root: index.php, .htaccess, /css /js /img /vendor
/database      schema.sql (schema + seed), migrations/
/uploads       runtime user uploads (gitignored)
/logs          PHP error log, app log (gitignored)
```

---

## 9. Non-functional requirements

- **Mobile-first responsive.** Designed for 360px width, scaled up.
- **Accessibility.** Semantic HTML, label/input pairing, keyboard nav for
  the quiz screen.
- **Security.**
  - Bcrypt password hashes.
  - CSRF token on every state-changing POST / AJAX.
  - Prepared statements only — no string-concatenated SQL.
  - Session cookies: `HttpOnly`, `Secure`, `SameSite=Lax`.
  - Role check on every controller action (server-side).
- **Anti-cheat (best effort).**
  - Server is the clock; client timer is display only.
  - Tab-blur events logged on the attempt.
  - One active attempt per school per round.
  - Questions delivered in randomised order per attempt.
- **Auditability.** Admin overrides and result publishes write to `audit_log`.
- **Print.** Each report has a `@media print` stylesheet and auto-triggers
  `window.print()` on load when `?print=1`.

---

## 10. Out of scope (v1)

- Native mobile apps.
- Real-time multiplayer / live head-to-head rounds.
- Payment / fee collection.
- SMS / WhatsApp notifications (email only).
- Multilingual UI (English only for v1).

---

## 11. Glossary

- **Slot** — a fixed wall-clock window (default 30 min) during which an
  assigned team may begin their quiz.
- **Attempt** — a single school's single run through a round's quiz.
- **Round** — Round 1 (qualifier) or Round 2 (final).
- **Question set** — a curated, ordered list of questions for a round.
- **Active pool** — approved questions eligible to be placed into a set.
