# Guía para crear un CRUD nuevo en SIGA-UTN

> **Propósito de este documento.** Es la referencia viva y autoritativa para agregar
> cualquier CRUD nuevo al sistema. Claude debe leerlo e interiorizarlo **antes** de
> generar código para un módulo nuevo, y seguir estos lineamientos al pie de la letra.
> El patrón está probado con **Role** y **Permission**: cada paso señala el archivo real
> que se puede copiar y adaptar.
>
> **Ejemplo usado en toda la guía:** un CRUD hipotético de `Docente` (Teacher) dentro de
> un bounded context `Academic`. Reemplaza `Docente` / `docente` / `Academic` por tu
> entidad real.

## Índice

- [0. Lo que ya tienes gratis (no lo reconstruyas)](#0-lo-que-ya-tienes-gratis-no-lo-reconstruyas)
- [1. Arquitectura en una tabla](#1-arquitectura-en-una-tabla)
- [2. Bounded Contexts: cómo decidir dónde va cada entidad nueva](#2-bounded-contexts-cómo-decidir-dónde-va-cada-entidad-nueva)
- [3. Código en inglés, vistas en español: siempre](#3-código-en-inglés-vistas-en-español-siempre)
- [Paso 1: Generar el esqueleto](#paso-1-generar-el-esqueleto)
- [Paso 2: Sembrar los permisos del módulo](#paso-2-sembrar-los-permisos-del-módulo)
- [Paso 3: Capa de Dominio](#paso-3-capa-de-dominio)
- [Paso 4: Capa de Aplicación](#paso-4-capa-de-aplicación)
- [Paso 5: Infraestructura](#paso-5-infraestructura)
- [Paso 6: Ruta](#paso-6-ruta)
- [Paso 7: Policy](#paso-7-policy)
- [Paso 8: Form Object](#paso-8-form-object)
- [Paso 9: Livewire Component](#paso-9-livewire-component)
- [Paso 10: Vista Blade](#paso-10-vista-blade)
- [Paso 11: Sidebar](#paso-11-sidebar)
- [Paso 12: Traducciones](#paso-12-traducciones)
- [Paso 13: Verificación](#paso-13-verificación)
- [Errores comunes ya resueltos](#errores-comunes-ya-resueltos-para-no-volver-a-caer-en-ellos)
- [Resumen ultra-corto](#resumen-ultra-corto-para-cuando-ya-te-sepas-el-proceso)

---

## 0. Lo que ya tienes gratis (no lo reconstruyas)

Antes de empezar, ten claro qué es **100% reutilizable tal cual** y qué hay que escribir
a mano por cada CRUD nuevo.

### Reutilizable sin tocar nada

| Pieza | Archivo | Qué hace |
|---|---|---|
| Estado de tabla (paginación/orden/búsqueda) | `app/Livewire/Concerns/InteractsWithDataTable.php` | Trait: modo cliente/servidor, `refreshTable()`, `sort()`, `gotoPage()`, etc. |
| Store Alpine de tabla cliente | `resources/js/data-table.js` | Búsqueda/orden/paginación 100% en navegador, cero peticiones. |
| Shell de tabla | `resources/views/components/ui/data-table.blade.php` | Header, controles, paginación, dropdown de descarga. Recibe props, no sabe nada de tu entidad. |
| Íconos de fila | `resources/views/components/ui/row-actions.blade.php` | Editar/eliminar, llama `askDelete(id)`. |
| Modal crear/editar (shell) | `resources/views/components/ui/modal.blade.php` | Header/backdrop/footer genérico; tú solo llenas el slot con tus campos. |
| Modal de confirmación de borrado | `resources/views/components/ui/confirm-delete-modal.blade.php` | Flujo confirmar → éxito, estilo SweetAlert. |
| Todas las clases visuales | `resources/css/app.css` | `.card`, `.data-row`, `.status-badge`, `.action-icon`, `.btn*`, `.modal*`, `.pagination`, etc. |
| Comando de scaffolding | `app/Console/Commands/DDDStructure.php` | Genera la estructura de carpetas + clases base de las 4 capas. |

### Hay que escribir por cada CRUD (esto es lo que cubre esta guía)

1. Entidad de Dominio + Contrato + Excepciones (si aplica)
2. Permisos del módulo en el seeder
3. DTO + UseCases (Create/Update/Delete/Find/List)
4. Modelo Eloquent (si la entidad es nueva) + Repositorio Eloquent
5. Policy
6. Form Object (Livewire)
7. Livewire Component
8. Vista Blade
9. Ruta
10. Entrada en el sidebar
11. Traducciones

---

## 1. Arquitectura en una tabla

```text
src/{Context}/{Entity}/
├── Domain/
│   ├── Entities/{Entity}.php          ← PHP puro, sin Eloquent, sin Illuminate
│   ├── Contracts/{Entity}RepositoryInterface.php
│   ├── Exceptions/                     ← opcional, solo con invariantes
│   └── ValueObjects/                   ← opcional, casi nunca hace falta
├── Application/
│   ├── DTOs/{Entity}DTO.php
│   └── UseCases/
│       ├── Create{Entity}UseCase.php
│       ├── Update{Entity}UseCase.php
│       ├── Delete{Entity}UseCase.php
│       ├── Find{Entity}UseCase.php
│       └── List{Entity}sUseCase.php    ← no la genera el scaffold (Paso 4)
├── Infrastructure/
│   └── Persistence/Repositories/Eloquent{Entity}Repository.php
└── Presentation/
    ├── Livewire/
    │   ├── {Entity}Component.php
    │   └── Forms/{Entity}Form.php
    ├── Policies/{Entity}Policy.php
    └── Routes/web.php
```

> **Regla de oro:** el Dominio nunca importa Eloquent ni Illuminate. Solo Infrastructure
> conoce el ORM.

---

## 2. Bounded Contexts: cómo decidir dónde va cada entidad nueva

Esto es lo primero que hay que decidir, **antes** de correr el comando de scaffolding. Es
también la pregunta que más confusión genera, así que conviene explicarla bien.

### Por qué Role y Permission viven en el mismo contexto (`IdentityAccess`)

Un bounded context **no es "una carpeta por entidad"**: es una agrupación de entidades que
comparten el mismo lenguaje de negocio y que casi siempre cambian juntas. Role y Permission
son, en la práctica, **dos caras de la misma pregunta** ("¿quién puede hacer qué?"):

- Comparten vocabulario (`module`, `action`, `permission`, `role`).
- Se referencian todo el tiempo entre sí: un Role *tiene* Permissions, y el modal de Role
  necesita leer el catálogo completo de Permission para armar el checklist
  (`RoleComponent::permissionCatalog()`, que lee `ListPermissionsUseCase`, una lectura
  cruzada entre contextos, documentada y aceptada explícitamente porque ambos viven bajo el
  mismo paraguas de `IdentityAccess`).
- Un cambio típico (agregar un módulo nuevo) siempre toca ambas entidades a la vez: se crean
  los Permissions del módulo (Paso 2) **y** alguien tiene que asignárselos a un Role.

Por eso comparten `src/IdentityAccess/`, aunque cada una tenga su propia carpeta de 4 capas
completa. `src/IdentityAccess/Role/...` y `src/IdentityAccess/Permission/...` son
independientes entre sí: el contexto solo las agrupa, no las fusiona.

### Cómo aplicar esto a un contexto nuevo, como `Academic`

Cuando el sistema empiece a construir el módulo académico real (Docentes, Aulas, Cursos,
Grupos, Oferta académica), **todas esas entidades van dentro del mismo contexto**
`Academic`. Son la misma familia de conceptos (todas describen "cómo se organiza la
actividad académica"), comparten reglas relacionadas (un Grupo pertenece a un Curso, un
Curso lo dicta un Docente, un Docente usa un Aula) y es razonable que cambien juntas.

```text
src/
├── IdentityAccess/
│   ├── Role/          ← Domain/Application/Infrastructure/Presentation completas
│   └── Permission/    ← Domain/Application/Infrastructure/Presentation completas
│
└── Academic/
    ├── Docente/       ← Domain/Application/Infrastructure/Presentation completas
    ├── Aula/          ← Domain/Application/Infrastructure/Presentation completas
    ├── Curso/         ← Domain/Application/Infrastructure/Presentation completas
    └── Grupo/         ← Domain/Application/Infrastructure/Presentation completas
```

Cada entidad sigue siendo su propio módulo independiente (su propio Contrato, su propio
Repositorio, su propio Component). El contexto solo dice "estas entidades hablan el mismo
idioma de negocio"; no las mezcla en una sola carpeta ni comparte Repository entre ellas.

### Regla práctica para decidir

Antes de correr `make:ddd`, pregúntate:

- **¿Comparten vocabulario y reglas de negocio?** (Docente/Aula/Curso: sí, todo es
  "gestión académica")
- **¿Se leen o modifican juntas con frecuencia?** (como Role leyendo el catálogo de
  Permission)
- **¿Un cambio de negocio típico tocaría a ambas a la vez?**

Si la respuesta es sí a la mayoría → mismo contexto, entidades hermanas
(`src/Academic/Docente/`, `src/Academic/Aula/`). Si una entidad es conceptualmente
independiente aunque esté relacionada de lejos (por ejemplo, `Riesgos`, alertas de riesgo
académico, bien podría ser su **propio** contexto `AcademicRisk`, porque su ciclo de vida,
sus reglas y quién las mantiene son distintos a la gestión de Docentes/Aulas/Cursos) →
contexto separado.

```bash
# Docente y Aula, mismo contexto Academic, cada uno su propio comando:
php artisan make:ddd Academic Docente --livewire --policy
php artisan make:ddd Academic Aula --livewire --policy
```

---

## 3. Código en inglés, vistas en español: siempre

Esta regla no tiene excepción en ningún archivo. Recordatorio rápido con el ejemplo de esta
guía:

**Código (100% inglés):** nombres de clase, método, variable, propiedad, comentario,
mensaje de excepción interno.

```php
class DocenteComponent extends Component
{
    public ?int $editingId = null;

    public function openCreateModal(): void   // inglés, siempre
    {
        // ...
    }
}
```

**Texto visible al usuario (siempre vía `__()`, nunca hardcodeado):** la clave de
traducción se escribe en inglés, el valor en `es.json` en español.

```blade
<button wire:click="openCreateModal">{{ __('Add') }}</button>
```

```json
{ "Add": "Agregar" }
```

Ningún string visible al usuario final va directo en español en el Blade. Siempre pasa por
`__('...')`, y la traducción vive únicamente en `lang/es.json`. Esto es lo que nos permitió,
por ejemplo, cambiar el título de "Roles" a "Gestión de roles" en un solo lugar (Paso 12)
sin tocar ni un archivo de código.

---

## Paso 1: Generar el esqueleto

```bash
php artisan make:ddd Academic Docente --livewire --policy
```

- `--livewire` genera el componente base en `Presentation/Livewire/`.
- `--policy` genera la Policy y la cablea automáticamente en `DomainServiceProvider`.
- **No uses `--api`** a menos que este CRUD necesite de verdad un endpoint REST además de
  la UI. Nuestra app es 100% Livewire; ese flag existe para el día que haga falta, no por
  defecto (ver `DDDStructure.php`).
- Si la entidad tiene reglas de negocio que puedan violarse (como "no se puede borrar un
  rol protegido"), agrega también `--exception`.

Esto crea las carpetas y las 5 piezas base (Entity, Contract, Repository, DTO, Create
UseCase). Los pasos siguientes completan lo que el scaffold **no** genera por defecto
(permisos sembrados, list/paginación, Form, vista, ruta).

---

## Paso 2: Sembrar los permisos del módulo

Antes de escribir una sola línea de las capas de Dominio/Aplicación, asegúrate de que los
permisos que tu Policy y tu UI van a necesitar **existan en la base de datos**. El patrón
ya está armado para que esto sea trivial. Revisa `database/seeders/PermissionSeeder.php`:

```php
class PermissionSeeder extends Seeder
{
    private const ACTIONS = [
        'create', 'view', 'edit', 'delete', 'search', 'export_pdf', 'export_excel',
    ];

    // Extend this list as new manageable modules are added.
    private const MODULES = ['roles', 'permissions'];

    public function run(): void
    {
        foreach (self::MODULES as $module) {
            foreach (self::ACTIONS as $action) {
                Permission::query()->firstOrCreate(
                    ['name' => "{$module}.{$action}"],
                    ['module' => $module, 'action' => $action],
                );
            }
        }
    }
}
```

Todo lo que hay que hacer es **agregar el nombre de tu módulo al array `MODULES`**:

```php
private const MODULES = ['roles', 'permissions', 'docentes'];
```

Esto genera automáticamente las 7 combinaciones (`docentes.create`, `docentes.view`,
`docentes.edit`, `docentes.delete`, `docentes.search`, `docentes.export_pdf`,
`docentes.export_excel`): exactamente las que la Policy (Paso 7) y las vistas van a
referenciar por nombre.

**Qué pasa con los roles al correr el seeder de nuevo:** `RoleSeeder.php` sincroniza
**todos** los permisos existentes al rol Superadmin cada vez que corre:

```php
$superadmin->permissions()->sync(Permission::query()->pluck('id'));
```

Como es un `sync()` (no un `attach()`), es idempotente y siempre deja a Superadmin con el
100% de los permisos actuales, incluidos los nuevos de `docentes.*` que acabas de agregar,
sin tocar `RoleSeeder.php` para nada. El rol `Admin`, en cambio, se crea sin ningún permiso
por diseño (así arranca en el sistema hoy). Si tu módulo debe estar disponible para Admin,
se le asignan permisos manualmente desde la propia UI de Roles una vez que el CRUD esté
funcionando, no desde el seeder.

Corre:

```bash
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RoleSeeder
```

(o `php artisan migrate:fresh --seed` si estás reconstruyendo la base completa en
desarrollo).

---

## Paso 3: Capa de Dominio

### 3.1 Entidad (`Domain/Entities/Docente.php`)

PHP puro. Ejemplo mínimo (calca la forma, no el contenido, de `Role.php`):

```php
<?php

declare(strict_types=1);

namespace Src\Academic\Docente\Domain\Entities;

final class Docente
{
    private function __construct(
        private readonly ?int $id,
        private string $name,
        private string $email,
    ) {}

    public static function create(string $name, string $email): self
    {
        return new self(id: null, name: $name, email: $email);
    }

    public static function reconstitute(int $id, string $name, string $email): self
    {
        return new self(id: $id, name: $name, email: $email);
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }

    public function id(): ?int { return $this->id; }
    public function name(): string { return $this->name; }
    public function email(): string { return $this->email; }
}
```

Si hay una regla de negocio real (como "Superadmin no se puede borrar"), va **dentro** de
la entidad, lanzando una excepción de dominio propia. Ve `Role.php::rename()` +
`RoleIsProtectedException`.

### 3.2 Contrato (`Domain/Contracts/DocenteRepositoryInterface.php`)

**Importante:** el stub base solo genera `find`/`save`/`delete`. Para que este CRUD tenga
tabla con búsqueda, agrega `all()` **y** `paginate()` a mano: es el mismo contrato exacto
que ya usan Role y Permission.

```php
<?php

declare(strict_types=1);

namespace Src\Academic\Docente\Domain\Contracts;

use Src\Academic\Docente\Domain\Entities\Docente;

interface DocenteRepositoryInterface
{
    public function find(int $id): ?Docente;

    /** @return array<int, Docente> */
    public function all(?string $sortBy = null, string $sortDir = 'asc'): array;

    /** @return array{items: array<int, Docente>, total: int} */
    public function paginate(?string $search, int $perPage, int $page, ?string $sortBy = null, string $sortDir = 'asc'): array;

    public function save(Docente $docente): Docente;

    public function delete(int $id): void;
}
```

---

## Paso 4: Capa de Aplicación

### 4.1 DTO (`Application/DTOs/DocenteDTO.php`)

```php
<?php

declare(strict_types=1);

namespace Src\Academic\Docente\Application\DTOs;

final class DocenteDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}
```

### 4.2 UseCases

`Create`/`Update`/`Delete`/`Find` siguen el patrón que ya generó el scaffold (uno por
archivo, un método `handle()`). El que **falta agregar a mano** es el de listado dual:
copia `ListRolesUseCase.php` tal cual, cambiando el tipo.

```php
<?php

declare(strict_types=1);

namespace Src\Academic\Docente\Application\UseCases;

use Src\Academic\Docente\Domain\Contracts\DocenteRepositoryInterface;

final class ListDocentesUseCase
{
    public function __construct(
        private readonly DocenteRepositoryInterface $repository,
    ) {}

    /** @return array<int, \Src\Academic\Docente\Domain\Entities\Docente> */
    public function all(?string $sortBy = null, string $sortDir = 'asc'): array
    {
        return $this->repository->all($sortBy, $sortDir);
    }

    /** @return array{items: array, total: int} */
    public function paginate(
        ?string $search = null,
        int $perPage = 10,
        int $page = 1,
        ?string $sortBy = null,
        string $sortDir = 'asc',
    ): array {
        return $this->repository->paginate($search, $perPage, $page, $sortBy, $sortDir);
    }
}
```

---

## Paso 5: Infraestructura

### 5.1 Modelo Eloquent

Si `Docente` es una entidad nueva, necesita su migración + modelo Eloquent en
`app/Models/Docente.php` (fuera de `src/`: los modelos Eloquent viven siempre en
`App\Models`, nunca dentro del bounded context).

**No olvides los `@property-read` para relaciones.** Si tu entidad tiene relaciones
(`belongsToMany`, etc.), documenta el docblock igual que hicimos en
`Role.php`/`Permission.php`, o PHPStan va a fallar con `property.notFound` la primera vez
que accedas a la relación como propiedad mágica (`$model->algunaRelacion`).

### 5.2 Repositorio Eloquent

Copia `EloquentRoleRepository.php`. Los puntos que ya nos costaron una ronda de debugging,
para que no los repitas:

- En `all()`, si haces
  `Model::query()->with(...)->orderBy(...)->get()->map($this->toDomain(...))`, PHPStan a
  veces infiere `Collection<int,stdClass>` en vez de tu modelo. Fuerza el tipo con un
  `@var` explícito antes del `->map()`:

  ```php
  /** @var \Illuminate\Database\Eloquent\Collection<int, DocenteModel> $models */
  $models = DocenteModel::query()->orderBy($column, $direction)->get();
  return $models->map($this->toDomain(...))->all();
  ```

- Usa `DocenteModel::query()->create(...)`, nunca `DocenteModel::create(...)` a secas: la
  forma estática corta a veces no la reconoce Larastan (nos pasó con `User::create()` en
  `CreateNewUser.php`).

---

## Paso 6: Ruta

`Presentation/Routes/web.php` (el scaffold no lo genera salvo `--api`, y ese flag trae
Controller que no queremos):

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Academic\Docente\Presentation\Livewire\DocenteComponent;

Route::middleware(['web', 'auth', 'verified'])
    ->get('docentes', DocenteComponent::class)
    ->name('academic.docente.index');
```

Se autocarga solo: `DomainServiceProvider` ya escanea
`src/*/*/Presentation/Routes/web.php` vía `glob()`, no hay que registrar nada más.

---

## Paso 7: Policy

El scaffold ya generó el archivo base con `--policy`. Complétalo con los métodos que uses
(`viewAny`, `create`, `update`, `delete`, `exportPdf`, `exportExcel`), todos delegando a
`hasPermissionTo('docentes.{accion}')`. Copia `RolePolicy.php` como plantilla exacta. Estos
son exactamente los nombres de permiso que sembraste en el Paso 2, así que deben coincidir
letra por letra.

---

## Paso 8: Form Object

Copia `RoleForm.php`. **Dos cosas que rompen si no las respetas:**

1. El método de hidratación se llama `fromEntity()`, **nunca** `fill()`: `Livewire\Form` ya
   declara su propio `fill(mixed $values)` para otra cosa; sobreescribirlo con un tipo más
   estricto es un error fatal de PHP (incompatibilidad de covarianza), no solo un aviso de
   PHPStan.

2. Para la unicidad, usa `Rule::unique('tabla', 'columna')->ignore($component->editingId)`,
   leyendo `editingId` así:

   ```php
   public function rules(): array
   {
       /** @var \Src\Academic\Docente\Presentation\Livewire\DocenteComponent $component */
       $component = $this->component;

       return [
           'email' => ['required', 'email', Rule::unique('docentes', 'email')->ignore($component->editingId)],
       ];
   }
   ```

Plantilla completa:

```php
<?php

declare(strict_types=1);

namespace Src\Academic\Docente\Presentation\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;
use Src\Academic\Docente\Application\DTOs\DocenteDTO;
use Src\Academic\Docente\Domain\Entities\Docente;
use Src\Academic\Docente\Presentation\Livewire\DocenteComponent;

class DocenteForm extends Form
{
    public string $name = '';
    public string $email = '';

    public function rules(): array
    {
        /** @var DocenteComponent $component */
        $component = $this->component;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('docentes', 'email')->ignore($component->editingId)],
        ];
    }

    public function fromEntity(Docente $docente): void
    {
        $this->name = $docente->name();
        $this->email = $docente->email();
    }

    public function toDto(): DocenteDTO
    {
        return new DocenteDTO(name: $this->name, email: $this->email);
    }
}
```

---

## Paso 9: Livewire Component

Este es el archivo más largo, pero es 95% copiar `RoleComponent.php` y cambiar nombres.
Plantilla completa:

```php
<?php

declare(strict_types=1);

namespace Src\Academic\Docente\Presentation\Livewire;

use App\Livewire\Concerns\InteractsWithDataTable;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Src\Academic\Docente\Application\UseCases\CreateDocenteUseCase;
use Src\Academic\Docente\Application\UseCases\DeleteDocenteUseCase;
use Src\Academic\Docente\Application\UseCases\FindDocenteUseCase;
use Src\Academic\Docente\Application\UseCases\ListDocentesUseCase;
use Src\Academic\Docente\Application\UseCases\UpdateDocenteUseCase;
use Src\Academic\Docente\Domain\Entities\Docente;
use Src\Academic\Docente\Presentation\Livewire\Forms\DocenteForm;

class DocenteComponent extends Component
{
    use AuthorizesRequests;
    use InteractsWithDataTable;

    // 'client' para catálogos pequeños (hasta unos cientos de filas);
    // 'server' si esta entidad va a tener miles de registros.
    protected string $tableMode = 'client';

    public bool $showModal = false;
    public ?int $editingId = null;
    public DocenteForm $form;

    public function mount(): void
    {
        $this->authorize('viewAny', Docente::class);
        // El sortKey por defecto SIEMPRE se asigna aquí, NUNCA como
        // propiedad de clase: redeclarar una propiedad del trait con un
        // valor por defecto distinto es un error fatal de composición
        // de PHP (nos pasó dos veces, ver sección de errores comunes).
        $this->sortKey = 'name';
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Docente::class);
        $this->editingId = null;
        $this->form->reset();
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEditModal(int $id, FindDocenteUseCase $useCase): void
    {
        $this->authorize('update', Docente::class);
        $docente = $useCase->handle($id);
        $this->editingId = $id;
        $this->form->fromEntity($docente);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function save(CreateDocenteUseCase $createUseCase, UpdateDocenteUseCase $updateUseCase, ListDocentesUseCase $listUseCase): void
    {
        $this->form->validate();

        if ($this->editingId === null) {
            $this->authorize('create', Docente::class);
            $createUseCase->handle($this->form->toDto());
        } else {
            $this->authorize('update', Docente::class);
            $updateUseCase->handle($this->editingId, $this->form->toDto());
        }

        $this->showModal = false;
        $this->refreshTable($this->freshRows($listUseCase));
        $this->dispatch('toast', variant: 'success', text: $this->editingId === null
            ? __('Teacher created.')
            : __('Teacher updated.'));
    }

    // Nombre del método SIEMPRE "delete": <x-ui.confirm-delete-modal>
    // llama $wire.delete(id) a ciegas, es la convención que lo hace reusable.
    public function delete(int $id, DeleteDocenteUseCase $useCase, ListDocentesUseCase $listUseCase): void
    {
        $this->authorize('delete', Docente::class);
        $useCase->handle($id);
        $this->refreshTable($this->freshRows($listUseCase));
        $this->dispatch('toast', variant: 'success', text: __('Teacher deleted.'));
    }

    public function exportPdf(): void
    {
        $this->authorize('exportPdf', Docente::class);
        $this->dispatch('toast', variant: 'info', text: __('Export coming soon.'));
    }

    public function exportExcel(): void
    {
        $this->authorize('exportExcel', Docente::class);
        $this->dispatch('toast', variant: 'info', text: __('Export coming soon.'));
    }

    public function render(ListDocentesUseCase $useCase): View
    {
        $view = $this->isServerMode()
            ? $this->renderServerMode($useCase)
            : $this->renderClientMode($useCase);

        /** @disregard P1013 Livewire registra ->layout() como macro en runtime */
        return $view->layout('components.layouts.dashboard', [
            'title' => __('Teachers'),
            'subtitle' => __('Active teaching staff and their academic load'),
        ]);
    }

    private function renderClientMode(ListDocentesUseCase $useCase): View
    {
        return view('academic.docente.livewire.docente-component', [
            'tableMode' => 'client',
            'rows' => $this->freshRows($useCase),
        ]);
    }

    private function renderServerMode(ListDocentesUseCase $useCase): View
    {
        $result = $useCase->paginate(
            search: $this->search !== '' ? $this->search : null,
            perPage: $this->perPage,
            page: $this->page,
            sortBy: $this->sortKey,
            sortDir: $this->sortDir,
        );

        $paginator = new LengthAwarePaginator(
            items: $result['items'],
            total: $result['total'],
            perPage: $this->perPage,
            currentPage: $this->page,
        );

        return view('academic.docente.livewire.docente-component', [
            'tableMode' => 'server',
            'docentes' => $paginator,
        ]);
    }

    /** @return array<string, mixed> */
    private function toRow(Docente $docente): array
    {
        return [
            'id' => $docente->id(),
            'name' => $docente->name(),
            'email' => $docente->email(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function freshRows(ListDocentesUseCase $useCase): array
    {
        return array_map($this->toRow(...), $useCase->all(sortBy: $this->sortKey, sortDir: $this->sortDir));
    }
}
```

---

## Paso 10: Vista Blade

Ruta: `resources/views/academic/docente/livewire/docente-component.blade.php` (el punto en
`view('academic.docente.livewire.docente-component')` se traduce a carpetas; Laravel espera
esa ruta exacta).

Estructura completa (copia `role-component.blade.php` y adapta columnas/campos):

```blade
<div x-data="{
    confirmDelete: { open: false, step: 'confirm', id: null },
    askDelete(id) {
        this.confirmDelete = { open: true, step: 'confirm', id };
    },
    runDelete() {
        $wire.delete(this.confirmDelete.id)
            .then(() => { this.confirmDelete.step = 'success'; })
            .catch(() => { this.confirmDelete.open = false; });
    },
    closeDeleteModal() {
        this.confirmDelete.open = false;
    },
}">
    <x-ui.data-table
        :headers="[
            ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
            ['key' => 'email', 'label' => __('Email'), 'sortable' => true],
        ]"
        :mode="$tableMode"
        :rows="$rows ?? []"
        :searchable="['name', 'email']"
        :paginator="$docentes ?? null"
        :sort-key="$sortKey"
        :sort-dir="$sortDir"
        :per-page="$perPage"
        table-cols="2fr 2fr 1fr"
        :can-create="Auth::user()->can('create', \Src\Academic\Docente\Domain\Entities\Docente::class)"
        :can-export="Auth::user()->can('exportPdf', \Src\Academic\Docente\Domain\Entities\Docente::class)"
        :title="__('Teachers management')">

        @if ($tableMode === 'client')
            <template x-for="row in pageRows" :key="row.id">
                <div class="data-row" role="row">
                    <span x-text="row.name"></span>
                    <span x-text="row.email"></span>
                    <div class="actions-cell">
                        <x-ui.row-actions
                            :can-edit="Auth::user()->hasPermissionTo('docentes.edit')"
                            :can-delete="Auth::user()->hasPermissionTo('docentes.delete')"
                            edit-action="$wire.openEditModal(row.id)"
                            delete-id="row.id" />
                    </div>
                </div>
            </template>
            <div class="empty-row" x-show="pageRows.length === 0">{{ __('No records found') }}</div>
        @else
            @forelse ($docentes as $docente)
            <div class="data-row" role="row">
                <span>{{ $docente->name() }}</span>
                <span>{{ $docente->email() }}</span>
                <div class="actions-cell">
                    <x-ui.row-actions
                        :can-edit="Auth::user()->can('update', $docente)"
                        :can-delete="Auth::user()->can('delete', $docente)"
                        edit-action="$wire.openEditModal({{ $docente->id() }})"
                        delete-id="{{ $docente->id() }}" />
                </div>
            </div>
            @empty
            <div class="empty-row">{{ __('No records found') }}</div>
            @endforelse
        @endif
    </x-ui.data-table>

    <x-ui.modal :show="$showModal" :title="$editingId === null ? __('New teacher') : __('Edit teacher')">
        <div class="form-field">
            <label for="docenteName">{{ __('Name') }}</label>
            <input type="text" id="docenteName" wire:model="form.name" class="{{ $errors->has('form.name') ? 'has-error' : '' }}">
            @error('form.name') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-field">
            <label for="docenteEmail">{{ __('Email') }}</label>
            <input type="text" id="docenteEmail" wire:model="form.email" class="{{ $errors->has('form.email') ? 'has-error' : '' }}">
            @error('form.email') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeModal">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-primary" wire:click="save">{{ __('Confirm') }}</button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm-delete-modal :success-text="__('The teacher has been deleted.')" />
</div>
```

**No envuelvas esto en `<div class="max-w-6xl mx-auto">` ni nada con `max-w-*`.**
`<x-ui.data-table>` ya es 100% ancho por diseño (`.card { width: 100% }`). Envolverlo así
angosta la tabla sin motivo: nos pasó y hubo que corregirlo.

---

## Paso 11: Sidebar

En `sidebar_blade.php`, agrega el link siguiendo **exactamente** el patrón de
Roles/Permisos, nunca el patrón de las secciones que aún no existen (Oferta académica,
etc.):

```blade
<a href="{{ route('academic.docente.index') }}" wire:navigate wire:current="active" class="nav-item">
    <svg>...</svg>
    <span class="nav-text" data-labels>{{ __('Teachers') }}</span>
</a>
```

**Por qué `wire:current="active"` y no `:class="{ active: currentSection === 'x' }"` ni
`request()->routeIs(...)`:** el sidebar está persistido con `x-persist="sidebar"`
(necesario para que no se rompa el alto completo, ver errores comunes más abajo). Dentro de
un elemento persistido, los condicionales de servidor (`request()->routeIs()`) se congelan
la primera vez que se renderizan y nunca se vuelven a evaluar. `wire:current` es la única
forma que Livewire ofrece de resaltar el link activo correctamente dentro de contenido
persistido, porque lo resuelve en el cliente comparando contra la URL real en cada
navegación.

Si el CRUD tiene subitems tipo acordeón (como Grupos/Reportes), copia ese patrón completo
incluyendo `x-on:livewire:navigated.window="open = false"` para que no se quede pegado
abierto al navegar a otra página real.

---

## Paso 12: Traducciones

Agrega a `lang/es.json` (mínimo indispensable, agrega las que tu CRUD necesite):

```json
{
  "Teachers": "Docentes",
  "Teachers management": "Gestión de docentes",
  "Active teaching staff and their academic load": "Personal docente activo y su carga académica",
  "New teacher": "Nuevo docente",
  "Edit teacher": "Editar docente",
  "Teacher created.": "Docente creado.",
  "Teacher updated.": "Docente actualizado.",
  "Teacher deleted.": "Docente eliminado.",
  "The teacher has been deleted.": "El docente ha sido eliminado."
}
```

Las claves genéricas (`Name`, `Actions`, `Show`, `Search`, `Cancel`, `Confirm`,
`Are you sure?`, etc.) ya existen; no las repitas. Recuerda: la clave siempre en inglés, el
valor en español (Sección 3).

---

## Paso 13: Verificación

1. `composer run types:check`: debe quedar en 0 errores.
2. Prueba manual mínima:
   - Listar carga sin errores en consola del navegador.
   - Buscar filtra sin peticiones de red (pestaña Network del navegador).
   - Crear → aparece en la tabla **sin refrescar la página**.
   - Editar → los datos correctos se precargan en el modal.
   - Eliminar → modal de confirmación → fila desaparece **sin refrescar**.
   - Alternar dark mode → todo (badges, íconos, modal) se ve bien en ambos temas.
   - Sidebar: el link se resalta correcto y el título del topbar coincide con la página.
   - Como Superadmin, confirma que los permisos `docentes.*` nuevos aparecen listados en la
     pantalla de Permisos.

---

## Errores comunes ya resueltos (para no volver a caer en ellos)

| Síntoma | Causa | Fix |
|---|---|---|
| Error fatal "define the same property" al arrancar | Redeclaraste `$sortKey` (u otra propiedad del trait) como propiedad de clase con un valor por defecto distinto | Nunca redeclares propiedades del trait; asígnalas dentro de `mount()` |
| `method.childParameterType` en PHPStan sobre `fill()` | `Livewire\Form` ya tiene su propio `fill(mixed $values)` | Nombra tu método de hidratación `fromEntity()`, no `fill()` |
| La tabla se ve apilada en vez de en columnas | Tu clase CSS choca de nombre con una utilidad real de Tailwind (nos pasó con `table-row`) | Revisa que tus clases custom no coincidan con ninguna utilidad de Tailwind antes de usarlas |
| El sidebar no llega hasta abajo | `@persist('sidebar')...@endpersist` envuelve el contenido en un `<div x-persist>` extra que rompe el `align-items:stretch` | Usa el atributo `x-persist="sidebar"` directo en `<aside>`, no la directiva Blade `@persist` |
| El resaltado del sidebar se queda pegado en la página vieja | Contenido persistido (`x-persist`) nunca vuelve a evaluar `request()->routeIs(...)` | Usa `wire:current="active"` en vez de condicionales de servidor |
| El dropdown de "Descargar" no abre | `x-on:click.outside` puesto en el panel en vez de en el contenedor que incluye también el botón; el propio clic del botón se interpreta como "afuera" | Pon `click.outside` en el `<div>` que envuelve botón + panel juntos |
| La tabla no se actualiza tras crear/editar/eliminar (modo cliente) sin refrescar la página | Alpine preserva su propio estado a través del morphing de Livewire; un `x-data="crudTable({...})"` nuevo en el HTML no se vuelve a leer | Después de mutar, llama `$this->refreshTable($this->freshRows($useCase))`, que dispara un evento de navegador que el store Alpine escucha y aplica directo |
| `Call to an undefined method Illuminate\Contracts\View\View::layout()` | `->layout()` es un macro de Livewire en tiempo de ejecución, PHPStan no lo conoce | `ignoreErrors` en `phpstan.neon` scoped a `Presentation/Livewire/*.php` (ya configurado, no repetir) |
| `Access to an undefined property Model::$relacion` | Acceder a una relación Eloquent como propiedad mágica sin que PHPStan la conozca | Agrega `@property-read Collection<int, X> $relacion` al docblock del modelo |
| `argument.type` en `Collection<int,stdClass>` al hacer `->get()->map(...)` | Larastan a veces pierde el tipo genérico del modelo en cadenas de builder complejas | Fuerza el tipo con `/** @var Collection<int, MiModelo> */` antes del `->map()` |
| La tabla se ve angosta, no ocupa todo el ancho | Envolviste `<x-ui.data-table>` en un `<div class="max-w-* mx-auto">` | No lo hagas: `.card` ya es `width: 100%` por diseño |
| Duplicado silencioso al guardar (sin mensaje de error claro) | Falta validación de unicidad antes de llegar al UseCase | `Rule::unique('tabla', 'columna')->ignore($component->editingId)` en el Form Object |
| La Policy siempre rechaza, aunque el usuario debería poder | Se sembraron permisos con nombre distinto al que la Policy/vista consulta (`docentes.editar` vs `docentes.edit`) | El nombre sale de `MODULES`+`ACTIONS` en `PermissionSeeder`; usa siempre el mismo string en Policy, vista y seeder |

---

## Resumen ultra-corto (para cuando ya te sepas el proceso)

```bash
php artisan make:ddd {Context} {Entity} --livewire --policy
```

1. Agrega el módulo a `PermissionSeeder::MODULES` y corre los seeders.
2. Completa el Contrato con `all()` + `paginate()`.
3. Agrega `List{Entity}sUseCase` (all + paginate).
4. Completa el Repositorio Eloquent (ojo con `@var` en `map()` y `@property-read` en el
   modelo).
5. Crea `Presentation/Routes/web.php` con la ruta GET.
6. Completa la Policy.
7. Crea el Form Object (`fromEntity()`, no `fill()`;
   `Rule::unique(...)->ignore($component->editingId)`).
8. Completa el Livewire Component (copia RoleComponent, cambia nombres, `sortKey` en
   `mount()`, `refreshTable()` tras cada mutación).
9. Crea la vista Blade (copia `role-component.blade.php`, cambia columnas/campos, agrega el
   `x-data` de `confirmDelete`).
10. Agrega el link al sidebar con `wire:navigate wire:current="active"`.
11. Agrega las traducciones a `es.json` (clave en inglés, valor en español).
12. `composer run types:check` + prueba manual.

**Antes de todo esto:** confirma en qué bounded context vive tu entidad (Sección 2). Si ya
existe un contexto hermano con el mismo lenguaje de negocio, tu entidad va ahí dentro como
módulo propio, no en uno nuevo.
