<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Permission\Domain\ValueObjects;

/**
 * Read model answering "what's left to register?" against the closed
 * PermissionCatalog — drives the Permission screen's Create availability
 * and its missing-only Module/Action selects. Pure computation over an
 * already-fetched list of registered (module, action) pairs; fetching
 * that list from storage is the calling UseCase's job, not this class's.
 */
final class PermissionCatalogStatus
{
    /** @var array<string, array<int, string>> */
    private readonly array $missingByModule;

    private readonly int $totalOfficialCount;

    /**
     * @param  array<string, array<int, string>>  $missingByModule
     */
    private function __construct(array $missingByModule, int $totalOfficialCount)
    {
        // Modules with nothing missing are dropped so availableModules()
        // never offers a module that has no missing action left.
        $this->missingByModule = array_filter($missingByModule, fn (array $actions): bool => $actions !== []);
        $this->totalOfficialCount = $totalOfficialCount;
    }

    /**
     * @param  array<int, array{module: string, action: string}>  $registered
     */
    public static function fromRegistered(array $registered): self
    {
        $registeredSet = [];
        foreach ($registered as $permission) {
            $registeredSet["{$permission['module']}.{$permission['action']}"] = true;
        }

        $missingByModule = [];
        $totalOfficialCount = 0;

        foreach (PermissionCatalog::all() as $module => $actions) {
            foreach ($actions as $action) {
                $totalOfficialCount++;

                if (! isset($registeredSet["{$module}.{$action}"])) {
                    $missingByModule[$module][] = $action;
                }
            }
        }

        return new self($missingByModule, $totalOfficialCount);
    }

    public function isComplete(): bool
    {
        return $this->missingByModule === [];
    }

    public function registeredCount(): int
    {
        return $this->totalOfficialCount - $this->missingCount();
    }

    public function totalOfficialCount(): int
    {
        return $this->totalOfficialCount;
    }

    /**
     * @return array<int, string>
     */
    public function availableModules(): array
    {
        return array_keys($this->missingByModule);
    }

    /**
     * @return array<int, string>
     */
    public function availableActionsFor(?string $module): array
    {
        return $this->missingByModule[$module] ?? [];
    }

    private function missingCount(): int
    {
        return array_sum(array_map(count(...), $this->missingByModule));
    }
}
