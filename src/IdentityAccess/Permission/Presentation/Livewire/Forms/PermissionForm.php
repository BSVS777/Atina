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

        // Restricted to what's actually missing, not the whole catalog —
        // the modal must never offer a combination that already exists
        // (see PermissionCatalogStatus). This also doubles as backend
        // protection against a forged request re-selecting an existing
        // combination the UI never offered.
        $status = $isCreating ? $component->catalogStatus() : null;

        return [
            'module' => [
                'required',
                'string',
                'max:255',
                ...($isCreating ? [Rule::in($status->availableModules())] : []),
            ],
            'action' => [
                'required',
                'string',
                'max:255',
                // Checked before Rule::in(): Laravel's Validator stops
                // recording further rule failures for an attribute once
                // an object-based rule like In() fails, which would
                // otherwise bury this clearer message behind In()'s
                // generic "invalid selection" one. Uniqueness is on the
                // (module, action) pair, not on "action" alone — the
                // extra where() scopes it correctly. Also the last line
                // of defense if Rule::in() is ever bypassed (e.g. a
                // stale request racing another creation of the same
                // combination).
                Rule::unique('permissions', 'action')
                    ->where(fn ($query) => $query->where('module', $this->module))
                    ->ignore($component->editingId),
                ...($isCreating ? [Rule::in($status->availableActionsFor($this->module))] : []),
            ],
        ];
    }

    /**
     * "El valor del campo action ya ha sido registrado." (the framework's
     * generic `unique` message) reads as if the *action name alone* were
     * duplicated, not the (module, action) pair — misleading given
     * actions like "gestionar" are legitimately shared across modules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.unique' => __('The permission :module.:action is already registered.', [
                'module' => $this->module,
                'action' => $this->action,
            ]),
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
        /** @var PermissionComponent $component */
        $component = $this->component;

        $validActions = $component->editingId === null
            ? $component->catalogStatus()->availableActionsFor($this->module)
            : PermissionCatalog::actionsFor($this->module);

        if (! in_array($this->action, $validActions, true)) {
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
