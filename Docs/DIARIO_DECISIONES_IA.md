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

---

## 2026-08-10 — Correction: `origin` is the intentional canonical repository

### AI consultation

The previous journal entry above (Learning section) flagged `origin`
(`https://github.com/BSVS777/Atina.git`) as an apparently unintentional,
unrelated-history remote and recommended not touching it without user
input. The user then explicitly clarified that this interpretation was
wrong: `BSVS777/Atina` is intentionally the canonical GitHub repository for
this project going forward, and the unrelated-history situation is expected
because SIGA was deliberately introduced as the new project foundation on
top of it. The user asked to reconcile the two histories (Atina's
`origin/main` and the current SIGA-based `integration/atina-foundation`
tree) without discarding either, and without pushing anything.

### Accepted

- **Kept `origin` unchanged**, pointing at `BSVS777/Atina.git`, per the
  user's explicit correction. No remote reconfiguration performed.
- **Connected the two histories with an `ours`-strategy merge**, run from
  `integration/atina-foundation`:
  `git merge origin/main --allow-unrelated-histories -s ours -m "chore: preserve Atina history during SIGA migration"`.
  This makes `origin/main` an ancestor of `integration/atina-foundation`
  (verified via `git merge-base --is-ancestor origin/main
  integration/atina-foundation`, exit code 0) while keeping the current
  SIGA-integrated working tree byte-for-byte unchanged (`git diff
  HEAD^1..HEAD` was empty after the merge). Both Atina's original commit
  history and the SIGA/Academic integration history remain fully walkable
  in `git log --graph --all`, satisfying "preserve provenance of both
  projects, don't squash or discard either."
- **Renamed the old local `main` (SIGA starter-kit baseline, commit
  `c112f53`) to `siga-baseline`** rather than deleting it, since it is
  historically meaningful (the professor-provided starting point) but no
  longer represents "the main branch" once `origin/main` exists locally.
- **Recreated local `main` to point at and track `origin/main`**
  (`git branch main origin/main` + `--set-upstream-to`), so `main` now
  correctly means "the accepted canonical Atina/SIGA state on GitHub,"
  matching normal Git conventions, without switching away from
  `integration/atina-foundation` (the active branch and worktree state).
- **No push performed.** Per explicit instruction, the eventual
  `git push -u origin integration/atina-foundation` and follow-up PR into
  `main` are left for the user to trigger when ready.

### Rejected

- Rewriting/deleting the old local `main` outright — rejected because it
  represents real provenance (the SIGA baseline) rather than disposable
  state; renaming to `siga-baseline` preserves it losslessly.
- A regular (non-`ours`) merge of `origin/main` — rejected because it would
  attempt to reconcile two unrelated full application trees file-by-file,
  which is neither meaningful nor desired; the goal was ancestry linkage,
  not content reconciliation.

### Learning

- An AI's inference from remote metadata alone ("this remote's history is
  unrelated, therefore it's probably misconfigured") can be a reasonable
  hypothesis to flag but is not authoritative — the human running a
  multi-project migration may have full intent behind an unrelated-history
  remote that isn't visible from `git log` alone. The correct behavior
  (previously followed here too) was to surface the observation rather than
  act on it unilaterally, which made this correction a one-step fix instead
  of an unwind.
- `-s ours` combined with `--allow-unrelated-histories` is the right tool
  specifically for "make repository B an ancestor of repository A without
  changing A's tree" — worth remembering as the standard pattern for
  reconciling a rebrand/refoundation scenario where two previously separate
  projects need connected history without a real file-level merge.

---

## 2026-08-11 — Academic Affinity module (DO-01/DO-02/DO-02a/DO-02b/DO-02d): reversal of the English-schema decision and reconciliation with the professor's real MySQL database

### AI consultation

The user asked to implement `IMPLEMENT_ACADEMIC_AFFINITIES.md` in full,
using `Docs/requirements/Proyecto_3_Gestion_Docente_Atinencias.docx` (the
SRS excerpt: FR-DO-01, DO-02, DO-02a, DO-02b, DO-02d) as the primary source
of truth, working autonomously through discovery, reconciliation, the new
module, tests, and verification.

### Accepted

- **MySQL target reconciliation, reversing the 2026-08-10 decision.** The
  earlier entry above explicitly chose to keep SIGA's schema in English and
  *not* treat the professor's shared schema as an external contract. The
  user corrected this today: connected to the real local MySQL instance and
  discovered `gestion_academica_utn_test` — a professor-provided database
  already containing the exact institutional schema (`docentes`,
  `atestados`, `catalogos_atinencia`, `catalogo_atinencia_especialidad`,
  `verificaciones_atinencia`, `notas_tecnicas`, `asignaciones_docentes`,
  `carreras`, `cursos`, `periodos_academicos`, `grupos`, `roles`,
  `permissions`, real seeded roles/permissions/role-permission matrix). The
  user's explicit instruction: this database is the persistence source of
  truth; do not create a replacement `siga`/`siga_test` database; reconcile
  SIGA's migrations onto it non-destructively; keep Spanish identifiers at
  the schema boundary; keep all code (Domain/Application/Presentation)
  English. I initially misread the first ambiguity here — see Corrections.
- **Idempotent/guarded migrations as the reconciliation mechanism.** Every
  migration that touches a table the official database might already have
  starts with `Schema::hasTable()`/`Schema::hasColumn()` guards. Running
  plain `php artisan migrate` (never `--fresh`) is then safe in both
  directions: against the real `gestion_academica_utn_test` (guards skip
  already-existing tables/columns, only genuinely new/additive changes
  apply) and against a fresh environment (sqlite test suite), which builds
  the full official-shaped schema from scratch. Verified both paths: the
  real database migrated cleanly with zero data loss (spot-checked
  `roles`/`permissions` row counts before/after), and the sqlite test suite
  (85 tests) passes against a schema built the same way.
- **Additive-only schema changes on the official database.** Three real
  gaps existed between SIGA's needs and the official schema: `users` was
  missing `avatar_path`; `permissions` was missing `module`/`action` (used
  throughout SIGA's own Role/Permission checklist UI) — backfilled for the
  16 pre-existing rows by splitting `name` on its first `.`; and
  `verificaciones_atinencia` was missing a way to record which academic
  credential justified an "Atinente" result (`IMPLEMENT_ACADEMIC_AFFINITIES.md`
  §13 requires this). All three were additive, nullable, non-breaking
  `ALTER TABLE` migrations — no official column or row was renamed, dropped,
  or had its meaning changed.
- **English/Spanish boundary: two different techniques depending on whether
  the entity already goes through a Domain+Repository layer.** For plain
  Eloquent models with no Domain wrapper (`Position`, `Specialty`,
  `Teacher`, `Career`, `Course`, `AcademicTerm`) — used directly from
  Presentation/Blade — added Laravel 11 `Attribute::make(get:, set:)`
  virtual accessors so every other layer reads/writes English attribute
  names (`$teacher->firstName`) while the real column stays Spanish
  (`nombre`). This does **not** cover query-builder-level column references
  (`orderBy()`, `get([...])`, `firstOrCreate()`), which still need the real
  Spanish column name — fixed each such call site explicitly (documented in
  Corrections). For entities already behind a Domain entity + Repository
  (`AcademicCredential`, `AuditLog`, and the new `AffinityCatalogVersion`,
  `TeacherAssignment`, `AffinityVerification`, `TechnicalNote`), the
  Eloquent model keeps the raw Spanish column names directly, and only the
  Repository's `toDomain()`/`save()` methods (Infrastructure layer) know
  about them — Domain/Application/Presentation never see a Spanish
  identifier. This second pattern needed no accessors and is the one used
  for every genuinely new business entity in this module.
- **Custom `CastsAttributes` classes for four native MySQL ENUM columns
  with literal Spanish values** (`atestados.grado`, `auditorias.accion`,
  `asignaciones_docentes.estado`, `verificaciones_atinencia.resultado`,
  `notas_tecnicas.estado`): `DegreeLevelCast`, `ProposalStatusCast`,
  `VerificationResultCast`, `TechnicalNoteStatusCast`. Each has a
  `TO_DATABASE` map and a `toDatabaseValue()` static helper reused by
  repositories for raw `where()` clauses (Eloquent unwraps PHP enums in
  query bindings using their *English* backing value, which would silently
  fail to match a literal-Spanish-ENUM column without this translation).
- **Business logic taken from the physical schema, not invented,
  wherever the schema was more specific than the SRS text.** Read
  `catalogo_atinencia_especialidad` (specialty id only, no degree-level
  column) as the authoritative resolution of the SRS's ambiguous "al menos
  un grado del docente figura en la entrada": affinity matching compares
  **specialty only**, any degree level counts — the schema simply cannot
  express a degree-level requirement, so the matching algorithm doesn't
  either.
- **Adopted Atina's own `DUDAS_LOGICA_NEGOCIO.md` (its documented
  design-ambiguity log, later partially resolved against the same
  professor schema) as a secondary reference for the ambiguities the SRS
  text itself doesn't resolve**, subordinate to the Word statement and the
  physical schema: D5/D6 (catalog version resolution when no entry's
  validity window covers the target date — implemented exactly as
  documented: pick the entry with the latest `vigencia_inicio` that is
  still ≤ the target date; if none exists, pick the earliest entry
  instead; flag both as provisional), D7 (overlapping validity ranges
  blocked in the application layer, not a DB constraint), D10/D11
  (verifications are immutable snapshots — new events are appended, never
  recalculated), D12 (a Technical Note never overwrites the original
  verification's `resultado` — implemented as an *appended* new
  `AffinityVerification` row with `result = TechnicalNote`, leaving the
  original `NotMatched`/`NoCatalog` row untouched; this is the rubric's
  explicit Excelente/Regular differentiator), D13 (ratification is a real,
  separate manual action — `RatifyTechnicalNoteUseCase`/
  `RejectTechnicalNoteUseCase` — not just automatic expiry), D14 (an
  expired Technical Note is terminal; retrying requires a brand-new
  `TeacherAssignment`, not a reopened one — enforced structurally, since
  `notas_tecnicas.asignacion_docente_id` is unique).
- **Authorization matrix read directly off the official
  `permission_role` table**, not invented or copied from SIGA's own
  `module.create/edit/...` convention: `atestados.gestionar` (Administrador
  + Coordinadora de Docencia) gates DO-01 credential mutation;
  `catalogo.gestionar` (Administrador only) gates DO-02 catalog version
  creation; `atinencia.verificar` (Administrador + Coordinadora) gates
  proposing an assignment, attaching a Technical Note, and the DO-02d
  manual no-catalog decision; `nota_tecnica.aprobar` (Administrador only)
  gates ratifying/rejecting a Technical Note. Retired SIGA's own
  self-invented `academic_credentials.create`/`.edit` permissions in favor
  of the single official `atestados.gestionar` (the official schema does
  not split create/edit for this action). `RoleSeeder` no longer seeds a
  separate `Admin` role — the official `Administrador` already fills that
  purpose — and now reproduces all 9 official roles, the 16 official
  permissions, and the real role→permission matrix, so a fresh/test
  environment's RBAC substrate matches production instead of only having
  SIGA's `Superadmin`.
- **`APP_LOCALE=en` → `es`** (`.env` and `.env.example`). Discovered while
  browser-verifying the new screens: the project's own `lang/es.json`
  already had ~140 pre-existing Spanish translations (plus everything
  added for this module) that were silently never applied because the
  configured locale was English and no locale-switching code exists
  anywhere in the app — every `__()` call across the *entire* application,
  not just this module, was rendering its raw English key. This
  contradicts `AI_HARNESS.md`'s explicit "UI is Spanish" rule. Fixed the
  default; verified full-suite (85/85) still passes and re-verified the new
  screens render correctly in Spanish afterward.
- **Scope-narrowed three professor-owned tables deliberately**, documented
  as such rather than fully modeled: `cursos` (only `carrera_id`, `codigo`,
  `nombre`, `activo` — the real table's `es_servicio`/`es_cuello_botella`/
  `requiere_laboratorio`/`tipo_laboratorio` are scheduling/curriculum
  concerns this module doesn't touch; transversal/service courses shared
  across multiple careers are explicitly out of scope, so `carrera_id` is
  treated as required, not nullable, in this module's own understanding of
  the schema); `grupos` (only `curso_id`, `periodo_academico_id`, `numero`
  are meaningful here — `meta_id`/`modalidad_id`/`cupo` are mandatory
  columns owned by the room/HR-scheduling module and get a fixed bootstrap
  value, never exposed to this module's domain or UI); `asignaciones_docentes`
  (only `grupo_id`, `docente_id`, `estado` are used — `jornada` is a
  mandatory HR workload-fraction column with no default on the real table,
  set to a fixed `1.00` placeholder; `condicion_nombramiento`,
  `quincena`, `numero_accion_personal`, `observacion` are untouched).
  `asignacion_cambios` (schedule/room change history) was not implemented
  at all — it belongs to a different, unbuilt module (room/schedule
  management), not DO-01/02/02a/02b/02d.

### Rejected

- Creating a new `siga`/`siga_test` MySQL database, even temporarily — the
  user was explicit and immediate about this being unacceptable once the
  official database's real purpose was clarified; the databases created in
  the brief window before that correction landed were dropped immediately.
- Running `migrate:fresh` or any destructive command against
  `gestion_academica_utn_test` at any point.
- A generic polymorphic "Files" module for the Technical Note's PDF
  attachment — the professor's schema already has a generic `archivos`
  table built for exactly this purpose (its `tipo_documento` column
  comment even lists "Criterio Técnico" as an example); reused it directly
  (`App\Models\Archivo`, no Domain wrapper, created only from
  `EloquentTechnicalNoteRepository`) instead of inventing a parallel
  attachment mechanism.
- Modeling `AffinityCatalog` as a separate parent entity above
  `AffinityCatalogVersion` — the official `catalogos_atinencia` table has
  no separate "catalog header" row; each version is already a complete,
  self-sufficient, immutable row keyed by `(curso_id, version)`. Adding a
  header entity would have been ceremony with no corresponding schema
  concept.
- A separate `AffinityVerification`-vs-`TeacherAssignment` split modeled as
  two independently-created aggregates — the official schema already
  expresses this exactly as "one `asignaciones_docentes` row (coarse
  Proposed/Confirmed/Rejected status) with an append-only
  `verificaciones_atinencia` trail attached to it," which is what got
  built; a richer, invented status enum on the assignment itself (blocked/
  pending-manual/etc., considered early in the session) was dropped in
  favor of reading that nuance off the latest verification's `resultado`
  instead, matching the real column's only three values.

### Corrections

- **Put a real MySQL password directly into `phpunit.xml`** (a tracked,
  committed file) for a few minutes before realizing the mistake — caught
  it myself before the user flagged it, removed it immediately, and moved
  the credential into `.env.testing`, added to `.gitignore`. That
  particular file was later deleted anyway once the `siga_test` approach
  was reversed, but the lesson (never put a live secret in a tracked
  config file, even as a "temporary" step) stands.
- **Misjudged which database the user meant on the first pass.** After
  discovering the professor's schema in `gestion_academica_utn_test`, I
  initially proposed (and briefly executed, before the user's correction)
  creating fresh `siga`/`siga_test` databases and treating the professor's
  database as read-only inspection material only. The user corrected this
  twice in the same exchange: first to stop and not create replacement
  databases, then (after providing credentials) to explicitly confirm
  `gestion_academica_utn_test` itself is the target for ongoing
  development, with `siga_test`-style disposable-database validation
  replaced by non-destructive `migrate:status` + guarded `migrate` +
  read/write smoke queries.
- **`AcademicManagementDemoSeeder` and `AffinityDemoSeeder` were not
  idempotent on the first attempts**, which surfaced as real errors against
  the real database mid-session: a leftover `teacher_id`/`degree_level`
  array-key bug (pre-existing seeder using the old English column names,
  not yet touched by the reconciliation pass) failed loudly with a MySQL
  "unknown column" error; then a second run duplicated 8 teachers because
  the seeder unconditionally called `Teacher::factory()->count(8)->create()`
  every run. Fixed both: corrected the column names, and added a
  `Teacher::query()->count() > 0` / `asignaciones_docentes` existence guard
  so re-running `db:seed` against the real database is safe. Left the
  ~8 extra duplicate demo teachers created during the debugging cycle in
  place rather than attempting a cleanup `DELETE` against the shared
  database — harmless (small, clearly fake factory data) and safer than a
  destructive fix.
- **`SpecialtyFactory`/`PositionFactory` used `fake()->randomElement()`
  over an 8/4-item fixed list without `unique()`**, which caused a
  real (not hypothetical) `UNIQUE constraint failed: especialidades.nombre`
  test failure once a test created two specialties in the same run. Fixed
  by switching both factories to `fake()->unique()->randomElement(...)`.
- **PHPStan caught several real gaps on the first pass**: two new Eloquent
  models (`TeacherAssignment`, `Archivo`) were missing `use HasFactory`
  despite their factories existing, so `::factory()` calls would have
  failed at runtime the first time anything outside the test suite's happy
  path exercised them; `auth()->id()` returns `int|string|null` (not
  `int|null`) and was passed directly into several use cases typed
  `?int $actorUserId` — switched every call site to `auth()->user()?->id`,
  which matches the existing convention already used elsewhere in the
  codebase (`TeacherProfileComponent`); the three new `CastsAttributes::set()`
  methods were missing `@return array<string, string>` PHPDoc.

### Learning

- Reading the *actual* target database's schema (via read-only
  `SHOW CREATE TABLE`) before designing anything turned what would have
  been a from-scratch, guessed design for DO-02/02a/02b/02d into a direct
  port of an already-correct, already-battle-tested schema — the
  `especialidades`-only (no degree-level) affinity match, the append-only
  verification trail, and the explicit `Ratificada`/`Rechazada`/`Vencida`
  Technical Note states were all things I would likely have designed
  differently (and less correctly, per the rubric's own stated
  Excelente/Regular criteria) without that inspection.
- A migration guarded with `Schema::hasTable()`/`hasColumn()` is a strong,
  general pattern for "this project's migrations must build correctly on
  both a fresh environment and an environment where the official schema
  already exists" — worth keeping as the default template for any future
  work against this same professor-provided database, rather than
  special-casing it per table.
- Virtual English attribute accessors (`Attribute::make`) are a clean
  solution for plain, Presentation-facing models, but they have a real
  blind spot: Eloquent's query builder (`where`, `orderBy`, column-selecting
  `get()`, `firstOrCreate`) never goes through them. Any model using this
  pattern needs its raw-query call sites audited by hand; there is no way
  to make the accessor "leak" into query building automatically.

---

## 2026-08-13 — UX/UI polish pass on the Academic Affinity screens

### AI consultation

The user asked for a UI/UX improvement pass over the Docentes / Affinity
Catalog / Affinity Verification screens (sidebar nesting, catalog-version
modal, proposed-assignments table, "propose teacher" and "technical note"
modals), under a hard constraint: reuse the existing visual system exactly
(no new colors, badges, typography, components, or dependencies), and keep
the change composition/layout-only.

### Accepted

- **Sidebar nesting** (`resources/views/components/siga/sidebar.blade.php`):
  made "Docentes" a parent nav item with "Catálogo de atinencias" and
  "Verificación de atinencias" as children, reusing the `nav-parent` /
  `nav-children` / `chevron-toggle` classes and Alpine toggle idiom already
  established there for the placeholder "Groups"/"Reports" sections — zero
  new CSS. Because the `<aside>` sidebar is `x-persist`ed across
  `wire:navigate` transitions (a deliberate choice documented inline, to
  avoid breaking `align-items: stretch`), the new group's DOM node is only
  ever mounted once; a small Alpine helper (`syncTeacherGroup`, run on
  `x-init` and on the `livewire:navigated` window event) recomputes both the
  parent's "active" state and open/closed state from `window.location.pathname`
  on every SPA navigation, since a one-time Blade `request()->routeIs()`
  check would go stale the moment the user navigates away from the first
  page they ever loaded.
- **Affinity Catalog modal** (`AffinityCatalogComponent.php` +
  its view): grouped the create-version form into three visually separated
  blocks (legal documentation, validity period, affine specialties) using
  the `control-group` class (already used elsewhere as a small uppercase
  eyebrow label) purely for section headers — no new class. Replaced the
  specialties checklist's bare unbounded `<div>` with the exact
  `permissions-list`/`permission-item` pattern already used by the Roles
  CRUD (bounded height, scrollable), and reused that same screen's Alpine
  local-filter idiom for a specialty search box — added only when there are
  more than 8 specialties, per the brief's explicit "only add search if a
  reusable pattern already exists" rule.
- **Proposed-assignments table** (`TeacherAssignmentComponent` view):
  rebalanced column widths (narrower Actions column, wider
  Catalog/justification column), top-aligned multi-badge cells instead of
  center-aligning them against single-line cells, and split the
  catalog-citation + technical-note-badge cell into a flex column instead of
  a manual `<br>`. No badge colors, business rules, or Livewire method names
  touched.
- **Modals**: added one contextual sentence to the "Propose teacher" modal
  (clarifies the teacher→group→verify dependency) and one muted helper line
  under "Ratification deadline" in the technical-note modal (states that an
  Administrator must ratify before that date) — both net-new `lang/es.json`
  keys, both plain text using the existing `textSecondary`/`textMuted`
  tokens, no new components.

### Rejected

- A searchable/autocomplete `<select>` for the Teacher field in the
  "propose teacher" modal — no such reusable component exists in the
  codebase (confirmed via full-repo search), and the brief explicitly
  forbids adding an external dependency just for this.
- A disabled-until-valid state on the "Verificar atinencia" primary button —
  no existing screen in the codebase implements that pattern, and the brief
  said to add it only if the pattern already existed.
- Touching the Teachers list/profile screens beyond the sidebar entry — the
  brief gave no specific density/hierarchy complaint about them (unlike
  Catalog/Verification/Assignments), and they already use the shared
  `<x-ui.data-table>` component cleanly; changing them would have been
  scope creep against "smallest reviewable diff."

### Corrected

- None — Pint and the full test suite (`php artisan test`, 85/85) passed on
  the first run; no server-side/business logic was touched (all changes are
  Blade markup, inline scoped styles reusing existing CSS custom properties,
  one Alpine snippet, and one new read-only view variable
  `selectedCourse`).

### Learning

- `wire:current` cannot express "this parent is active because one of its
  *sibling* routes is active" — it only compares its own element's `href`
  to the current URL. Combined with `x-persist` (which means the sidebar's
  Alpine scopes mount once, not per navigation), any "parent glows active
  when a child page is open" requirement in this codebase needs a small
  explicit `livewire:navigated`-driven recompute, not `wire:current` alone.
- Browser-based verification (Playwright/Puppeteer) was not runnable in this
  environment: the project's Python has no `pip`/`playwright`, and the
  `puppeteer` npm dependency has no Chrome binary downloaded
  (`puppeteer browsers install chrome` was not run, since downloading a
  browser binary wasn't something to do unprompted). Verification for this
  change therefore relied on the full automated test suite (85/85 passing,
  unchanged assertions on rendered text used by `assertSee`), Pint, and
  direct diff review — not a real rendered screenshot. Flagged to the user
  as the one open risk in this change.

---

## 2026-08-13 — Sidebar toggle bug fix + global form-field border gap

### AI consultation

The user reported two problems after the first UX pass: (1) the "Docentes"
sidebar group doesn't collapse when clicked again or when another nav group
is opened, and (2) across forms in general, some fields render with a
visible bordered box (text inputs) while others — noticed inside the
Academic Affinity modals — render as plain unstyled text, with no visual
cue that they're editable.

### Accepted

- **Sidebar toggle root cause**: the collapse chevron was nested inside
  `<a href="..." wire:navigate>`. Livewire's `wire:navigate` click
  interceptor is attached in the capture phase; Alpine's
  `@click.stop.prevent` on the nested chevron runs in the bubble phase,
  which fires *after* capture — so Livewire's listener already decided to
  navigate before Alpine's handler could stop it. Every click on/near the
  chevron re-navigated to the Teachers page and re-opened the group via the
  route-sync logic, which looked like "it won't close." Root-cause fix:
  moved the toggle into a sibling `<button>` outside the `<a>` (new
  `.nav-parent-row`/`.nav-parent-link`/`.nav-parent-toggle` CSS, additive
  only — `.nav-item`/`.nav-parent`/`.nav-children` untouched), so there is
  no ancestor anchor for Livewire's interceptor to find.
- **Accordion mutual exclusion**: introduced `Alpine.store('sidebarNav',
  { openGroup: null })` (`resources/js/sidebar-nav.js`, registered via the
  same `alpine:init` convention already used by `data-table.js`) so
  Docentes/Groups/Reports share one "which group is open" value instead of
  three independent local booleans — opening one now closes the others,
  which is what "cierra al abrir otro nodo" asked for. Chose a store over
  per-node local state specifically because the sidebar is `x-persist`ed
  across `wire:navigate`, so there is no single common ancestor scope that
  survives navigation for all three groups to read from.
- **Global form-field border gap**: root cause was that
  `.form-field input[type="text"], .form-field textarea` in `app.css` never
  covered `select`, `input[type="date"]`, `input[type="file"]`, or
  `input[type="number"]` — every screen using those types (Affinity
  Catalog, Teacher Assignment, Academic Credentials, Teacher list's course
  filter) silently fell back to unstyled browser-default controls next to
  properly-boxed text inputs in the same form. Fixed once at the shared
  selector (added the four missing types to the box, focus-glow, and
  `.has-error` rules) instead of patching each view — same border-radius/
  border-color/background tokens already used for text inputs, so every
  form field across the whole app now gets the same rounded box uniformly,
  with zero new colors or tokens.

### Rejected

- Keeping Groups' original "open by default on first load" quirk
  (`x-data="{ open: true }"`) now that all three groups share one store —
  it was an arbitrary leftover from placeholder scaffolding, not a real
  requirement, and keeping it would have meant special-casing the shared
  store's initial value for a non-functional section while Docentes (which
  now has real routes) has no equivalent claim to default-open. All three
  now default closed unless a real route match claims the store.

### Learning

- On this codebase's sidebar, any interactive control that must NOT trigger
  `wire:navigate` cannot safely live inside an `<a wire:navigate>` even with
  `.stop.prevent` — the fix has to be structural (sibling element), not
  event-modifier based, because of the capture/bubble ordering between
  Livewire's own listener and Alpine's.
- A `.form-field` (or any shared form-wrapper class) selector list needs to
  be audited against *every* input type actually used under it, not just
  the ones present when the rule was first written — `type="text"` was the
  only case covered originally (Role/Permission forms), and every module
  added afterward with a different input type silently fell outside it with
  no error, no lint signal, nothing but a visual inconsistency a human had
  to notice by eye.

---

## 2026-08-20 — Teacher creation, exports, localization audit, upload bug fix

### AI consultation

The user asked for an implementation pass on the three Academic screens
(Docentes, Catálogo de Atinencias, Verificación de Atinencias): add a real
"Agregar docente" action (previously read-only by design), add PDF/XLSX
exports to all three, fully audit and permanently fix the module's
localization inconsistencies (not just patch the reported examples), fix a
too-tight header spacing in the Verificación table, and diagnose/fix a
reported "cannot upload a PDF" bug in the Technical Note attach flow.
TypeScript/JWT/external-API work was explicitly out of scope for this pass.

Before implementing, the following were confirmed with the user rather than
guessed, per the module's own "do not guess silently" instruction:

- Teacher creation does **not** create/link a `User` account — `user_id`
  stays null, matching the existing seeded data shape.
- Teacher creation is gated on the existing official `usuarios.gestionar`
  permission (currently granted only to `Administrador` in `RoleSeeder`),
  not a new SIGA-owned `teachers.create` permission.
- The English demo seed data in `puestos`/`especialidades` (e.g. "Professor
  2", "Information Systems Engineering") was left alone this pass — only
  the two confirmed hardcoded-string bugs (`Teacher`, `Credentials`) were
  fixed. Reseeding institutional Spanish reference data is a separate,
  larger decision than this pass's scope.
- The Technical Note upload environment gap (see below) was fixed locally
  (`php.ini` `upload_max_filesize`/`post_max_size` raised) so the fix could
  be verified end-to-end with a real PDF, in addition to the code fix.

### Accepted

- **Teacher creation**: added `TeacherPolicy::create()` (gated on
  `usuarios.gestionar`, mirroring `AcademicCredentialPolicy`'s
  single-permission, no-delete-method shape), a `TeacherForm` Form object,
  and a `CreateTeacherUseCase` that calls `Teacher::create()` directly — no
  repository/Entity layer was introduced because none existed for Teacher
  before (it was Presentation-only, read-only by design). Fields exposed:
  position (select, from `Position::all()`), national ID/cédula, first
  name, last/second last name, estimated workload, active — exactly the
  `docentes` table's columns, no invented fields. No edit/delete added;
  create was the only requested action.
- **Exports**: reused the existing `InteractsWithExports` trait and
  `Src\Shared\Export` ports/adapters (Spatie PDF/Excel) end-to-end for all
  three screens, following `RoleComponent`'s established `exportPdf`/
  `exportExcel` pattern exactly — no new export library, no duplicate
  infrastructure. Each screen's export is gated on the same permission its
  `create`/`decide` action already uses (`usuarios.gestionar` for Docentes,
  `catalogo.gestionar` for Catálogo, `atinencia.verificar` for
  Verificación), and respects the current search filter via a new shared
  `InteractsWithDataTable::filterRows()` (factored out of `paginateRows()`
  so on-screen filtering and export filtering are guaranteed identical, not
  two parallel implementations that could drift). Verificación's export
  adds a `catalogOrJustification` field to `toRow()` — export-only, prefers
  `justification()` when a future use case starts populating it, falls back
  to the existing catalog citation today.
- **Localization root causes** (category A: hardcoded strings — the *only*
  category that actually needed fixing this pass): `Teacher`, `Credentials`,
  and `Course` were all already wrapped in `__()` calls — the bug was
  never a hardcoded string in the traditional sense, it was a **missing
  `lang/es.json` entry**, silently rendering the literal English string
  because the app has no `lang/en.json` fallback file to mask the gap
  (`APP_FALLBACK_LOCALE=en` points at a file that doesn't exist). Fixed by
  adding the three missing keys. Verified there are no other such gaps in
  the Academic module by diffing every `__('...')` literal found under
  `resources/views/academic` and `src/Academic` against `lang/es.json` —
  zero missing after the fix. Category B (enum/status display values like
  `Confirmed`/`Ratified`/`Expired`) was already fully covered by existing
  keys — no action needed. Category C (`puestos`/`especialidades` reference
  data seeded in English) is a real, verified issue but was explicitly left
  alone this pass per the user's decision above — documented here so it
  doesn't get silently rediscovered as "new" later.
- **Also published `lang/es/validation.php`** (framework-native Laravel
  Spanish validation strings) — discovered while wiring Teacher's form
  validation that this file never existed anywhere in the repo, so every
  form in the app (Role, Permission, AcademicCredential, Teacher, etc.) was
  silently showing English validation messages under `APP_LOCALE=es`. This
  is a pre-existing, app-wide gap, not a Teacher-specific one; fixing it in
  one place fixed it everywhere (full suite re-run confirmed no
  regressions).
- **Verificación table spacing**: the shared `.data-row`/`.data-row-head`
  grid had no `column-gap` at all — every data-table in the app relied
  purely on `--table-cols` fr-ratios with zero gutter between adjacent
  header cells. Fixed once at the shared component (`app.css`,
  `column-gap: 20px` on both rules) rather than special-casing the
  Verificación view, since the same lack-of-gap affects every table, it was
  just most visible on Verificación's two long adjacent headers
  ("Resultado de verificación" / "Catálogo / Justificación").
- **Technical Note PDF upload — the real bug**: static code tracing alone
  (Livewire wiring, `WithFileUploads` placement, validation rules, storage
  config) all checked out correct and did **not** find the actual cause —
  it only surfaces when the interaction is actually driven in a browser.
  The real root cause is an **infinite recursion in the dropzone's Alpine
  JS**: the file `<input>` had `x-on:change="accept($event.target.files)"`,
  and `accept()` — used by the drag-and-drop path to synthesize a `change`
  event so Livewire's `wire:model` listener would fire — unconditionally
  called `this.$refs.input.dispatchEvent(new Event('change', {bubbles:
  true}))` on every invocation, including when `accept()` itself was
  invoked *by* that same change event (native file-picker selection, or
  its own synthesized dispatch). That dispatch re-triggered the same
  `x-on:change` handler, which called `accept()` again, which dispatched
  again — an unconditional, always-triggering synchronous recursion that
  blew the JS call stack (`Maximum call stack size exceeded`, confirmed via
  live browser console capture) on every single file selection, by any
  method. This fully explains "cannot upload a PDF" — the browser tab's JS
  thread crashed before the upload could meaningfully proceed. Fixed by
  splitting the single `accept()` into `handleFiles()` (UI-state-only,
  dispatch-free, bound to the input's own `x-on:change`) and `acceptDrop()`
  (drag-and-drop only: sets `input.files` and dispatches `change` exactly
  once, which now safely lands on the dispatch-free `handleFiles()`).
- **Technical Note upload — contributing environment gap**: separately,
  local `php.ini` had `upload_max_filesize=2M` against the form's own
  documented 10 MB limit (`TechnicalNoteForm.php` `max:10240`), which would
  have silently rejected any real signed PDF over ~2 MB even after the JS
  fix. Raised to `upload_max_filesize=12M` / `post_max_size=20M` locally
  (matching Livewire's own 12 MB temp-upload default headroom) so the fix
  could be verified end-to-end with a real PDF, per the user's explicit
  confirmation. This is a local dev-environment setting, not tracked by
  git — flagged here so it's not silently rediscovered as a "regression"
  on a machine where it wasn't applied.
- **Technical Note upload — silent-failure UI bug**: independently of the
  above, the `livewire-upload-error` handler only cleared the filename with
  no user-visible feedback on a real server-side upload failure. Added an
  `uploadFailed` state and a visible error message so future failures
  (wrong environment limits, storage errors, etc.) are not silently
  invisible to the user again.
- **Verified end-to-end in a real browser** (Playwright, headless
  Chromium, logged in as the seeded `Administrador` account): created a
  teacher through the UI and confirmed it appears in the list; downloaded
  real PDF and XLSX files from all three screens (had to run `npx puppeteer
  browsers install chrome-headless-shell` first — Browsershot's headless
  Chrome wasn't present in this environment, a pre-existing setup gap, not
  introduced by this change); uploaded a real PDF through the Technical
  Note dropzone and confirmed the resulting "Nota técnica: Ratificación
  pendiente" row appeared with the exact deadline entered, no console
  errors.

### Rejected

- A teacher-specific upload permission or teacher-specific validation
  message convention — reused existing official permissions and, once
  discovered missing, the framework-native Spanish validation file, instead
  of inventing anything module-specific.
- Reseeding `puestos`/`especialidades` demo data to Spanish this pass — a
  real issue, deliberately deferred, see Accepted above.
- Patching the Verificación spacing issue with a page-specific style
  override — fixed once at the shared data-table component instead, since
  the same gap-less grid affects every table in the app.

### Learning

- A root-cause diagnosis done entirely through static code reading can be
  wrong even when every individually-checked piece (trait usage, form
  binding, validation rules, storage config) is correct — an Alpine
  event-recursion bug only exists in the *interaction between* two pieces
  of markup that each look fine in isolation, and only manifests when
  actually driven in a browser. The lesson generalizes: for a "user cannot
  do X" report tied to client-side interactivity, a real browser
  reproduction is not optional verification, it's how the actual bug gets
  found in the first place.
- `lang/es.json` having no matching `lang/en.json` fallback means every
  missing Spanish key is a silent literal-English leak with no error, no
  warning, nothing but a human noticing it by eye — the same category of
  gap as the `.form-field` selector list from the 2026-08-13 entry above.
  Worth a systematic audit (diff every `__('...')` call site against the
  key file) rather than trusting that "it's already wrapped in `__()`"
  means it's actually translated.

---

## 2026-08-20 — Browsershot temp-dir fix + a narrowly-scoped edit action on Catalog versions

### AI consultation

Two requests. First: PDF export was throwing `ErrorException mkdir():
Permission denied` at `vendor/spatie/temporary-directory/src/TemporaryDirectory.php:50`.
Second: add row action buttons to the Catálogo de Atinencias table.

### Accepted

- **Browsershot temp path fix.** Browsershot (used by `SpatiePdfExporter`)
  defaults to `sys_get_temp_dir()` for the intermediate HTML/options files
  it hands to headless Chrome. On this Windows setup the PHP process
  identity lacks write access to that OS-level temp folder — confirmed by
  reading `spatie/temporary-directory`'s `create()`, which calls a bare
  `mkdir()` with no fallback. Fixed by calling
  `->setCustomTempPath(storage_path('app/browsershot'))` in
  `SpatiePdfExporter::fromHtml()`, since `storage/app` is already
  guaranteed writable by Laravel itself — sidesteps the OS temp dir
  entirely rather than trying to fix its permissions. The directory is
  created on demand and tracked with its own `.gitignore` (parent
  `storage/app/.gitignore` needed an explicit `!browsershot/` exception
  added, or the whole folder — including its own `.gitignore` — would
  have been silently excluded by the parent's blanket `*` rule).
- **Scoped-down edit action on catalog versions** (asked the user first —
  the initial request was a bare "add edit"). `CreateAffinityCatalogVersionUseCase`
  and the requirements matrix both document DO-02's explicit rule: "cada
  actualización crea una nueva versión sin eliminar las anteriores" — no
  in-place edit, ever. That rule exists because
  `AffinityVerification.catalogVersionId` is immutable and historical
  verifications must keep showing the catalog version that was actually
  applied at the time (DO-02's "historical verification shows the version
  applied at the time" requirement, already covered by
  `TeacherAssignmentVerificationTest`). Editing a version already cited by
  a verification in place would silently rewrite that citation. Presented
  this conflict to the user with the two real options (edit-only-while-unused
  vs. unrestricted edit that breaks the historical guarantee); the user chose
  the safe option. Implemented `UpdateAffinityCatalogVersionUseCase`, which
  refuses (`CatalogVersionInUseException`) the moment
  `AffinityCatalogVersionRepositoryInterface::hasVerifications()` is true,
  still re-validates the overlap rule (excluding itself) and re-runs the
  same constructor invariants, and audits the change like the create path
  does. `course_id` and `version_number` are read back from the existing
  entity rather than trusted from the submitted form, so a tampered
  request can't move a version to another course or renumber it. The
  Livewire component computes `canEdit` per row (`! hasVerifications()`)
  and hides the edit icon once a row is no longer safely editable; the use
  case re-checks server-side regardless, since a verification could land
  between page render and form submit.

### Rejected

- Unrestricted in-place editing of any catalog version at any time — the
  user's literal first request — because it would let an Administrador
  retroactively change what a past, already-decided teacher-assignment
  verification's catalog citation shows, contradicting a requirement the
  matrix already lists as `IMPLEMENTED`.
- Bulk-checking `hasVerifications()` for all rows in one query — the
  catalog is a small, per-course, rarely-updated list (new versions are a
  deliberate administrative act, not routine data entry), so one query per
  row stays proportional; a batched check would be premature optimization
  for a table that never grows large in this domain.

### Learning

- A permission-denied error inside a third-party package's temp-file
  handling is usually not a bug in that package — it's a mismatch between
  the OS-level directory the package defaults to and what the running
  process is actually allowed to touch. The generalizable fix is the same
  shape every time: point the package at a directory the *application*
  already knows is writable (Laravel's own `storage_path()`) instead of
  trusting an OS default that varies by host/service-account setup.
- Not every "add X" request is safe to implement as literally asked. When
  the codebase already documents *why* a capability was deliberately left
  out (not just that it was left out), that's a signal to surface the
  conflict before writing code, not after — the safe middle ground here
  (edit only before first use) was not something the user had already
  considered, and delivering the literal ask would have quietly
  reintroduced a bug DO-02 was written to prevent.

---

## 2026-08-21 — Fix: cancelling the technical-note upload dropzone didn't clear the file

### AI consultation

User reported: uploading a PDF in "Adjuntar nota técnica" and then hitting
Cancel or the X doesn't remove the file — and opening the modal for a
*different* row afterwards still shows the previous file selected.

### Accepted

- Two independent bugs, same feature, fixed together:
  1. **Backend**: `closeNoteModal()` only flipped `showNoteModal = false` —
     it never reset `noteForm` nor deleted the already-uploaded
     `TemporaryUploadedFile` from `storage/app/livewire-tmp`, unlike
     `openNoteModal()` which does reset. Fixed by calling
     `$this->noteForm->document?->delete()` then `reset()` +
     `resetValidation()` in `closeNoteModal()`
     (`TeacherAssignmentComponent.php`), mirroring what open already did.
  2. **Frontend**: the note modal (`x-ui.modal`) is always present in the
     DOM (visibility toggled by a CSS class, not `x-if`/`wire:key` on the
     modal itself), so the dropzone's Alpine `x-data` (`fileName`,
     `invalid`, `uploadFailed`) never unmounts between opens — it's local
     client state Livewire's re-render can't touch. Fixed by keying the
     dropzone `<div>` on `wire:key="note-dropzone-{{ $activeAssignmentId
     }}-{{ $showNoteModal ? 'open' : 'closed' }}"`, so morphdom
     destroys/recreates the element (and Alpine reinitializes fresh) on
     every open, close, or row switch.
- Added `test_closing_the_modal_clears_the_selected_document_and_deletes_its_temp_file`
  to `TechnicalNoteUploadTest` asserting both the Livewire property and the
  physical temp file are gone after `closeNoteModal()`.

### Learning

- A Blade modal that stays mounted in the DOM (hidden via CSS, not
  conditionally rendered) silently decouples any Alpine `x-data` inside it
  from the Livewire component's lifecycle: PHP-side `reset()` never
  reaches client-only state. `wire:key`ing the stateful inner element to
  something that changes on every open/close is the fix, not trying to
  drive Alpine state from PHP.

---

## 2026-08-24 — Batch 1: functional/UI acceptance closeout (DO-01/02a/02b/02d) + localization cleanup

### AI consultation

User asked to implement `Docs/Atina_Implementation_Prompt_Batches/01_BATCH_FUNCTIONAL_UI_CLOSEOUT.md`,
a scripted closeout pass covering four literal UI acceptance gaps a prior
audit (`FINAL_ACADEMIC_MODULE_AUDIT.md`, untracked in this working copy)
had flagged against the Academic module, plus a full localization audit
and a re-verification of the Technical Note PDF upload flow. TypeScript,
JWT and an external REST API were explicitly out of scope for this batch.

### Accepted

- **DO-01 citation gap (career/course missing)**: `TeacherProfileComponent`
  now eager-loads `Course::with('career')` for the context-course dropdown
  and, once a course is selected, renders a `:career · :code — :name` line
  (e.g. "Ingeniería del Software · ISW-521 — Programación en Ambiente Web
  I") above the existing catalog citation line, which was itself reworded
  from `v:number — :agreement / Gazette :gazette` to `Catalog v:number ·
  Agreement :agreement · Gazette :gazette` to match the target presentation
  concept in the batch brief. No change to the affinity engine, schema, or
  `ResolveApplicableCatalogVersionUseCase` — this is presentation-layer
  only, reusing the already-loaded `Course→Career` relationship. Covered by
  a new `TeacherProfileComponentTest::test_evaluating_affinity_in_a_course_context_shows_career_course_version_and_agreement`.
- **DO-02a blocking message**: added an explicit "Assignment blocked: the
  teacher does not meet the affinity required for this course." line under
  the "No Atinente" badge in `TeacherAssignmentComponent`'s table, shown
  only for `not_matched` results. Badge and "Attach technical note" action
  unchanged.
- **DO-02d manual-approval label**: added an explicit "No catalog —
  pending manual approval" line, shown only while `canDecideNoCatalog` is
  true (i.e. `no_catalog` result, not yet decided) — derived from existing
  state, no new DB enum. Approve/Reject/audit behavior unchanged.
- **Technical Note pending label**: replaced the generic
  `"Technical note: Pending ratification (date)"` line with an explicit
  "Technical note — ratification pending from the University Council" +
  "Deadline: :date" pair, shown only while the note's status is
  `pending_ratification`; ratified/rejected/expired notes keep the
  previous generic rendering (`ucfirst(str_replace('_',' ', status))`),
  since the batch only asked to clarify the *pending* state.
- **Localization audit**: diffed every `__('...')` call site under
  `resources/views/academic` and `src/Academic` against `lang/es.json` —
  zero missing keys (the four new UI-copy additions above were added to
  `lang/es.json`). Added the ~14 previously-missing custom validation
  attribute labels for `AcademicCredentialForm`, `AffinityCatalogVersionForm`,
  `ProposeAssignmentForm` and `TechnicalNoteForm` to `lang/es/validation.php`'s
  `attributes` array (only `Teacher`'s fields had labels before this pass —
  every other Academic form's validation errors were silently showing raw
  camelCase field names like "The document field is required." instead of
  a Spanish label).
- **Position/Specialty English demo seed data** (the item explicitly
  deferred in the 2026-08-20 entry, "Category C"): fixed at the source.
  `AcademicManagementDemoSeeder` seeded `puestos`/`especialidades` rows in
  English (`'Professor 2'`, `'Information Systems Engineering'`, …), which
  render directly as institutional display values with no `__()`
  translation layer — a real localization bug, not a missing-key one.
  Inspecting the local dev DB (`gestion_academica_utn`) showed it already
  held the correct Spanish rows (`'Profesor 2'`, `'Ingeniería en Sistemas
  de Información'`, …, ids 1-8 for each table) sitting *alongside* the
  seeder's English duplicates (ids 5-8 / 9-16) — the English rows were
  pure seeder-created junk, not official schema data. Updated the seeder
  to the exact Spanish strings already present (so `firstOrCreate` matches
  existing rows on a fresh run instead of duplicating), then — after
  confirming zero `docentes.puesto_id` / `atestados.especialidad_id`
  references to the English rows and getting the user's explicit go-ahead
  before touching the shared local database — deleted the 4 orphaned
  Position rows and 8 orphaned Specialty rows.
- **Technical Note PDF upload re-verification**: re-ran
  `TechnicalNoteUploadTest` (valid PDF succeeds, missing PDF rejected,
  non-PDF rejected, oversized rejected, invalid deadline rejected, cancel
  clears the temp file) unchanged — all six still pass on current HEAD, no
  regression found, flow left as-is per the batch instruction.
- Added `tests/Feature/Academic/TeacherAssignmentUiAcceptanceTest.php`
  (3 focused Livewire-render tests for the three `TeacherAssignmentComponent`
  UI-copy fixes above, asserting the exact rendered strings via `assertSee`).

### Rejected

- Adding a new DB column/enum for the "Sin catálogo" pending-approval
  state — the batch explicitly said not to if it's derivable from existing
  state, and `canDecideNoCatalog` (`result === NoCatalog && !isDecided()`)
  already derives it correctly.
- Changing the Technical Note state machine, the affinity matching
  algorithm, or any DO-02/DO-02a/DO-02b/DO-02d business behavior — out of
  scope for this batch, none of it was touched.
- Re-seeding `puestos`/`especialidades` with anything other than the exact
  strings the local DB already had — inventing new Spanish translations
  instead would have created a *third* set of near-duplicate rows instead
  of consolidating on the one already in use.

### Verification

- `php artisan test`: 116 passed / 122 total. The 6 failures (3 filename-
  slug mismatches, 3 `Spatie\SimpleExcel\SimpleExcelWriter not found`
  errors) are all in `AffinityCatalogExportTest`/`TeacherAssignmentExportTest`/
  `TeacherExportTest` and are pre-existing environment issues unrelated to
  this batch: `.env` has `APP_LOCALE=en` (so `__()`-derived export
  filenames render in English against Spanish-literal test expectations),
  and `spatie/simple-excel` is declared in `composer.json`/`composer.lock`
  but not present in `vendor/` in this sandbox. Neither touched by this
  batch's diff; not fixed here as it's outside the batch's stated scope.
- `./vendor/bin/pint --test`: fails on the same 21 pre-existing
  SIGA-baseline files outside `src/Academic` as the prior audit found —
  zero files under `src/Academic/**`, `tests/**`, or files touched by this
  batch fail.
- `./vendor/bin/phpstan analyse`: produces no output and exits 1 in this
  sandbox regardless of flags (`--memory-limit`, `--debug`), same as the
  prior audit's documented finding; `phpstan --version` works, confirming
  the binary itself is fine. Unverifiable here, not guessed as pass or
  fail.
- `npm run build`: succeeds, 24 modules transformed.
- Browser tooling (`tabs_context_mcp`) was not connected in this
  environment/session either — verification instead relied on Livewire
  `assertSee` feature tests against the actual rendered Blade output for
  all four UI acceptance fixes, which is a more precise (string-exact)
  check than a visual screenshot would have been for this kind of change.

### Learning

- When an audit says demo seed data is "still English" but a shared local
  database has already accumulated real Spanish rows alongside it, check
  the DB before touching the seeder — the fix might be "point the seeder
  at what's already there and clean up the duplicates it created," not
  "invent a new translation."

## 2026-08-24 — Small verification correction: local environment fix, full suite re-run, professor-decision documentation correction

### AI consultation

User asked for a small, non-feature correction pass: fix the local
environment issues that had left `php artisan test` at 116/122 in the
prior Batch 1 verification, re-run the full suite and static/build
checks, and correct any current-state documentation that still presented
two business decisions as unresolved after the professor had since
explicitly confirmed them. No TypeScript, JWT, or external REST API work;
no push.

### Accepted

- **Local `.env` locale fix**: `APP_LOCALE`/`APP_FALLBACK_LOCALE` were
  `en`/`en` locally against a project intended to run in Spanish
  (`.env.example` already specified `es`/`en`). Corrected the local `.env`
  only (not committed) to `APP_LOCALE=es`, ran `optimize:clear`, confirmed
  `app()->getLocale()` resolves to `es`.
- **Restored `vendor/` via `composer install`**: `spatie/simple-excel` was
  declared in `composer.json`/`composer.lock` but missing from the local
  `vendor/` install (a stale/partial local install, not a lockfile
  problem). `composer install` reconciled `vendor/` against the existing
  lock file — no `composer.json`/`composer.lock` changes. This also
  removed several Pest packages that were present in `vendor/` but not
  declared in `composer.lock` (the project's actual test framework is
  PHPUnit, confirmed via `composer.json`'s `require-dev`), and brought a
  few packages up to their already-locked versions.
- **Professor-decision documentation correction**: the professor has since
  explicitly confirmed two items `FINAL_ACADEMIC_MODULE_AUDIT.md` had
  flagged as needing confirmation: (1) affinity matching is specialty-only
  — the credential's specialty must be explicitly listed in the course's
  applicable catalog entry; degree level does not independently determine
  affinity; a related specialty is not automatically affine (e.g. a
  Cybersecurity specialty is not automatically affine to a Programming
  course); no semantic/fuzzy/AI-based inference. (2) Administrador's
  broader-than-SRS-text Academic-module access (Technical-Note-creation,
  No-Catalog-decision) is intentional — Administrador has access to
  everything. Updated the relevant classifications/tables in
  `FINAL_ACADEMIC_MODULE_AUDIT.md` (the document that actually contained
  the "NEEDS PROFESSOR DECISION" wording) from unresolved to
  professor-confirmed, preserving the underlying code-trace evidence.
  `Docs/ACADEMIC_AFFINITY_REQUIREMENTS_MATRIX.md` and `README.md` were
  checked and already contained no stale wording on either point.

### Rejected

- Changing the affinity-matching algorithm or the authorization model —
  the professor's confirmation matches what the code already does
  (exact specialty-ID membership; Administrador granted every Academic
  permission), so no behavior change was needed, only the documentation's
  status.
- Rewriting the predates-all-versions catalog edge case
  (`FINAL_ACADEMIC_MODULE_AUDIT.md` §11/§13, `CatalogVersionResolver`'s
  behavior when the target date precedes every catalog version) — the
  professor has not confirmed this one; left explicitly unresolved and
  the current fallback-to-earliest-version behavior unchanged.
- A broader rewrite of `FINAL_ACADEMIC_MODULE_AUDIT.md` (e.g. its DO-01
  citation gap or DO-02b form-only-validation findings, which the batch
  instructions scoped to a later pass) — out of scope for this
  correction; only the two professor-confirmed items were touched.

### Verification

- `php artisan test`: 122/122 passing (267 assertions), up from the prior
  116/122 — the six prior failures were exactly the locale and
  `spatie/simple-excel` issues above, both environment-only.
- `./vendor/bin/phpstan analyse`: the bare command crashes with a PHP
  memory-limit exhaustion (128M default CLI `memory_limit`) — an
  environment quirk the project's own `composer.json` `types:check`
  script already works around via `--memory-limit=1G`. Re-run with that
  flag: 0 errors, PASS.
- `./vendor/bin/pint --test`: fails on 20 pre-existing baseline files, all
  confirmed (via `git log -1 -- <path>` per file) to trace only to the
  original `c112f53` SIGA-starter-kit-baseline commit — none touched by
  any Academic-batch commit. All files touched by the Academic work pass
  Pint.
- `npm run build`: succeeds, 24 modules transformed.

### Learning

- A bare `./vendor/bin/phpstan analyse` failing with no diagnostics isn't
  automatically "unverifiable" — check whether the project's own Composer
  scripts already invoke it with a non-default flag (here,
  `--memory-limit=1G`) before concluding the tool itself is broken in this
  environment.

## 2026-08-24 — Batch 2: tests and docs for professor-confirmed business rules

### AI consultation

User asked to implement
`Docs/Atina_Implementation_Prompt_Batches/02_BATCH_BUSINESS_RULES_TESTS_DOCS.md`:
a focused business-rule verification pass covering three
professor-confirmed decisions — (1) affinity matching is exact
specialty-catalog membership, no DegreeLevel, no inference of related
specialties; (2) prior-catalog-version fallback (D5) is confirmed,
while the separate predates-all-versions edge case (D6) stays
explicitly unresolved; (3) Administrador has access to everything in
the Academic module. Scope: strengthen tests for all three, update
`Docs/ACADEMIC_AFFINITY_REQUIREMENTS_MATRIX.md`, append this entry,
audit README/matrix/journal for stale ambiguity wording, and run the
full verification suite. No TypeScript, JWT, or external REST API
work; no push.

### Accepted

- **New domain unit test** `AffinityCatalogVersionMatchingTest` (4
  tests) exercising `AffinityCatalogVersion::isAffineToSpecialty()`
  directly: exact-match Atinente, unrelated-specialty non-match, and —
  named after the professor's own example — a related-but-unlisted
  specialty (Cybersecurity vs. a Programming course's catalog) still
  produces No Atinente. Plus one test proving membership is exact-ID,
  not partial/prefix matching.
- **New feature test** `AdministradorFullAccessTest` (2 tests) seeding
  the *real* `PermissionSeeder`/`RoleSeeder` and asserting the actual
  seeded "Administrador" role — not a synthetic single-permission
  stand-in — passes every Academic policy ability: `Teacher::create`,
  `AcademicCredential::create/update`, `AffinityCatalogVersion::create/
  update/exportPdf/exportExcel`, `TeacherAssignment::create/decide/
  exportPdf/exportExcel`, `TechnicalNote::create/approve`. This closes
  a real gap: the existing `TeacherAssignmentAuthorizationTest` only
  tested permission strings in isolation, never the seeded role that
  production actually grants them through.
- **Clarifying docblock** on `CatalogVersionResolverTest` distinguishing
  which existing test (`test_d5_no_exact_match_applies_the_most_recent_
  prior_version_as_provisional` and its sibling) covers the
  professor-confirmed D5 fallback from the one
  (`test_d6_target_date_before_all_versions_applies_the_earliest_as_
  provisional`) that covers the still-unconfirmed D6 edge case — no
  behavior change, both were already tested.
- **Matrix updates**: DO-02's version-resolution row now states D5 is
  professor-confirmed and D6 is not; a new DO-02a row documents the
  specialty-matching rule and cites the new test; a new note under
  Authorization documents "Administrador has access to everything" and
  cites `AdministradorFullAccessTest`.
- **Corrected a stale per-section status** in
  `FINAL_ACADEMIC_MODULE_AUDIT.md`: DO-02a's own `STATUS: NEEDS
  PROFESSOR DECISION` header (its only cited reason was the
  now-resolved specialty-matching question) contradicted that same
  document's own summary table (`DO-02a | CONFIRMED`) and Section 11.
  Updated to `STATUS: CONFIRMED` with a dated note; left every other
  section of that audit untouched.
- **Committed `FINAL_ACADEMIC_MODULE_AUDIT.md` for the first time.** It
  existed only as an untracked working-directory file — the prior
  session's journal entry (2026-08-24, above) said it had been
  "updated," but that commit (`fe5f313`) only touched this journal, not
  the audit file itself. Fixed by including it in this batch's commits
  rather than leaving durable audit content permanently unversioned.

### Rejected

- Refactoring `AffinityCatalogVersion::isAffineToSpecialty()`,
  `ProposeTeacherAssignmentUseCase`, or any Academic policy — all three
  already implement exactly what the professor confirmed (exact
  specialty-ID membership; permission-gated policies with Administrador
  already holding every official Academic permission). Changing working
  code to "prove" an already-satisfied rule would be unjustified churn.
- Semantic/fuzzy/AI-based specialty matching, and matching that also
  considers `DegreeLevel` — both explicitly ruled out by the professor's
  clarification.
- Restricting Administrador from any Coordinadora de Docencia action
  (Technical Note creation, No Catalog decisions) — the professor
  confirmed the opposite: Administrador's broader-than-SRS-text access
  is intentional, not a bug to fix.
- Reinterpreting or "resolving" the predates-all-versions (D6) catalog
  edge case as if the professor had answered it — they have not. Left
  it explicitly unresolved in both the matrix and the audit file,
  wording it as a separate, distinct question from the D5 fallback the
  professor did confirm.
- A broader rewrite of `FINAL_ACADEMIC_MODULE_AUDIT.md` (its DO-01
  citation gap, DO-02b form-only-validation finding, stale test counts
  from its own point-in-time session narrative, etc.) — out of this
  batch's stated scope, which is limited to the three professor-
  confirmed rules; only the one stale status header was corrected.

### Why

Tests needed to trace directly to the professor's own words (the
Cybersecurity/Programming example, "Administrador has access to
everything") so a reviewer can see the confirmation reflected as an
executable assertion, not just prose. The Administrador test in
particular needed to exercise the *seeded role*, not a hand-picked
permission string, because the actual production risk is a future
`RoleSeeder` edit silently dropping a permission from "Administrador" —
a synthetic-permission test would not catch that regression.

### Corrections

- The audit file being untracked was discovered only while verifying
  where the prior session's claimed edits actually landed — `git log
  --all -- FINAL_ACADEMIC_MODULE_AUDIT.md` returned nothing despite the
  journal claiming it was updated. Corrected by committing it now
  rather than silently leaving the discrepancy in place.
- No test or production-code corrections were needed this batch — the
  professor-confirmed rules already matched the implemented behavior in
  full; only test coverage and documentation traceability were gaps.

### Learning

- A decision-journal entry claiming a file was "updated" is not proof
  the file was committed — `git log --all -- <path>` (or `git status`
  for stray untracked files) is the independent check, and should be
  run before trusting a prior entry's own account of what it did.
- When a batch instruction says "if current code already satisfies
  this, do not refactor, add tests instead," the highest-value test is
  usually the one that exercises the *real* seeded/configured state
  (an actual role from `RoleSeeder`) rather than a synthetic stand-in
  that only proves the policy's `if` statement is syntactically correct.

## 2026-08-24 — Batch 3: real TypeScript integration (data table)

### AI consultation

User asked to implement
`Docs/Atina_Implementation_Prompt_Batches/03_BATCH_TYPESCRIPT.md`: add
real, demonstrable TypeScript to the TALL stack without turning the app
into a SPA and without replacing Livewire/Alpine/Blade/Tailwind, by
migrating one existing production JS behavior (preference order:
data table, Academic search/filter/export, sidebar nav, other). No JWT
or external REST API work; no push.

### Accepted

- **`resources/js/data-table.ts`** replaces `resources/js/data-table.js`
  verbatim in behavior — the reusable `Alpine.data('crudTable', ...)`
  factory backing every client-mode `<x-ui.data-table>` (currently
  Role and Permission management). Chosen because it's priority #1 in
  the batch's own list and the most substantial, genuinely reusable
  client behavior in the codebase (search/sort/pagination/i18n
  summary), not a toy example.
- Added `SortDirection`, `TableRow` (`Record<string, unknown>` — rows
  are opaque plain-JSON per CRUD screen, so a shared concrete interface
  would be fiction), `CrudTableConfig`, `DataTableRefreshEventDetail`,
  and a `CrudTableState` interface describing the full Alpine component
  contract (state, derived getters, actions). `init()` is typed with an
  explicit `this: CrudTableState & AlpineWatcher` parameter so only the
  one method that calls `$watch` needs to know about Alpine's injected
  magic property, instead of threading it through every getter.
- `resources/js/types/alpine.d.ts`: a minimal ambient `declare global`
  for `Alpine.data(...)`, scoped to exactly what this file calls.
  Alpine is not an npm dependency here — Livewire injects it as a
  runtime global — so there is no `@types/alpinejs` to install; writing
  the two-line surface actually used was more proportional than adding
  a real Alpine dependency purely for its bundled types.
- `tsconfig.json`: `strict: true`, `noEmit`, DOM + DOM.Iterable libs,
  `moduleResolution: "Bundler"` (Vite-native), `noUncheckedIndexedAccess`
  for extra safety on row/array access. Scoped to `resources/js/**` only.
- `typescript` added as a devDependency; `"typecheck": "tsc --noEmit"`
  script added to `package.json`.
- `resources/js/app.js` now imports `./data-table.ts` (Vite resolves the
  extension transparently); the stale in-repo comment pointing at the
  old `.js` path (`role-component.blade.php`) was corrected to `.ts`.
- Old `resources/js/data-table.js` deleted — fully superseded, no
  remaining references.
- README gets a short "Frontend stack" section stating TALL +
  TypeScript, naming the migrated file, and the `npm run typecheck` /
  `npm run build` commands.

### Rejected

- Migrating Academic search/filter/export or the sidebar-nav store
  instead: the batch's own priority order puts the data table first,
  and it's the more substantial, more reusable behavior of the two
  remaining unmigrated candidates.
- A generic `AlpineComponent<T>` "magic properties" helper type (with
  `$el`, `$refs`, `$dispatch`, etc.) — this file only ever calls
  `$watch`, so a single narrow `AlpineWatcher` interface used just on
  `init()` was enough; a fuller helper would be speculative for
  behavior that doesn't exist yet.
- Installing `alpinejs` as a real npm dependency just to get its
  bundled types — it isn't part of the actual runtime bundle (Livewire
  supplies Alpine), so adding it would misrepresent the dependency graph
  for a types-only benefit a two-line ambient declaration already gives.

### Why

The goal was TypeScript that is real and load-bearing — type-checked,
compiled by Vite, and actually executed by the browser on an existing
production screen — not an `example.ts` or a console demo. Picking the
already-most-complex client behavior (search + sort + pagination +
Livewire hand-off event contract) gives the strongest evidence that the
migration is genuine, since a trivial file could pass typecheck without
proving anything about real integration.

### Verification

- `npm run typecheck` — passes, zero errors.
- `npm run build` — succeeds; `public/build/assets/app-*.js` contains
  the compiled `crudTable` factory.
- `php artisan test` — 128/128 passing (no PHP files changed; this
  confirms the Blade/Livewire integration point was left intact).
- `./vendor/bin/phpstan analyse` — 0 errors.
- `./vendor/bin/pint --test` — fails, but only on pre-existing PHP
  files this batch never touched (`HasRolesAndPermissions.php`,
  `DDDStructure.php`, `RoleComponent.php`, etc.) — confirmed via
  `git status` that none of them are part of this diff. Pre-existing
  condition, out of this batch's scope.
- Browser/E2E verification was attempted but not completed: the Claude
  Chrome extension reported not connected, and a raw `curl`-based login
  wasn't feasible because the login form is a Livewire component (CSRF
  token lives in Livewire's snapshot/checksum protocol, not a plain
  hidden input) — scripting that walk was disproportionate to what it
  would verify beyond what typecheck/build/tests already cover. Manual
  browser verification of the Roles screen is still recommended before
  considering this fully closed.

### Learning

- On this project, Alpine.js has no corresponding npm package at all
  (Livewire injects it at runtime) — any future `.ts` file that touches
  `Alpine.*` needs its own ambient `declare global` addition (extend
  `resources/js/types/alpine.d.ts` rather than duplicating a new one).
- TypeScript's `this: T` parameter annotation is the proportional way
  to type an Alpine `x-data` factory's magic properties (`$watch`,
  `$refs`, ...) without forcing every unrelated getter/method in the
  same object literal to also declare a wider `this` type it never uses.

---

## 2026-08-25 — RBAC hardening: controlled Module/Action selection for the Permission editor

### AI consultation

The Permission create/edit modal let an admin type `module` and `action`
as free text. Nothing stopped a typo (`atinencias.verificar` instead of
`atinencia.verificar`) from becoming a real, unique-constrained database
row that no Policy or Seeder ever checks — an orphaned, silently
useless permission indistinguishable in the UI from a real one. The
task was to close that gap without introducing JWT/external-API work
or touching the Academic module.

### Accepted

- **`PermissionCatalog`** (`src/IdentityAccess/Permission/Domain/ValueObjects/PermissionCatalog.php`),
  a pure-PHP, framework-free value object holding the single source of
  truth for which `(module, action)` pairs are official: the 2
  SIGA-own manageable modules (`roles`, `permissions`) crossed with the
  7 shared CRUD actions, merged with the 16 institutional permissions
  already hardcoded in `PermissionSeeder` (`atestados.gestionar`,
  `atinencia.verificar`, `oferta.gestionar`/`consultar`/`consolidar`,
  etc.). `PermissionSeeder` now iterates `PermissionCatalog::all()`
  instead of keeping its own `MODULES`/`ACTIONS` arrays — one place
  defines the vocabulary, not four.
- **Domain-level enforcement, not just UI.** `Permission::create()`
  now throws `InvalidPermissionException` for any `(module, action)`
  outside the catalog — the backstop below Presentation validation, so
  it holds even if a future caller skips the Livewire form entirely.
  `Permission::redefine()` (used only by `UpdatePermissionUseCase`) now
  throws `PermissionIsProtectedException` for any attempt to change an
  already-persisted permission's module/action; saving the unchanged
  pair is a harmless no-op. `Permission::reconstitute()` deliberately
  skips catalog validation — a pre-existing/legacy row outside the
  catalog must still load to be viewed/reported/deleted, never crash
  the list.
- **Controlled Módulo/Acción `<select>`s.** The Livewire modal
  (`permission-component.blade.php`) replaces both free-text `<input>`s
  with `<select>`s sourced from `PermissionCatalog::modules()` /
  `::actionsFor($form->module)`. `PermissionForm::updatedModule()`
  (Livewire's Form-object hook, resolved from `form.module`'s dotted
  path) clears `action` when it's no longer valid for the newly
  selected module. A read-only "Nombre" preview shows the derived
  `module.action` string live — never a field the user types into.
- **Server-side validation mirrors the catalog, but only on create.**
  `PermissionForm::rules()` adds `Rule::in(PermissionCatalog::modules())`
  / `Rule::in(PermissionCatalog::actionsFor($this->module))`, gated to
  `$component->editingId === null`. Editing skips the catalog
  `Rule::in` deliberately — module/action are read-only in the UI
  (`@disabled($editingId !== null)`, same pattern already used by
  `AffinityCatalogComponent`'s course select) and protected at the
  Domain level regardless; re-validating catalog membership on an
  unchanged value would wrongly block re-saving a legacy row that
  happens to sit outside the catalog.
- **`PermissionComponent::save()`** now wraps both branches in a
  try/catch for `InvalidPermissionException`/`PermissionIsProtectedException`,
  mirroring `RoleComponent`'s existing `RoleIsProtectedException`
  handling — a forged Livewire request gets a Spanish toast, not a 500.
- **`PermissionLabelFormatter`** gained `moduleLabel()`/`actionLabel()`
  (extracted from `forHumans()`) plus entries for all 14 catalog
  modules/15 catalog actions, so the new selects and the pre-existing
  Role-modal permission checklist share one label map instead of two.
  `forHumans()`'s exact output ("Exportar PDF de roles", "Editar
  permisos", ...) is preserved byte-for-byte via a small preposition
  rule, since nothing about display wording needed to change.
- Read-only inspection of the real `gestion_academica_utn` database
  (see Verification) confirmed all 30 existing permission rows already
  match the catalog exactly — no legacy/unrecognized rows to report or
  migrate.

### Rejected

- **Arbitrary free-text module/action** — the entire point of this
  pass; removed from the UI and rejected server-side.
- **Frontend-only validation** — `<select>` options alone don't stop a
  forged Livewire request; `Rule::in()` plus the Domain-level guards
  are what actually enforce it.
- **Silent typo normalization** (e.g. auto-slugging or fuzzy-matching
  a mistyped module to the nearest catalog entry) — the brief is
  explicit that arbitrary text should be rejected, not guessed at.
- **Unrestricted renaming of an existing permission** — `redefine()`
  now refuses any module/action change on a persisted row, full stop,
  rather than trying to distinguish "official" from "custom" rows
  (there's no meaningful distinction left once creation is
  catalog-gated: every row that can exist is, by construction, a
  catalog entry).
- **A separate "display label" column** — not needed; `description`
  already exists in the schema/model but was already out of scope for
  the Domain entity/Form before this change, and the task didn't ask
  for it.

### Why

Permission `module.action` strings are authorization contracts —
`RoleSeeder::OFFICIAL_ROLE_PERMISSIONS` and every Policy method
reference them by exact string. An editable free-text pair made it
possible to create a row that looks like a real permission but that no
code path checks, or to break a real one by "fixing a typo" that
happened to be intentional. Constraining both fields to the same
closed vocabulary the Seeder and Policies already use — enforced at
the Domain layer, not just the browser — makes an invalid or orphaned
permission structurally impossible to create through the UI.

### Corrections

None — this was new hardening work on an existing, un-tested editor
(no prior Role/Permission test coverage existed in the repo before
this batch).

### Learning

- Livewire's `SupportFormObjects::update()` resolves an `updated{Studly}`
  hook on the Form object itself for a `form.module`-style nested
  property (studly-cased from the path after the first dot) — no
  wiring needed on the owning component, unlike the top-level-property
  `updated{Property}` hooks used elsewhere in this codebase
  (`AffinityCatalogComponent::updatedSelectedCourseId()`).
- Authorization configuration must be constrained by the same
  canonical vocabulary the Policies and Seeders already use — a closed
  enum/catalog is the right shape once "what counts as valid" stops
  being open-ended, per `Docs/Guia-CRUD-SIGA-UTN.md`'s documented (if
  rarely used) `Domain/ValueObjects/` folder.

### Verification

- New tests: `tests/Unit/IdentityAccess/PermissionCatalogTest.php` (7),
  `tests/Unit/IdentityAccess/PermissionEntityTest.php` (7),
  `tests/Feature/IdentityAccess/PermissionManagementTest.php` (10) —
  valid create, derived name, unofficial module rejected, action not
  belonging to module rejected, duplicate rejected, dependent-select
  reset/preserve behavior, edit-mode render, forged-rename rejection
  via direct Livewire component-state manipulation (bypassing the
  disabled `<select>`s), and a seeding-regression check (30 rows,
  Administrador's institutional permissions unchanged).
- `php artisan test` — 152/152 passing (up from 128; all pre-existing
  tests still pass unmodified).
- `./vendor/bin/phpstan analyse --memory-limit=1G` — 0 errors.
- `./vendor/bin/pint --test` — fails, but only on files this batch
  never touched, plus 3 residual fixers on `PermissionComponent.php`
  (`concat_space`, `unary_operator_spaces`, `not_operator_with_successor_space`)
  that also appear identically on the untouched `RoleComponent.php` —
  confirmed pre-existing, repo-wide Pint/config drift, not introduced
  here (the one fixer this diff did trigger, `no_unused_imports` on an
  unused `PermissionCatalog` import, was fixed).
- `npm run typecheck` — 0 errors (no `.ts` files touched).
- `npm run build` — succeeds.
- Read-only query against the real `gestion_academica_utn` MySQL
  database: all 30 existing `permissions` rows match
  `PermissionCatalog` exactly — 0 legacy/unrecognized rows.
- Browser/E2E verification was attempted but not completed: the Claude
  Chrome extension reported not connected (same limitation recorded in
  the 2026-08-24 TypeScript-migration entry). `php artisan serve` +
  `curl` confirmed the app boots, `/` returns 200, and `/permissions`
  correctly redirects an unauthenticated guest — but this app's login
  form is a Livewire component on the home route (`Fortify::loginView`
  redirects `/login` to `route('home')`), so a full curl-scripted login
  walk was not attempted, same reasoning as the prior entry. Manual
  browser verification of the Permission screen (Módulo/Acción selects,
  dependent reset, read-only edit mode) is still recommended before
  considering this fully closed.

---

## 2026-08-25 — Fix: topbar profile role read from a non-existent scalar property

### AI consultation

The authenticated Superadmin (`prueba@gmail.com`, seeded by `DatabaseSeeder`)
saw "Coordinadora Académica" in the profile dropdown instead of their real
role. The task was a small, isolated bug fix: find the root cause and make
the topbar show the user's actual persisted role via the existing
`roles()` many-to-many relationship, without JWT/external-API work,
without a new `role` column, and without re-enabling Module/Action editing
in the Permission editor (that protection, from the prior 2026-08-25 entry,
was to stay untouched).

### Root cause

`resources/views/components/siga/topbar.blade.php` read
`auth()->user()->role ?? __('Academic Coordinator')`. `User` has no `role`
scalar property — roles live in `HasRolesAndPermissions::roles()`
(`belongsToMany(Role::class, 'role_user')`) — so the expression always
evaluated to `null`, and every user, regardless of their real role, fell
through to the hardcoded `__('Academic Coordinator')` label.

### Accepted

- **`User::roleLabel(): string`** (`app/Models/User.php`), alongside the
  existing `initials()`/`avatarUrl()` display helpers: reads
  `$this->roles->pluck('name')`, joins them with `, ` when non-empty, and
  falls back to `__('No role assigned')` (`"Sin rol asignado"`) when the
  user has no assigned role. Role names are already the real, seeded
  Spanish strings (`Superadmin`, `Administrador`, `Coordinadora de
  Docencia`, ...) — no translation layer needed, they're persisted data,
  not UI copy.
- **No priority/ordering column exists on `roles`** (verified: the
  migration only has `id`, `name`, `description`, `timestamps`). Since the
  project doesn't already define a role-priority order, and every seeded
  demo user (`DatabaseSeeder`) has exactly one role, a concise joined list
  was chosen over inventing a priority scheme with no real requirement
  behind it — the simplest strategy that satisfies "if the project
  already defines priority, use it; otherwise display role names in a
  concise form."
- Topbar now calls `auth()->user()->roleLabel()`.
- Removed the now-dead `"Academic Coordinator": "Coordinadora Académica"`
  entry from `lang/es.json` (its only use was the fallback just removed)
  and added `"No role assigned": "Sin rol asignado"`.

### Rejected

- **A new `role` column on `users`** — explicitly out of scope; would
  duplicate the RBAC model the `roles()` relationship already provides.
- **Hardcoding role labels by email** — the brief explicitly forbids this;
  `roleLabel()` reads only from the persisted relationship.
- **A fabricated role-priority ranking** (e.g. treating `Superadmin` >
  `Administrador` > ... by an arbitrary array) — nothing in the schema or
  seeders defines this today, and inventing one would be a guess dressed
  up as a rule. Left as a joined list; revisit only if the project
  actually introduces multi-role users with a defined precedence.
- **Adding a priority column to `roles` ourselves** — no requirement asked
  for it, and it would be a schema change far outside a "small bug fix."

### Learning

- The topbar's own comment block already documents a similar past bug
  class (client-side Alpine `sections[currentSection]` map going stale
  across `wire:navigate`) — this is a second instance of the same root
  shape: a piece of UI state that looks server-rendered but was actually
  silently wrong for every user, masked by a plausible-looking fallback
  string instead of an error.

### Verification

- New test: `tests/Feature/IdentityAccess/TopbarRoleDisplayTest.php` (5) —
  Superadmin shows "Superadmin", Administrador shows "Administrador",
  Coordinadora de Docencia shows its real persisted name, an unassigned
  user shows `__('No role assigned')` and never "Academic Coordinator" /
  "Coordinadora Académica", and a static assertion that the blade file no
  longer contains the old `->role ??` fallback pattern.
- `php artisan test` — 157/157 passing (up from 152).
- `./vendor/bin/phpstan analyse --memory-limit=1G` — 0 errors.
- `./vendor/bin/pint --test` — fails only on files this change never
  touched (pre-existing, repo-wide drift, same as the prior entry);
  `app/Models/User.php` itself was run through `pint` and passes clean.
- `npm run typecheck` — 0 errors. `npm run build` — succeeds.
- Browser verification: the Claude Chrome extension was not connected
  (same recurring limitation as the two prior entries), so real-browser
  E2E was not possible. As a substitute, scripted an HTTP session in
  PowerShell — fetched the CSRF token from `/login`, posted Fortify
  credentials for `prueba@gmail.com` / `12345678`, then requested
  `/dashboard` — and confirmed the rendered HTML's
  `<div class="profile-role">` contains exactly "Superadmin". Also
  confirmed `/permissions` still returns 200 for that session, and that
  `permission-component.blade.php`'s Módulo/Acción `<select>`s are
  untouched (`@disabled($editingId !== null)` still present) — the
  protection from the prior entry was not affected by this change. A real
  browser check of the profile dropdown is still recommended before
  considering this fully closed.

---

## 2026-08-25 — RBAC Permission Catalog: missing-only creation + protect official permissions from deletion

### AI consultation

The prior two entries closed the Permission Catalog hardening (closed
vocabulary, protected rename) and the topbar role-display fix, but the
Create Permission modal still let a user select a (module, action)
combination that already existed — submission just failed with a generic,
misleading `unique` message (`"El valor del campo action ya ha sido
registrado."`, which reads as if the bare action name were duplicated).
Separately, nothing stopped deleting one of the 30 official permissions
through the UI, even though those names are referenced by Policies and
`RoleSeeder` — a temporary authorization break until manually recreated.
Asked to close both gaps: missing-only creation (hide/disable Create when
the catalog is complete; offer only unregistered module/action
combinations otherwise) and protect official permissions from deletion,
without touching JWT/external-API work or the Academic module.

### Accepted

- **`PermissionCatalogStatus`** (new Domain value object,
  `Domain/ValueObjects/`) — pure computation of "what's missing" from an
  already-fetched list of registered (module, action) pairs against
  `PermissionCatalog::all()`. Exposes `isComplete()`, `registeredCount()`,
  `totalOfficialCount()`, `availableModules()`, `availableActionsFor()`.
  Kept as a second, separate value object rather than adding this
  responsibility onto `PermissionCatalog` itself — `PermissionCatalog` is
  the static, hardcoded vocabulary; `PermissionCatalogStatus` is a
  request-scoped read model over live registration state. Conflating them
  would make the pure, dependency-free catalog carry a runtime concern.
- **`GetPermissionCatalogStatusUseCase`** (new Application use case) —
  the one place that fetches `PermissionRepositoryInterface::all()` and
  hands it to `PermissionCatalogStatus::fromRegistered()`. Follows the
  existing one-use-case-per-operation convention in this bounded context.
- **`PermissionComponent::catalogStatus()`** resolves the use case via
  `app()` and is deliberately **not cached** on the component instance.
  Caught this during implementation: `save()`/`delete()` call
  `catalogStatus()` once during `$this->form->validate()` (pre-mutation)
  and once again during the final `render()` of that same request — a
  cached value would silently serve pre-mutation counts back in the
  response for the very request that just changed them. The catalog is a
  ~30-row table, so recomputing per call is negligible; correctness won over
  micro-optimizing an uncached query.
- **Create button**: hidden (not disabled) via
  `data-table`'s existing `can-create` prop —
  `Auth::user()->can('create', ...) && ! $catalogStatus->isComplete()`.
  `openCreateModal()` also re-checks `isComplete()` server-side (dispatches
  a danger toast and refuses to open) as defense-in-depth against a forged
  `wire:click`.
- **Status line**: a small muted `":registered de :total permisos
  oficiales registrados"` paragraph above the table (not inside the
  shared `<x-ui.data-table>` slot — that slot is a CSS-grid row template;
  a stray `<p>` inside it would have broken the table-inner grid layout).
- **Missing-only Module/Action selects**: `PermissionForm::rules()` now
  restricts `Rule::in()` to `$catalogStatus->availableModules()` /
  `availableActionsFor($module)` while creating (unchanged, full-catalog
  behavior preserved for the edit path, where module/action stay
  read-only regardless). `updatedModule()` clears a now-invalid `action`
  using the same missing-only set.
- **Rule order inside `action`'s rule array matters** — verified
  empirically via `tinker` before assuming Laravel's validator: placing
  `Rule::unique()` **before** `Rule::in()` was required, because when
  `Rule::in()` (an object-based rule) fails first, Laravel's `Validator`
  does not record `Unique`'s failure at all in `$validator->failed()`
  (confirmed by direct experiment: swapping the two rules' order changed
  which one(s) appeared in `failed()`). Reordering means a genuine
  duplicate always surfaces the clearer, permission-level message first,
  while the missing-only restriction still independently rejects the
  submission either way.
- **Improved duplicate message**: `PermissionForm::messages()` overrides
  `action.unique` with `"El permiso :module.:action ya está registrado."`
  — reachable directly (edit-mode forgery, where `Rule::in()` isn't
  applied) and indirectly (create-mode, now that it's ordered first).
- **`Permission::isProtected()`** (Domain entity) — `PermissionCatalog::
  isOfficial($this->module, $this->action)`, mirroring `Role::
  isProtected()`'s exact shape and reasoning. A pre-existing/legacy row
  outside the catalog is not protected — preserves the same "don't invent
  custom-permission functionality that doesn't exist" stance as the prior
  entries.
- **`DeletePermissionUseCase`** now throws a new
  `PermissionIsProtectedException::forDeletion()` when the found
  permission `isProtected()`, mirroring `DeleteRoleUseCase`'s existing
  guard for `Role::isProtected()` exactly — same exception class, an
  added factory method (distinct wording from the existing rename-guard
  message). `PermissionComponent::delete()` catches it the same way
  `save()` already catches `InvalidPermissionException`/
  `PermissionIsProtectedException` — dispatches a danger toast, does not
  rethrow — matching `RoleComponent::delete()`'s established convention
  for the identical scenario.
- **UI hiding**: `toRow()` now exposes `'protected' => $permission-
  >isProtected()`; the Blade view passes `delete-visible="!row.protected"`
  (client mode, matching `role-component.blade.php`'s exact precedent)
  and `&& ! $permission->isProtected()` on server mode's `can-delete`.
  Domain guard (use case) + UI hiding, not UI-only, per the brief.
- **Edit/detail behavior left untouched** — Module/Action/Name were
  already always read-only in edit mode for *every* existing permission
  (official or not, since the prior "protected identity" entry), so there
  was no separate "official vs. custom edit" distinction to add; changing
  the edit action's icon/semantics for a purely cosmetic reason was
  judged not worth the extra surface for this pass.
- Local dev DB note: while empirically verifying the rule-order finding
  above with `tinker` against the real `gestion_academica_utn` database,
  a debug `firstOrCreate`+`delete()` round-trip accidentally deleted the
  already-seeded `roles.edit` permission row. Caught immediately via a
  fresh official/persisted/missing/unexpected count check (30/30/0/0
  expected, got 29 persisted), restored via `firstOrCreate` (same name,
  module, action), and re-verified 30/30/0/0. No role had `roles.edit`
  attached in this database, so no pivot data was lost — but the restored
  row's primary key differs from the original (harmless, since nothing in
  this codebase references permissions by id, only by name).

### Rejected

- **Disabling instead of hiding the Create button** — hiding is cleaner
  per the brief's explicit preference and the catalog-complete state
  genuinely offers nothing to do.
- **A visually dominant completeness banner** — kept it a small muted
  line consistent with the existing design system, per the brief.
- **Caching `catalogStatus()` per-request** — see Accepted above; the
  staleness risk it introduced outweighed the negligible query savings.
- **A badge column ("Custom"/"System") for permissions**, mirroring
  `RoleComponent`'s Type column — not requested, and every currently
  visible permission is already official (there is no custom-permission
  authoring path), so the column would always read "System" and add
  nothing. `Role`'s equivalent column earns its place because custom
  roles genuinely exist; permissions don't have that today.
- **Redesigning the Edit action's icon/semantics** for official
  permissions — the brief allowed this as one option but called it
  optional; left untouched as the smallest-change choice (see Accepted).

### Learning

- When a Livewire component method is called both as a validation
  precondition *and* again during that same request's final `render()`,
  memoizing it on the instance is a correctness bug waiting to surface —
  the two calls straddle a mutation, not just consecutive reads. Verified
  this by tracing the actual `save()`/`delete()` call order rather than
  trusting the "cache read-heavy things" instinct by default.
- Laravel's `Validator::failed()` does not always list every rule that
  failed for an attribute — the order rules are declared in the array can
  determine which failures get recorded. Confirmed by direct experiment
  (`tinker`, not just reading Laravel source) before writing the fix or
  the test that depends on it.

### Verification

- New test: `tests/Unit/IdentityAccess/PermissionCatalogStatusTest.php`
  (3) — complete catalog (`isComplete()` true, counts 30/30, empty
  `availableModules()`), one missing combination (`isComplete()` false,
  29/30, only the affected module offered, only its missing action
  offered, a fully-registered module never offered), unknown/null module
  returns an empty action list.
- Extended `tests/Feature/IdentityAccess/PermissionManagementTest.php`
  (+7): Create unavailable + forged `openCreateModal()` refused when the
  catalog is complete (30/30); Create available with missing-only
  module/action filtering when one permission (`roles.export_excel`) is
  deleted, and completeness is restored (and Create hides again) after
  creating it; a forged create request for an already-registered
  combination (`roles.edit`) is rejected; a duplicate forged during edit
  (module/action changed to collide with another existing permission)
  shows the exact improved message and leaves the original row untouched;
  an official permission (`atinencia.verificar`) cannot be deleted via the
  component and stays in the database; deleting a protected permission
  does not detach it from a role that has it assigned
  (`Administrador`/`atinencia.verificar`); a legacy permission outside the
  catalog can still be deleted normally (preserves the pre-existing
  legacy-row support).
- `php artisan test` — 167/167 passing (up from 157).
- `./vendor/bin/phpstan analyse --memory-limit=1G` — 0 errors (one
  `missingType.iterableValue` on `PermissionForm::messages()`'s return
  type was fixed with a `@return array<string, string>` docblock).
- `./vendor/bin/pint --test` — fails only on files this change never
  touched; confirmed by temporarily swapping `PermissionComponent.php`
  back to its pre-change (`HEAD`) content and re-running Pint on just that
  file — the same 3 fixers (`concat_space`, `unary_operator_spaces`,
  `not_operator_with_successor_space`) failed identically, confirming
  they predate this change (consistent with the identical finding on this
  same file in the prior Permission-catalog entry).
- `npm run typecheck` — 0 errors. `npm run build` — succeeds.
- Read-only query against the real `gestion_academica_utn` MySQL
  database (after the accidental-delete/restore noted above): 30
  official, 30 persisted, 0 missing, 0 unexpected.
- Browser verification: the Claude Chrome extension was not connected
  (same recurring limitation as the prior two entries). As a substitute,
  scripted an authenticated HTTP session in PowerShell (`prueba@gmail.com`
  / `12345678`, a Superadmin confirmed via `tinker` to hold
  `permissions.create`) and fetched `/permissions`. Confirmed in the
  rendered HTML: the status line reads exactly "30 de 30 permisos
  oficiales registrados"; the `openCreateModal()` Alpine call is entirely
  absent from the markup (Create is hidden, not merely disabled, and not
  hidden for lack of authorization); the client-mode delete icon carries
  `x-show="!row.protected"`; no exception/error markers in the response;
  search input, sortable headers, and pagination controls render
  unchanged (their Alpine logic was not touched by this change). Genuine
  browser interaction (clicking Create, watching the modal's live
  missing-only selects, checking the console) is still recommended before
  considering this fully closed.

---

## 2026-08-25 — Batch 4: real JWT API authentication boundary

### AI consultation

User asked to implement
`Docs/Atina_Implementation_Prompt_Batches/04_BATCH_JWT.md`: add a
real JWT-authenticated API boundary (`routes/api.php`) alongside the
existing Fortify/session web auth, without replacing it, without
selecting an external REST API provider, and without pushing.

### Pre-flight findings

- No `routes/api.php`, no `config/auth.php` `api` guard, no JWT
  dependency in `composer.json` before this batch — the API boundary
  did not exist at all.
- RBAC is a dependency-free, Spatie-inspired trait
  (`App\Concerns\HasRolesAndPermissions`) on `App\Models\User`
  (`roles`/`permissions` many-to-many, `hasPermissionTo()` checking
  both direct and role-inherited grants) — no package to reuse or
  duplicate.
- `bootstrap/app.php` already sets
  `shouldRenderJsonWhen(fn ($r) => $r->is('api/*') || $r->expectsJson())`,
  so JSON error rendering for `/api/*` was already correct before this
  batch touched anything.
- `src/IdentityAccess/{Permission,Role}` is the existing hexagonal-DDD
  convention (`Domain/Application/Infrastructure/Presentation`), with
  `Presentation/Routes/web.php` auto-loaded by
  `DomainServiceProvider::loadContextRoutes()` via a glob. That glob
  only covers `web.php`, and the batch explicitly asked for a
  conventional `routes/api.php`, so the new context's routes are wired
  through the standard Laravel `routes/api.php` entry point instead of
  extending the glob — the controller/middleware classes still live
  under the DDD structure.

### Accepted

- **`firebase/php-jwt` v7.1.0** (composer, no vulnerability advisories)
  as the signing library. Chosen over a Laravel-specific package
  (`tymon/jwt-auth` and forks) because it is a minimal encode/decode
  primitive with no opinion about guards, config surface, or app
  bootstrapping — the batch explicitly asked to keep JWT-library
  classes out of Domain behind a `TokenServiceInterface`, which fits a
  primitive library far better than adopting a second, competing auth
  framework on top of Fortify.
- New bounded context `Src\IdentityAccess\Authentication`:
  - `Domain\Contracts\TokenServiceInterface` — `issue(Authenticatable): IssuedToken`,
    `resolveSubject(string): int`. No Eloquent, no JWT types.
  - `Domain\ValueObjects\IssuedToken`, `Domain\Exceptions\{TokenException,
    InvalidTokenException, ExpiredTokenException, InvalidCredentialsException}`.
  - `Infrastructure\Services\JwtTokenService` — the only class in the
    context allowed to import `Firebase\JWT\*`; maps every decode
    failure to `InvalidTokenException` except `ExpiredException`,
    which maps to `ExpiredTokenException`.
  - `Application\UseCases\AuthenticateUserUseCase` — looks up
    `App\Models\User` directly (not a Domain entity: there is no
    business logic here beyond "check the password hash, issue a
    token," matching `AI_HARNESS.md`'s "don't force DDD layers onto
    entities with no real domain logic"; the existing `Role`/`Permission`
    Domain entities are intentionally not reused for the same reason
    Fortify itself doesn't route through them).
  - `Presentation\Http\{Controllers\AuthController, Middleware\AuthenticateJwt,
    Requests\LoginRequest}`.
- `routes/api.php` (new): `POST /api/auth/login` (no middleware —
  Laravel's default `api` middleware group carries no session/CSRF),
  `GET /api/me` behind the new `jwt.auth` alias.
- `bootstrap/app.php`: `withRouting(api: ...)` added; `jwt.auth` alias
  registered for `AuthenticateJwt`.
- `AuthenticateJwt` middleware returns the *same* generic
  `401 {"message":"Unauthenticated."}` for every rejection reason
  (missing header, malformed token, bad signature, expired) — this was
  a deliberate reading of "consistent JSON without leaking
  cryptographic details": distinguishing the reasons in the response
  would itself be the leak.
- `DomainServiceProvider::register()` binds `TokenServiceInterface` as
  a singleton built from `config('jwt.*')`, alongside the existing
  interface→implementation array (which can't express constructor
  args, so this one binding is a small explicit exception to that
  loop).
- New `config/jwt.php` (`secret`, `ttl` minutes, `issuer`); `.env.example`
  documents `JWT_SECRET`/`JWT_TTL`/`JWT_ISSUER` with key-generation
  instructions in a comment; local `.env` (gitignored) got a freshly
  generated secret so the app actually runs.
- `phpunit.xml` got its own `JWT_SECRET`/`JWT_TTL`/`JWT_ISSUER` test
  values, separate from the local `.env` secret.
- Added `App\Concerns\HasRolesAndPermissions::allPermissionNames()`
  (direct + role-inherited permission names, deduplicated) — this
  aggregate didn't exist before (only per-permission `hasPermissionTo`
  checks did) and is needed by `/api/me`; reusing it there means the
  API's authorization data is read off the exact same relations the
  web UI already uses, not a parallel computation.
- `/api/me` returns `id, name, email, roles, permissions` and requires
  `$request->user()` (set via `setUserResolver()` in the middleware) —
  no custom Guard class or `Auth::viaRequest()` closure was introduced;
  the middleware resolves the user itself and hands it to the request,
  which is enough for a stateless JSON endpoint and avoids fighting
  Laravel's guard abstraction for a single route.

### Rejected

- `tymon/jwt-auth` / `php-open-source-saver/jwt-auth` — full guard-based
  JWT auth frameworks; would have meant configuring a second competing
  auth stack next to Fortify for a feature this batch needs only two
  routes from.
- A custom `Auth::viaRequest()` guard or a full `Illuminate\Contracts\Auth\Guard`
  implementation — the explicit middleware class the batch asked for
  already satisfies every required behavior (401 on missing/malformed/
  bad-signature/expired, `$request->user()` populated on success)
  without adding the indirection of a second authentication mechanism
  Laravel has to know about.
- Reusing the `Src\IdentityAccess\Role`/`Permission` Domain entities
  inside `AuthenticateUserUseCase` or `AuthController::me()` — those
  entities exist to enforce the Permission catalog's business rules on
  *writes* (create/edit/delete permissions and roles); reading a user's
  roles/permissions for a JSON response is a plain relation read, and
  routing it through the Domain entities would add a translation layer
  with no behavior to justify it.
- Rate-limiting `/api/auth/login` — not requested by the batch and out
  of scope; flagged below as a visible follow-up instead of added
  speculatively.

### Why

The batch's own framing — "prefer a minimal maintained implementation,"
"keep JWT out of Domain," "do not introduce an oversized auth
framework" — pointed at composing a small `TokenServiceInterface`
around a primitive encode/decode library rather than adopting a second
Laravel auth package. This keeps Fortify as the only thing that owns
"authentication" as a concept for the web app, with the JWT boundary
existing purely as an alternate credential-to-identity path for JSON
clients that terminates at the same `User` row and the same RBAC
relations.

### Follow-up / visible risk

- `/api/auth/login` has no rate limiting (`throttle:api` is not
  registered — Laravel 13's `api` middleware group ships with no
  default limiter, matching the framework's out-of-the-box behavior,
  and Fortify's own `login` limiter only applies to `routes/web.php`).
  A brute-force-resistant login endpoint would need a `RateLimiter::for('api-login', ...)`
  registration plus a `throttle:api-login` on the route — left out
  because it wasn't in the batch's scope, not because it's unimportant.
- `External REST API → pending professor selection.` This batch
  deliberately does not choose or implement one — see the batch file's
  explicit instruction and `Docs/ACADEMIC_AFFINITY_REQUIREMENTS_MATRIX.md`'s
  existing "NOT_APPLICABLE" row for the same item.

### Verification

- New tests: `tests/Feature/Api/JwtAuthenticationTest.php` (10) — valid
  credentials return a token; wrong password and unknown email both
  401; `/api/me` 401 with no token, a malformed token, a token signed
  with a different secret, and an expired token (both forged directly
  with `Firebase\JWT\JWT::encode()` against the app's real
  `config('jwt.secret')` where relevant); a valid token reaches
  `/api/me` and returns the right id/name/email; roles and
  role-inherited permissions round-trip through the real `Docente` and
  `Administrador` seeded roles (via `PermissionSeeder`/`RoleSeeder`).
- `php artisan test` — 177/177 passing (up from 167; includes the
  pre-existing `tests/Feature/Auth/AuthenticationTest.php` unchanged
  and still green, confirming Fortify/session login was not disturbed).
- `./vendor/bin/phpstan analyse --memory-limit=1G` — 0 errors.
- `./vendor/bin/pint --test` — the batch's own files (`HasRolesAndPermissions.php`,
  `tests/Feature/Api/JwtAuthenticationTest.php`, and every new file
  under `src/IdentityAccess/Authentication/`) pass cleanly (two were
  auto-fixed with `./vendor/bin/pint <path>` scoped to only those
  files). Remaining repo-wide `pint --test` failures are on files this
  batch never touched (`DDDStructure.php`, `Logout.php`,
  `FortifyServiceProvider.php`, `RoleComponent.php`, etc.) — pre-existing,
  confirmed via `git status`.
- `npm run typecheck` — 0 errors. `npm run build` — succeeds (no
  frontend files changed by this batch).
- Manual HTTP verification against the real running app
  (`php artisan serve`, real MySQL `gestion_academica_utn`): created a
  throwaway user (`batch4-jwt-check@example.test`), confirmed
  `POST /api/auth/login` returns 401 on a wrong password and a real
  Bearer token + `expires_in: 3600` on the right one; confirmed
  `GET /api/me` returns 401 with no `Authorization` header and 401
  with `Authorization: Bearer not-a-jwt`, then 200 with the correct
  id/name/email using the real token. Deleted the throwaway user
  immediately after (confirmed 0 rows remaining) — no permanent change
  to the real database.

### Learning

- A trait method with a hard `Illuminate\Database\Eloquent\Collection`
  return type crashed at runtime (`TypeError`) the first time it was
  actually exercised through a real HTTP request, even though PHPStan
  and the file's own logic looked correct: `Collection::pluck()` on an
  Eloquent collection returns a base `Illuminate\Support\Collection`,
  not another Eloquent collection, so a same-named `use` import
  silently pointed the type hint at the wrong class. Caught by the
  `php artisan test` run, not by static analysis — a concrete argument
  for the harness's "independent verification" requirement even on
  code that type-checks.

---

## 2026-08-25 — Batch 5: OpenAlex external REST API integration + final audit

### AI consultation

User asked to implement
`Docs/Atina_Implementation_Prompt_Batches/05_BATCH_OPENALEX_EXTERNAL_API_AND_FINAL_AUDIT.md`:
select and wire in the project's external REST API requirement (left
open since Batch 4's journal entry: "External REST API → pending
professor selection"), then perform a final functional/technical audit
against the official requirements. The professor did not prescribe a
provider, so the batch itself proposed OpenAlex's Institutions API as a
domain-relevant, defensible choice and asked it to be implemented as
enrichment only — never a hard dependency, never a factor in affinity.

### Pre-flight findings

- Batch 4 (JWT) confirmed present and green on `integration/atina-foundation`
  (`5ae4e06`), 4 commits ahead of `origin`; `routes/api.php` carried
  `POST /api/auth/login` and `GET /api/me` behind `jwt.auth` only.
- `Src\Academic\AcademicCredential` already had the exact
  `Domain/Application/Infrastructure/Presentation` shape this batch
  needed to extend: `Domain/Contracts/AcademicCredentialRepositoryInterface`,
  `Application/UseCases/*`, `Presentation/Livewire/Forms/AcademicCredentialForm`
  consumed by `Src\Academic\Teacher\Presentation\Livewire\TeacherProfileComponent`
  (the credential create/edit modal). No existing `institution` lookup
  of any kind.
- `DomainServiceProvider` already had a precedent for a config-built
  singleton binding that can't go through the plain interface→class
  array (`TokenServiceInterface` ← `JwtTokenService`, built from
  `config('jwt.*')`) — the new `InstitutionSearchServiceInterface`
  binding follows the same pattern.
- `tests/TestCase.php` had no global HTTP safety net — nothing prevented
  a test from making a real outbound request. Since the new
  `updatedFormInstitution()` Livewire hook fires on *any* property
  update of `form.institution` (including Livewire's `->set(...)` in
  tests, not only real debounced typing), every pre-existing test that
  did `->set('form.institution', 'UTN')` (3 characters — meets the new
  minimum-query-length) would now trigger a real search unless guarded.

### Accepted

- **OpenAlex Institutions API** (`GET /autocomplete/institutions?q=`),
  no API key required for basic lookup. Chosen for direct domain
  relevance (Teacher → Academic credential → Institution → OpenAlex
  institution lookup) and because it needs no signup/secret to
  demonstrate live in the oral defense.
- New Domain-owned, provider-neutral contract in the `AcademicCredential`
  context: `Domain\Contracts\InstitutionSearchServiceInterface::search(string $query, int $limit): list<InstitutionSearchResult>`,
  `Domain\InstitutionSearchResult` (externalId, name, hint, countryCode,
  rorId — no raw OpenAlex fields), `Domain\Exceptions\InstitutionSearchUnavailableException`.
- `Application\UseCases\SearchAcademicInstitutionsUseCase`: trims/normalizes
  whitespace, enforces `MIN_QUERY_LENGTH = 3` before ever calling the
  port, bounds the limit to `MAX_LIMIT = 20`. No HTTP code, no affinity
  logic.
- `Infrastructure\Services\OpenAlexInstitutionSearchService`: the only
  class in the context allowed to import `Illuminate\Support\Facades\Http`
  or know OpenAlex's response shape. Wraps the whole HTTP call in a
  broad `catch (\Throwable)` (not just `ConnectionException`) — see
  "Corrections" below for why this had to be broad, not narrow. Caches
  successful results per normalized-query+limit via the app's default
  cache store (`Cache::remember`, TTL from `openalex.cache_ttl`,
  default 900s) — a failure is never cached, since `Cache::remember`
  only stores a value if the closure returns normally.
- `config/openalex.php` (`base_url`, `api_key`, `timeout` bounded 1–15s,
  `institution_limit` bounded 1–20, `cache_ttl`); `.env.example` and
  local `.env` both document `OPENALEX_*`.
- `DomainServiceProvider` binds `InstitutionSearchServiceInterface` as a
  singleton built from `config('openalex.*')`, same shape as the
  existing `TokenServiceInterface` binding.
- `TeacherProfileComponent` (not a new nested Livewire component — same
  reasoning the file's own docblock already gives for keeping credential
  management on one component) gained `updatedFormInstitution(string $value)`,
  `selectInstitution(string $name)`, and three public properties
  (`institutionSuggestions`, `institutionSearchUnavailable`,
  `institutionSearchPerformed`). The Institution `<input>` switched from
  `wire:model` (deferred) to `wire:model.live.debounce.400ms`. Selecting
  a suggestion just writes into the existing `form.institution` string —
  no new field, no required selection, manual typing always still works.
- Optional read-only `GET /api/institutions/search?q=` behind `jwt.auth`,
  reusing `SearchAcademicInstitutionsUseCase` directly (no duplicated
  logic) — added because the batch explicitly invited it as a way to
  demonstrate the JWT boundary on a second endpoint. A provider outage
  returns `200 {"results": [], "message": "..."}`, not a 5xx: the API
  boundary keeps the same "enrichment, not a hard dependency" contract
  the UI has.
- `Http::preventStrayRequests()` added globally in `tests/TestCase.php`
  — every test now fails fast (not by making a real network call) if it
  triggers an HTTP request with no matching `Http::fake()`. See
  "Corrections."

### Rejected

- A generic checkbox/quotes/placeholder REST API with no domain
  relevance — the batch itself steered away from this, and it would not
  survive "why this API?" in the oral defense.
- Calling OpenAlex directly from browser JavaScript/TypeScript — every
  request goes through Laravel; the browser never sees `OPENALEX_API_KEY`
  (which stays server-side in `config/openalex.php`, never rendered to
  Blade).
- Requiring a selected suggestion before saving, or persisting the raw
  OpenAlex payload — the existing `atestados.institucion` column remains
  the single source of truth; no new migration/column was added.
- Letting OpenAlex results influence `AffinityCatalogVersion::isAffineToSpecialty()`
  or any DO-02a outcome in any way — the search use case and the
  affinity-matching domain share no code path; verified by grep (no
  cross-import) and by a dedicated test
  (`test_institution_search_does_not_touch_course_affinity_context`).
- A dedicated `InstitutionSearchComponent` nested Livewire component —
  would only add inter-component wiring for a feature that lives
  entirely inside the one existing credential modal.
- Redis / a new cache backend for the suggestion cache — the existing
  default cache store (`database` in production, `array` in tests) is
  enough for a 900-second TTL on a low-traffic autocomplete feature.

### Why

Institution search sits one hop from the existing Institution field and
nowhere near the affinity pipeline, so it was straightforward to keep it
fully outside the trust boundary that decides Atinente/No
Atinente/Nota técnica/Sin catálogo: the port returns plain data, the use
case does no business reasoning, and the adapter converts every failure
mode into one exception type the Presentation layer already knows how to
treat as "keep going, let the user type manually." Hexagonal Architecture
made this a genuinely small change — Domain/Application don't know
OpenAlex exists, so if the provider were swapped out later,
`TeacherProfileComponent` and `SearchAcademicInstitutionsUseCase` would
not change at all.

### Corrections

- **First adapter version caught only `Illuminate\Http\Client\ConnectionException`.**
  Realized while writing tests that `updatedFormInstitution()` fires on
  Livewire's `->set('form.institution', ...)` in tests exactly like a
  real debounced keystroke does — meaning every pre-existing test that
  sets a 3+ character institution (`AcademicCredentialAuditTest`,
  `AcademicCredentialAuthorizationTest`, all using `'UTN'`/`'UCR'`/`'UNA'`)
  would now make the component call the real adapter. Without
  `Http::fake()` in those tests, and with the new `Http::preventStrayRequests()`
  safety net in place, the client throws `Illuminate\Http\Client\StrayRequestException`
  (a `\RuntimeException`, not a `ConnectionException`) — which the
  narrow catch would have let escape uncaught, breaking every one of
  those tests. Fixed by broadening the adapter's catch to `\Throwable`
  around the HTTP call specifically (not around JSON parsing, which is
  handled by explicit `is_array`/key checks instead) — this is also
  simply more correct against the batch's own "handle all of the
  following gracefully" list, which is not limited to connection
  errors.
- **First `collect(...)->filter()->values()->all()` chain failed PHPStan**
  (`level: 7`): Larastan could not narrow the Collection's generic
  return type to `list<InstitutionSearchResult>` through that method
  chain. Fixed by wrapping the whole chain in `array_values(...)`
  instead of relying on `->values()`, which PHPStan recognizes as
  producing a genuine list.

### Follow-up / visible risk

- No rate limiting on `/api/institutions/search` beyond OpenAlex's own
  429 (which the adapter already treats as "unavailable") — matches the
  same out-of-scope note already recorded for `/api/auth/login` in the
  Batch 4 entry.
- `countryCode` is always `null` in the current mapping: OpenAlex's
  `/autocomplete/institutions` payload does not reliably expose a
  country code field (only `hint`, which is a free-text location
  string) — documented as a known gap rather than guessed at with a
  fragile parse of `hint`.
- D6 (target date predates every catalog version) remains unresolved,
  unchanged by this batch — still not to be read as professor-confirmed;
  see the DO-02 row in `Docs/ACADEMIC_AFFINITY_REQUIREMENTS_MATRIX.md`.
- Browser walkthrough could not be performed in this environment — the
  Claude-in-Chrome extension was not connected (see the final report for
  this batch). All UI behavior was instead verified via Livewire
  component tests plus one live `php artisan tinker` call through the
  real use case against the real OpenAlex API (see Verification).

### Verification

- New tests (25 total, all passing): `SearchAcademicInstitutionsUseCaseTest`
  (5, unit, fake port — short/whitespace queries never reach the port,
  normalization, limit bounding), `OpenAlexInstitutionSearchAdapterTest`
  (8, `Http::fake()` — correct endpoint/query, successful mapping with no
  raw-payload leak, empty results are not a failure, connection failure,
  429, 500, malformed JSON, unexpected shape), `AcademicCredentialInstitutionSearchTest`
  (7, Livewire — short query never calls the provider, suggestions
  render, selecting a suggestion populates the field, manual entry
  saves, a provider outage does not block saving, an institution not
  among the suggestions still saves, search does not touch
  `contextCourseId`/affinity context), `InstitutionSearchApiTest` (5 —
  missing/invalid JWT → 401, valid JWT + valid `q` → 200 with
  provider-neutral JSON, valid JWT + `q` under the minimum length → 422,
  provider outage → 200 with an empty result set).
- `php artisan test` — **202/202 passing** (up from 177 after Batch 4),
  including every pre-existing Academic/RBAC/Auth test unchanged and
  still green.
- `./vendor/bin/phpstan analyse --memory-limit=1G` — 0 errors.
- `./vendor/bin/pint --test` — every file this batch touched or created
  is clean. Remaining repo-wide failures are pre-existing baseline drift
  on files this batch never touched (`DDDStructure.php`, `Logout.php`,
  `FortifyServiceProvider.php`, `RoleComponent.php`, `PermissionDTO.php`,
  `RoleDTO.php`, etc. — confirmed via `git status`).
- `npm run typecheck` — 0 errors. `npm run build` — succeeds.
- `php artisan route:list` — `GET|HEAD api/institutions/search` present
  behind `jwt.auth`, alongside the unchanged `api/auth/login`/`api/me`.
- `php artisan migrate:status` — unchanged; no new migration was added,
  confirming no new column was needed.
- Architecture gate: `grep` across `src/Academic/AcademicCredential/{Domain,Application}`
  and `src/Shared` for `^use Illuminate`/`^use Livewire`/`^use Guzzle` —
  zero matches.
- **Live OpenAlex verification (real Internet, not `Http::fake()`):**
  `Invoke-RestMethod` against `https://api.openalex.org/autocomplete/institutions?q=Universidad%20de%20Costa%20Rica`
  returned a real result (`Universidad de Costa Rica`, `San José, Costa
  Rica`, ROR `https://ror.org/02yzgww51`). Then, separately, ran the
  *actual* application code path live via `php artisan tinker`
  (`app(SearchAcademicInstitutionsUseCase::class)->handle(...)`,
  bypassing `Http::fake()` entirely) against the same query — one real
  result returned and correctly mapped into `InstitutionSearchResult`,
  confirming the Use Case → Port → Adapter → real OpenAlex path works
  end to end, not just the adapter in isolation.
- Browser walkthrough: **UNVERIFIED IN THIS ENVIRONMENT** — the
  Claude-in-Chrome extension reported "not connected" when queried; no
  UI screenshot/interaction was captured, so this is reported as not
  done rather than assumed to work from the passing Livewire tests
  alone.

### Learning

External integrations should enrich a workflow, not gate it, unless a
requirement explicitly demands otherwise — the concrete mechanism for
that in this codebase was making the *type system* enforce it: the
Domain contract returns a plain list (never throws for "no matches"),
and the one exception type that does exist
(`InstitutionSearchUnavailableException`) is caught exactly once, at the
Presentation boundary, and turned into a UI state rather than a
validation error. The Livewire testing surprise (`->set()` firing the
same `updated*` hooks a real keystroke would) is also a reusable lesson
for this codebase specifically: any future debounced/live Livewire
property tied to an external call needs the same `Http::preventStrayRequests()`
discipline in `tests/TestCase.php` from day one, not bolted on after the
fact.
