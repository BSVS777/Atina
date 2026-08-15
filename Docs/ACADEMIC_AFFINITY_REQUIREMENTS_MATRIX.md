# Academic Affinity Requirements Traceability Matrix

Source of truth: `Docs/requirements/Proyecto_3_Gestion_Docente_Atinencias.docx`
(FR-DO-01, DO-02, DO-02a, DO-02b, DO-02d). Persistence source of truth:
`gestion_academica_utn_test` (professor-provided MySQL database).

Statuses: `IMPLEMENTED`, `PARTIALLY_IMPLEMENTED`, `NOT_IMPLEMENTED`, `NOT_APPLICABLE`.

## DO-01 — Registro de Atestados Académicos del Docente

| Requirement | Status | Domain/Application | Model | Migration | Factory | Seeder | Route | UI | Test | Notes |
|---|---|---|---|---|---|---|---|---|---|---|
| Store teacher's academic credentials (degree, institution, year, specialty) | IMPLEMENTED | `Src\Academic\AcademicCredential\{Domain,Application}` | `App\Models\AcademicCredential` (→ `atestados`) | `2026_08_10_090003_create_atestados_table.php` | `AcademicCredentialFactory` | `AcademicManagementDemoSeeder` | `academic.teacher.profile` | `teacher-profile-component.blade.php` (Academic credentials table) | `RegisterAcademicCredentialUseCaseTest`, `EditAcademicCredentialUseCaseTest` | Verified against real MySQL |
| Only Administrador/Coordinadora de Docencia can mutate | IMPLEMENTED | `AcademicCredentialPolicy` (`atestados.gestionar`) | — | — | — | `RoleSeeder` (official role→permission matrix) | — | Create/edit buttons hidden without permission | `AcademicCredentialAuthorizationTest` | Permission matches official `permission_role` data exactly |
| Every mutation is audited (user, date, field, before, after) | IMPLEMENTED | `RegisterAcademicCredentialUseCase`, `EditAcademicCredentialUseCase` + `Src\Shared\Audit` | `App\Models\AuditLog` (→ `auditorias`) | `2026_08_10_090004_create_auditorias_table.php` | — | — | — | — | `AcademicCredentialAuditTest` | Edit only audits changed fields; rejected attempts are not audited (matches statement) |
| Profile in course context shows affinity result + catalog citation | IMPLEMENTED | `ResolveApplicableCatalogVersionUseCase` reused from `TeacherProfileComponent` | — | — | — | — | `academic.teacher.profile` | "Evaluate affinity in the context of a course" selector + per-credential Atinente/No Atinente badge + catalog citation | Browser-verified | DO-01-F4. Uses "today" as the reference date (no group selected on this screen) — documented simplification |
| Delete academic credential | NOT_IMPLEMENTED (by design) | — | — | — | — | — | — | No delete UI/policy method | — | Statement only describes "registra o edita"; explicit business-rule omission, not an unfinished CRUD |

## DO-02 — Catálogo de Atinencias, versionado

| Requirement | Status | Domain/Application | Model | Migration | Factory | Seeder | Route | UI | Test | Notes |
|---|---|---|---|---|---|---|---|---|---|---|
| Catalog structured by (carrera, curso), versioned | IMPLEMENTED | `Src\Academic\AffinityCatalog\*` | `App\Models\AffinityCatalogVersion` (→ `catalogos_atinencia`) | `2026_08_10_090020_create_catalogos_atinencia_table.php` | `AffinityCatalogVersionFactory` | `AffinityDemoSeeder` | `academic.affinity-catalog.index` | `affinity-catalog-component.blade.php` | `AffinityCatalogVersioningTest` | Keyed by `curso_id` alone on the real schema; career is reached transitively via `cursos.carrera_id` (every course in this module's scope belongs to exactly one career) |
| Acuerdo + número de Gaceta mandatory | IMPLEMENTED | `AffinityCatalogVersion` entity constructor | — | — | — | — | — | Form validation | `AffinityCatalogVersioningTest::test_missing_council_agreement_is_rejected` / `test_missing_gazette_number_is_rejected` | |
| Every update creates a new version, none deleted | IMPLEMENTED | `CreateAffinityCatalogVersionUseCase` | — | — | — | — | — | Version list shows all historical versions | `AffinityCatalogVersioningTest::test_each_update_creates_a_new_version_without_deleting_the_previous_one` | Browser-verified: v1 (2024-2025) and v2 (2026-indefinite) both visible |
| Overlapping validity ranges blocked | IMPLEMENTED | `AffinityCatalogVersion::overlapsRange()` + `CreateAffinityCatalogVersionUseCase` | — | — | — | — | — | Inline form error | `AffinityCatalogVersioningTest::test_overlapping_validity_ranges_are_blocked` | D7 — validated in application layer, not a DB constraint (matches the professor's own documented design) |
| Version resolution: applies entry covering target date; else closest-prior, else earliest, both provisional | IMPLEMENTED | `CatalogVersionResolver` (pure domain service) | — | — | — | — | — | Provisional badge shown | `CatalogVersionResolverTest` (7 unit tests covering exact match, D5, D6, gap-between-versions, pick-by-date-not-number) | D5/D6 — the rubric's explicit Excelente/Regular differentiator |
| Historical verification shows the version applied at the time | IMPLEMENTED | `AffinityVerification.catalogVersionId` (immutable, never recalculated) | — | — | — | — | — | Catalog citation column | `TeacherAssignmentVerificationTest::test_historical_verification_keeps_the_catalog_version_that_applied_at_the_time` | D10/D11 |
| Only Administrador updates the catalog | IMPLEMENTED | `AffinityCatalogVersionPolicy` (`catalogo.gestionar`) | — | — | — | — | — | "New version" button hidden without permission; sidebar item hidden | `TeacherAssignmentAuthorizationTest::test_creating_a_catalog_version_requires_catalogo_gestionar` | Matches official `permission_role`: only Administrador has `catalogo.gestionar` |

## DO-02a — Verificación Automática de Atinencia

| Requirement | Status | Domain/Application | Model | Migration | Factory | Seeder | Route | UI | Test | Notes |
|---|---|---|---|---|---|---|---|---|---|---|
| Synchronous automatic verification on proposing a teacher | IMPLEMENTED | `ProposeTeacherAssignmentUseCase` | `App\Models\TeacherAssignment` (→ `asignaciones_docentes`) | `2026_08_10_090022_create_asignaciones_docentes_table.php` | `TeacherAssignmentFactory` | `AffinityDemoSeeder` | `academic.teacher-assignment.index` | "Propose teacher" modal | `TeacherAssignmentVerificationTest` | Browser-verified end-to-end (live proposal against real MySQL) |
| Atinente result: assignment proceeds | IMPLEMENTED | `ProposeTeacherAssignmentUseCase` → `TeacherAssignment::confirm()` | `App\Models\AffinityVerification` (→ `verificaciones_atinencia`) | `2026_08_10_090023_create_verificaciones_atinencia_table.php` | `AffinityVerificationFactory` | — | — | Status badge "Confirmed" / "Atinente" | `test_a_teacher_with_an_affine_credential_is_matched_and_confirmed` | Browser-verified |
| No Atinente: assignment blocked, Nota Técnica offered | IMPLEMENTED | same use case | — | — | — | — | — | Status "Proposed" / "No Atinente" + "Attach technical note" button | `test_a_teacher_without_an_affine_credential_is_not_matched_and_stays_blocked` | Browser-verified |
| Sin catálogo: delegates to DO-02d | IMPLEMENTED | same use case | — | — | — | — | — | "Sin catálogo" + Approve/Reject buttons | `test_a_course_with_no_catalog_produces_no_catalog_result` | Browser-verified |
| Result shows catalog citation (version, agreement, gazette) | IMPLEMENTED | — | — | — | — | — | — | Catalog / justification column | Browser-verified | |
| Matched credential recorded | IMPLEMENTED | `AffinityVerification.matchedCredentialId` | — | `2026_08_10_090024_add_atestado_id_to_verificaciones_atinencia_table.php` | — | — | — | — | Covered indirectly via DO-01-F4 test | Additive column, not in the official schema — see journal |

## DO-02b — Nota Técnica

| Requirement | Status | Domain/Application | Model | Migration | Factory | Seeder | Route | UI | Test | Notes |
|---|---|---|---|---|---|---|---|---|---|---|
| Coordinadora registers provisional assignment with mandatory signed PDF | IMPLEMENTED | `AttachTechnicalNoteUseCase` | `App\Models\TechnicalNote` (→ `notas_tecnicas`), `App\Models\Archivo` (→ `archivos`) | `2026_08_10_090025_create_notas_tecnicas_table.php`, `2026_08_10_090021_create_archivos_table.php` | `TechnicalNoteFactory`, `ArchivoFactory` | `AffinityDemoSeeder` | — | "Attach technical note" modal (file upload + deadline) | `TechnicalNoteFlowTest::test_a_technical_note_cannot_be_attached_when_the_latest_result_is_matched`, `::test_a_second_technical_note_cannot_be_attached_to_the_same_assignment` | Missing-PDF validation confirmed via Livewire form rule; **live browser file upload could not be exercised** — Livewire's async upload failed client-side in the automated browser tool without reaching the server (no server log entry); verified instead via automated tests + the seeder's real `Storage`-backed execution |
| Original verification result not overwritten | IMPLEMENTED | `AttachTechnicalNoteUseCase` appends a new `AffinityVerification` row | — | — | — | — | — | — | `TechnicalNoteFlowTest::test_attaching_a_technical_note_does_not_overwrite_the_original_verification` | D12 — rubric's explicit Excelente/Regular differentiator; browser-verified live (Ted Orn Daugherty row kept "Nota técnica" as the *verification* label while the assignment's status changed) |
| Ratification deadline required; SLA auto-expiry | IMPLEMENTED | `ExpireOverdueTechnicalNotesUseCase` + `affinity:expire-overdue-technical-notes` scheduled command | — | — | — | — | — | Note status badge | `TechnicalNoteFlowTest::test_an_overdue_pending_note_is_automatically_marked_expired` | D14 — expiry is terminal, no reopening |
| Ratification/rejection recorded (D13) | IMPLEMENTED | `RatifyTechnicalNoteUseCase`, `RejectTechnicalNoteUseCase` | — | — | — | — | — | Ratify/Reject note buttons (Administrador only) | `TechnicalNoteFlowTest::test_ratifying_a_technical_note_confirms_the_assignment`, `::test_rejecting_a_technical_note_rejects_the_assignment` | Browser-verified live: ratifying moved the assignment to Confirmed |

## DO-02d — Gestión sin Catálogo

| Requirement | Status | Domain/Application | Model | Migration | Factory | Seeder | Route | UI | Test | Notes |
|---|---|---|---|---|---|---|---|---|---|---|
| Assignment marked "Pendiente de aprobación manual" | IMPLEMENTED | Read off `TeacherAssignment.status === Proposed` + latest `VerificationResult::NoCatalog` | — | — | — | — | — | "Sin catálogo" badge, no separate status column (matches official schema) | `NoCatalogDecisionTest` | |
| Coordinadora approves/rejects manually | IMPLEMENTED | `DecideNoCatalogAssignmentUseCase` | — | — | — | — | — | Approve/Reject buttons | `test_approving_a_no_catalog_assignment_confirms_it`, `test_rejecting_a_no_catalog_assignment_rejects_it` | Browser-verified live |
| Decision recorded in audit log with user, date, result | IMPLEMENTED | `DecideNoCatalogAssignmentUseCase` → `Src\Shared\Audit` | — | — | — | — | — | — | `test_the_decision_is_recorded_in_the_audit_log` | |
| Decided assignment cannot be re-decided | IMPLEMENTED | `TeacherAssignment::isDecided()` guard | — | — | — | — | — | — | `test_a_decided_assignment_cannot_be_decided_again` | |

## Authorization

| Permission (official) | Roles granted (official `permission_role`) | Enforced by | Test |
|---|---|---|---|
| `atestados.gestionar` | Administrador, Coordinadora de Docencia | `AcademicCredentialPolicy` | `AcademicCredentialAuthorizationTest` |
| `catalogo.gestionar` | Administrador | `AffinityCatalogVersionPolicy` | `TeacherAssignmentAuthorizationTest::test_creating_a_catalog_version_requires_catalogo_gestionar` |
| `atinencia.verificar` | Administrador, Coordinadora de Docencia | `TeacherAssignmentPolicy`, `TechnicalNotePolicy::create` | `TeacherAssignmentAuthorizationTest` |
| `nota_tecnica.aprobar` | Administrador | `TechnicalNotePolicy::approve` | `TeacherAssignmentAuthorizationTest::test_ratifying_a_technical_note_requires_nota_tecnica_aprobar_not_atinencia_verificar` |

## Supporting reference entities (not directly required by the SRS, needed as context)

| Entity | Status | Notes |
|---|---|---|
| Career (`carreras`) | IMPLEMENTED (plain model, read-only for this module) | Real official career names seeded |
| Course (`cursos`) | IMPLEMENTED (scoped: `carrera_id`, `codigo`, `nombre`, `activo` only) | Transversal/service courses out of scope |
| AcademicTerm (`periodos_academicos`) | IMPLEMENTED | `startDate` drives DO-02's date resolution |
| CourseGroup (`grupos`) | IMPLEMENTED (scoped: `curso_id`, `periodo_academico_id`, `numero` only) | `meta_id`/`modalidad_id`/`cupo` are bootstrap-only, owned by another module |
| `unidades_ejecutoras`/`metas`/`modalidades` | IMPLEMENTED (bootstrap only, no Eloquent model) | Exist only to satisfy `grupos`' mandatory FKs |

## NOT_APPLICABLE / out of this module's scope

| Item | Reason |
|---|---|
| `asignacion_cambios` (schedule/room change history) | Owned by a different, unbuilt module (room/schedule management) |
| `es_servicio`/`es_cuello_botella`/`requiere_laboratorio`/`tipo_laboratorio` on `cursos` | Curriculum/scheduling concerns, not affinity verification |
| `jornada`/`condicion_nombramiento`/`quincena`/`numero_accion_personal`/`observacion` on `asignaciones_docentes` | HR/payroll concerns owned by another module |
| Full JWT/TypeScript/external-REST-API stack requirements (SRS §3b) | Cross-module, project-wide technical requirements outside `IMPLEMENT_ACADEMIC_AFFINITIES.md`'s stated scope; not evaluated here |
