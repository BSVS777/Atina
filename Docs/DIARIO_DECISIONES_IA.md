# Diario de Decisiones IA

Registro de interacciones relevantes con IA durante el desarrollo: qué se consultó, qué se aceptó, qué se rechazó (y por qué), qué se tuvo que corregir, y qué se aprendió.

---

## 2026-07-30 — Setup inicial del repositorio

**Consulta:** generar `.gitignore` con todo lo que no es necesario versionar en GitHub.

**Aceptado:** ignorar directorios de tooling/config local de IA (`.claude/`, `.agents/`, `.atl/`, `.ai-harness/`) por no ser entregables del proyecto, más ruido estándar de OS/editor (`.DS_Store`, `Thumbs.db`, `*.swp`).

**Rechazado:** no se agregaron ignores específicos de stack (`node_modules/`, `target/`, `venv/`, etc.) porque todavía no hay código ni stack definido — se agregarán cuando se elija.

**Corregido:** nada, no hubo errores en esta iteración.

**Aprendido:** conviene mantener el `.gitignore` mínimo y ampliarlo recién cuando se sepa el stack, en vez de anticipar reglas especulativas.

---

## 2026-08-03 — Reglas permanentes de commits locales y diario de decisiones

**Consulta:** incorporar como regla permanente del harness que (1) cada implementación nueva relevante, aunque sea mínima, genere un commit local en inglés con formato Conventional Commits, sin push al remoto (el push lo controla el usuario), y (2) cada decisión de desarrollo con IA se documente en este diario con: qué se consultó, qué se aceptó, qué se rechazó y por qué, qué se corrigió por incorrecto/incompleto, y qué se aprendió.

**Aceptado:** ambas reglas se agregan a `AI_HARNESS.md` (fuente única, sección nueva "Commits y diario de decisiones" + refuerzo en Definition of Done).

**Rechazado:** no se automatiza con git hooks (pre-commit/post-commit) todavía, porque no hay stack ni gestor de paquetes definido en el repo — queda como disciplina del agente hasta que exista tooling que lo justifique.

**Corregido:** nada, no hubo errores en esta iteración.

**Aprendido:** `CLAUDE.md` y `GEMINI.md` usan `@AI_HARNESS.md` como referencia (un solo lugar para editar), pero `AGENTS.md` y `.github/copilot-instructions.md` son copias estáticas del mismo contenido sin mecanismo de sync en este repo — cualquier cambio a las reglas del harness debe replicarse manualmente en los tres archivos hasta que exista una herramienta de sincronización.

---

## 2026-08-03 — Scaffold de la aplicación Laravel (TALL) y arquitectura hexagonal/DDD

**Consulta:** scaffoldear la estructura base del proyecto: aplicación Laravel con el stack TALL y una organización hexagonal/DDD.

**Aceptado:**
- Motor de base de datos: MySQL 8.0 (decisión del usuario, entre MySQL y PostgreSQL — ambos ya estaban instalados y corriendo localmente).
- Starter kit oficial `--livewire` de `laravel new` (incluye Livewire + Tailwind v4 + Alpine vía Flux), sobre PHP 8.4 sirviendo desde Laravel Herd.
- Pest como framework de test (en vez de PHPUnit puro) específicamente porque `pestphp/pest-plugin-arch` da un gate de arquitectura nativo ("el dominio no importa Illuminate/Livewire/Flux") sin tener que escribir un analizador estático a mano — encaja con la regla de "Arquitectura Hexagonal + DDD" de `AI_HARNESS.md` §3.
- Separación física: `src/` (namespace `Atina\`, PSR-4 propio) para Domain + Application, framework-agnóstico; `app/` (namespace `App\`, ya provisto por Laravel) para toda la Infraestructura (Eloquent, Livewire, controllers, providers). Se prefirió esto sobre anidar `Domain/Application/Infrastructure` dentro de `app/App\Domain\...` porque la separación de carpetas raíz hace el límite arquitectónico visible de un vistazo y es lo que el test de arquitectura verifica.
- TypeScript: `AI_HARNESS.md` §3 lo exige y el starter kit Livewire no lo trae por defecto (a diferencia de los starter kits Vue/React). Se agregó `typescript` como devDependency, `tsconfig.json`, y se renombraron `resources/js/app.js`/`passkeys.js` a `.ts` (compilan y type-checean limpio con `tsc --noEmit`).

**Rechazado:**
- No se usó `laravel new .` directo sobre la raíz del repo — el instalador rechaza `--force` quan el target es el directorio actual. Se scaffoldeó en un directorio temporal y se hizo merge manual preservando `Docs/`, los archivos de harness y `.github/copilot-instructions.md`.
- No se instaló Laravel Boost (`--no-boost`): el repo ya tiene su propio harness de IA (`AI_HARNESS.md`); agregar otra capa de tooling de IA sin evaluarla primero violaba la regla de "no introducir una dependencia sin evaluar mantenimiento y alternativa".
- No se creó código de dominio real todavía (VOs, aggregate `Docente`, etc.) — el pedido era "estructura base"; la implementación de DO-01 queda para el siguiente slice. `src/Docencia/**/README.md` documenta qué va en cada carpeta.

**Corregido:** el primer intento de `laravel new . --force` falló ("Cannot use --force option when using current directory"); el segundo intento (`laravel new .atina_scaffold_tmp`) falló porque el instalador ejecuta `composer` como subproceso y el PATH de esta sesión de shell no tenía el bin de Herd — se corrigió exportando `PATH` con `~/.config/herd/bin` antes de invocar.

**Aprendido:** en Windows, Laravel Herd no expone `php`/`composer`/`laravel` como ejecutables sueltos en su carpeta `bin` — son `.bat` (`php.bat`, `composer.bat`, `laravel.bat`) más una copia real (`php84/php.exe`) y phars (`composer.phar`, `laravel.phar`). Git Bash no resuelve `.bat` vía `which`/PATHEXT automáticamente como sí lo hace `cmd.exe`, así que los comandos deben invocarse con paths explícitos o con el bin de Herd exportado al `PATH` de la sesión.

---

## 2026-08-03 — Schema compartido del profesor: implementación vía migraciones con SQL crudo

**Consulta:** durante el merge del scaffold apareció `sistema_gestion_academica_utn.sql` en la raíz del repo, sin trackear en git y sin que yo lo hubiera creado — se le preguntó al usuario de dónde salía antes de tocarlo o de decidir el nombre de la base de datos.

**Aceptado:** es el schema físico MySQL que el profesor entregó para los 5 módulos del sistema (RBAC, catálogos académicos, repositorio curricular, estudiantes, atinencias, docentes/atestados, oferta académica, reservas de aulas, solicitudes estudiantiles, gestión documental — ~35 tablas). El usuario indicó implementarlo completo y usarlo tal cual. Se extrajo, sin modificar el contenido, a `database/sql/schema_compartido.sql` (secciones 3-8; las secciones 1-2 —auth, cache, jobs, passkeys— ya las cubrían al detalle las migraciones por defecto del scaffold de Laravel, verificado comparando columna por columna) y `database/sql/seed_compartido.sql` (sección 9: roles, permisos, carreras, catálogos base), cada uno cargado por una única migración/seeder vía `DB::unprepared(File::get(...))`. El archivo original se movió a `Docs/sistema_gestion_academica_utn.sql` como referencia.

**Rechazado:** no se transcribió el schema a mano a los métodos fluidos de `Schema::create()` de Laravel (35 tablas, ENUMs, CHECK constraints, FKs con distintos `ON DELETE`). El riesgo de una discrepancia de un solo tipo de columna o constraint frente al schema real —compartido por 5 equipos que dependen de la misma base de datos— pesaba más que la idiomaticidad de usar el query builder de Laravel. Cargar el `.sql` verbatim garantiza fidelidad exacta con lo que entregó el profesor.

**Corregido:** los tests con `RefreshDatabase` fallaban contra sqlite in-memory (`SQLSTATE[HY000]: ... near "SET": syntax error`) porque el schema es SQL de MySQL puro (`BIGINT UNSIGNED AUTO_INCREMENT`, `ENGINE = InnoDB`, `ENUM`, `CHECK`) y no es válido en SQLite. Se cambió `phpunit.xml` para que los tests corran contra una base MySQL real y separada (`gestion_academica_utn_test`) en vez de `:memory:`, sacrificando la velocidad de sqlite in-memory a cambio de fidelidad total con el schema compartido — se consideró la corrección correcta dado que el objetivo es una base de datos física compartida entre 5 grupos, no solo velocidad de test.

**Aprendido:** el schema del profesor ya resuelve o corrige varias de las ambigüedades documentadas en `Docs/DUDAS_LOGICA_NEGOCIO.md` antes de preguntarle nada (p. ej. RN-01 tiene 5 grados académicos, no 4 — faltaba "Diplomado"; el rol se llama literalmente `Coordinadora de Docencia`; sí existe un rol `Docente` que se autoconsulta, contradiciendo la asunción T4 original). Se documentó la reconciliación completa en una sección nueva (`Docs/DUDAS_LOGICA_NEGOCIO.md` §7) en vez de reescribir las asunciones originales, para conservar el registro histórico de qué se pensaba antes de tener el schema real.

---

## 2026-08-04 — Implementación de DO-01-F1/F2/F3: CRUD de Atestados académicos

**Consulta:** "puedes proceder con los cruds" (retomando la sesión tras un `/clear`, sin contexto explícito de qué CRUDs). Antes de implementar, se le devolvieron dos preguntas de alcance al usuario en vez de asumir: (1) ¿CRUD de solo Atestados (lo que exige DO-01 según la matriz) o también Docentes/catálogos?, (2) ¿construir RBAC real (Role/Permission Eloquent + Gate) ahora, dado que no existía, o dejar el CRUD sin restricción de rol por ahora?

**Aceptado:**
- Alcance: solo Atestados académicos (alta/edición, sin baja — consistente con D2). El docente se trata como referencia externa ya existente (T3); su perfil se muestra en solo lectura.
- RBAC real ahora: se agregaron `App\Models\Role`/`Permission` (Eloquent sobre `roles`/`permissions`/`role_user`/`permission_role`/`permission_user`, ya sembradas por `GestionAcademicaUtnSeeder`) y `User::permisos()`/`tienePermiso()`. RN-04 se expresa en el dominio como el permiso `atestados.gestionar` (ya existente en el seed compartido, mapeado a Administrador y Coordinadora de Docencia) en vez de nombres de rol hardcodeados — el Gate `gestionar-atestados` es solo un adaptador delgado que traduce `User::permisos()` a `PoliticaAutorizacionAtestado::puedeGestionar()`.
- Arquitectura: dominio/aplicación puros bajo `src/Docencia/` (`AtestadoAcademico`, `AnioObtencion`, `Especialidad`, `PoliticaAutorizacionAtestado`, `AuditLogEntry`, casos de uso `RegistrarAtestadoAcademico`/`EditarAtestadoAcademico` con puertos `AtestadoRepository`/`AuditLogRepository`); adaptadores Eloquent en `app/Docencia/Repositories/`; UI en Livewire Blaze (`resources/views/pages/docencia/⚡docentes.blade.php`, `⚡docente-perfil.blade.php`) con modal Flux para alta/edición.
- Auditoría (DO-01-F3): la edición calcula un diff campo por campo y solo audita los campos que realmente cambiaron (no el registro completo), coherente con RN-05.

**Rechazado:**
- No se construyó CRUD de Docentes ni de catálogos (Puestos/Especialidades) — fuera del alcance de DO-01 según la matriz; puede pedirse como slice aparte.
- No se usó un `AuditLogEntry` por campo cambiado (una fila de auditoría por campo); se usó una fila por acción con un JSON `cambios` que mapea cada campo a `{anterior, nuevo}`, que es lo que realmente soporta la tabla física `auditorias` (`cambios JSON`, una fila por creación/modificación).

**Corregido:**
- Test propio: dos aserciones (`toBe`) sobre el JSON de `auditorias.cambios` fallaban porque MySQL reordena las claves de un objeto JSON en su formato de almacenamiento binario (por longitud de clave, no alfabético ni por orden de inserción) — se cambiaron a `toEqual` (comparación sin importar el orden), documentando el motivo en el propio test para que no se "arregle" mal en el futuro.
- Test propio: `Livewire::test(...)->call(...)` en una acción sin permiso no lanza `AuthorizationException` como excepción PHP capturable — Livewire deliberadamente deja que esa excepción puntual la maneje el handler HTTP de Laravel dentro de su ciclo de testing (`RequestBroker::temporarilyDisableExceptionHandlingAndMiddleware` excluye explícitamente `AuthorizationException`), así que el resultado correcto es una respuesta 403 sobre el objeto de test (`->assertForbidden()`), no una excepción propagada.
- Bug preexistente (no introducido en esta sesión, pero bloqueaba tener la suite completa en verde): `tests/Feature/Docencia/FactoriesSanityTest.php` creaba dos `Especialidad::factory()->create()` sin nombre explícito; como el vocabulario de esa factory es una lista fija de 8 nombres con `UNIQUE` en BD, había ~1/8 de probabilidad de colisión entre las dos llamadas dentro del mismo test. Se corrigió pasando `nombre` explícito y distinto a cada creación.
- `App\Models\User::permisos()` y `EloquentAuditLogRepository::registrar()` no pasaban Larastan nivel 7 (`request()?->ip()` sobre un tipo no-nullable; `Collection::values()->all()` no se infiere como `list<string>`). Se corrigió con `request()->ip()` directo y `array_values()` nativo sobre el array ya mapeado a `string`.

**Aprendido:** MySQL no garantiza el orden de las claves de un objeto JSON al persistirlo (las reordena por longitud en su formato binario interno) — cualquier código o test que dependa de `array_keys()`/serialización ordenada de una columna `JSON` leída de MySQL es frágil por diseño, hay que comparar por clave, no por orden. También: en Livewire (v4, con el mecanismo de testing basado en `RequestBroker`), las excepciones de autorización (`AuthorizationException`) y HTTP no se propagan como excepciones PHP en `Livewire::test()->call()` — se resuelven en una respuesta con status code, verificable con los métodos de `Illuminate\Testing\TestResponse` reenviados vía `__call` (`assertForbidden()`, `assertStatus()`, etc.), a diferencia de cualquier otra excepción de la aplicación que sí se propaga tal cual.

---
