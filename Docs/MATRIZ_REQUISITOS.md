# Matriz de Requisitos — Módulo de Gestión Docente (Atinencias)

Fuente: `Docs/Enunciado/Proyecto_3_Gestion_Docente_Atinencias.docx` (SRS adaptado FR-DO-01/02/02a/02b/02d).
Alcance de este documento: foco en **DO-01**, con trazabilidad de sus dependencias hacia DO-02/DO-02a (requeridas por la rúbrica de "completitud del módulo").

Schema físico compartido por los 5 módulos del sistema (entregado por el profesor, autoritativo): `Docs/sistema_gestion_academica_utn.sql`. Resuelve varias de las ambiguedades de `Docs/DUDAS_LOGICA_NEGOCIO.md` — ver su §8.

## 1. Requisitos funcionales

| ID | Requerimiento | Criterio de aceptación | Archivos/componentes (propuesto) | Test | Evidencia | Estado |
|---|---|---|---|---|---|---|
| DO-01-F1 | Almacenar atestados académicos del docente (grado, institución, año, área de especialización) | Perfil del docente muestra lista completa de atestados | `Docente` (aggregate), `AtestadoAcademico` (entity), `DocenteRepository` (puerto) | Unit: invariantes de `AtestadoAcademico`. Integration: repositorio Eloquent persiste y recupera | Captura de perfil con ≥2 atestados | No iniciado |
| DO-01-F2 | Restringir modificación de atestados a rol Administrador o Coordinadora de Docencia | Un rol distinto intentando modificar es rechazado | `PoliticaAutorizacionAtestado` (dominio), adaptador de auth (Laravel Guard → `Rol` VO) | Unit: política rechaza rol no autorizado. Feature: request con rol no autorizado → 403 | Log/captura de intento rechazado (403 + mensaje) | No iniciado |
| DO-01-F3 | Registrar auditoría de toda modificación (usuario, fecha, campo, valor anterior, valor nuevo) | Cada modificación queda en log de auditoría con esos 5 campos | `AuditLogEntry` (entity), `AuditLogRepository` (puerto) | Unit: `RegistrarAtestadoAcademico` emite entrada de auditoría correcta. Integration: fila persistida en BD | Query SQL/captura de la fila de auditoría | No iniciado |
| DO-01-F4 | Mostrar, en contexto de un curso, el resultado de evaluación de atinencia y la referencia del catálogo que lo justifica (depende de DO-02/DO-02a) | Perfil consultado en contexto de curso muestra resultado + cita (carrera, curso, versión, acuerdo) | `ConsultarPerfilDocenteEnContextoCursoUseCase`, puerto `EvaluacionAtinenciaPort` (implementación real llega con DO-02a; stub por ahora) | Contract test contra el puerto con doble de prueba (`Atinente`/`Sin catálogo`) | N/A hasta que DO-02a exista — documentar como pendiente explícito | Bloqueado por DO-02a (no iniciado) |
| DO-02 | Catálogo de atinencias versionado por (carrera, curso) | — | Fuera de este slice | — | — | No iniciado — dependencia de DO-01-F4 |
| DO-02a | Verificación automática de atinencia (4 resultados) | — | Fuera de este slice | — | — | No iniciado — dependencia de DO-01-F4 |
| DO-02b | Flujo de Nota técnica | — | Fuera de este slice | — | — | No iniciado |
| DO-02d | Gestión sin catálogo | — | Fuera de este slice | — | — | No iniciado |

## 2. Reglas de negocio (DO-01)

| Regla | Detalle | Dónde se aplica |
|---|---|---|
| RN-01 | Grados válidos: **diplomado**, bachillerato, licenciatura, maestría, doctorado (enumeración cerrada — corregido: el schema del profesor (`atestados.grado`) tiene 5 valores, no 4; el enunciado original omitía "Diplomado") | VO `GradoAcademico` |
| RN-02 | Área de especialización es obligatoria y debe ser comparable con el vocabulario del catálogo (DO-02) | VO `AreaEspecializacion` — **decisión de diseño compartida con DO-02**, ver §6 |
| RN-03 | Año de obtención no puede ser futuro ni implausible | VO `AñoObtencion` (validación de rango) |
| RN-04 | Solo Administrador o Coordinadora de Docencia modifican atestados | Política de autorización de dominio, no en el borde HTTP |
| RN-05 | Toda modificación (alta o edición) genera auditoría; los intentos rechazados por rol **no** generan auditoría según el enunciado (solo se audita la modificación efectiva) | Application service |

## 3. No-funcionales y restricciones técnicas (aplican al módulo completo, condición §3b del enunciado)

| Restricción | Detalle | Nota |
|---|---|---|
| Stack TALL | Tailwind + Alpine.js + Laravel + Livewire | Adaptadores de entrada (UI) |
| TypeScript | Obligatorio en frontend | No aplica al dominio |
| API REST externa | Al menos una, en algún módulo del proyecto | No necesariamente en DO-01; verificar con el equipo qué módulo la cubre |
| JWT | Autenticación por JWT | Definir en diseño si cubre solo la API propia o también el guard web (asunción, ver §5) |
| Variables de entorno | Config sensible fuera del código | Estándar Laravel `.env` |
| Pruebas unitarias | Básicas, con foco en riesgo | Ver columna Test de la matriz |
| Arquitectura Hexagonal + DDD | El paquete de dominio no puede importar Laravel/Livewire/Alpine | Gate de arquitectura — ver §6 |
| Repositorio Git documentado | Historial legible + README con instalación | Gestión de equipo, no de código |

## 4. Restricciones de proceso (condición §3)

- Equipo máximo 3 integrantes.
- 3 revisiones de avance (semanas 10, 12, 14) — requisito de admisibilidad, sin nota propia.
- Diario de decisiones técnicas e IA (10% de la nota) — debe documentar consultas, aceptación/rechazo con argumento técnico, al menos un error real de la IA detectado y corregido, y aprendizaje específico.
- Defensa oral: cualquier integrante debe poder explicar arquitectura, justificar reglas de negocio y resolver una modificación en vivo anticipando su impacto.

## 5. Ambigüedades y asunciones explícitas

| # | Ambigüedad | Asunción propuesta | Impacto si cambia |
|---|---|---|---|
| A1 | Nombre técnico exacto del rol "Coordinadora de Docencia" | Rol de sistema `coordinador_docencia` (neutro), mostrado en UI como "Coordinadora de Docencia" | Cosmético, bajo impacto |
| A2 | Alcance de JWT: ¿protege solo un endpoint API o también el login web? | JWT protege un endpoint API REST propio (expone perfil de docente); la UI Livewire usa el guard de sesión estándar de Laravel | Si el docente exige JWT en toda la app, cambia el adaptador de autenticación — confirmar en revisión de diseño |
| A3 | "Área de especialización" del docente vs. "grados/especialidades atinentes" del catálogo (DO-02): ¿mismo vocabulario controlado o texto libre comparado por igualdad? | Mismo VO/enumeración compartida entre `AtestadoAcademico` y la entrada de catálogo, para que la comparación de DO-02a sea determinística | Si son textos libres, la comparación de atinencia en DO-02a requiere normalización/fuzzy match — cambia el diseño del caso de uso de verificación |
| A4 | ¿Se audita el intento rechazado por rol no autorizado (DO-01-F2), o solo las modificaciones efectivas? | Solo se audita la modificación efectiva; el rechazo se responde con 403 sin escribir en el audit log (el enunciado solo pide auditar "modificación") | Si se requiere auditar también los rechazos, agrega un tipo de evento nuevo al audit log |

## 6. Decisión de diseño que debe tomarse ahora (no en DO-02)

`GradoAcademico` + `AreaEspecializacion` son el vocabulario que **DO-02a comparará** contra la lista de "grados/especialidades atinentes" del catálogo (DO-02). Si en DO-01 se modela como texto libre sin esta previsión, DO-02a queda con comparación ambigua y hay que retrabajar ambos módulos. Se modela como Value Object compartido desde DO-01, aunque su consumidor real (DO-02a) todavía no exista — no es especulación, es un contrato que el propio enunciado ya fija.

## 7. Trazabilidad con la rúbrica de Funcionalidad (§4.1 del enunciado)

| Rubro de rúbrica | Relación con DO-01 | Qué se audita en esta slice |
|---|---|---|
| Completitud del módulo (los 5 reqs conectados) | DO-01 es 1 de 5; esta slice sola **no puede alcanzar "Excelente"** en este rubro — depende de DO-02/02a/02b/02d | Documentar explícitamente que el slice es parcial por diseño |
| Correctitud de la máquina de estados | No aplica a DO-01 directamente (vive en DO-02a) | N/A — dejar constancia en la matriz (fila DO-02a) |
| Integración técnica obligatoria (stack completo) | Parcialmente cubierto por esta slice (Hexagonal/DDD, tests, parte de TALL) | JWT y API REST externa probablemente se resuelven en otro módulo/slice del equipo |

