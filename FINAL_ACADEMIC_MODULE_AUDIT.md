# Final Academic / Docencia Module Audit

Audit-only pass. No code was modified, refactored, committed, or pushed.

---

## 1. Repository State

- **Repository:** `git@github.com:BSVS777/Atina.git` (origin)
- **Current branch:** `integration/atina-foundation`
- **HEAD:** `387a3d5ea5c2a631919035c4791fb168c59755a8`
- **Branch tracking:** up to date with `origin/integration/atina-foundation`
- **Working tree at audit start:**
  - deleted (unstaged): `CLAUDE_MASTER_PROMPT_SIGA_ATINA.md`
  - untracked: `revision.md` (the audit brief itself)
  - no other pending changes
- **Recent history (`git log -10 --oneline`):**
  ```
  387a3d5 feat: resolve UI bugs, refine aesthetics, and add search section
  a5b89dd temp: local state before branch checkout
  56ce86a fix(database): remove unused WithoutModelEvents trait from seeder
  fc90450 chore: remove redundant task prompt doc
  6dc3ff6 chore: gitignore local AI harness and skill tooling config
  90e0e17 fix(sidebar): fix Docentes toggle and add global form-field borders
  0da4229 feat(academic): improve UX of affinity docentes screens
  bf8a5a7 docs: add academic affinity requirements matrix, README and AI journal entries
  21046f3 chore: switch default locale to Spanish
  a935ec6 feat: seed deterministic academic affinity demo data
  ```

This audit was performed against `integration/atina-foundation` @ `387a3d5`, as instructed.

---

## 2. Confirmed Functional Scope

Read directly from `Docs/requirements/Proyecto_3_Gestion_Docente_Atinencias.docx` (extracted and read in full, not summarized from a secondary source).

### Required for this project

- **DO-01** — Registro de Atestados Académicos del Docente
- **DO-02** — Catálogo de Atinencias por Carrera y Curso, con Versionado
- **DO-02a** — Verificación Automática de Atinencia y Resultados
- **DO-02b** — Flujo de Atinencia por Nota Técnica
- **DO-02d** — Gestión de Asignaciones sin Catálogo de Atinencias

These are the only five functional requirements named in the document's "Requerimientos funcionales que el equipo debe implementar" section. No classroom management, scheduling, enrollment, grading, or document-management requirement is named anywhere in the text.

Section 3(b) of the same document additionally imposes **project-wide, non-functional** technical requirements that apply "sin excepción" regardless of module: TALL stack, TypeScript, an external REST API, JWT authentication, environment variables, unit tests, a documented Git repository, and Hexagonal/DDD architecture. These are evaluated separately in Sections 5–7 below — they are not "out of scope" merely because they are cross-cutting.

### Supporting only (needed as context, not independently required)

- `carreras` (careers) — read-only reference
- `cursos` (courses) — scoped to `carrera_id`, `codigo`, `nombre`, `activo` only
- `periodos_academicos` (academic terms) — supplies the date DO-02/DO-02a resolve against
- `grupos` (course groups) — scoped to `curso_id`, `periodo_academico_id`, `numero` only
- `docentes` (teachers) — explicitly a read-only external reference, no CRUD
- `especialidades` / `puestos` (specialties/positions) — reference data consumed by DO-01/DO-02a
- `archivos` (generic file table) — reused for the Technical Note PDF, not modeled separately
- The RBAC substrate (`roles`, `permissions`, `permission_role`) — needed to enforce DO-01/DO-02/DO-02b/DO-02d's authorization rules

### Safely out of scope

- `asignacion_cambios` (schedule/room change history) — belongs to an unbuilt room/schedule module
- `es_servicio` / `es_cuello_botella` / `requiere_laboratorio` / `tipo_laboratorio` on `cursos` — curriculum/scheduling concerns
- `jornada` / `condicion_nombramiento` / `quincena` / `numero_accion_personal` / `observacion` on `asignaciones_docentes` — HR/payroll concerns owned by another module
- Classroom management, student requests, document management beyond the Technical Note PDF, enrollment, grades

### Explicit answer: do the other ~30 professor-schema modules need to be implemented?

**No.** The official Word statement's functional-requirements section names only DO-01, DO-02, DO-02a, DO-02b, and DO-02d. The professor's institutional database is a 35-table schema shared across five student teams working on five different modules; this team's module statement does not require implementing the other teams' tables. This conclusion is drawn directly from the Word text, not from an architectural assumption about the shared schema.

---

## 3. Functional Requirements Audit

### DO-01 — Registro de Atestados Académicos del Docente

**STATUS: PARTIAL**

- **Requirement evidence (verbatim, SRS):** "Consultado el perfil de un docente en el contexto de un curso, el sistema muestra cada grado con su resultado de evaluación y la referencia del catálogo (**carrera, curso, versión, acuerdo**)." / "Solo un usuario con rol Administrador o Coordinadora de Docencia puede modificar atestados; toda modificación queda en un registro de auditoría."
- **Implementation evidence:**
  - Storage: `atestados` migration (`database/migrations/2026_08_10_090003_create_atestados_table.php:21-31`) stores `especialidad_id`, `grado` (enum), `institucion`, `anio_obtencion` — all four required fields present.
  - Authorization: `AcademicCredentialPolicy::create()`/`update()` (`src/Academic/AcademicCredential/Presentation/Policies/AcademicCredentialPolicy.php:22-30`) both gate on `atestados.gestionar`. `RoleSeeder::OFFICIAL_ROLE_PERMISSIONS` (`database/seeders/RoleSeeder.php:38-60`) grants this permission to exactly Administrador and Coordinadora de Docencia — no other named role.
  - Audit trail: `RegisterAcademicCredentialUseCase::handle()` writes a full before/after `AuditLogEntry` on create; `EditAcademicCredentialUseCase::handle()` computes a real per-field diff and only writes when a field actually changed. Both capture actor, timestamp (via Eloquent's `created_at`), field, before, and after.
  - Course-context citation: `TeacherProfileComponent::render()` (`src/Academic/Teacher/Presentation/Livewire/TeacherProfileComponent.php:103-123`) builds a citation string of the form `'v:{number} — {agreement} / Gazette {gazette}{provisional}'`, rendered at `resources/views/academic/teacher/livewire/teacher-profile-component.blade.php:25-33`.
- **Test evidence:** `RegisterAcademicCredentialUseCaseTest`, `EditAcademicCredentialUseCaseTest` (storage + duplicate detection), `AcademicCredentialAuthorizationTest` (role gating), `AcademicCredentialAuditTest` (audit field coverage) — all pass in the live suite run (see Section 8).
- **UI evidence:** Teacher profile page renders the credentials table with the citation string described above.
- **Actual gap:** The citation string contains **only version, agreement, and gazette number** — it never renders **carrera** anywhere on the page, and **curso** appears only implicitly as the already-selected `<option>` text in a course dropdown, never repeated inside the citation itself. The SRS acceptance criterion explicitly requires all four items (carrera, curso, versión, acuerdo) to be shown together. This is a literal, code-level gap, not a documentation overstatement only — the existing requirements matrix's "IMPLEMENTED" status for this row is inaccurate.
- Delete is correctly absent: the SRS flow only says "registra o edita"; no delete method, route, or policy exists anywhere in the credential slice, confirmed by grep.

---

### DO-02 — Catálogo de Atinencias por Carrera y Curso, con Versionado

**STATUS: CONFIRMED**

- **Requirement evidence:** Catalog keyed by (carrera, curso); acuerdo + Gaceta mandatory; each update creates a new version, none deleted; verification uses the destination term's start date; if no entry is vigente, apply the last vigente entry and mark "provisional por vigencia futura"; historical verifications preserve the version applied.
- **Implementation evidence:**
  - Key structure: `catalogos_atinencia` (`database/migrations/2026_08_10_090020_create_catalogos_atinencia_table.php:19-31`) is keyed by `curso_id` alone, unique on `[curso_id, version]`. Career is reached transitively because `cursos.carrera_id` is a **mandatory, non-nullable** foreign key (`database/migrations/2026_08_10_090012_create_cursos_table.php:25`) — a real DB-enforced invariant, not just documentation. **Classification: COMPLIANT WITH DOCUMENTED INVARIANT** — correct given the module's scope, but the invariant depends on transversal/multi-career "service courses" (`es_servicio`) staying out of scope, since a genuinely shared course would break the "one curso → one carrera" assumption.
  - Mandatory fields: `AffinityCatalogVersion`'s constructor throws `InvalidArgumentException` if acuerdo or Gaceta is blank.
  - Versioning without deletion: `CreateAffinityCatalogVersionUseCase::handle()` only ever constructs and saves a new row — no update/delete of any prior version anywhere in the method.
  - Overlap enforcement: `AffinityCatalogVersion::overlapsRange()` is called at write time inside `CreateAffinityCatalogVersionUseCase`, throwing `OverlappingCatalogVersionException` on any hit — enforced, not a dead method (application-layer only, no DB constraint, matching the project's own documented design).
  - Date targeting: traced end-to-end — `TeacherAssignmentComponent` loads `CourseGroup::with('academicTerm')` and passes `$group->academicTerm->start_date` into the proposal DTO, which flows into `ProposeTeacherAssignmentUseCase`, which passes it to the resolver. Never `now()`.
  - Historical immutability: `AffinityVerification.catalogVersionId` is stored once at verification time; the entity has no setters, so it cannot be recalculated later.
- **Test evidence:** `AffinityCatalogVersioningTest` (missing-acuerdo, missing-Gaceta, versioning-without-deletion, overlap-blocking), `CatalogVersionResolverTest` (7 methods covering all 5 date-resolution cases — see Section 4), `TeacherAssignmentVerificationTest::test_historical_verification_keeps_the_catalog_version_that_applied_at_the_time`.
- **UI evidence:** Affinity Catalog screen lists all historical versions without deleting prior ones (browser-verified in a prior session per the project's own journal; not independently re-verified live this session — see Section 9).
- **Actual gap:** None against the literal SRS text. The one caveat (transversal-course invariant) is a scope-boundary note, not a defect.

---

### DO-02a — Verificación Automática de Atinencia y Resultados

**STATUS: CONFIRMED** (updated 2026-08-24 — the specialty-only matching question below, the only reason this section was previously flagged, is now professor-confirmed; see Section 11 and `Docs/DIARIO_DECISIONES_IA.md`)

- **Requirement evidence:** Exactly four outcomes (Atinente, No Atinente, Nota técnica, Sin catálogo); synchronous verification against the DO-02-selected catalog entry; "al menos un grado del docente figura en la entrada vigente" → Atinente.
- **Implementation evidence:**
  - The verification runs synchronously inline in `ProposeTeacherAssignmentUseCase::handle()` — no queue dispatch.
  - Catalog selection correctly delegates into the DO-02 resolver with the term start date.
  - The matching comparison (`ProposeTeacherAssignmentUseCase.php:61-66`):
    ```php
    foreach ($this->credentials->forTeacher($dto->teacherId) as $credential) {
        if ($resolved->version->isAffineToSpecialty($credential->specialtyId())) {
            $matchedCredential = $credential;
            break;
        }
    }
    ```
    `isAffineToSpecialty()` (`src/Academic/AffinityCatalog/Domain/Entities/AffinityCatalogVersion.php:107-110`) does `in_array($specialtyId, $this->specialtyIds, true)`. `AcademicCredential::degreeLevel()` exists but is **never called anywhere in this use case** — confirmed by reading the complete file.
  - Atinente → `TeacherAssignment::confirm()`; No Atinente → assignment stays blocked, Technical Note offered; null catalog resolution → `VerificationResult::NoCatalog`, delegated to DO-02d.
  - `AffinityVerification` has no setters at all — only a constructor — so a historical result cannot change when the catalog changes later.
- **Test evidence:** `TeacherAssignmentVerificationTest` — Matched/NotMatched/NoCatalog outcomes, historical catalog-version preservation. All pass in the live run.
- **UI evidence:** "Propose teacher" modal shows the result badge and catalog citation (previously browser-verified per the project journal; not independently re-verified live this session).
- **Actual gap / ambiguity (see Section 4 for full analysis):** The comparison field is **specialty only**; degree level is stored but structurally ignored. This is schema-grounded (the professor's own pivot table `catalogo_atinencia_especialidad` has no degree-level column) and the SRS acceptance criterion's "grado" wording has since been **professor-confirmed**: the specialty itself must be explicitly listed in the course's affinity catalog entry — no degree-level criterion, no semantic/fuzzy matching, no inference from related specialties. See `Docs/DIARIO_DECISIONES_IA.md` for the confirmation.

---

### DO-02b — Flujo de Atinencia por Nota Técnica

**STATUS: PARTIAL**

- **Requirement evidence:** Can start from "No Atinente" o "Sin catálogo"; signed PDF mandatory; missing PDF blocks persistence; ratification deadline mandatory; labeled "Nota técnica — ratificación pendiente"; expires automatically after the deadline without requiring a page to be opened.
- **Implementation evidence:**
  - Entry guard (`AttachTechnicalNoteUseCase.php:39`):
    ```php
    if ($latest === null || ! in_array($latest->result(), [VerificationResult::NotMatched, VerificationResult::NoCatalog], true)) {
        throw InvalidAssignmentTransitionException::technicalNoteRequiresNotMatchedOrNoCatalog();
    }
    ```
    Both `NotMatched` (No Atinente) and `NoCatalog` (Sin catálogo) are accepted, matching the SRS text exactly, and this is documented as deliberately coexisting with DO-02d's separate manual-approval path for the same "Sin catálogo" result.
  - PDF/deadline validation — **only enforced in the Livewire form**, not the domain layer:
    - Domain constructor (`src/Academic/TeacherAssignment/Domain/Entities/TechnicalNote.php:26-28`) only rejects an empty document-path string — no mime-type check.
    - Real enforcement is in `src/Academic/TeacherAssignment/Presentation/Livewire/Forms/TechnicalNoteForm.php:23-26`: `'document' => ['required','file','mimes:pdf','max:10240']`, `'ratificationDeadline' => ['required','date','after_or_equal:today']`.
    - The use case itself (`AttachTechnicalNoteUseCase.php:52`) does `new DateTimeImmutable($dto->ratificationDeadline)` with **no guard** — an empty string silently resolves to "now" instead of throwing.
  - Label: rendered UI shows "Nota técnica: Ratificación pendiente" across two adjacent badges (`teacher-assignment-component.blade.php:44,63-65`), which is substantively equivalent to but not a verbatim match of the SRS's literal "Nota técnica — ratificación pendiente" string.
  - Scheduled expiry is genuinely registered: `routes/console.php:11` — `Schedule::command('affinity:expire-overdue-technical-notes')->daily();`, backed by `ExpireOverdueTechnicalNotesUseCase`, which marks overdue unresolved notes as expired without requiring any page view. Expiry is terminal (`TechnicalNoteStatus::Expired`, never reopened; `notas_tecnicas.asignacion_docente_id` is unique, structurally preventing reuse).
  - Ratification/rejection: `RatifyTechnicalNoteUseCase`/`RejectTechnicalNoteUseCase` persist correct state transitions; the original `AffinityVerification` row is never overwritten (a new row is appended instead).
- **Test evidence:** `TechnicalNoteFlowTest` — guard requiring NotMatched/NoCatalog, duplicate-note blocking, non-overwrite of original verification, automatic overdue expiry, ratify/reject — all pass. **No test drives the use case directly with a non-PDF file or a past/empty deadline** to confirm domain-level rejection, because no such rejection exists to confirm.
- **UI evidence:** Technical Note modal enforces file type and deadline client/server-side via the Livewire form rule (previously browser-verified per the project journal for the happy path; the missing-PDF block was confirmed via the form rule, not a live file-upload attempt, per the journal's own note that Livewire's async upload could not be exercised in that session's browser tooling).
- **Actual gap:** PDF-type and deadline validation exist only at the Presentation (form) layer. If `AttachTechnicalNoteUseCase` is ever invoked from any other entry point (a future API, a console command, a test that bypasses the form), nothing in the domain or application layer stops a non-PDF attachment or an already-overdue deadline from being persisted.

---

### DO-02d — Gestión de Asignaciones sin Catálogo de Atinencias

**STATUS: CONFIRMED**

- **Requirement evidence:** "Sin catálogo" → "Pendiente de aprobación manual"; Coordinadora approves/rejects; decision recorded in audit log with user, date, result; automatic blocking must not apply for a career with no catalog.
- **Implementation evidence:**
  - `DecideNoCatalogAssignmentUseCase` has zero references to `TechnicalNote` anywhere in the file and no PDF requirement — confirmed fully independent of DO-02b's code path.
  - No-redecision guard (`DecideNoCatalogAssignmentUseCase.php:43-45`):
    ```php
    if ($assignment->isDecided()) {
        throw InvalidAssignmentTransitionException::assignmentAlreadyDecided();
    }
    ```
    backed by `TeacherAssignment::isDecided()` (`status !== ProposalStatus::Proposed`).
  - Audit write (`DecideNoCatalogAssignmentUseCase.php:50-56`) records `actorUserId`, `auditableType`, `auditableId`, action, and before/after status.
  - "Sin catálogo" is reached directly from `ProposeTeacherAssignmentUseCase` when the catalog resolver returns null — not a state that only becomes reachable via Technical Note.
- **Test evidence:** `NoCatalogDecisionTest::test_approving_a_no_catalog_assignment_confirms_it`, `::test_rejecting_a_no_catalog_assignment_rejects_it`, `::test_the_decision_is_recorded_in_the_audit_log`, `::test_a_decided_assignment_cannot_be_decided_again` — all pass.
- **UI evidence:** "Sin catálogo" badge with Approve/Reject buttons (previously browser-verified per the project journal).
- **Actual gap:** None. `ESTADO_GENERAL.md` does not exist anywhere in this repository (confirmed by repeated full-repo search), and the specific claim that "No Catalog uses the same flow as Technical Notes" appears in none of README.md, the requirements matrix, or the AI journal — there is no stale documentation making this claim to correct.

---

## 4. Business Logic Findings

### Affinity matching

**Does the algorithm compare specialty only, degree only, specialty + degree, or something else?**

**Specialty only.** Confirmed by direct code trace: `ProposeTeacherAssignmentUseCase.php:61-66` calls `AffinityCatalogVersion::isAffineToSpecialty($credential->specialtyId())`, which does a plain `in_array()` against the catalog entry's `specialtyIds`. `AcademicCredential::degreeLevel()` exists on the entity but is never referenced anywhere in the matching use case.

**Is that unquestionably compliant with the official statement?** **Yes — professor-confirmed.** The catalog's physical pivot table (`catalogo_atinencia_especialidad`) has only an `especialidad_id` column — degree-level matching is structurally inexpressible against the professor's own schema. The professor has since explicitly confirmed that affinity is decided by exact specialty membership in the course's applicable catalog entry: a specialty related to the course subject matter (e.g. Cybersecurity vs. Programming) is not automatically affine, degree level does not independently determine affinity, and there is no semantic/fuzzy/AI-based inference. **Classification: RESOLVED — PROFESSOR-CONFIRMED.**

### Catalog lookup

**Is `(career, course)` really satisfied by using only `course_id`?**

Structurally, yes, with a caveat. `catalogos_atinencia` is keyed by `curso_id` alone, but `cursos.carrera_id` is a mandatory, non-nullable foreign key — every course belongs to exactly one career by database constraint, not just convention, so `(carrera, curso)` is always transitively and unambiguously derivable from `curso_id` alone. **Classification: COMPLIANT WITH DOCUMENTED INVARIANT.** The invariant would break only if a genuinely multi-career "service course" (the schema's own `es_servicio` flag) were ever brought into this module's scope — currently it is deliberately excluded, so the invariant holds.

### Catalog date fallback

| Case | Behavior | Source |
|---|---|---|
| Exact version exists | Returns that version, not provisional | Explicit in SRS |
| Date falls between two versions (gap) | Picks the immediately-preceding version, marked provisional | Explicit in SRS ("si no existe entrada vigente... aplica la última entrada vigente") |
| Only older versions exist | Same behavior — latest-starting old version, provisional | Explicit in SRS |
| Target date predates every version | Falls back to the **earliest future** version, marked provisional | **Assumption**, documented in the project's own journal (D6) — the SRS only describes falling back to "la última entrada vigente," which does not exist in this case (there is no past vigente entry at all) |
| No catalog exists at all | Returns null → delegated to DO-02d as "Sin catálogo" | Explicit in SRS |

All five cases are covered by individually-read (not test-name-only) assertions in `CatalogVersionResolverTest` (7 methods). An exact match is checked first in the algorithm, so "provisional" is never misapplied to case 1. **The predates-every-version case is the one genuinely worth a professor's confirmation** — a defensible but unstated extrapolation, not a literal reading of the text.

### Technical Note

**Can it start from No Atinente?** Yes — confirmed, `AttachTechnicalNoteUseCase.php:39` accepts `VerificationResult::NotMatched`.

**Can it also start from Sin catálogo?** Yes — the same guard also accepts `VerificationResult::NoCatalog`, matching the SRS's literal "No Atinente o Sin catálogo" wording.

**Does the normal Sin catálogo flow require a Technical Note?** **No.** `DecideNoCatalogAssignmentUseCase` (the DO-02d manual-approval path) has zero references to `TechnicalNote` and no PDF requirement — it is a fully independent code path. Both DO-02b and DO-02d are reachable from a "Sin catálogo" result, by design, as two separate options rather than one being a prerequisite for the other.

### Roles

| Action | Expected role (literal SRS text) | Actual permission → roles granted | Flag |
|---|---|---|---|
| Modify academic credentials | Administrador o Coordinadora | `atestados.gestionar` → Administrador, Coordinadora | Match |
| Update affinity catalog | Administrador only | `catalogo.gestionar` → Administrador only | Match |
| Propose/verify teacher assignment | Not stated explicitly | `atinencia.verificar` → Administrador, Coordinadora | No literal spec to compare against |
| Create Technical Note | Coordinadora de Docencia, specifically | `atinencia.verificar` → Administrador **and** Coordinadora | **Broader than spec — depends on interpretation** |
| Ratify/reject Technical Note | Implicit — Consejo Universitario | `nota_tecnica.aprobar` → Administrador only | Administrador-only is a plausible digital proxy for Council authority, but is an interpretation, not a literal named role |
| No-catalog manual decision | Coordinadora de Docencia, specifically | `atinencia.verificar` → Administrador **and** Coordinadora | **Broader than spec — depends on interpretation** |

Two of six actions grant Administrador access where the SRS names only Coordinadora de Docencia. This mirrors the actual shape of the professor's own `permission_role` table (not invented by the implementation team). The professor has since explicitly confirmed this is intentional: **Administrador has access to everything**, including all Academic-module/Coordinadora operations. See `Docs/DIARIO_DECISIONES_IA.md` for the confirmation.

---

## 5. Technical Requirements Audit

| Requirement | Current state | Evidence |
|---|---|---|
| Tailwind | **Implemented and demonstrable** | `resources/css/app.css:1` → `@import "tailwindcss";`; wired via `@tailwindcss/vite` in `vite.config.js`. |
| Alpine | **Implemented and demonstrable** | Not a separate npm dependency — bundled inside Livewire 4.1, injected via `@fluxScripts`. Genuine usage confirmed 65 times across 16 Blade files (`x-data`/`x-init`/`x-show`/`x-on`), including a non-trivial root-layout state object and Academic-module views — not unused starter-kit boilerplate. |
| Laravel | **Implemented and demonstrable** | `composer.json` → `laravel/framework: ^13.17`. |
| Livewire | **Implemented and demonstrable** | `composer.json` → `livewire/livewire: ^4.1`; real components (`TeacherComponent`, `AffinityCatalogComponent`, `TeacherAssignmentComponent`, etc.) wired to real routes. |
| TypeScript | **Not implemented** | Zero `.ts` files anywhere in `resources/**` (glob confirms). No `tsconfig*.json` at the project level. `package.json` lists no `typescript` dependency. `vite.config.js` has no TS plugin or entry — only `.js`/`.css` entries. |
| External REST API | **Not implemented** | Repo-wide grep for `Http::(get\|post\|put\|patch\|delete)\(`, `GuzzleHttp\Client`, `new Client(` returns zero matches outside `vendor/`. No `ApiClient`/`Gateway`/`ExternalService` port exists under `src/`. `.env.example` has no external API base URL or key. The app's own `/academic/*` routes are internal UI, not consumption of a third-party service. |
| JWT | **Not implemented** | `config/auth.php` defines exactly one guard: `web` (session-based). No `tymon/jwt-auth`, `firebase/php-jwt`, or Sanctum token guard in `composer.json`. No `config/jwt.php`. No `routes/api.php` file exists at all. The app authenticates exclusively via Laravel session/Fortify — confirmed not to be confused with JWT. |
| Environment variables | **Implemented and demonstrable** | `.env.example` (66 lines) externalizes DB, session, cache, queue, mail, AWS-placeholder, and Vite configuration, all consumed via `env()` in `config/*.php`. |
| Unit/feature tests | **Implemented and demonstrable** | 85 tests, 85 passing, 177 assertions (live re-run, see Section 8). |
| Git documentation | **Implemented and demonstrable** | Readable commit history; `README.md` has real setup instructions, seeded-user credentials, route table. |
| Hexagonal Architecture | **Implemented and demonstrable** | See Section 6 — zero framework leaks found in the Domain layer across all four bounded contexts. |
| DDD | **Implemented and demonstrable** | Entities, value objects, domain services, repository interfaces, and use cases are all present and consistently applied across AcademicCredential, AffinityCatalog, and TeacherAssignment. |

**TypeScript, the External REST API, and JWT are not classified as out of scope.** The Word statement states them as mandatory "sin excepción" project-wide requirements in §3(b), independent of which module is being evaluated, and all three are confirmed **entirely absent** from this repository by direct inspection (not assumed from a starter-kit default). The rubric's own Insuficiente tier for "integración técnica obligatoria" is triggered by two or more missing elements; this repository is currently missing all three.

**The Academic Affinity functional module can be fully complete on its own business-logic merits while the overall project statement remains non-compliant on this specific technical-integration axis** — these are independent grading lines per the rubric structure in the Word document itself.

---

## 6. Architecture Audit

Grep commands run against every `Domain/**/*.php` file in all four Academic-related bounded contexts (`AcademicCredential`, `AffinityCatalog`, `TeacherAssignment`, and `Shared/Audit`), searching for `Illuminate\`, `Laravel`, `Livewire`, `Alpine`, Eloquent base-class references, any Facade call (`Auth::`, `DB::`, `Storage::`, `Config::`), and any HTTP/Request object:

```
Grep pattern: Illuminate\\|Livewire\\|Alpine|Eloquent|Facades\\|extends Model|Auth::|DB::|Storage::|Http\\Request
Path: src/Academic/**/Domain, src/Shared/Audit/**/Domain
→ Zero matches
```

**No violations found.** `Teacher` deliberately has no Domain layer at all (`Glob src/Academic/Teacher/Domain/**/*.php` → zero files) — this is a documented scope decision (Teacher is treated as a read-only external reference with no business invariants), not an architectural gap, since its only code is a Presentation-layer Livewire component reading an Eloquent model directly, which is an acceptable Presentation-layer concern.

Dependency direction was verified by reading two representative use-case constructors directly:

- `RegisterAcademicCredentialUseCase.__construct(AcademicCredentialRepositoryInterface, AuditLogRepositoryInterface)` — both are Domain-defined interfaces.
- `ProposeTeacherAssignmentUseCase.__construct(TeacherAssignmentRepositoryInterface, AffinityVerificationRepositoryInterface, AcademicCredentialRepositoryInterface, ResolveApplicableCatalogVersionUseCase, AuditLogRepositoryInterface)` — all five dependencies are interfaces or other Application-layer use cases, zero concrete Infrastructure classes injected directly.

Presentation → Application → Domain holds on every case sampled, with Infrastructure implementing the ports (`EloquentAcademicCredentialRepository implements AcademicCredentialRepositoryInterface`, bound via `DomainServiceProvider::$domainBindings`). No inversions found.

---

## 7. Database Audit

- **MySQL compatibility:** Confirmed live this session. `php artisan migrate:status` was executed directly against the configured connection and succeeded, listing every Academic-module migration as already applied against the real database (`gestion_academica_utn`).
- **Institutional Spanish schema compatibility:** Migrations for Academic tables (`atestados`, `catalogos_atinencia`, `asignaciones_docentes`, etc.) are guarded with `Schema::hasTable()`/`Schema::hasColumn()`, confirmed by direct inspection of `2026_08_10_090003_create_atestados_table.php` and others — safe to run against either a fresh database or the pre-existing institutional one, never destructive.
- **English application/domain boundary:** Confirmed — Domain/Application/Presentation code uses English identifiers throughout; only the Eloquent model/repository (Infrastructure) layer and native ENUM cast classes (`DegreeLevelCast`, `ProposalStatusCast`, `VerificationResultCast`, `TechnicalNoteStatusCast`) know about the literal Spanish column/enum values, isolating the boundary correctly.
- **Migration safety:** All additive-only changes to shared institutional tables are nullable `ALTER TABLE` migrations (e.g. `avatar_path` on `users`, `module`/`action` on `permissions`) — no official column or row is renamed, dropped, or reinterpreted.
- **Could SQLite tests hide MySQL-specific behavior?** The automated test suite runs exclusively against in-memory SQLite (`phpunit.xml` forces `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`), confirmed by direct inspection. This means native-MySQL-specific behavior (e.g. real `ENUM` column semantics) is not exercised by the automated suite itself — the custom Cast classes exist specifically to bridge this gap, and their design (mapping literal Spanish `ENUM` values, e.g. `atestados.grado`) only makes sense against a real MySQL contract, which is architecturally consistent evidence of MySQL intent even though the automated tests don't touch it directly.
- **Evidence of real MySQL execution:** `php artisan migrate:status` succeeded live against `gestion_academica_utn` this session (see command output in Section 8's surrounding verification). This is direct, current-session evidence, not a carried-over claim.

---

## 8. Verification Results

Commands executed live against current HEAD (`387a3d5`) this session.

| Command | Result |
|---|---|
| `php artisan test` | **85 executed, 85 passed, 0 failed, 0 skipped**, 177 assertions, ~2.5s |
| `./vendor/bin/pint --test` | **Fails on 22 files** — every one is pre-existing SIGA-baseline code outside `src/Academic` (e.g. `FortifyServiceProvider`, `app/Models/User.php`, nine files under `src/IdentityAccess/**`). **Zero files under `src/Academic/**` or `src/Shared/Audit/**` fail** — the Academic module itself is Pint-clean. |
| `./vendor/bin/phpstan analyse` | **Could not produce output in this sandbox.** Five distinct invocation attempts (direct binary, via Composer script, with `--debug`/`-vvv`, with `memory_limit=-1`) all exited with code 1 and zero stdout/stderr. `phpstan.phar --version` succeeds in the same shell, confirming the binary itself works — `analyse` specifically fails to produce any output here. Reported honestly as unverifiable, not guessed as pass or fail. |
| `npm run build` | **Succeeds** — 24 modules transformed, built in 726ms. One informational (non-error) note about an optional `fontaine` package for font-fallback optimization. |
| `composer test` | Not a distinct script in `composer.json`; `php artisan test` is the actual test entrypoint used above. |

**The prior "85/85 passing" claim in the project's own AI journal is confirmed accurate on current HEAD, independently re-verified — not reused without execution.**

Test-to-requirement mapping (all confirmed by reading the actual assertions, not just test names):

- **DO-01:** `RegisterAcademicCredentialUseCaseTest`, `EditAcademicCredentialUseCaseTest`, `AcademicCredentialAuthorizationTest`, `AcademicCredentialAuditTest`
- **DO-02 (+ 5 date-edge-cases):** `AffinityCatalogVersioningTest`, `CatalogVersionResolverTest` (7 methods, one per resolver behavior)
- **DO-02a:** `TeacherAssignmentVerificationTest`
- **DO-02b:** `TechnicalNoteFlowTest`
- **DO-02d:** `NoCatalogDecisionTest`
- **Authorization:** `TeacherAssignmentAuthorizationTest`, plus the authorization assertions embedded in `AcademicCredentialAuthorizationTest`

**Acceptance criteria with no automated test:**
- The DO-01 carrera-in-citation requirement — untested, because it is unimplemented.
- The literal string "Nota técnica — ratificación pendiente" — no test asserts this exact rendered phrase.
- Domain-level rejection of a non-PDF file or a past/empty ratification deadline in `AttachTechnicalNoteUseCase` — untested, because no such rejection exists at that layer (see DO-02b gap above).

---

## 9. Browser Verification

**Chrome browser automation tooling was not connected in this environment this session** — confirmed by attempting `tabs_context_mcp`, which returned an explicit "Browser extension is not connected" error. This is not assumed from a prior session's note; it was tested directly and failed to connect.

**What was tested instead (HTTP-level, live, this session):**
- `GET /` → 200
- `GET /academic/teachers` (unauthenticated) → 302 → `/login`
- `GET /academic/affinity-catalog` (unauthenticated) → 302 → `/login`
- `GET /academic/teacher-assignments` (unauthenticated) → 302 → `/login`
- A raw `curl` POST to `/login` with scraped CSRF tokens returned **419** (CSRF/session mismatch) — consistent with this application's login being a Livewire-driven, JS-executed form rather than a plain HTML POST target. This means a full authenticated walkthrough could not be completed via raw HTTP in this session either.

**What was not tested:** the full 15-item interactive walkthrough specified in the audit brief (authenticated teacher list/profile, credential CRUD, course-context affinity display, catalog version creation, the four proposal outcomes, Technical Note modal and PDF validation, manual approve/reject, unauthorized-role behavior, responsive/console checks) — none of this could be executed live this session due to the tooling connection failure. Any "Browser-verified" status in the existing requirements matrix reflects a prior session's claim and could not be independently reconciled or re-confirmed in this session.

---

## 10. Documentation Accuracy

- **`ESTADO_GENERAL.md`:** does not exist anywhere in this repository — confirmed by full-repo glob search, run twice, at different points in this audit.
- **`Docs/ACADEMIC_AFFINITY_REQUIREMENTS_MATRIX.md`:** the DO-01 course-context citation row is marked `IMPLEMENTED` but overstates the actual UI — career is never rendered and course is only implicit. This is the one concrete inaccuracy found in the matrix against current code.
- **README.md:** setup instructions, seeded users, and route table were spot-checked and matched the actual `RoleSeeder`/route configuration — no inaccuracy found.
- **"85/85 tests passing" claim (AI journal):** accurate on current HEAD, independently re-verified live this session (Section 8).
- **"Integration branch has not been pushed":** this phrasing does not appear anywhere in README.md, the requirements matrix, or the AI journal — and it would be false if it did, since `git status` confirms the branch tracks and is up to date with `origin`.
- **"No Catalog uses the same flow as Technical Notes":** this phrasing (or a close variant) does not appear anywhere in README.md, the requirements matrix, or the AI journal. The only place this phrase appears in the repository is the audit brief itself, quoting it as a hypothetical to check against `ESTADO_GENERAL.md` — a file that does not exist. There is no stale documentation making this claim.
- **Browser-verification claims:** the requirements matrix marks several DO-02a/DO-02b/DO-02d rows "Browser-verified" / "Browser-verified live," while this session's own attempt to connect browser tooling failed outright, and the project's own AI journal repeatedly notes the same tooling was unavailable during several earlier verification passes. This tension could not be resolved from this session alone — it is surfaced, not silently accepted.

---

## 11. Remaining Business Questions

| Question | Classification |
|---|---|
| Should the affinity-matching algorithm consider degree level (grado) in addition to, or instead of, specialty only? | **RESOLVED — PROFESSOR-CONFIRMED.** Specialty-only, exact catalog membership; no degree-level criterion; no related-specialty inference. |
| Is falling back to the earliest **future** catalog version correct when the target date predates every catalog version (vs. treating it as equivalent to "Sin catálogo")? | **SHOULD CONFIRM WITH PROFESSOR** — still unresolved; a documented but unstated extrapolation beyond the literal SRS text. |
| Should Administrador have the same Technical-Note-creation and No-Catalog-decision powers the SRS text names only for Coordinadora de Docencia? | **RESOLVED — PROFESSOR-CONFIRMED.** Administrador has access to all Academic-module operations, intentionally. |
| Is Administrador-only ratification of a Technical Note an acceptable digital proxy for "ratificación...del Consejo Universitario"? | **SAFE ASSUMPTION** — no literal SRS-named role exists to compare against; Administrador-only is the most conservative reasonable interpretation available. |
| Does the (carrera, curso) invariant via `curso_id` alone remain safe if transversal/service courses are ever brought into scope? | **SAFE ASSUMPTION for current scope** — not blocking, since those courses are currently and deliberately excluded. |

No blocking business ambiguity was identified — nothing found prevents the module from being demonstrated and defended as-is; the items above affect grading precision and correctness confidence, not whether the system runs.

---

## 12. Final Compliance Matrix

| Area | Status | Evidence | Action Required |
|---|---|---|---|
| DO-01 | PARTIAL | Storage/authorization/audit trail compliant; course-context citation omits carrera, curso only implicit | Add carrera + curso to the citation string |
| DO-02 | CONFIRMED | `(curso_id, version)` unique key + mandatory `carrera_id` FK; acuerdo/Gaceta/versioning/overlap/date-targeting/immutability all verified | None |
| DO-02a | CONFIRMED | 4-outcome machine correct; matching is specialty-only, degree level unused — professor-confirmed as intended | None |
| DO-02b | PARTIAL | Entry points, auto-expiry, scheduled command all correct; PDF/deadline validation is form-only, not domain-enforced | Move validation into the domain/use-case layer |
| DO-02d | CONFIRMED | Fully independent of Technical Note; audit log and no-redecision guard both verified in code and tests | None |
| Authorization | CONFIRMED | Credential/catalog permissions match SRS exactly; Administrador's broader Technical-Note-creation and No-Catalog-decision access is professor-confirmed as intentional (Administrador has access to everything) | None |
| Auditability | PARTIAL | Credential create/edit and No-Catalog decision audited (required, met); catalog version creation and Technical Note ratify/reject are not audited | Add audit logging to ratify/reject at minimum |
| Catalog history | CONFIRMED | Full version history preserved; historical verification immutable; all 5 date-edge-cases individually tested | None |
| State machine | NEEDS PROFESSOR DECISION | 4 outcomes correctly distinguished; predates-all-versions case is an unstated extrapolation | Confirm intended behavior |
| MySQL | CONFIRMED | `migrate:status` succeeded live against `gestion_academica_utn` this session; guarded migrations; native-ENUM-aware casts | None |
| TALL | CONFIRMED | Tailwind/Alpine/Laravel/Livewire all demonstrated with direct evidence, not assumed | None |
| TypeScript | MISSING | Zero `.ts` files, no `tsconfig`, no TS plugin anywhere in the build | Add real TypeScript usage |
| External REST API | MISSING | Zero external HTTP client calls repo-wide | Integrate one real external REST API |
| JWT | MISSING | Only session/Fortify auth exists; no JWT package, guard, or config anywhere | Add a real JWT-authenticated flow |
| Environment variables | CONFIRMED | `.env.example` externalizes all real config via `env()` | None |
| Tests | PARTIAL | 85/85 passing, re-run live; Pint clean in-module; PHPStan produced no output in this sandbox (unverifiable, not failed) | Run PHPStan in a real dev environment; add tests for the DO-02b validation gap |
| Hexagonal/DDD | CONFIRMED | Zero framework imports found in any Academic/Shared-Audit Domain file; dependency direction verified on sampled use cases | None |
| Git/README | CONFIRMED | Branch tracks and is up to date with origin; README has real, accurate setup instructions | None |
| AI Journal | CONFIRMED | 831 lines, 5 dated entries, real accepted/rejected/corrected/learned content | None |
| Browser verification | CANNOT VERIFY | Chrome extension failed to connect this session; HTTP-level checks confirm auth-gating only | Run a real interactive walkthrough before the defense |
| Localization | CONFIRMED | Zero hardcoded UI strings found in Academic Blade views; two Spanish strings in the Domain layer are unreachable defensive guards, not real UI text | None |

---

## 13. Final Verdict

### C. MODULE HAS FUNCTIONAL GAPS

The core state machine (catalog versioning, the four-outcome verification, Technical Note's entry/expiry rules, No-Catalog's independence) is correct, well-tested, and cleanly architected. Affinity matching (specialty-only) and Administrador's broader Academic-module access, both flagged in this audit as needing professor confirmation, have since been professor-confirmed as intended. Two literal acceptance-criteria gaps exist in code (DO-01's missing carrera/curso citation, DO-02b's form-only validation), one server-side validation gap allows a theoretical bypass, and three project-wide mandatory technologies are entirely absent. This is not a business-logic catastrophe — most of the module is genuinely solid — but it is not yet "complete" against the literal text of its own acceptance criteria.

### Must fix before final delivery

- DO-01: render carrera and curso in the per-credential course-context citation, alongside the already-present version and agreement.
- DO-02b: enforce PDF mime-type and non-past ratification deadline at the domain/use-case level, not only in the Livewire form.
- Build real TypeScript usage, one real external REST API consumption, and a genuine JWT-authenticated flow — all three are currently entirely absent and are stated as mandatory "sin excepción" in the Word document.

### Should improve before final delivery

- Add audit logging to Technical Note ratification/rejection — currently only the automatic-expiry path is logged for what is otherwise the module's highest-stakes manual decision.
- Add audit logging to catalog version creation, proportionate to its downstream effect on every future affinity determination.
- Get PHPStan producing real output in a non-sandboxed development environment before the defense — this audit could not obtain a verdict either way.
- Perform one real interactive browser walkthrough (Administrador, Coordinadora, and an unauthorized role) before the oral defense — this audit's browser tooling failed to connect.

### Confirm with professor

- Whether falling back to the earliest future catalog version is the intended behavior when the target date predates every catalog version — **still unresolved**.

The following were open at the time of this audit and have since been **professor-confirmed** (see `Docs/DIARIO_DECISIONES_IA.md`):
- Affinity matching is specialty-only, exact catalog membership, no degree-level criterion.
- Administrador intentionally has access to all Academic-module operations, including those the SRS text names only for Coordinadora de Docencia.

### Safely out of scope

Every SIGA/professor-schema module outside DO-01, DO-02, DO-02a, DO-02b, and DO-02d — including classroom/room management, student requests, scheduling/room-change history, document management beyond the Technical Note PDF, enrollment, and grades. None of these appear in the Word statement's functional-requirements section for this team's module.
