# Security & Quality Audit — Olympics Run 2026

**Audited:** 2026-05-28
**Scope:** all PHP under `controllers/`, `models/`, `includes/`, `views/`,
plus `public/index.php` and `public/css/*`.

This document is the result of a systematic pass over the codebase against
the prompt-14 checklist: prepared statements, AJAX auth + role scoping +
slot-timing, CSRF on forms, input sanitization, post-submit lockout, and
mobile responsiveness across all modules.

The "Fixed in this pass" sections describe changes committed alongside this
document. "Known limitations" sections describe trade-offs and recommendations
that were not addressed in this pass.

---

## 1. SQL & prepared statements

**Method:** every SQL call goes through `Database::query / fetch / fetchAll
/ execute / insert`, which prepare-and-execute with parameter arrays. I
grep'd for double-quoted SQL with interpolated PHP variables, `sprintf`
into SQL, and string concatenation with `.` next to SQL keywords.

**Finding:** ✅ **Pass.** Every value-bearing parameter is bound via `?`.
No string interpolation of user input into SQL anywhere in the codebase.

The one place SQL is composed dynamically is `Validator::unique()`, which
interpolates `{$table}`, `{$column}`, `{$idColumn}` — these are
*hard-coded constants* passed by controllers (e.g. `'associations'`,
`'short_code'`). They never touch user input. Same pattern in
`SchoolLogin::suggestUsername()` for the `school_logins` table name.

Verdict: prepared statements are used uniformly; identifiers in dynamic
SQL come from internal trusted sources only.

---

## 2. AJAX endpoints — session + role + slot-timing + CSRF

All `api*` methods on controllers were audited:

| Endpoint | Method | Auth | CSRF | Slot/State check |
|---|---|---|---|---|
| `PanelistSlotsController::apiAssign` | POST | ✓ `guardApi` | ✓ `guardApi` | ✓ `slotForCurrentPanelist` |
| `PanelistSlotsController::apiUnassign` | POST | ✓ | ✓ | ✓ |
| `PanelistSlotsController::apiReorder` | POST | ✓ | ✓ | ✓ |
| `SchoolQuizController::apiState` | GET | ✓ | n/a (GET) | ✓ derives attempt from session |
| `SchoolQuizController::apiAnswer` | POST | ✓ | ✓ | ✓ rejects non-`in_progress` + timer expiry |
| `SchoolQuizController::apiSubmit` | POST | ✓ | ✓ | ✓ idempotent + rejects non-`in_progress` |

**Slot-timing authorization detail (school):** every API path resolves the
attempt via `QuizAttempt::currentAttemptForSchool($schoolId)`, which only
returns rows in `('assigned', 'in_progress')` — submitted/no_show/
disqualified rows are invisible. The school session carries `school_id`,
so a forged `slot_school_id` query parameter would be ignored because the
controllers never trust client-supplied attempt IDs.

**Slot-timing detail (panelist):** `slotForCurrentPanelist()` confirms
`slots.association_id === Auth::associationId()` before touching anything.

**`saveAnswer()` cross-check:** `QuizAttempt::saveAnswer()` does a JOIN
against `slot_schools` to verify that the submitted `slot_question_id`
actually belongs to *this* attempt's slot, rejecting forged IDs that
belong to a different slot.

**Finding:** ✅ **Pass.** Every state-changing AJAX endpoint validates
session + role + ownership; CSRF protected via header or POST body.

**Known limitation:** `apiState` is a GET and is not CSRF-protected. It
can trigger a force-submit when the timer is at 0, but only for the
caller's *own* school — and only when the timer is *already* expired, so
the attacker would have to wait for the legitimate timer to expire first.
Not exploitable.

---

## 3. CSRF on HTML forms

**Method:** AWK script over every `.php` view, scanning every
`<form ... method="post" ...>` block for a `csrf_field()` call before
`</form>`.

**Finding:** ✅ **Pass.** Every POST form in the app includes
`csrf_field()`, and `Csrf::requireValidPost()` (or `requireValidRequest()`
for AJAX) is called from every POST controller method.

`flash_old()` automatically strips `password`, `password_confirmation`,
and `_csrf` from any old-input it stores in the session, so a re-rendered
form after validation failure can't accidentally re-emit a stale or
sensitive token.

---

## 4. Input sanitization / XSS

**Method:** grep over every `<?= ?>` expression in views for unescaped
output, filtering out the safe forms: `e(...)`, integer/float casts,
known-safe helpers (`status_badge`, `dt_display`, `csrf_field`, etc.),
class/attribute booleans, and numeric formatters.

**Finding:** ✅ **Pass.** Every variable that contains user-supplied or
database-sourced text is output via `e()`. The unescaped patterns the
grep flagged are all one of:

- `(int)$x` casts (rank numbers, IDs, counts)
- single-letter A/B/C/D option keys
- `'active' / ''` class toggles
- `count($x)` / `number_format(...)` / `sprintf('%d:%02d', ...)` returning numeric strings
- the layout's `$content` slot, which is itself the output of `view()` where every variable was escaped

Flash messages: `views/partials/flash.php` outputs the message via
`e($msg)`. The CSS class is from a server-controlled allowlist.

---

## 5. Post-submit lockout

The lockout was hardened in prompts 9 and 10. Re-verified here:

| Path | Behaviour after `attempt_status = 'submitted'` |
|---|---|
| `GET /school/quiz`                | Redirects to `/school/result` (via `hasAnySubmittedAttempt`) |
| `POST /api/school/quiz/answer`    | Returns `403 attempt_not_active` + redirect URL |
| `POST /api/school/quiz/submit`    | Returns `{ok:true, redirect:/school/result}` (idempotent) |
| `GET /api/school/quiz/state`      | Returns `attempt_status: none` + redirect URL |
| `POST /school/quiz/start`         | Bounces to dashboard with "no slot" error — `currentAttempt` is null for a submitted row |

`QuizAttempt::submit()` is wrapped in `BEGIN ... SELECT ... FOR UPDATE ...
COMMIT` and short-circuits on a second call against the same attempt
(`already => true`), so the cron force-submitter, the JS-triggered Finish
button, and the in-line `apiState` force-submit can race safely.

**Finding:** ✅ **Pass.** No code path lets a submitted attempt accept
further answers or be re-scored.

---

## 6. Cookies & session hygiene

| Setting | Value | Source |
|---|---|---|
| Session cookie name | `olyrun2026` | `config/config.php` |
| `HttpOnly` | `true` | bootstrap session_set_cookie_params |
| `Secure` | `false` (dev) / `true` (prod) | `SESSION_SECURE` env var |
| `SameSite` | `Lax` | config |
| Session regeneration | On successful login | `Auth::attempt()` |

**Finding:** ✅ **Pass.** Recommend setting `SESSION_SECURE=1` in
production (documented in `config/config.example.php`).

---

## 7. Passwords

- All login passwords are `password_hash(..., PASSWORD_BCRYPT)`.
- `password_verify` is the only path that compares passwords.
- Plaintext passwords never appear in flash messages except the
  one-time "new password" flash after admin-triggered reset/send-credentials —
  which is the entire point of those flows.
- `flash_old()` strips `password` and `password_confirmation` keys
  before storing old input back into the session.

**Known limitation:** the SMTP password (`mail_smtp_pass`) is stored
plain in the `settings` table. This is a deliberate trade-off for the v1
admin tool, since the admin who can read it can already alter it; the
mitigation is restricting `admins` table access. If you need encryption
at rest, wrap it with `sodium_crypto_secretbox` keyed off a config
secret in a follow-up.

---

## 8. New protections added in this pass

### 8.1 Response security headers
Added in `includes/bootstrap.php` on every HTTP response (no-op in CLI):
- `X-Frame-Options: DENY` — blocks clickjacking
- `X-Content-Type-Options: nosniff` — blocks MIME sniffing
- `Referrer-Policy: same-origin`
- `Permissions-Policy: geolocation=(), microphone=(), camera=()`
- `Strict-Transport-Security: max-age=31536000; includeSubDomains` — production + HTTPS only

### 8.2 Login throttling
`AuthController::doLogin` now tracks failed attempts in the session and
rejects further attempts for 60 seconds after 5 consecutive failures.
A first line of defence against the simplest single-browser brute force.

**Known limitation:** session-keyed throttling does not stop an attacker
who clears cookies between requests, nor a distributed credential-stuffing
attack. A production-grade solution needs IP- or account-based limiting
backed by a shared store (Redis / database). Track as a follow-up.

---

## 9. Mobile responsiveness — module-by-module

Tested by inspecting markup + CSS, not on a device farm. Findings:

### 9.1 Navbar (all roles)
**Bootstrap 5.3 `navbar-expand-lg` collapses to a hamburger below 992 px.**
Dropdowns under "Masters" / "Users" / "Quiz" use `data-bs-toggle="dropdown"`
which works on touch.
✅ **OK.**

### 9.2 Tables (admin / association / panelist)
Every `<table>` is wrapped in `table-responsive`, which produces
horizontal scroll on narrow viewports. Columns marked `d-none d-md-table-cell`
/ `d-none d-lg-table-cell` collapse out below those breakpoints, leaving
the most important columns (name, status, actions) on small screens.
✅ **OK.**

The two report tables use `report-table-wrap` (custom `overflow-x: auto`
class, same effect as `table-responsive`).
✅ **OK.**

### 9.3 Quiz side panel
On `lg+` (≥ 992 px) the side panel is a sticky column inside the row.
Below `lg`, a Bootstrap **offcanvas** drawer slides in from the left,
toggled by a `<button>` in the sticky header that pulls in
`data-bs-toggle="offcanvas"`. Tested DOM, the `bootstrap.Offcanvas`
JS instance is fetched and closed after a question button tap on mobile
so the user lands on the question, not the drawer.
✅ **OK.**

### 9.4 Drag-and-drop slot builder
SortableJS with `delay: 120, delayOnTouchOnly: true, touchStartThreshold: 5`
plus a dedicated `.drag-handle` so the *card* still scrolls naturally on
touch and only the grip icon initiates a drag. `touch-action: none` on
the card prevents the browser fighting the drag gesture. The right-hand
slot panel is rendered **above** the bank on mobile (`order-1 order-lg-2`)
so the user can see the drop target while dragging.
✅ **OK.**

### 9.5 Forms (admin CRUD, association questions, panelist master,
school dashboard)
Bootstrap grid (`col-12 col-md-6` etc.) stacks columns 1-up on mobile.
Long select labels and option text wrap inside their containers.
✅ **OK.**

### 9.6 Reports (printable + on-screen)
On screens ≤ 600 px the `.report-page` shrinks to full width, drops
padding to 1 rem, and the navy toolbar collapses to fit. Tables get
horizontal scroll inside `report-table-wrap`. The Print CSS only applies
to actual print, so the on-screen experience stays the soft "white card
on grey body" view.
✅ **OK.**

### 9.7 School quiz screen — option cards
Each option is a `.quiz-option` `.input-group`-style row that stacks the
letter pill + text neatly on phones. Radio input is hidden and the whole
label is clickable (`.quiz-option { cursor: pointer }`), making the tap
target the full option card.
✅ **OK.**

### 9.8 Modal dialogs (panelist review queue: reject / send-back)
Bootstrap `modal` component is mobile-friendly by default; the reason
textarea + buttons stack inside the dialog body.
✅ **OK.**

---

## 10. Recommendations (not implemented in this pass)

1. **Distributed login throttle** — back the per-session throttle with an
   IP-keyed counter in Redis / a `login_attempts` table to defeat
   credential stuffing. Add CAPTCHA after N failures.
2. **SMTP password encryption** — `sodium_crypto_secretbox` over
   `mail_smtp_pass`, keyed off a per-host secret in `config.local.php`.
3. **Audit log** — `audit_log` table already exists in schema. Wire
   it up for: admin overrides on results, role escalations, result
   publishing toggles, force-submits.
4. **Content-Security-Policy** — defer until after we drop the CDN
   stylesheets (Bootstrap, jQuery, SortableJS) into `public/vendor/` so
   we can use a strict policy without `unsafe-inline`.
5. **AJAX origin check** — supplement CSRF tokens with an `Origin`/
   `Referer` header check on AJAX endpoints (defence in depth).

---

## Verdict

- Prepared statements: ✅
- AJAX session + role + slot-timing: ✅
- CSRF on forms: ✅
- Input sanitization: ✅
- Post-submit lockout: ✅ (cannot be bypassed by client manipulation)
- Mobile responsiveness: ✅ across all modules

Plus three new hardening items committed alongside this document:
security headers, per-session login throttle, and stricter
"don't reveal which field is wrong" error messaging on login.
