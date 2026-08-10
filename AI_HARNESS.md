<!-- AI-HARNESS:GENERATED -->

---
ai_harness_profile: professional
ai_harness_version: 3.1.0
source: AI Dev Harness BSVS (optimized for SIGA, see CLAUDE_MASTER_PROMPT_SIGA_ATINA.md)
---

# Active AI Harness Profile: PROFESSIONAL (optimized)

## Objective

Build maintainable, testable, deliverable Laravel software using proportional
architecture, vertical slices, explicit contracts where useful, independent
verification, small reviewable diffs, and visible risks and decisions.

## Delivery method

```text
Discovery → decision/spec when needed → vertical slice → implementation → verification → review → local commit
```

A formal spec is required for: new features, business-behavior changes,
significant schema changes, ambiguous requirements, changes spanning
multiple modules, or decisions with meaningful tradeoffs.

A formal spec is NOT required for: typo fixes, mechanical renames with known
scope, small config changes, low-risk refactors, formatting, trivial doc
updates. For small work, a clear implementation intent plus validation is
enough.

## Architecture

- High cohesion, low coupling, clear responsibilities, proportional
  abstraction, framework-native solutions when appropriate.
- Do not change architecture without identifying the concrete problem it
  solves.
- Do not introduce DDD, hexagonal layers, repositories, services, actions,
  DTOs, or interfaces merely because they're "clean architecture." Use them
  only when they reduce real coupling, clarify business behavior, enable
  testing, or match the existing architecture.
- SIGA already has an explicit hexagonal-DDD pattern under `src/{Context}/{Entity}/`
  (Domain/Application/Infrastructure/Presentation) — see
  `Docs/Guia-CRUD-SIGA-UTN.md` (copied from Atina) for the concrete, worked
  recipe and `app/Console/Commands/DDDStructure.php` (`php artisan make:ddd`)
  for scaffolding. Respect this pattern for new bounded contexts; don't force
  full 4-layer modules onto entities with no real domain logic (plain lookup
  tables stay plain Eloquent models in `app/Models`).

## Dependencies

Before adding a dependency, check: does Laravel/PHP already solve this? Is a
small local implementation safer? What's the maintenance status,
compatibility, and security implication? Don't reject a useful dependency
just to avoid dependencies — use the best maintenance/value tradeoff.

## Error handling and contracts

Explicit validation, predictable failure behavior. Avoid silent failures,
swallowed exceptions, unvalidated external input, ambiguous returns. Prefer
framework-native validation/exceptions; don't add custom result wrappers or
exception hierarchies unless they solve an actual problem.

## Testing strategy

Risk-based, not coverage-chasing. Prioritize: business rules, authorization,
validation, data integrity, destructive operations, important CRUD flows,
regressions, relationships, critical integration behavior. Don't write tests
that only confirm Laravel itself works. Don't rely solely on tests generated
by the same AI that implemented the feature — pair with at least one
independent verification (route:list, migration status, a real query, an
HTTP flow, rendered HTML, logs, static analysis, lint/build, manual browser
check, direct diff review).

## Browser verification

Use a real browser for important end-to-end flows when tooling is
available: auth, create/update/delete, validation errors, navigation,
role/permission behavior, localization, critical responsive interactions.
Not required for every small backend/database-only change.

## Security

Never expose secrets, print credentials, commit `.env`, connect production
with broad write permissions without authorization, weaken auth to make
tests pass, or disable CSRF/security middleware without justification.
Validate input at trust boundaries. Respect authorization. Least privilege
for external/database access.

## Accessibility

Proportional to the affected surface. For modified UI: labels, semantic
controls, keyboard usability, error feedback, contrast, meaningful table
structure.

## Observability

Laravel-native logging/error visibility is normally sufficient for this
project. Don't add a metrics/tracing stack without an actual operational
need.

## Small reviewable diffs

Surgical changes over full-file rewrites. `git diff` / `git status` before
every commit; don't silently include unrelated files.

## Parallel work and worktrees

Only when there's real parallel/conflicting work. Not for ordinary
sequential development.

## Destructive actions — stop and report before

Destructive migrations against valuable data, `migrate:fresh` on an unknown
database, deleting tables with uncertain ownership, rewriting Git history,
force checkout/reset, deleting uncommitted user work, irreversible data
transforms, production actions, or anything requiring a business decision.
Never `git reset --hard` / `git clean -fd` / `git checkout -- .` without
explicit authorization.

## Commits and decision journal (mandatory)

### Local commits

- After each relevant new implementation (however small), create a local
  commit in English following Conventional Commits (`feat:`, `fix:`,
  `refactor:`, `docs:`, `test:`, `chore:`, ...).
- Never `git push`. The user controls all remote pushes.
- Analysis-only work (reading, exploration, questions) doesn't require a
  commit unless it produces a durable documentation deliverable.
- Smallest meaningful commit scope that leaves the project in a valid state.
  Don't combine unrelated changes; don't create meaningless micro-commits.

### AI decision journal

Every material development decision made with AI assistance gets a new
entry in `Docs/DIARIO_DECISIONES_IA.md` (never overwrite previous entries),
documenting: what was asked, what was accepted, what was rejected and why,
what had to be corrected, what was learned. Material = architecture choices,
schema changes, relationship changes, naming migrations, validation
behavior, dependency choices, security decisions, UI integration approaches,
behavior changes, rejected alternatives, corrected AI mistakes, testing
strategy, migration/compatibility decisions. Skip trivial syntax choices and
routine formatter output.

## English-only internal standard

Class/model/controller/service/method/variable/property/constant/enum/file/
directory/route/table/column/migration/factory/seeder/validation-key/test
names, technical docs, developer-facing messages, and code comments are all
English. Existing Spanish internal identifiers get normalized to English
when it can be done safely and completely (every reference updated in the
same change — no partial renames).

Exception: identifiers this project does not control (external APIs,
institutional contracts, professor-mandated external schemas) stay as-is,
isolated at the integration boundary.

## Localization

UI is Spanish; internals are English. User-facing text goes through
`lang/es.json` (semantic keys in English, e.g. `__('Teachers')`), never
hardcoded Spanish in controllers/components/Blade.

## Comment standard

Minimal, single-line, atomic (one idea per comment), outside function
bodies, no narration of obvious code. Required PHPDoc/type/static-analysis
annotations are exempt.

## Do not overengineer

No repositories/interfaces/DTOs/service layers/command buses/event
buses/custom result objects/custom exception hierarchies/extra packages
unless the actual problem benefits from them.

## Definition of Done

A slice is done when applicable: acceptance behavior implemented, English
internal naming, localized UI strings, relevant validation/authorization in
place, coherent DB relationships, risk-appropriate tests run, build/lint/
static checks run where available, critical flows independently verified,
browser-verified for critical UI when available, security/accessibility
considered, risks/debt visible, docs updated, `Docs/DIARIO_DECISIONES_IA.md`
updated for material decisions, diff reviewed, rollback considered, atomic
local Conventional Commit created, nothing pushed.
