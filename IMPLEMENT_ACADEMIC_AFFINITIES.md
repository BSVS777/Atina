# Master Implementation Prompt — Atina / Academic Affinities Foundation Completion

## Mission

Complete the current `integration/atina-foundation` branch so that the project reaches a solid, reviewable foundation for the **Academic / Teaching Affinities module**, using the project statement Word document as the **source of truth**.

The implementation must close the gaps already identified in the current repository, with special focus on:

- English standardization inside the project.
- Complete and coherent database foundation.
- MySQL as the real development database.
- Models.
- Migrations.
- Factories.
- Seeders.
- CRUD/use-case coverage required by the statement.
- Academic credentials.
- Affinity catalog and catalog versioning.
- Affinity verification.
- Technical Notes.
- “No catalog” path.
- Authorization aligned with the required roles.
- AI Decision Journal.
- Automated tests.
- A real visual result in the application.
- Final report of anything intentionally not implemented and why.

Do not stop after scaffolding. The expected result is a functional, reviewable implementation.

---

# 1. Operating Mode

Work autonomously and exhaustively.

Do not ask for confirmation for routine implementation decisions that can be resolved by:

1. the Word project statement,
2. the current repository architecture,
3. existing conventions in `src/IdentityAccess`,
4. the current `Docs/Guia-CRUD-SIGA-UTN.md`,
5. existing code and tests,
6. conservative software-engineering judgment.

If information is genuinely missing from the source of truth and implementing it would require inventing a business rule, do **not** invent it.

Instead:

- implement the maximum safe subset,
- document the limitation,
- include it in the final **Not Implemented / Pending** section,
- explain exactly why it was not implemented.

---

# 2. Source of Truth Priority

Use this priority order whenever documents or code disagree:

1. **Project statement Word document**.
2. Existing confirmed business rules documented in the repository.
3. Current architecture of the SIGA foundation.
4. Existing tests.
5. Current implementation.
6. Supporting contextual documents.
7. Engineering judgment.

Never override an explicit requirement from the Word statement merely because the existing code behaves differently.

---

# 3. Language Standard

## Mandatory rule

Inside the project, all technical/internal implementation must be in **English**.

This includes:

- class names,
- namespaces,
- variables,
- methods,
- DTOs,
- use cases,
- repositories,
- policies,
- route names,
- table names,
- column names,
- enum names,
- enum values where technically reasonable,
- migration names,
- factory names,
- seeder names,
- comments,
- docblocks,
- validation attribute names,
- internal error messages,
- module folder names,
- test names,
- permission names,
- event names,
- service names,
- database objects created by this project.

## Allowed exceptions

These may remain in their original language:

- demo/test data,
- names of people,
- sample academic specialties or institutional labels,
- translation files,
- user-facing Spanish translations,
- `Docs/DIARIO_DECISIONES_IA.md`, which must remain in **Spanish**.

Do not translate existing test/demo data only for cosmetic consistency.

---

# 4. Current Architectural Direction

Preserve the existing SIGA architectural conventions.

The project currently follows a modular/hexagonal approach similar to:

```text
src/
  Academic/
  IdentityAccess/
  Shared/
```

Use the established layering where there is real value:

```text
Domain/
Application/
Infrastructure/
Presentation/
```

Do not create DDD ceremony for trivial read-only data if it adds no business value.

However, entities with meaningful business rules must have clear application/domain boundaries.

Follow the patterns already used by:

```text
src/IdentityAccess/Role
src/IdentityAccess/Permission
src/Academic/AcademicCredential
src/Shared/Audit
```

Reuse existing shared UI components and conventions wherever possible.

Do not introduce a second design system.

---

# 5. Git Safety

Work only on:

```text
integration/atina-foundation
```

Before changing files:

```bash
git status
git branch --show-current
git log -5 --oneline
```

Confirm the active branch is:

```text
integration/atina-foundation
```

Do not push.

Do not force push.

Do not rewrite existing history.

Create intentional local Conventional Commits in English after meaningful implementation slices.

Examples:

```text
feat: add affinity catalog persistence foundation
feat: implement affinity verification workflow
feat: add technical note approval flow
chore: configure mysql development environment
test: cover academic affinity state transitions
docs: record affinity implementation decisions
```

---

# 6. Baseline Verification Before Implementation

Before modifying business code, inspect and document:

```bash
php artisan about
php artisan route:list
php artisan migrate:status
php artisan test
```

Also inspect:

```text
.env.example
config/database.php
database/
app/Models/
src/Academic/
src/Shared/
src/IdentityAccess/
routes/
resources/views/academic/
Docs/DIARIO_DECISIONES_IA.md
Docs/Guia-CRUD-SIGA-UTN.md
```

Record any existing failure before modifying it.

Do not silently “fix” unrelated failures unless they prevent the requested module from working.

---

# 7. MySQL — Mandatory

The project must use **MySQL** as the real development database.

SQLite may remain available for isolated tests only if technically justified, but it must no longer be the documented/default development database.

## Update `.env.example`

Use a safe example such as:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=atina
DB_USERNAME=root
DB_PASSWORD=
```

Do not commit real credentials.

## Update application defaults where appropriate

Review:

```text
config/database.php
```

The documented/default project behavior must clearly favor MySQL.

Do not remove Laravel's other supported connection definitions unless there is a strong reason.

## MySQL compatibility validation

All custom migrations must run on MySQL.

Validate with a disposable MySQL database:

```bash
php artisan migrate:fresh --seed
php artisan test
```

Do not claim MySQL support merely because Laravel contains a MySQL driver.

The project must be actually migrated and seeded successfully on MySQL.

---

# 8. Database Foundation

Review the entire current:

```text
database/
```

The final database layer must be coherent with the Word statement and the implemented module.

At minimum, implement the persistence foundation needed for:

1. Positions.
2. Specialties.
3. Teachers.
4. Academic Credentials.
5. Audit Logs.
6. Affinity Catalogs.
7. Affinity Catalog Versions.
8. Catalog rules/items that associate academic requirements with courses.
9. Affinity Verifications.
10. Technical Notes.
11. The “No Catalog” decision path.
12. Any required supporting/pivot structures that are explicitly necessary for these flows.

Do not import unrelated tables from other project modules merely to inflate completeness.

If another bounded context owns an entity such as Course or Assignment, use the least invasive compatible integration possible.

---

# 9. Academic Credentials — DO-01

The existing Academic Credential implementation must be completed and aligned with the source of truth.

## Required data

Academic credentials must support the fields required by the statement, including the current conceptual mapping:

```text
Teacher
Specialty
Degree Level
Institution
Year Obtained
```

Preserve or improve existing validation:

- non-empty institution,
- valid year range,
- duplicate prevention,
- relational integrity.

## CRUD behavior

Do not blindly implement Delete.

If the source of truth explicitly defines:

> create/edit without deletion

then the correct implementation is:

```text
Create
Read
Update
NO Delete
```

This must be clearly documented as a **business-rule omission**, not an unfinished CRUD.

The UI, policy and repository must remain consistent with that decision.

---

# 10. Teacher Management

Review the Word statement carefully before changing Teacher lifecycle behavior.

If Teacher is an external/reference entity and the statement does not authorize this module to create/update/delete teachers:

- keep Teacher read-only,
- provide list/search/profile navigation,
- do not invent Teacher CRUD,
- include this in the final “Not Implemented” report.

If the Word explicitly requires Teacher lifecycle management, implement only the operations actually supported by the statement.

---

# 11. Authorization — Fix Current Gap

The current implementation uses generic permission checks for academic credentials.

The source of truth requires mutation access to match the authorized roles, including:

- Administrator.
- Teaching Coordinator / equivalent English role for “Coordinadora de Docencia”.

Implement a clear English role name such as:

```text
Teaching Coordinator
```

unless the existing repository already establishes another English naming convention.

## Requirements

Create/seed the role.

Assign the appropriate permissions.

Ensure only the authorized roles can mutate Academic Credentials.

Keep Superadmin bypass behavior if already part of the platform foundation.

Use permissions for implementation flexibility, but verify the resulting seeded role matrix matches the statement.

Add authorization tests.

---

# 12. Affinity Catalog — DO-02

Implement the missing catalog foundation.

The catalog must be **versionable**.

Do not model it as a single mutable table that destroys historical meaning.

A safe conceptual structure may include:

```text
AffinityCatalog
AffinityCatalogVersion
AffinityCatalogRule / AffinityCatalogItem
```

Use better names if the project conventions suggest them.

## Catalog requirements

At minimum support:

- identity,
- version,
- validity/effective dates if required by the statement,
- status if required,
- association to the academic/course context,
- admissible specialties/credentials,
- auditability,
- preservation of historical versions.

The exact schema must follow the Word statement.

Do not invent approval states that the statement does not describe.

---

# 13. Affinity Verification — DO-02a

Implement the verification workflow required to determine whether a teacher/credential is affine for a specific academic/course context.

The result must be historically explainable.

A verification record must preserve enough information so that a future reviewer can answer:

- Which teacher was evaluated?
- Which course/context was evaluated?
- Which academic credential was considered?
- Which catalog version was used?
- What result was produced?
- Why?
- Who performed/triggered the verification when applicable?
- When did it happen?

Do not derive old verification results dynamically from the newest catalog version.

Historical decisions must remain tied to the version used when the verification happened.

---

# 14. Verification Result Model

Use an explicit enum/value representation in English if the Word establishes finite states.

Examples are allowed only if supported by the source:

```text
MATCH
NO_MATCH
REQUIRES_TECHNICAL_NOTE
NO_CATALOG
```

Do not copy these names blindly.

First map the actual states from the Word document.

The implementation must make state transitions obvious and testable.

---

# 15. Technical Note — DO-02b

Implement the missing Technical Note workflow.

It must support the exceptional path defined by the statement, especially when regular catalog matching is insufficient and the institutional rules allow another justification path.

The Technical Note must preserve:

- related teacher,
- course/context,
- verification,
- justification,
- status/result,
- responsible actor,
- timestamps,
- audit trail,
- any evidence/reference field explicitly required by the statement.

Use English technical names.

Spanish user-facing labels may be delivered through translation files.

Do not invent unsupported approval chains.

---

# 16. “No Catalog” Flow — DO-02d

Implement the explicit “No Catalog” scenario.

The system must not crash, silently approve, silently reject, or fake a match if the applicable catalog does not exist.

The result must be explicit and understandable.

The UI must communicate the condition clearly.

The verification persistence must preserve that the decision occurred without an applicable catalog.

Add automated tests.

---

# 17. Models

Implement all Eloquent models necessary for the requested scope.

Each model must have:

- explicit `$fillable` or guarded strategy consistent with the project,
- correct casts,
- correct relationships,
- timestamps where appropriate,
- enum casts where appropriate,
- clear table mapping only if convention requires it,
- no unnecessary business logic inside Eloquent models.

Review existing models and standardize naming.

---

# 18. Factories

Every meaningful testable persistence entity in this scope should have a factory unless there is a documented reason not to.

Factories must:

- respect foreign keys,
- avoid impossible uniqueness collisions,
- generate valid business states,
- support useful factory states.

Examples of useful states where appropriate:

```php
->active()
->expired()
->approved()
->rejected()
->withoutCatalog()
```

Only create states that exist in the business rules.

---

# 19. Seeders

Create deterministic and useful seed data.

The main seed process must produce enough data to visually demonstrate the module.

At minimum seed:

- roles,
- permissions,
- authorized users,
- positions,
- specialties,
- teachers,
- academic credentials,
- at least one valid affinity catalog,
- at least two catalog versions if versioning can be demonstrated safely,
- representative catalog rules,
- representative verification examples,
- representative Technical Note examples if the lifecycle allows seeded examples.

Test/demo data may remain in Spanish if it already exists.

Seeders themselves and code identifiers must be in English.

Avoid huge data imports unless explicitly required.

A small coherent demo dataset is preferred over thousands of meaningless rows.

---

# 20. Database Constraints

Move enforceable integrity rules into the database when appropriate.

Examples:

- foreign keys,
- unique indexes,
- non-null constraints,
- indexed lookup fields,
- composite uniqueness,
- restrictive deletes where historical records must survive,
- cascade deletes only where business semantics truly allow them.

Be especially careful not to cascade-delete historical verification or Technical Note evidence accidentally.

Historical records should favor integrity and traceability over convenience.

---

# 21. Auditability

Preserve and extend the existing `Shared/Audit` mechanism.

At minimum audit meaningful mutations for:

- Academic Credentials,
- catalog/version changes,
- catalog rules,
- Technical Notes,
- manual verification decisions,
- other high-value business state changes.

Audit entries must identify:

- actor,
- target entity,
- action,
- relevant before/after changes,
- timestamp,
- IP when available.

Do not log secrets.

---

# 22. CRUD / Application Operations

Implement application operations according to the actual lifecycle of each entity.

Do not force a full C/R/U/D matrix onto immutable/historical entities.

For each entity, explicitly decide and document:

```text
Create?
Read?
Update?
Delete?
Why?
```

Example:

```text
AcademicCredential
Create: yes
Read: yes
Update: yes
Delete: no
Reason: source requirement explicitly excludes deletion
```

This lifecycle matrix must be included in the final report.

---

# 23. Visual Result — Mandatory

There must be a real visual result in the running application.

Do not finish with backend-only code.

Use the existing SIGA design system.

## Required minimum visual scope

Create or complete screens that allow a reviewer to visually inspect:

### Teachers

- teacher list,
- search/filter if already supported,
- teacher profile,
- academic credential list.

### Academic Credentials

- create modal/form,
- edit modal/form,
- validation feedback,
- permission-based controls.

### Affinity Catalog

- catalog list,
- version indicator,
- current/active version visualization,
- catalog details,
- rule/item list.

### Affinity Verification

A visual verification view that shows at least:

```text
Teacher
Course/context
Credential
Catalog version
Verification result
Justification/reference
```

### Technical Note

A visual view/form appropriate to the actual supported workflow.

### No Catalog state

A clear visual state explaining that no applicable catalog exists.

---

# 24. UX Requirements

The visual result must be usable, not just technically rendered.

Reuse:

```text
x-ui.data-table
x-ui.modal
x-ui.row-actions
existing SIGA layout/navigation
```

where suitable.

Requirements:

- no raw database IDs presented as primary labels,
- readable state labels,
- empty states,
- success/error feedback,
- responsive enough for normal desktop use,
- route navigation integrated into the existing sidebar,
- permissions reflected in visible controls,
- no dead buttons.

Do not introduce a new frontend framework.

---

# 25. Spanish UI Support

Internal code remains English.

User-facing Spanish content should be delivered through the existing translation strategy where appropriate.

Prefer:

```php
__('Affinity Catalog')
```

with Spanish equivalents in translation files rather than hardcoding Spanish strings directly into implementation code.

---

# 26. Tests

Add comprehensive tests for the implemented scope.

At minimum:

## Database / Model

- relationships,
- factory validity,
- unique constraints,
- enum casts,
- MySQL-compatible migrations where feasible.

## Academic Credentials

- create,
- read,
- update,
- duplicate prevention,
- invalid year,
- audit on create,
- audit on effective update,
- no unnecessary audit when no change occurs,
- unauthorized mutation rejection.

## Authorization

- Admin can manage credentials.
- Teaching Coordinator can manage credentials.
- Unauthorized role cannot mutate.
- Superadmin bypass remains valid if platform behavior requires it.

## Catalog

- create/read/update lifecycle if supported,
- version preservation,
- applicable version resolution,
- historical version not overwritten.

## Verification

- matching result,
- non-matching result,
- correct catalog version persistence,
- historical result remains stable after newer catalog version exists.

## Technical Note

- valid supported flow,
- invalid transition rejection,
- authorization,
- audit.

## No Catalog

- explicit persisted result,
- correct UI state,
- no accidental approval/rejection.

## Feature/UI

Use Livewire feature tests for major forms/components.

---

# 27. Quality Gates

Run:

```bash
php artisan test
```

Run scoped static analysis/formatting according to the existing project tools.

If the repository uses Pint:

```bash
./vendor/bin/pint --test
```

If full-repo Pint fails because of verified pre-existing unrelated style issues, do not hide it.

Instead:

1. run Pint on all files changed by this implementation,
2. document the unrelated baseline issue,
3. ensure all changed files pass.

If PHPStan is configured:

```bash
./vendor/bin/phpstan analyse
```

At minimum, all newly implemented module code must pass.

---

# 28. Browser Verification

After implementation:

1. run the app,
2. log in using seeded authorized users,
3. manually verify every new visual route,
4. confirm forms persist data,
5. confirm validation appears,
6. confirm authorization hides/blocks actions,
7. confirm catalog version appears,
8. confirm verification result is readable,
9. confirm Technical Note flow is readable,
10. confirm No Catalog state is readable.

Do not claim visual completion based solely on Blade templates existing.

---

# 29. AI Decision Journal — Spanish

Update:

```text
Docs/DIARIO_DECISIONES_IA.md
```

The journal must stay in **Spanish**.

For every meaningful implementation phase record:

```text
Consulta realizada a la IA
Qué se aceptó
Qué se rechazó
Por qué
Errores detectados
Correcciones
Qué se aprendió
```

Document real decisions, not filler.

Especially document:

- MySQL migration decisions,
- English naming decisions,
- role/permission alignment,
- catalog versioning model,
- verification state model,
- Technical Note decisions,
- No Catalog behavior,
- any requirement ambiguity,
- any implementation intentionally omitted.

---

# 30. Documentation

Update project documentation sufficiently so another developer can run the module.

Document:

```text
Requirements
PHP version
Node version if relevant
Composer install
npm install
MySQL database creation
.env configuration
php artisan key:generate
php artisan migrate:fresh --seed
npm run dev
php artisan serve
Seeded users
Relevant routes
Test commands
```

Do not expose real secrets.

---

# 31. Recommended Implementation Order

Execute in this order unless repository realities require a small adjustment.

## Phase 0 — Baseline

- inspect branch,
- inspect tests,
- inspect Word statement,
- map requirements to code,
- record baseline.

## Phase 1 — MySQL

- `.env.example`,
- database defaults,
- MySQL validation,
- fix migration incompatibilities.

## Phase 2 — Naming / English Audit

Search for technical Spanish identifiers across:

```text
app/
src/
database/
routes/
tests/
resources/
```

Do not blindly translate:

```text
lang/
Docs/DIARIO_DECISIONES_IA.md
demo data
```

Fix real internal naming inconsistencies.

## Phase 3 — DO-01 Completion

- authorization roles,
- Teaching Coordinator,
- permission matrix,
- Academic Credential stabilization,
- tests.

## Phase 4 — Catalog Persistence

- models,
- migrations,
- factories,
- seeders,
- repositories,
- DTOs,
- use cases.

## Phase 5 — Catalog Visual Management

- routes,
- Livewire components,
- Blade views,
- navigation,
- tests.

## Phase 6 — Verification

- domain states,
- persistence,
- application service/use case,
- historical version binding,
- tests.

## Phase 7 — Technical Note

- persistence,
- state rules,
- authorization,
- audit,
- UI,
- tests.

## Phase 8 — No Catalog

- state handling,
- persistence,
- UI,
- tests.

## Phase 9 — Seeded Demonstration

Create a deterministic scenario that visually demonstrates:

```text
1 teacher with a matching credential
1 teacher with a non-matching credential
1 verification against a specific catalog version
1 Technical Note case if supported
1 No Catalog case
```

## Phase 10 — Full Verification

- MySQL fresh migration,
- seed,
- test suite,
- static analysis,
- formatting,
- browser walkthrough.

## Phase 11 — Documentation + AI Journal

Update all documentation and the Spanish AI journal.

## Phase 12 — Final Audit

Search repository for:

```text
TODO
FIXME
stub
placeholder
NotImplemented
dd(
dump(
ray(
```

Remove accidental development leftovers.

---

# 32. Requirement Traceability

Create or update a traceability document, for example:

```text
Docs/ACADEMIC_AFFINITY_REQUIREMENTS_MATRIX.md
```

Map every implemented requirement to:

```text
Requirement
Status
Domain/Application implementation
Model
Migration
Factory
Seeder
Route
UI
Test
Notes
```

Use statuses:

```text
IMPLEMENTED
PARTIALLY_IMPLEMENTED
NOT_IMPLEMENTED
NOT_APPLICABLE
```

Never use “Implemented” unless the behavior has been verified.

---

# 33. Mandatory Final Report

At the end, provide a structured final report.

## A. Completed

List every completed implementation area.

## B. Database

List:

- new/changed migrations,
- models,
- factories,
- seeders,
- constraints,
- MySQL verification result.

## C. CRUD / Lifecycle Matrix

Example format:

| Entity | Create | Read | Update | Delete | Reason |
|---|---:|---:|---:|---:|---|
| AcademicCredential | Yes | Yes | Yes | No | Deletion excluded by source requirement |

Include all implemented business entities.

## D. Visual Result

List every screen/route that can be opened and what can be demonstrated.

## E. Tests

Report:

```text
Total passing
Total failing
Any skipped tests
Any baseline failures
```

Do not hide failures.

## F. English Standardization

Report any remaining Spanish identifiers and justify each intentional exception.

## G. AI Journal

Confirm the Spanish journal was updated and summarize the new entries.

## H. Not Implemented / Pending — MANDATORY

End with a complete list titled:

```text
NOT IMPLEMENTED / PENDING
```

For each item include:

```text
Item
Reason
Source limitation / technical limitation / scope decision
Impact
Recommended next step
```

Examples of valid reasons:

- not required by the Word statement,
- explicitly prohibited by business rule,
- owned by another module,
- source of truth does not define enough behavior,
- external dependency unavailable,
- deliberately postponed because implementation would require inventing business logic.

“Ran out of time” is not an acceptable reason if the item is required and implementable.

---

# 34. Definition of Done

Do not consider the task finished until all applicable points below are true:

- project internals are standardized to English,
- allowed language exceptions are respected,
- MySQL is configured as the real documented development DB,
- `migrate:fresh --seed` succeeds on MySQL,
- required models exist,
- required migrations exist,
- required factories exist,
- required seeders exist,
- Academic Credential DO-01 is aligned with authorization requirements,
- Teaching Coordinator role exists if required by the statement,
- Affinity Catalog foundation exists,
- catalog versioning exists,
- verification exists,
- Technical Note exists,
- No Catalog state exists,
- historical verification references are preserved,
- auditability exists,
- visual UI exists,
- navigation exists,
- representative seed data exists,
- tests exist,
- changed code passes formatting/static analysis gates,
- AI journal is updated in Spanish,
- traceability matrix is updated,
- final non-implemented list is complete and justified.

---

# 35. Final Constraint

Do not overengineer beyond the statement.

The goal is not to implement every possible future academic feature.

The goal is to produce a **complete, coherent, demonstrable foundation for the scope actually required**, with enough visual functionality that a professor or reviewer can open the application and understand what was built without reading the source code first.

Where the Word statement and current architecture permit implementation, complete it.

Where the Word statement does not provide enough information, stop at the safest boundary and explicitly report the omission and reason.
