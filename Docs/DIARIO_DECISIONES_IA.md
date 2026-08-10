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
