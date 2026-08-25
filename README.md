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
npm ci
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

## Frontend stack

TALL (Tailwind, Alpine, Laravel, Livewire) + TypeScript. TypeScript is used
for real, type-checked client behavior — not a demo file — starting with
the reusable client-side CRUD data table (`resources/js/data-table.ts`,
wired into `resources/js/app.js` and bundled by Vite). Alpine and Livewire
are unchanged; this is a focused migration of one existing behavior, not a
SPA rewrite.

```bash
npm run typecheck
npm run build
```

`typescript` is a devDependency, so `npm ci` (not `npm ci --omit=dev`) must be
used in every environment that runs `typecheck` or `build` — including CI.

## Tests

```bash
php artisan test tests/Unit
php artisan test
./vendor/bin/pint --dirty
./vendor/bin/phpstan analyse --memory-limit=1G
```

The suite is split into two levels, and the split is meaningful:

- `tests/Unit` — isolated business rules (domain entities, value objects,
  domain services, application use cases). No Laravel container, no
  database, no HTTP, no Livewire, no network: collaborators are replaced
  by in-memory fakes under `tests/Unit/**/Fakes`, so the whole suite runs
  in well under a second.
- `tests/Feature` — the same behavior through the framework: routes,
  Livewire components, policies, Eloquent persistence and the JWT API.

The automated test suite runs against an isolated in-memory SQLite
database (`phpunit.xml`), never against the MySQL database configured in
`.env`.

## API authentication (JWT)

Two independent authentication boundaries coexist:

```text
Blade / Livewire → Fortify → Laravel session auth      (routes/web.php)
JSON clients      → Bearer JWT                          (routes/api.php)
```

`routes/api.php` carries no session/CSRF middleware. It is signed with
[`firebase/php-jwt`](https://github.com/firebase/php-jwt) behind
`Src\IdentityAccess\Authentication\Domain\Contracts\TokenServiceInterface`
(implemented by `JwtTokenService` in that context's `Infrastructure` layer)
so no other layer references the JWT library directly.

Environment variables (see `.env.example`):

| Variable | Purpose |
|---|---|
| `JWT_SECRET` | HS256 signing secret. Generate with `php -r "echo bin2hex(random_bytes(32));"` — never commit a real value. |
| `JWT_TTL` | Access token lifetime in minutes (default `60`). |
| `JWT_ISSUER` | Value stored in the token's `iss` claim (defaults to `APP_NAME`). |

### Endpoints

```bash
# Log in — returns a Bearer token
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"admin@gmail.com","password":"12345678"}'
# => {"access_token":"...","token_type":"Bearer","expires_in":3600}

# Call a protected endpoint
curl http://localhost:8000/api/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <access_token>"
# => {"id":1,"name":"...","email":"...","roles":[...],"permissions":[...]}
```

The `jwt.auth` middleware (`AuthenticateJwt`) returns a generic
`401 {"message":"Unauthenticated."}` for every rejection reason — missing,
malformed, bad-signature, or expired token — so failures never leak which
check failed. `/api/me` reuses the existing RBAC model
(`App\Concerns\HasRolesAndPermissions`) directly; there is no separate
authorization system for the API.

## External REST API (OpenAlex institution search)

The professor did not prescribe a specific external REST API, so the
project chose [OpenAlex](https://api.openalex.org)'s Institutions
autocomplete endpoint (`GET /autocomplete/institutions?q=`) — real
academic-institution data, directly relevant to the Academic Credential →
Institution field it assists.

```text
Institution input (credential form)
    ↓ Livewire debounce
SearchAcademicInstitutionsUseCase                 (Application)
    ↓
InstitutionSearchServiceInterface                 (Domain contract)
    ↓
OpenAlexInstitutionSearchService                   (Infrastructure adapter)
    ↓
GET https://api.openalex.org/autocomplete/institutions?q=...
```

This is enrichment-only: it assists filling in the existing `institution`
field and is never a hard dependency for creating or editing an academic
credential, and it never participates in affinity decisions (Atinente / No
Atinente / Nota técnica / Sin catálogo). If OpenAlex is slow,
unreachable, misconfigured, or rate-limited, the credential form falls
back to manual typing with a concise non-blocking message — see
`OpenAlexInstitutionSearchService` and
`Src\Academic\AcademicCredential\Domain\Exceptions\InstitutionSearchUnavailableException`.

Environment variables (see `.env.example`):

| Variable | Purpose |
|---|---|
| `OPENALEX_BASE_URL` | REST base (default `https://api.openalex.org`). |
| `OPENALEX_API_KEY` | Optional — basic lookup works without one. Read server-side only (`config/openalex.php`), never sent to the browser. |
| `OPENALEX_TIMEOUT` | Request timeout in seconds, bounded to 1–15 (default `5`). |
| `OPENALEX_INSTITUTION_LIMIT` | Max suggestions returned, bounded to 1–20 (default `8`). |
| `OPENALEX_CACHE_TTL` | Seconds an identical normalized query is cached for, using the app's default cache store (default `900`). |

A read-only JWT-protected endpoint reuses the same use case, to
demonstrate the API boundary without duplicating logic:

```bash
curl "http://localhost:8000/api/institutions/search?q=Universidad" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <access_token>"
# => {"results":[{"externalId":"https://openalex.org/I31944674","name":"Universidad de Costa Rica","hint":"San José, Costa Rica","countryCode":null,"rorId":"https://ror.org/02yzgww51"}]}
```

Automated tests use `Http::fake()` exclusively — see
`tests/Feature/Academic/OpenAlexInstitutionSearchAdapterTest.php` and
`tests/TestCase.php` (`Http::preventStrayRequests()` is enabled globally so
no test can accidentally depend on live Internet access).

## Scheduled tasks

`affinity:expire-overdue-technical-notes` marks Technical Notes past their
ratification deadline as expired (DO-02b). Runs daily via
`routes/console.php`'s `Schedule::command(...)`; requires the Laravel
scheduler (`php artisan schedule:work` in development, a cron entry
calling `schedule:run` every minute in production).
