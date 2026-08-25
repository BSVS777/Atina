<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Permission\Presentation\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;
use Src\IdentityAccess\Permission\Application\DTOs\PermissionDTO;
use Src\IdentityAccess\Permission\Domain\Entities\Permission;
use Src\IdentityAccess\Permission\Domain\ValueObjects\PermissionCatalog;
use Src\IdentityAccess\Permission\Presentation\Livewire\PermissionComponent;

class PermissionForm extends Form
{
    public string $module = '';

    public string $action = '';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var PermissionComponent $component */
        $component = $this->component;

        // The catalog is only enforced while creating. An existing
        // permission's module/action is read-only in the UI and
        // protected at the Domain level (Permission::redefine()) — a
        // pre-existing/legacy row outside the catalog must still be
        // saveable as a no-op, not rejected here.
        $isCreating = $component->editingId === null;

        return [
            'module' => [
                'required',
                'string',
                'max:255',
                ...($isCreating ? [Rule::in(PermissionCatalog::modules())] : []),
            ],
            'action' => [
                'required',
                'string',
                'max:255',
                ...($isCreating ? [Rule::in(PermissionCatalog::actionsFor($this->module))] : []),
                // Uniqueness is on the (module, action) pair, not on
                // "action" alone — the extra where() scopes it correctly.
                Rule::unique('permissions', 'action')
                    ->where(fn ($query) => $query->where('module', $this->module))
                    ->ignore($component->editingId),
            ],
        ];
    }

    /**
     * Module drives which actions are valid (see PermissionCatalog).
     * Livewire calls this automatically when `form.module` changes
     * (SupportFormObjects resolves "updated" + studly(property) on the
     * Form instance itself) — clearing a now-invalid action keeps the
     * dependent Action select from silently submitting a stale value.
     */
    public function updatedModule(): void
    {
        if (! PermissionCatalog::isOfficial($this->module, $this->action)) {
            $this->action = '';
        }
    }

    /**
     * Hydrates the form from an existing Permission for the edit modal.
     * Named `fromEntity()`, not `fill()` — see RoleForm for why that
     * name is reserved by the Livewire\Form base class.
     */
    public function fromEntity(Permission $permission): void
    {
        $this->module = $permission->module();
        $this->action = $permission->action();
    }

    public function toDto(): PermissionDTO
    {
        return new PermissionDTO(module: $this->module, action: $this->action);
    }
}
