# SIGA — Sistema Integrado de Gestión Académica y Docente

Laravel + Livewire application for UTN Sede San Carlos. Includes the
Academic Affinity Verification module (DO-01, DO-02, DO-02a, DO-02b,
DO-02d) — see `Docs/ACADEMIC_AFFINITY_REQUIREMENTS_MATRIX.md` for full
requirement traceability and `Docs/DIARIO_DECISIONES_IA.md` for the
development decision log.

## Requirements

- PHP 8.3+
- Node.js 18+
- MySQL 8

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure `.env` with your MySQL connection:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gestion_academica_utn_test
DB_USERNAME=root
DB_PASSWORD=
```

`gestion_academica_utn_test` is the professor-provided institutional
schema this project reconciles onto — see the AI decision journal entry
titled "Academic Affinity module ... reconciliation with the professor's
real MySQL database" for why the schema uses Spanish table/column names
at the persistence boundary while all application code stays English.

Migrations are guarded (`Schema::hasTable`/`hasColumn`) so they are safe
to run against either a fresh database or the pre-existing official one —
they never drop or recreate anything that already exists:

```bash
php artisan migrate
php artisan db:seed
npm run build
php artisan serve
```

## Seeded demo users

All seeded with password `12345678`.

| Email | Role |
|---|---|
| `prueba@gmail.com` | Superadmin |
| `admin@gmail.com` | Administrador |
| `coordinadora@gmail.com` | Coordinadora de Docencia |

## Relevant routes

| Route | Purpose |
|---|---|
| `/academic/teachers` | Teacher list |
| `/academic/teachers/{teacher}` | Teacher profile, academic credentials, DO-01-F4 course-context affinity |
| `/academic/affinity-catalog` | Versioned affinity catalog (DO-02, Administrador only) |
| `/academic/teacher-assignments` | Propose teachers, verification results, Technical Note, No Catalog (DO-02a/02b/02d) |
| `/roles`, `/permissions` | RBAC management |

## Tests

```bash
php artisan test
./vendor/bin/pint --dirty
./vendor/bin/phpstan analyse
```

The automated test suite runs against an isolated in-memory SQLite
database (`phpunit.xml`), never against the MySQL database configured in
`.env`.

## Scheduled tasks

`affinity:expire-overdue-technical-notes` marks Technical Notes past their
ratification deadline as expired (DO-02b). Runs daily via
`routes/console.php`'s `Schedule::command(...)`; requires the Laravel
scheduler (`php artisan schedule:work` in development, a cron entry
calling `schedule:run` every minute in production).
