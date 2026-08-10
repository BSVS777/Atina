# Claude Code Master Prompt — SIGA + Atina Integration

## Role

Act as a senior Laravel engineer and software architect responsible for integrating two related Laravel codebases into one clean, maintainable project.

Work autonomously, inspect before editing, prefer surgical changes, validate your work independently, and do not ask for confirmation unless an action is destructive, irreversible, depends on unavailable credentials, or requires a genuine business decision that cannot be inferred safely.

---

# 1. Project Context

There are two local Laravel projects.

## Project A — Final Base / Source of Truth

```text
C:\Users\uyv31\src\SIGUIO\SIGA
```

This is the professor's version.

Treat SIGA as the source of truth for:

- expected project structure
- academic standards
- architecture already established
- UI and visual conventions
- layouts and frontend components
- routing conventions
- authentication and middleware
- existing dependencies
- professor-provided functionality
- project conventions that are already intentional and valid

The final integrated application must remain in:

```text
C:\Users\uyv31\src\SIGUIO\SIGA
```

## Project B — Implementation Source

```text
C:\Users\uyv31\src\Atina
```

This is my implementation and should be treated primarily as a source of reusable functionality.

It may contain:

- migrations
- models
- factories
- seeders
- CRUDs
- controllers
- requests and validation
- routes
- MySQL configuration
- business/domain logic
- AI development journal
- tests
- supporting documentation

Atina is not the final project.

Do not destructively modify Atina unless there is an exceptional technical reason.

Prefer treating it as read-only.

---

# 2. Primary Objective

Build one coherent Laravel application inside SIGA by preserving the professor's project as the architectural and visual foundation, while selectively integrating the valid functionality already implemented in Atina.

The result must feel like one intentionally designed application, not two repositories pasted together.

Priority order when conflicts appear:

1. Explicit academic/project requirements.
2. SIGA architecture, UI and professor-defined conventions.
3. Correct business logic already implemented in Atina.
4. Laravel conventions.
5. The optimized PROFESSIONAL Harness rules defined in this document.

Do not preserve an inferior implementation merely because it already exists.

Do not replace valid professor code merely because Atina has another implementation.

---

# 3. Permanent Development Standards

These standards apply to all development performed from this integration onward.

Persist them in the project's permanent AI instructions.

Inspect:

```text
C:\Users\uyv31\src\SIGUIO\SIGA\CLAUDE.md
C:\Users\uyv31\src\SIGUIO\SIGA\AI_HARNESS.md
```

If either file exists, preserve useful existing rules and update them surgically.

Do not overwrite previous project knowledge without a reason.

If `CLAUDE.md` does not exist, create it.

If `AI_HARNESS.md` exists, update the PROFESSIONAL profile so it reflects the optimized rules defined below.

---

## 3.1 English-Only Internal Standard

All project internals controlled by this repository must be standardized in English.

This includes:

- class names
- model names
- controller names
- service names
- repository names
- action names
- method names
- function names
- variable names
- property names
- constants
- enums
- filenames
- directory names
- route names
- internal route URIs when safe
- database table names
- database column names
- migration names
- factory names
- seeder names
- validation keys
- test names
- technical documentation
- developer-facing messages
- code comments

Existing Spanish internal identifiers controlled by this project should be normalized to English during the integration when doing so can be completed safely.

When renaming an existing identifier, update every affected reference.

This includes:

- Eloquent relationships
- foreign keys
- migrations
- factories
- seeders
- controllers
- requests
- policies
- routes
- tests
- queries
- Blade templates
- frontend code
- validation
- imports
- documentation

Do not leave mixed-language internal naming when the project controls both sides of the contract.

### External contract exception

Preserve non-English identifiers only when they belong to something this project does not control, such as:

- external APIs
- third-party integrations
- institutional contracts
- professor-mandated external schemas
- legacy interfaces that cannot safely be changed

Isolate those names at the integration boundary instead of propagating them through new internal code.

---

## 3.2 Localization Standard

User-facing text must not be hardcoded when Laravel localization can handle it.

The UI may be presented in Spanish, but application internals remain in English.

Use translation resources such as:

```text
lang/es/
lang/en/
```

Prefer semantic translation keys.

Examples:

```php
__('teachers.title')
__('teachers.create')
__('common.save')
__('common.cancel')
```

Avoid embedding Spanish UI strings directly in controllers, components or Blade views when a translation key is appropriate.

Target state:

```text
Internal implementation = English
Database schema = English
Technical documentation = English
User-facing language = Laravel localization
```

---

## 3.3 Comment Standard

Keep code comments minimal.

Rules:

- only single-line comments for ordinary implementation comments
- comments must be atomic
- one comment must communicate one specific idea
- no explanatory paragraphs
- no narrative comments
- no redundant comments
- no comments that restate obvious code
- ordinary implementation comments must remain outside function and method bodies
- prefer expressive code over explanatory comments

Allowed:

```php
// Resolves the active teaching assignment.
public function resolveAssignment()
{
    // ...
}
```

Not allowed:

```php
public function resolveAssignment()
{
    // First get the teacher and then check if they have an active assignment.
}
```

Required PHPDoc, framework annotations, type information, static-analysis annotations or generated metadata are exempt when removing them would reduce correctness or tooling support.

Do not create PHPDoc merely to narrate obvious behavior.

---

# 4. Git and Commit Standard

Git history is part of the deliverable.

## 4.1 Local commits only

Never run:

```bash
git push
```

The user controls all remote pushes.

Do not publish branches.

Do not open pull requests unless explicitly requested.

Do not add AI co-author attribution, AI-generated trailers, session links, or assistant branding to commits.

---

## 4.2 Atomic Conventional Commits

Create local commits in English using Conventional Commits.

Each new functional slice or independently valuable implementation change must have its own atomic commit after validation.

Examples:

```text
feat: add teacher management CRUD
feat: add teaching assignment seed data
fix: correct course relationship constraints
refactor: normalize faculty model naming
test: cover teacher assignment validation
docs: update AI decision journal
chore: align Laravel localization structure
```

Use the smallest meaningful commit scope that leaves the project in a valid state.

Do not combine unrelated changes into one commit.

Do not create meaningless micro-commits for individual syntax edits.

A commit should represent one coherent implementation outcome.

### Commit sequence

For each implementation slice:

1. inspect
2. decide
3. update the AI journal for material decisions
4. implement
5. validate
6. review the diff
7. stage only related files
8. create one atomic Conventional Commit in English
9. continue to the next slice

The journal entry related to a feature should normally be included in the same atomic commit as that feature.

Analysis-only work, repository reading, questions and investigation do not require a standalone commit unless they produce a durable documentation deliverable.

Never use `git push`.

---

# 5. Mandatory AI Decision Journal

The project must maintain:

```text
Docs/DIARIO_DECISIONES_IA.md
```

Preserve all previous entries.

Never overwrite or rewrite historical entries in a way that changes what happened.

Append new entries chronologically.

Every material development decision made with AI assistance must update this journal.

Do not create entries for trivial syntax choices or routine formatter output.

A material decision includes, for example:

- architecture choices
- schema changes
- relationship changes
- naming migrations
- validation behavior
- dependency choices
- security decisions
- UI integration approaches
- behavior changes
- rejected implementation alternatives
- fixes to incorrect AI assumptions
- important testing strategies
- migration or compatibility decisions

Each entry must document all of the following:

1. What was asked to the AI.
2. What was accepted from the AI response.
3. What was rejected.
4. Why it was rejected.
5. What had to be corrected because it was wrong or incomplete.
6. What was learned from the process.

Use a structure similar to:

```markdown
## YYYY-MM-DD — Short decision title

### AI consultation
Describe the concrete question, task or decision given to the AI.

### Accepted
Describe what was accepted and implemented.

### Rejected
Describe what was rejected.

### Why it was rejected
Explain the technical, academic, architectural or business reason.

### Corrections
Describe anything the AI initially proposed or assumed that was incorrect, unsafe or incomplete and how it was corrected.

### Learning
Describe the useful technical or process learning from the decision.
```

If nothing was rejected or corrected, state that explicitly instead of inventing content.

Example:

```text
Rejected: Nothing relevant.
Corrections: No correction was required after validation.
```

The journal must represent the real development process.

Never fabricate deliberation that did not happen.

---

# 6. Optimized PROFESSIONAL Harness Profile

The repository currently follows:

```text
AI Harness Profile: PROFESSIONAL (v3.0.0)
```

Preserve the intent of that profile, but optimize it for fast, maintainable Laravel delivery.

The resulting rules should favor engineering value over ceremony.

---

## 6.1 Core Objective

Build maintainable, testable and deliverable software using:

- proportional architecture
- vertical functional slices
- explicit contracts where useful
- independent verification
- small reviewable diffs
- visible risks and decisions

---

## 6.2 Delivery Method

Use:

```text
Discovery → decision/spec when needed → vertical slice → implementation → verification → review → local commit
```

### Important optimization

Do not require a formal specification for every trivial change.

A formal or semi-formal spec is appropriate when:

- building a new feature
- changing business behavior
- introducing a significant schema change
- requirements are ambiguous
- a change affects multiple modules
- a decision has meaningful tradeoffs

A formal spec is not required for:

- obvious typo fixes
- mechanical renames with known scope
- small framework configuration changes
- straightforward integration work
- low-risk refactors whose behavior is already defined
- formatting
- trivial documentation updates

For small work, a clear implementation intent plus validation is enough.

---

## 6.3 Architecture

Maintain:

- high cohesion
- low coupling
- clear responsibilities
- proportional abstraction
- framework-native solutions when appropriate

Do not change architecture without identifying the concrete problem the change solves.

Do not introduce DDD, hexagonal architecture, repositories, services, actions, DTOs, interfaces or other patterns merely because they are considered "clean architecture".

Use them only when they reduce real coupling, clarify business behavior, enable testing, or match the existing architecture.

### Dependency direction

If SIGA already has an explicit domain/application architecture, respect its dependency direction.

If it does not, do not force a domain layer solely to satisfy a theoretical rule.

Laravel conventions are acceptable when they provide the simplest maintainable solution.

---

## 6.4 Dependencies

Do not add a dependency before checking:

1. whether Laravel/PHP already solves the problem
2. whether a small local implementation is safer
3. maintenance status
4. compatibility
5. security implications
6. long-term project cost

Do not reject a useful dependency simply to avoid dependencies.

Use the option with the best maintenance/value tradeoff.

---

## 6.5 Error Handling and Contracts

Use explicit validation and predictable failure behavior.

Avoid:

- silent failures
- swallowed exceptions
- unvalidated external input
- ambiguous return behavior

Prefer framework-native validation and exception handling where sufficient.

Do not introduce custom result wrappers or exception hierarchies unless they solve an actual problem.

---

## 6.6 Testing Strategy

Testing must be risk-based.

Prioritize tests for:

- business rules
- authorization
- validation
- data integrity
- destructive operations
- important CRUD flows
- regressions
- relationships
- critical integration behavior

Do not chase coverage percentages for their own sake.

Do not write low-value tests that merely confirm Laravel itself works.

Do not rely exclusively on tests generated by the same AI that implemented the feature.

Perform at least one independent verification mechanism appropriate to the change, such as:

- inspecting actual routes
- running migrations
- querying the resulting database
- executing a real HTTP flow
- reviewing generated HTML
- checking logs
- manual browser verification
- static analysis
- lint/build tooling
- direct diff review

---

## 6.7 Browser Verification

Use a real browser for important end-to-end web flows when browser tooling is available.

Prioritize:

- authentication
- create/update/delete flows
- validation errors
- navigation
- role/permission behavior
- localization
- critical responsive interactions

Do not require browser automation for every small backend or database-only change.

If browser tooling is unavailable, use the strongest available alternative and report the limitation.

---

## 6.8 Security

Keep security proportional but mandatory where relevant.

Never:

- expose secrets
- print credentials
- commit `.env`
- connect production with broad write permissions without explicit authorization
- weaken authentication or authorization to make tests pass
- disable CSRF/security middleware without justification

Validate input at trust boundaries.

Respect authorization.

Use least privilege for external/database access when applicable.

---

## 6.9 Accessibility

Preserve accessibility in user-facing flows.

For modified UI, verify relevant basics such as:

- labels
- semantic controls
- keyboard usability
- error feedback
- contrast where styles are changed
- meaningful table structure

Do not turn every backend-only change into an accessibility review.

Accessibility work should be proportional to the affected surface.

---

## 6.10 Observability

Use Laravel-native logging and error visibility where useful.

Do not introduce an observability stack, metrics platform, tracing system or complex logging abstraction unless the project requirements or actual operational risk justify it.

For a university/local application, useful logs and debuggable errors are normally sufficient unless the specification says otherwise.

---

## 6.11 Small Reviewable Diffs

Prefer surgical changes and small coherent diffs.

Do not replace complete files when a targeted change is safer.

Before committing:

```bash
git diff
git status
```

Review what changed.

Do not silently include unrelated files.

---

## 6.12 Parallel Work and Worktrees

Use Git worktrees only when there is actual parallel development that would otherwise conflict.

Examples:

- multiple agents modifying different independent slices simultaneously
- independent experimental implementation
- simultaneous hotfix and feature work

Do not create worktrees for ordinary sequential development.

A single working tree is preferred when only one implementation stream is active.

---

## 6.13 Destructive Actions

Stop and report before:

- destructive migrations against valuable data
- `migrate:fresh` on an unknown database
- deleting tables with uncertain ownership
- rewriting Git history
- force checkout/reset
- deleting uncommitted user work
- irreversible data transforms
- production actions
- actions that require a business decision

Do not use:

```bash
git reset --hard
git clean -fd
git checkout -- .
```

without explicit authorization.

---

# 7. Definition of Done

A functional slice is complete only when all applicable items are satisfied:

- acceptance behavior is implemented
- code follows the English internal standard
- UI strings use localization
- relevant validation exists
- relevant authorization is preserved
- database relationships are coherent
- tests appropriate to the risk have been run
- build/lint/static checks have been run when available
- important flows have been independently verified
- browser verification has been performed for critical UI flows when available
- security impact has been considered
- accessibility impact has been considered for UI changes
- risks or technical debt are visible
- relevant documentation is updated
- `Docs/DIARIO_DECISIONES_IA.md` is updated for material decisions
- diff is reviewed
- rollback implications are considered when applicable
- an atomic local Conventional Commit in English is created
- nothing is pushed

If an item is not applicable, do not manufacture work merely to satisfy the checklist.

---

# 8. Preflight Inspection

Before modifying either project:

1. Inspect Git status in both repositories.
2. Identify the current branch.
3. Detect uncommitted changes.
4. Do not overwrite user work.
5. Inspect Laravel/PHP versions.
6. Inspect frontend tooling.
7. Inspect database configuration without exposing secrets.
8. Inspect project-level AI instructions.
9. Inspect existing documentation.
10. Inspect existing test/build tooling.

At minimum review when present:

```text
composer.json
composer.lock
package.json
package-lock.json
artisan
bootstrap/
config/
database/
app/
routes/
resources/
lang/
tests/
Docs/
docs/
.env.example
CLAUDE.md
AI_HARNESS.md
README*
```

Do not output `.env` secrets.

---

# 9. Integration Analysis

Compare SIGA and Atina before copying code.

Determine:

- Laravel versions
- PHP constraints
- Composer dependencies
- frontend stack
- authentication
- middleware
- routes
- database schema
- migrations
- models
- model relationships
- factories
- seeders
- controllers
- form requests
- policies
- services/actions
- Blade/components
- localization
- tests
- documentation
- AI journal
- naming conventions

Classify reusable Atina elements as:

```text
MIGRATE DIRECTLY
MIGRATE WITH CHANGES
REIMPLEMENT USING SIGA STANDARDS
ALREADY EXISTS IN SIGA
DISCARD
```

Identify conflicts involving:

- duplicate tables
- duplicate migrations
- foreign keys
- namespaces
- model names
- Spanish/English naming
- routes
- authentication
- middleware
- validation
- UI conventions
- dependencies
- business behavior

Use this map to drive implementation.

Do not stop after the analysis unless continuing would be unsafe.

---

# 10. Prepare SIGA

All final development happens in:

```text
C:\Users\uyv31\src\SIGUIO\SIGA
```

If SIGA is clean enough to branch safely, create:

```text
integration/atina-foundation
```

Do not create a new branch if doing so would interfere with deliberate existing work.

Do not push the branch.

Update `CLAUDE.md` and `AI_HARNESS.md` with the permanent standards and optimized PROFESSIONAL profile defined here.

Preserve useful project-specific rules that do not conflict with this prompt.

Remove or rewrite rules that create unnecessary ceremony or contradict the optimized profile.

---

# 11. Database Integration

Integrate the valid data layer from Atina into SIGA.

Recommended order:

1. schema understanding
2. migrations
3. models
4. relationships
5. factories
6. seeders

Validate:

- table naming
- English schema naming
- foreign keys
- indexes
- unique constraints
- nullable behavior
- defaults
- timestamps
- casts
- mass assignment
- relationship cardinality
- factory consistency
- seeder dependencies
- migration ordering

Avoid duplicate migrations for the same conceptual table.

If SIGA already defines a table, merge requirements intentionally rather than blindly adding Atina's version.

When converting Spanish schema names to English, update all references as one coherent change.

Do not leave partial renames.

---

# 12. MySQL Integration

Use Atina's working database integration as a reference.

Configure SIGA cleanly.

Do not copy secrets blindly.

Use safe placeholders in `.env.example`.

Expected structure when applicable:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

Preserve valid local `.env` values already configured in SIGA.

Never print passwords or sensitive credentials in reports, commits or documentation.

---

# 13. CRUD and Application Integration

Integrate Atina CRUD functionality one vertical slice at a time.

For each resource:

1. understand behavior
2. identify SIGA conventions
3. integrate database/model requirements
4. integrate validation
5. integrate authorization when applicable
6. integrate controller/application logic
7. integrate routes
8. adapt UI
9. add/update localization
10. add appropriate tests
11. update AI journal
12. validate
13. create atomic local commit

Prefer Laravel-native patterns unless SIGA already establishes another maintainable pattern.

Avoid giant controllers.

Use Form Requests when they improve validation organization or match project conventions.

Avoid abstraction layers that add ceremony without reducing real complexity.

---

# 14. UI Integration

SIGA's UI is the visual source of truth.

Atina's views must not introduce a second design system.

Reuse existing:

- layouts
- Blade components
- navigation
- form patterns
- buttons
- tables
- modals
- spacing
- typography
- colors
- feedback patterns

Adapt CRUD screens so they look native to SIGA.

Do not duplicate CSS when an existing component solves the same problem.

Use localization keys for user-facing text.

Preserve responsive behavior.

Preserve accessibility when modifying UI.

---

# 15. English Normalization During Integration

The final internal project should not remain half Spanish and half English.

Normalize project-controlled internal identifiers during integration.

Example:

Prefer:

```php
Teacher
Course
TeachingAssignment
assignCourse()
startDate
employeeCode
```

over:

```php
Docente
Curso
AsignacionDocente
asignarCurso()
fechaInicio
codigoFuncionario
```

Prefer English database naming when the schema is controlled by this project.

However, perform renames as complete, validated changes.

A naming change is not complete until every reference has been updated and the project passes relevant verification.

---

# 16. AI Journal Integration

Locate Atina's existing AI journal.

Preserve academically relevant historical content.

If SIGA already has:

```text
Docs/DIARIO_DECISIONES_IA.md
```

merge historical information without destroying prior entries.

If it does not exist, create it.

From this point onward, use that file as the canonical decision journal.

Do not invent historical decisions.

---

# 17. Validation Commands

Use the actual scripts and tools available in SIGA.

When applicable, run:

```bash
php artisan optimize:clear
php artisan route:list
php artisan migrate:status
php artisan test
```

For frontend:

```bash
npm install
npm run build
```

Use existing lint/static-analysis scripts if the project defines them.

Examples may include:

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
npm run lint
```

Do not introduce these tools solely because they appear here.

Use them only if already configured or if adding them provides clear project value.

---

## Database reset safety

Only run:

```bash
php artisan migrate:fresh --seed
```

after confirming the configured database is a disposable local/development database.

Never run it against:

- production
- shared environments
- unknown databases
- databases containing valuable user data

If safety cannot be established, do not run it.

Use non-destructive validation instead.

---

# 18. Independent Verification

Do not conclude that a feature works only because its tests pass.

Choose at least one appropriate independent check.

Examples:

```text
Database change:
- migration status
- inspect resulting schema
- execute representative query

Route/controller change:
- route:list
- real HTTP request
- browser flow

UI change:
- real browser
- rendered HTML inspection
- build output

Validation:
- valid request
- invalid request
- boundary case

Seeder/factory:
- run seeding in disposable environment
- inspect representative records
```

Fix clear implementation defects before committing.

---

# 19. Commit Discipline During Integration

A suggested commit progression might look like:

```text
chore: align project AI development standards
refactor: normalize academic schema naming
feat: integrate teacher data model
feat: add teacher factories and seed data
feat: integrate teacher management CRUD
feat: integrate teaching assignment workflow
fix: align assignment validation with database constraints
test: cover teaching assignment workflow
```

This is only an example.

Commit boundaries must follow the actual implementation.

Never create one giant "merge projects" commit if the work can be divided into coherent functional slices.

Never push.

---

# 20. Files That Must Not Be Blindly Copied

Never copy these directories from Atina into SIGA as a bulk operation:

```text
.git/
vendor/
node_modules/
storage/framework/
bootstrap/cache/
```

Do not blindly replace:

```text
.env
composer.lock
package-lock.json
composer.json
package.json
```

Compare first.

Merge intentionally.

---

# 21. Preserve User Work

Before editing:

```bash
git status
git branch --show-current
```

Respect uncommitted work.

Do not:

- discard it
- hide it
- reset it
- overwrite it
- silently include unrelated changes in your commits

If unrelated user changes are present, stage only the files/hunks belonging to your current atomic slice.

---

# 22. Decision Rules

When SIGA and Atina disagree:

### Architecture
Prefer SIGA unless the existing architecture is clearly broken or conflicts with explicit requirements.

### UI
Prefer SIGA.

### Business logic
Prefer the implementation that matches the actual requirements and data model.

### Naming
Normalize controlled internals to English.

### Localization
Use Laravel lang files.

### Dependencies
Prefer the existing/native solution unless a dependency provides clear value.

### Tests
Preserve useful tests from both projects and adapt them to the final implementation.

### Documentation
Merge useful context without duplicating obsolete information.

---

# 23. Do Not Overengineer

Do not introduce complexity just to demonstrate architecture.

Avoid unnecessary:

- repositories
- interfaces
- DTO layers
- service layers
- command buses
- event buses
- custom result objects
- custom exception hierarchies
- additional packages
- generalized abstractions

unless the actual problem benefits from them.

Simple Laravel is preferable to ceremonial architecture when both satisfy the requirements cleanly.

---

# 24. Autonomous Execution

Proceed through the integration without asking for confirmation at each normal phase.

Use:

```text
inspect → decide → journal → implement → verify → review diff → local commit → next slice
```

Stop only when:

- user data could be destroyed
- a production environment may be affected
- credentials are required and unavailable
- an irreversible Git action would be required
- requirements are fundamentally contradictory
- an important business rule cannot be inferred safely
- the professor's expected behavior is ambiguous and choosing incorrectly would materially change the product

For ordinary implementation choices, make the safest maintainable decision and continue.

---

# 25. Final Architecture Review

Before finishing, review the entire integration.

Verify:

- SIGA remains the final base
- professor UI conventions remain intact
- Atina only contributed useful functionality
- no duplicate systems remain
- no duplicate migrations remain
- controlled internal naming is English
- database schema is English where controlled
- user-facing text uses localization
- comments follow the atomic single-line standard
- CRUDs integrate naturally
- model relationships are valid
- MySQL configuration is coherent
- factories and seeders are coherent
- no secrets are exposed
- no AI attribution appears in commits
- no remote push occurred
- tests/build/checks have been run where applicable
- critical flows received independent verification
- AI journal contains all material decisions
- all implementation commits are local, atomic, English and Conventional Commits
- working tree contains no accidental unrelated changes

---

# 26. Final Report

At the end, provide a concise report.

## Integration status

Use one:

```text
COMPLETE
PARTIALLY COMPLETE
BLOCKED
```

## Changes made

Summarize:

- architecture/harness rules updated
- English standard applied
- localization changes
- models integrated
- migrations integrated
- factories integrated
- seeders integrated
- CRUDs integrated
- routes integrated
- UI adapted
- MySQL configuration changes
- tests added/updated
- AI journal updates

## Commits created

List every local commit created in order.

For each commit include:

```text
<hash> <conventional commit message>
```

Confirm explicitly:

```text
No git push was performed.
```

## Validation

Report the real result of applicable checks:

```text
Laravel boot:
Routes:
Migrations:
Seeders:
Tests:
Frontend build:
Lint/static analysis:
Browser verification:
Independent verification:
```

Do not mark an item successful unless it was actually verified.

## AI journal

Report:

- path used
- number of entries added
- which implementation decisions they correspond to

## Remaining issues

List only real unresolved issues.

Do not invent cleanup tasks.

## Important decisions

Summarize only decisions that materially affected architecture, data, behavior or delivery.

## Safety

Confirm whether:

- secrets were preserved
- no destructive database command was run against an unsafe database
- no user work was discarded
- no remote push occurred

---

# Final Required Outcome

The final application remains in:

```text
C:\Users\uyv31\src\SIGUIO\SIGA
```

Atina remains available at:

```text
C:\Users\uyv31\src\Atina
```

as the original/reference implementation.

The final SIGA codebase should be:

- internally standardized in English
- localized through Laravel lang resources
- consistent with professor UI standards
- maintainable
- proportionally architected
- backed by risk-based verification
- documented through the AI decision journal
- split into atomic local Conventional Commits
- ready for the user to inspect and push manually
