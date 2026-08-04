# Bounded context: Docencia (Gestión Docente / Atinencias)

Namespace `Atina\Docencia\` (PSR-4 → `src/Docencia/`), separado de `App\` (Laravel).
Cubre DO-01 a DO-02d de `Docs/MATRIZ_REQUISITOS.md`.

## Regla de arquitectura (obligatoria, con gate automático)

Nada bajo `src/` puede importar `Illuminate\*`, `Livewire\*` ni `Flux\*`.
Verificado por `tests/Architecture/DomainNeverDependsOnFrameworkTest.php` (Pest Arch).

- `Domain/` y `Application/`: PHP puro, sin dependencias de framework.
- Los adaptadores (Eloquent, Livewire, controllers, providers) viven en `app/`,
  no aquí — `app/` es la capa de Infraestructura de este bounded context.

## Mapeo con el schema compartido

Las tablas de esta sección ya existen (creadas por el profesor, ver
`Docs/sistema_gestion_academica_utn.sql`, sección 5 y 6): `especialidades`,
`catalogos_atinencia`, `catalogo_atinencia_especialidad`, `puestos`, `docentes`,
`atestados`. Los adaptadores de persistencia en `app/` mapean sobre estas tablas
existentes — no se generan migraciones nuevas para ellas.
