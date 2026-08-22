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
