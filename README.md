# Olympics Run 2026

Online quiz platform for the **Kerala Olympic Association** — runs the
**Olympics Run 2026** inter-school sports quiz across two rounds.

For the full system spec (roles, workflow, data model, configurable settings)
see [`PROJECT_SPEC.md`](./PROJECT_SPEC.md).

---

## Tech stack

- PHP 8.x
- MySQL 8.x
- Vanilla JS + jQuery, AJAX everywhere
- Tailwind CSS, mobile-first responsive
- SortableJS for drag-and-drop (question curation, slot assignment)
- Printable HTML reports (opened in a new tab; uses `window.print`)

---

## Folder structure

```
olympicsrun2026.in/
├── PROJECT_SPEC.md            full system specification
├── README.md                  this file
├── .gitignore
│
├── config/                    environment config (copy .example -> .local)
│   └── config.example.php
│
├── includes/                  bootstrap, helpers, auth guard, CSRF, db
│
├── models/                    one class per domain entity (PDO-backed)
│
├── controllers/               one class per route group
│
├── views/                     PHP templates
│   ├── layouts/               base layouts (admin, school, public, print)
│   ├── partials/              header, footer, nav, flash messages
│   ├── auth/                  login, password reset
│   ├── admin/                 admin dashboard & screens
│   ├── association/           association-user screens
│   ├── panelist/              expert-panelist screens
│   ├── school/                school-team screens (incl. quiz)
│   ├── reports/               printable HTML reports
│   └── errors/                403, 404, 500
│
├── public/                    web root — point Apache/Nginx here
│   ├── index.php              front controller (created in next phase)
│   ├── .htaccess              rewrites everything to index.php
│   ├── css/                   compiled / static stylesheets
│   ├── js/                    app JS (jQuery code, AJAX, quiz client)
│   ├── img/                   logos, icons, static assets
│   └── vendor/                third-party JS (jQuery, SortableJS, Tailwind)
│
├── database/
│   ├── schema.sql             full schema + seed data
│   └── migrations/            (future) incremental schema changes
│
├── uploads/                   runtime user uploads (gitignored contents)
└── logs/                      app + PHP error logs (gitignored contents)
```

The **document root for the web server is `public/`**. Everything else
(config, includes, models, controllers, views, sql, uploads, logs) sits
outside the web root and is reached only via `include` from
`public/index.php`.

---

## URL convention

Front-controller routing handled by `public/index.php` + `public/.htaccess`:

- `/auth/login`, `/auth/logout`
- `/admin/...`, `/association/...`, `/panelist/...`, `/school/...`
- `/api/...` — JSON AJAX endpoints, CSRF-guarded
- `/reports/print/<name>?...` — printable HTML reports

---

## Local setup (once feature code lands)

1. Clone the repo.
2. Copy `config/config.example.php` → `config/config.local.php` and fill in
   DB credentials.
3. Create a MySQL 8 database, then load `database/schema.sql` (it contains
   schema + seed data in one file).
4. Point Apache/Nginx document root at the `public/` folder, ensure
   `mod_rewrite` is enabled.
5. Make sure `uploads/` and `logs/` are writable by the web-server user.

---

## Status

This commit is **scaffold only** — folder layout, spec, and config example.
No feature code yet. Next phase: front controller + bootstrap + auth +
schema. See `PROJECT_SPEC.md` for the build plan.

---

## License

Proprietary — Kerala Olympic Association. All rights reserved.
