# Dudas sobre la Lógica de Negocio — Módulo de Gestión Docente (Atinencias)

Fuente: `Docs/Enunciado/Proyecto_3_Gestion_Docente_Atinencias.docx`. Complementa `Docs/MATRIZ_REQUISITOS.md` §5 (esa sección tenía 4 asunciones; este documento las desarrolla y agrega el resto de dudas detectadas en los cinco requerimientos).

**Cómo usarlo**: cada duda que no se resuelva con el docente/cliente antes de cerrar el diseño se congela como "asunción de trabajo" (columna derecha) y queda documentada. Si la respuesta real difiere, se actualiza la matriz de requisitos y el diseño correspondiente — no se decide en silencio.

**Prioridad para preguntar primero**: D5, D12, D9, T1 — son las que más cambian el diseño y/o están explícitamente evaluadas por la rúbrica.

## 1. Transversales (afectan más de un requerimiento)

| ID | Duda | Por qué importa | Asunción de trabajo si no se resuelve |
|---|---|---|---|
| T1 | ¿Quién puede **consultar** (no modificar) perfiles de docente, catálogo y verificaciones? El enunciado solo restringe la modificación (DO-01) y la actualización del catálogo (DO-02) a Administrador/Coordinadora. | Si la consulta es libre para cualquier autenticado, el diseño de autorización es más simple; si también está restringida, hace falta una segunda política de lectura. | Consulta abierta a cualquier usuario autenticado del sistema; solo la escritura está restringida por rol. |
| T2 | ¿Existe una entidad "Cuatrimestre" centralizada (con fecha de inicio oficial) de la que DO-02/DO-02a dependen, o la fecha de inicio del cuatrimestre destino se ingresa libremente al proponer la asignación? | Si es una entidad de otro módulo, este módulo solo la consume (puerto de solo lectura); si no existe, hay que decidir quién la modela. | Se recibe como dato de entrada (fecha) en el caso de uso de verificación, sin modelar un `Cuatrimestre` propio — se asume que otro módulo del sistema la provee. |
| T3 | ¿El docente ya existe como usuario/persona en otro módulo del sistema (gestión de personal) y DO-01 solo administra sus atestados, o este módulo también da de alta al docente como tal? | El enunciado nunca describe un flujo de "crear docente", solo "registrar atestados de un docente" — sugiere que el docente ya existe. | El docente (cédula, nombre) es una referencia externa que ya existe; DO-01 solo persiste el aggregate de atestados asociado a esa cédula. |
| T4 | ¿Hay roles además de Administrador y Coordinadora de Docencia que participan en este módulo (p. ej. Director de Escuela, o el propio docente viendo su perfil)? | Cambia la matriz de autorización y las pantallas necesarias. | Solo Administrador y Coordinadora de Docencia operan sobre este módulo; no se modela un rol "Docente" que se auto-consulte. |

## 2. DO-01 — Atestados académicos

| ID | Duda | Por qué importa | Asunción de trabajo si no se resuelve |
|---|---|---|---|
| D1 | El "área de especialización" del atestado, ¿es el mismo vocabulario controlado que la "lista de grados/especialidades atinentes" del catálogo (DO-02), o son textos libres independientes comparados por otro criterio? | Es la base de la comparación de atinencia en DO-02a. Si son vocabularios distintos, la comparación no es determinística y falla el rubro de "correctitud de la máquina de estados". | Mismo Value Object/enumeración compartida entre atestado y entrada de catálogo (ver `Docs/MATRIZ_REQUISITOS.md` §6). |
| D2 | ¿Se pueden eliminar o desactivar atestados ingresados por error, o el flujo solo contempla alta y edición? | El enunciado solo menciona "registra o edita"; sin eliminación, un error de captura queda para siempre en el historial. | No hay eliminación; solo alta y edición. Un atestado incorrecto se corrige editando sus campos (y queda igual en auditoría). |
| D3 | ¿Un docente puede tener más de un atestado del mismo grado académico (p. ej. dos maestrías)? | Afecta si `GradoAcademico` es único por docente o si la lista permite repetidos. | Sí, se permite — el perfil es una lista, no un mapa por grado. |
| D4 | ¿Se audita también el **intento** de modificación rechazado por rol no autorizado, o solo la modificación efectiva? | El enunciado dice "toda modificación queda registrada", no menciona intentos rechazados. | Solo se audita la modificación efectiva; el rechazo se responde con error sin escribir en el audit log. |

## 3. DO-02 — Catálogo versionado

| ID | Duda | Por qué importa | Asunción de trabajo si no se resuelve |
|---|---|---|---|
| D5 | **La más crítica del documento.** Cuando no hay entrada vigente para la fecha del cuatrimestre destino, el enunciado dice "aplica la última entrada vigente disponible". ¿Eso significa la entrada cuya vigencia inicia más cerca (pero antes) de la fecha objetivo, o simplemente la versión más reciente creada, sin importar fechas? | La rúbrica califica explícitamente este caso límite como el que distingue "Excelente" de "Regular" en la máquina de estados. Ambas lecturas dan resultados distintos en escenarios reales. | Se interpreta como: la entrada con fecha de inicio de vigencia más reciente que sea **anterior o igual** a la fecha del cuatrimestre destino (no la versión más nueva por número, sino por fecha de vigencia). |
| D6 | Caso simétrico al de "vigencia futura": ¿qué pasa si la fecha del cuatrimestre destino es **anterior** a la vigencia más antigua registrada (todavía no existía ninguna entrada en esa fecha)? El enunciado no lo cubre. | Sin regla, el sistema no sabe qué hacer en ese caso — riesgo de excepción no controlada o de aplicar una entrada incorrecta. | Se trata igual que "sin entrada vigente": se aplica la entrada más antigua disponible y se etiqueta igual que el caso de vigencia futura (mismo mecanismo, mismo texto de advertencia, salvo que se decida un rótulo distinto). |
| D7 | ¿El sistema valida que dos versiones del mismo (carrera, curso) no tengan vigencias solapadas, o el Administrador puede crear entradas con fechas superpuestas? | Si se permiten solapamientos, DO-02a puede encontrar más de una entrada "vigente" para la misma fecha y no hay regla de desempate. | Se valida y se bloquea el guardado si la nueva vigencia se solapa con una vigencia existente del mismo par (carrera, curso). |
| D8 | Corregir una fecha de vigencia mal capturada en una entrada recién creada, ¿genera una nueva versión (como cualquier actualización) o se permite editar sin versionar mientras no haya verificaciones que ya la hayan usado? | El enunciado dice "cada actualización... crea una nueva versión" sin excepciones, pero eso puede generar versiones "ruido" por simples correcciones tipográficas. | Toda actualización crea versión nueva, sin excepción, tal como dice el enunciado — no se modela una vía de "corrección silenciosa". |

## 4. DO-02a — Verificación automática de atinencia

| ID | Duda | Por qué importa | Asunción de trabajo si no se resuelve |
|---|---|---|---|
| D9 | "Al menos un grado del docente figura en la entrada" — ¿la comparación es solo por **nivel** de grado (bachillerato/licenciatura/maestría/doctorado), o por nivel **+** área de especialización combinados? | Cambia completamente el algoritmo de matching y qué tan estricta es la atinencia. Depende directamente de D1. | Se compara la combinación (nivel + área de especialización) del docente contra la lista de grados/especialidades atinentes de la entrada — no alcanza con tener el nivel si el área no coincide. |
| D10 | Si el catálogo publica una nueva versión **después** de que una asignación ya fue verificada, ¿esa asignación existente se re-verifica automáticamente, o mantiene su resultado histórico? | El enunciado dice que toda verificación histórica "muestra la versión que se aplicó en su momento", lo cual sugiere que no se recalcula — pero no lo dice de forma explícita para este escenario. | Las verificaciones ya realizadas quedan con su resultado y versión de catálogo congelados; no se recalculan retroactivamente cuando aparece una nueva versión. |
| D11 | ¿El resultado de la verificación (y la versión de catálogo aplicada) se persiste como snapshot inmutable en el momento de verificar, o se recalcula cada vez que alguien consulta el historial? | Es condición necesaria para que D10 y el criterio de aceptación de DO-02 ("toda verificación histórica muestra la versión aplicada") sean ciertos. | Se persiste como snapshot inmutable en el momento de la verificación (resultado + referencia de versión de catálogo), no se recalcula al consultar. |

## 5. DO-02b — Nota técnica

| ID | Duda | Por qué importa | Asunción de trabajo si no se resuelve |
|---|---|---|---|
| D12 | **Marcada explícitamente por la rúbrica como falla común.** Cuando se inicia Nota técnica sobre una verificación "No Atinente" o "Sin catálogo", ¿el registro original de la verificación/asignación se **actualiza** (mismo registro cambia de estado a "Nota técnica — ratificación pendiente"), o se crea un registro de asignación nuevo en paralelo? | La rúbrica de Funcionalidad dice textualmente: "Regular = ... la Nota técnica no actualiza el resultado original de la verificación". Es un caso que se evalúa a propósito. | Es el **mismo registro de asignación** el que cambia de estado; no se crea una asignación paralela desconectada de la verificación original. |
| D13 | El enunciado describe el vencimiento automático por SLA, pero no describe la acción de **ratificación exitosa** por parte del Consejo Universitario: ¿quién la registra en el sistema y cómo se distingue de "vencida"? | Sin esta acción, una Nota técnica solo puede terminar en "vencida" — nunca en "ratificada", lo cual no parece ser la intención real del flujo. | Se agrega una acción manual de "Ratificar" ejecutable por Coordinadora/Administrador antes de la fecha límite, que cambia el estado a "Ratificada" y detiene el marcado automático de vencimiento. |
| D14 | Una Nota técnica marcada como "vencida" sin ratificación, ¿puede reabrirse con una nueva fecha límite, o el docente queda bloqueado de forma permanente para ese curso? | Afecta si hace falta un flujo de "reintento" o si "vencida" es un estado terminal. | Estado terminal: una vez vencida, la asignación queda bloqueada; para reintentar hay que iniciar un nuevo flujo de Nota técnica desde cero (nueva verificación). |

## 6. DO-02d — Asignaciones sin catálogo

| ID | Duda | Por qué importa | Asunción de trabajo si no se resuelve |
|---|---|---|---|
| D15 | Cuando finalmente se publica el catálogo de una carrera que antes no lo tenía, ¿se re-evalúan retroactivamente las asignaciones que ya fueron "Aprobadas" manualmente bajo "Sin catálogo"? | Riesgo de negocio real: una asignación aprobada a mano podría resultar "No Atinente" contra el catálogo recién publicado, y nadie se entera. | No se re-evalúan automáticamente; las decisiones manuales ya tomadas quedan firmes. Publicar el catálogo solo afecta asignaciones **nuevas** desde ese momento. |
| D16 | Si la Coordinadora **rechaza** la asignación sin catálogo, ¿qué pasa con el grupo (queda sin docente asignado, se notifica a alguien, se dispara otro flujo)? | El enunciado no lo describe — probablemente está fuera del alcance de este módulo, pero conviene confirmarlo para no asumir de más ni construir de menos. | Fuera de alcance de este módulo: el grupo queda sin docente asignado y la gestión de esa vacante la resuelve otro módulo/proceso manual. |

