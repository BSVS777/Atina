# AI Decision Journal

Record of relevant AI interactions during development: what was consulted,
what was accepted, what was rejected (and why), what had to be corrected,
and what was learned.

---

## 2026-08-10 — Kickoff of the SIGA + Atina integration

### AI consultation

The user asked to implement `CLAUDE_MASTER_PROMPT_SIGA_ATINA.md`: integrate
reusable functionality from `C:\Users\uyv31\src\Atina` into
`C:\Users\uyv31\src\SIGA` (the professor's/final codebase), preserving SIGA
as the architectural and visual source of truth.

### Accepted

- **Path resolution.** The master prompt references SIGA at
  `C:\Users\uyv31\src\SIGUIO\SIGA`, which does not exist. The only SIGA
  checkout on disk is `C:\Users\uyv31\src\SIGA` (a Laravel 13 + Livewire 4
  starter kit with an established hexagonal-DDD pattern under
  `src/IdentityAccess/{Role,Permission}`, RBAC, Fortify auth, and SIGA-branded
  UI, but no domain/business functionality yet). Treated as the intended
  target without asking, since it's the only project matching the
  description and no other candidate exists.
- **Schema scope** (asked the user, since it changes the shape of every
  following slice): Atina implements its "Docencia" module against a
  professor-provided, 35-table shared schema (`Docs/sistema_gestion_academica_utn.sql`)
  described in Atina's own journal as authoritative and shared across 5
  student teams. Atina only has real business logic for a small slice of it
  (teachers, academic credentials/"atestados", specialties, positions, audit
  log) — the other ~30 tables (classrooms, enrollment, course offerings,
  student requests, document management) are empty scaffolding with no
  implemented behavior. The user chose to bring only the tables Docencia
  actually uses into SIGA, not the full 35-table schema, to avoid maintaining
  dead schema for modules nobody has built yet.
- **RBAC approach** (asked the user): SIGA already has a working RBAC
  (`permissions` table with `module`+`action`+`name`, DDD `Role`/`Permission`
  CRUD, UI, and a documented seeding convention in `Docs/Guia-CRUD-SIGA-UTN.md`).
  The professor's shared schema defines a structurally different
  `permissions` shape (`name`+`description` only). The user chose to keep
  SIGA's own RBAC as-is and add the new module's permissions
  (`academic_credentials.*`) into it the normal way, rather than replacing
  SIGA's RBAC tables/DDD modules to match the professor's shape.
- Copied `Docs/Guia-CRUD-SIGA-UTN.md` from Atina into SIGA's own `Docs/` —
  despite living in Atina's repo, it documents SIGA's own CRUD conventions
  (worked example: a `Docente`/Teacher CRUD under an `Academic` bounded
  context) and is the concrete recipe this integration follows.
- Committed SIGA's untouched starter-kit files as a baseline commit before
  any integration change, then branched to `integration/atina-foundation`,
  so the diff history cleanly separates "professor's foundation" from
  "Atina integration work."

### Rejected

- Did not adopt the professor's full shared schema now (see Schema scope
  above).
- Did not replace SIGA's RBAC shape with the professor's (see RBAC approach
  above).
- Did not treat the professor's Spanish table/column names as an
  "external contract" exception to the English-only internal standard for
  the tables being ported: SIGA is not required to physically interoperate
  with the shared multi-team database (that constraint belongs to Atina's
  team), so its own copies of these tables are named in English like the
  rest of SIGA's schema.

### Corrections

Nothing to correct yet — this entry documents the kickoff, not an
implementation slice.

### Learning

The master prompt's assumed directory layout didn't match the actual
filesystem, and the schema/RBAC compatibility conflict wasn't visible until
after reading Atina's actual migrations, models and journal — worth doing a
full read of both codebases' real state before trusting a prompt's stated
assumptions, especially for a task with this much blast radius.

---

## 2026-08-10 — Porting Atina's Docencia module into SIGA's Academic context

### AI consultation

Implementation of the module scoped in the kickoff entry: teachers,
academic credentials ("atestados"), specialties and positions, following
`Docs/Guia-CRUD-SIGA-UTN.md`'s worked example (which literally uses
`Docente`/Teacher as its sample entity under an `Academic` context).

### Accepted

- Bounded context `Academic`, with `Teacher` and `AcademicCredential` as
  independent sibling entities (not one nested inside the other) — they're
  each their own aggregate with their own lifecycle, matching how
  `IdentityAccess` already groups `Role`/`Permission` as siblings rather
  than one containing the other.
- English names for the ported vocabulary: `Docente`→`Teacher`,
  `Atestado`→`AcademicCredential`, `Especialidad`→`Specialty`,
  `Puesto`→`Position`, `Auditoria`→`AuditLog`, `GradoAcademico`→
  `DegreeLevel` (values: diploma/bachelor/licentiate/master/doctorate —
  the Costa Rican degree ladder in English), `AnioObtencion`→`YearObtained`.
- `AuditLog` placed under `src/Shared/Audit`, not inside `Academic` —
  it's a cross-cutting concern (any future module could audit through it),
  matching how `Shared/Export` already holds the PDF/Excel exporters.
- `Teacher` has no Domain/Application layer, only a Presentation-layer
  Livewire component reading `App\Models\Teacher` directly — Atina never
  built one either (docente is explicitly a read-only external reference
  in its own design, "T3"), so this preserves that scope decision instead
  of inventing DDD ceremony around a module with no real business logic.
  `AcademicCredential` gets the full four-layer treatment since it has
  actual invariants (year range, duplicate detection) and use cases
  (register/edit with audit).
- `AcademicCredential`'s domain entity references `specialtyId` as a plain
  int, not Atina's nested `Especialidad` value object — Atina's own
  requirements doc justified that VO as a forward-looking contract for a
  catalog-comparison feature (DO-02) this integration explicitly excludes;
  without that consumer, the wrapper added indirection with no invariant
  behind it. Revisit if/when that catalog feature is ever ported.
- Authorization enforced only through `AcademicCredentialPolicy`
  (`academic_credentials.create` / `.edit`) at the Presentation boundary —
  dropped Atina's separate `PoliticaAutorizacionAtestado` domain-level
  indirection and its use-case-level re-check of the actor's permission
  list, to match SIGA's own established convention (Role/Permission use
  cases don't re-check authorization either; Policy classes are the single
  point of enforcement).
- No delete capability for `AcademicCredential` — the source requirement
  (DO-01) explicitly scoped this to create/edit only ("alta/edición, sin
  baja"); the Policy has no `delete` method and the UI has no delete
  action, rather than adding one nobody asked for.
- `academic_credentials.*` permissions added to `PermissionSeeder::MODULES`
  using SIGA's existing 7-action convention (create/view/edit/delete/
  search/export_pdf/export_excel), replacing Atina's single coarse
  `atestados.gestionar` permission — matches every other module in SIGA's
  RBAC, even though `view`/`delete`/`export_*` aren't wired to anything
  yet (view is implicitly open to any authenticated user, matching Atina's
  own design; delete and export were never built).
- Reused SIGA's existing `x-ui.data-table`/`x-ui.modal`/`x-ui.row-actions`
  Blade components instead of Atina's Flux-table Volt views, so the
  ported screens look native instead of introducing a second design
  system. The teacher's credentials sub-list intentionally does **not**
  use `x-ui.data-table` (which brings search/pagination controls that
  don't make sense for "one teacher's handful of credentials") — a
  smaller hand-built table reusing the same CSS classes instead.
- Wired the sidebar's existing "Teachers" nav item (previously a
  JS-only placeholder with no href) to the real route, following the
  exact pattern already used for Roles/Permissions above it.

### Rejected

- Did not port the ~30 unimplemented tables (classrooms, enrollment,
  course offerings, student requests, document management) from the
  professor's shared schema — see kickoff entry's schema-scope decision.
- Did not give `Teacher` create/edit/delete UI — Atina's own design
  treats it as an external reference; adding CRUD SIGA never asked for
  would go beyond what was actually validated.
- Did not add a "view" permission gate on the teacher list/profile pages —
  matches Atina's original `Route::middleware(['auth'])`-only access; only
  the credential mutation actions are permission-gated.

### Corrections

- PHPStan caught 4 real type errors during verification, all fixed:
  `orderBy()`'s direction parameter needs a literal `'asc'|'desc'`, not a
  plain `string` (`$this->sortDir`); a nullsafe `->position?->name` access
  on a relationship that's actually never null (the FK is `NOT NULL`);
  and `auth()->id()` returning `int|string|null` where the DTO expects
  `int|null` (switched to `auth()->user()?->id`, typed `int` on the model).
- The first version of the login-required-permission test for `Teacher`
  assumed a `hasPermissionTo` gate needed to exist; confirmed by reading
  Atina's actual route file that teacher access is `auth`-only, so no
  such gate was built (see Rejected above).

### Learning

Running `phpstan`/`pint` scoped to only the files this slice touched (not
the whole repo) was necessary — the pre-existing SIGA baseline already has
unrelated Pint style violations across files this integration never
touched, and running `pint --test` repo-wide would have produced a
misleading "failing" result. The pre-existing
`AuthenticationTest::test_login_screen_can_be_rendered` failure (302
instead of 200) reproduces in isolation on an untouched file and is
unrelated to this work — flagged for the user rather than fixed
out-of-scope.

---

## 2026-08-10 — Stabilization pass: branch isolation, architecture review, login test fix

### AI consultation

The user asked for a stabilization/isolation pass before continuing further
Atina integration: (1) verify all migration work happens on a dedicated
branch instead of `main`, (2) audit remote-push safety, (3) review whether
`src/Academic` and `src/Shared/Audit`'s Domain/Application layering is
proportional or was inherited mechanically from Atina, (4) find the root
cause of the `AuthenticationTest::test_login_screen_can_be_rendered`
failure flagged in the previous entry and fix the correct side, (5)
re-verify the Docencia/Academic slice still respects the six previously
established decisions, and (6) run the full verification suite. No new
business functionality was in scope.

### Accepted

- **Branch isolation was already in place.** `integration/atina-foundation`
  already existed, branched from `main`@`c112f53` ("chore: commit SIGA
  starter kit baseline"), and every Atina-integration commit
  (`b4e3491`..`5622582`) was already on it — `main` has never been touched
  by this work. No new branch was created; this pass only re-confirmed the
  isolation and documents it here as requested.
- **Login test was wrong, not the app.** `GET /login` returns 302 by
  design: `FortifyServiceProvider::configureViews()` sends
  `Fortify::loginView()` to `redirect()->route('home')` with an explicit
  comment explaining the login UI lives on the welcome page, not a separate
  `/login` screen. Both that provider and the failing test are untouched
  since the starter-kit baseline commit (`c112f53`) — this predates the
  Atina integration entirely. Root cause: a scaffold-default Fortify test
  that was never updated to match the app's actual auth UX. Fixed by
  rewriting the test to assert the real contract
  (`assertRedirect(route('home'))` instead of `assertOk()`), renamed to
  `test_login_screen_redirects_to_home`. `POST /login` (the real
  credential-submission path) was untouched and already passes.
- **Architecture review: everything reviewed is KEEP, all proportional and
  consistent with SIGA's pre-existing pattern**, not mechanically inherited
  from Atina:
  - `YearObtained` VO — KEEP. Enforces a real invariant (year between 1950
    and current year) reused identically by both create and edit.
  - `DegreeLevel` enum — KEEP. Mirrors a DB column with a fixed, meaningful
    value set.
  - `AcademicCredential` entity — KEEP. Validates a real invariant
    (non-blank institution) and stays immutable.
  - `AcademicCredentialRepositoryInterface` + `EloquentAcademicCredentialRepository`
    — KEEP. Same convention as the pre-existing `Role`/`Permission`
    repositories; not Atina-specific.
  - `RegisterAcademicCredentialUseCase` / `EditAcademicCredentialUseCase` —
    KEEP. Real business logic: duplicate-credential detection
    (teacher+specialty+degree), and edit only audits when a field actually
    changed — this is exactly the kind of business rule the harness says
    justifies a use-case layer.
  - `FindAcademicCredentialUseCase` / `ListAcademicCredentialsForTeacherUseCase`
    — KEEP, on consistency grounds. Each is a trivial one-line pass-through
    to the repository, which in isolation looks like unnecessary ceremony —
    but `IdentityAccess\Role` and `IdentityAccess\Permission` already have
    the identical `Find*`/`List*` use-case shape predating Academic, so
    diverging here would be the inconsistent choice, not the proportional
    one.
  - `AcademicCredentialDTO` — KEEP, same precedent (`RoleDTO`,
    `PermissionDTO` predate Academic).
  - `AcademicCredentialPolicy` — KEEP. Registered through the same
    `DomainServiceProvider::$domainPolicies` mechanism as `RolePolicy`/
    `PermissionPolicy`; correctly has no `delete` method (by design, per
    the earlier decision journal entry).
  - `DomainServiceProvider` — KEEP as-is (only cleaned up its style, see
    Corrections). It's the single existing SIGA-wide binding/policy/route
    provider used by `IdentityAccess` before Academic existed; Academic
    just added its own entries to the same arrays.
  - `Shared\Audit` (`AuditLogRepositoryInterface`, `AuditLogEntry`,
    `EloquentAuditLogRepository`) — KEEP. This one is new in this
    integration (not pre-existing), so it got the closest scrutiny: a
    single-consumer interface would normally be a smell, but
    `InMemoryAuditLogRepository` fakes it in
    `RegisterAcademicCredentialUseCaseTest` /
    `EditAcademicCredentialUseCaseTest` to unit-test the audit-on-change
    business rule without touching the database — real testability value,
    not cosmetic abstraction. The polymorphic `auditable_type`/`auditable_id`
    shape also anticipates the audit trail's only realistic future: more
    academic modules (grades, enrollment) needing the same trail.
  - `Shared\Export` (`ExcelExporterInterface`/`PdfExporterInterface`) was
    out of this review's scope — confirmed via `git log` it already existed
    in the `c112f53` starter-kit baseline, predating any Atina work.
- **Docencia/Academic slice re-verified** against all six previously
  established decisions (scoped tables only, shared schema as future
  reference, SIGA RBAC as sole authorization mechanism, English internals,
  `lang/es.json`-routed UI strings, SIGA visual conventions) — all still
  hold; no drift found.
- Fixed a scoped `Pint`/`PHPStan` run's findings (mechanical, no behavior
  change): import ordering, an unused `WithoutModelEvents` import in
  `PermissionSeeder`, fully-qualified class-string references replaced
  with imports in `DomainServiceProvider`, and a tautological
  `assertNotNull($log->created_at)` in `AcademicCredentialAuditTest`
  removed (Eloquent guarantees `created_at` is set on a freshly persisted
  record; PHPStan flagged it as always-true).

### Rejected

- Nothing relevant. No architecture element reviewed was classified REMOVE
  or SIMPLIFY — every abstraction present has a concrete, verifiable reason
  (an enforced invariant, a real business rule, an existing SIGA
  precedent, or a testability need), so nothing was removed.

### Why it was rejected

Not applicable — see Rejected above.

### Corrections

- `DomainServiceProvider`'s `$domainBindings`/`$domainPolicies` arrays used
  fully-qualified `\Foo\Bar\Baz::class` references inline instead of
  imported short names, and had a stray blank line splitting a docblock
  from the method it documents; both are pre-existing style issues from
  when Academic's entries were added, caught by `pint --test` scoped to
  branch-changed files and fixed with `pint`'s auto-fixer, then re-verified
  with a second scoped `pint --test` + `phpstan analyse` pass.

### Learning

- The remote `origin` (`https://github.com/BSVS777/Atina.git`) points to
  an entirely different, unrelated-history repository (`git ls-remote`
  shows `origin/main` at `e694cd4e`, sharing no ancestry with this
  repository's `main` at `c112f53`). This is almost certainly a leftover
  from how the checkout was created, not an intentional SIGA remote.
  Pushing either `main` or `integration/atina-foundation` to `origin` as
  configured would push SIGA's history into the Atina reference repo (or
  fail/diverge, depending on how GitHub is configured) — flagged for the
  user rather than changed, since remote configuration is explicitly a
  user decision.
- `migrate:fresh --seed` was verified against a disposable copy of the
  local `database/database.sqlite` (copied to the OS temp scratch
  directory, migrated/seeded there, then deleted) rather than against the
  real local dev database, since the harness requires *positively*
  verifying disposability first and copying sidesteps the question
  entirely — migrations, `PermissionSeeder`, `RoleSeeder`, and
  `AcademicManagementDemoSeeder` all completed cleanly.
- The Claude-in-Chrome browser extension was not connected in this
  environment, so the planned real-browser walkthrough of the Teacher
  list/profile pages could not run. Used the strongest available
  alternative instead: HTTP-level checks against a temporary
  `php artisan serve` instance confirming `GET /login` → 302 → `/`, and
  `GET /academic/teachers` (unauthenticated) → 302 → `/login`, combined
  with the existing Livewire-level feature tests
  (`TeacherIndexTest`, `TeacherProfileComponentTest`) that already exercise
  authenticated rendering and the credential CRUD modal end-to-end.
