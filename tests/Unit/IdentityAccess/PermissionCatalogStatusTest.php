<?php

namespace Tests\Unit\IdentityAccess;

use PHPUnit\Framework\TestCase;
use Src\IdentityAccess\Permission\Domain\ValueObjects\PermissionCatalog;
use Src\IdentityAccess\Permission\Domain\ValueObjects\PermissionCatalogStatus;

class PermissionCatalogStatusTest extends TestCase
{
    public function test_status_is_complete_when_every_official_combination_is_registered(): void
    {
        $status = PermissionCatalogStatus::fromRegistered($this->allOfficialCombinations());

        $this->assertTrue($status->isComplete());
        $this->assertSame(30, $status->registeredCount());
        $this->assertSame(30, $status->totalOfficialCount());
        $this->assertSame([], $status->availableModules());
        $this->assertSame([], $status->availableActionsFor('roles'));
    }

    public function test_status_reports_only_the_missing_module_and_action_when_one_combination_is_absent(): void
    {
        $registered = array_values(array_filter(
            $this->allOfficialCombinations(),
            static fn (array $p): bool => ! ($p['module'] === 'roles' && $p['action'] === 'export_excel'),
        ));

        $status = PermissionCatalogStatus::fromRegistered($registered);

        $this->assertFalse($status->isComplete());
        $this->assertSame(29, $status->registeredCount());
        $this->assertSame(30, $status->totalOfficialCount());
        $this->assertSame(['roles'], $status->availableModules());
        $this->assertSame(['export_excel'], $status->availableActionsFor('roles'));
        // A fully-registered module (e.g. "permissions") must not be offered.
        $this->assertNotContains('permissions', $status->availableModules());
    }

    public function test_available_actions_for_an_unregistered_or_unknown_module_is_empty(): void
    {
        $status = PermissionCatalogStatus::fromRegistered([]);

        $this->assertSame([], $status->availableActionsFor(null));
        $this->assertSame([], $status->availableActionsFor('unknown_module'));
    }

    public function test_status_reports_every_missing_combination_across_several_modules(): void
    {
        $status = PermissionCatalogStatus::fromRegistered($this->allOfficialCombinationsExcept([
            'roles.export_excel',
            'roles.delete',
            'atinencia.verificar',
        ]));

        $this->assertFalse($status->isComplete());
        $this->assertSame(27, $status->registeredCount());
        $this->assertSame(['roles', 'atinencia'], $status->availableModules());
        $this->assertSame(['delete', 'export_excel'], $status->availableActionsFor('roles'));
        $this->assertSame(['verificar'], $status->availableActionsFor('atinencia'));
    }

    public function test_nothing_registered_leaves_every_official_module_available(): void
    {
        $status = PermissionCatalogStatus::fromRegistered([]);

        $this->assertFalse($status->isComplete());
        $this->assertSame(0, $status->registeredCount());
        $this->assertSame(PermissionCatalog::modules(), $status->availableModules());
    }

    public function test_a_legacy_permission_outside_the_catalog_never_fills_an_official_gap(): void
    {
        $registered = $this->allOfficialCombinationsExcept(['atinencia.verificar']);
        $registered[] = ['module' => 'legado', 'action' => 'custom_action'];

        $status = PermissionCatalogStatus::fromRegistered($registered);

        $this->assertSame(29, $status->registeredCount());
        $this->assertSame(30, $status->totalOfficialCount());
        $this->assertSame(['atinencia'], $status->availableModules());
        $this->assertNotContains('legado', $status->availableModules());
    }

    public function test_the_order_of_the_registered_input_does_not_change_the_result(): void
    {
        $registered = $this->allOfficialCombinationsExcept(['roles.delete', 'oferta.consolidar']);

        $asStored = PermissionCatalogStatus::fromRegistered($registered);
        $reversed = PermissionCatalogStatus::fromRegistered(array_reverse($registered));

        $this->assertSame($asStored->availableModules(), $reversed->availableModules());
        $this->assertSame($asStored->availableActionsFor('roles'), $reversed->availableActionsFor('roles'));
        $this->assertSame($asStored->registeredCount(), $reversed->registeredCount());
    }

    public function test_a_pair_persisted_twice_is_still_counted_once(): void
    {
        $registered = $this->allOfficialCombinationsExcept(['roles.delete']);
        $registered[] = ['module' => 'atinencia', 'action' => 'verificar'];

        $status = PermissionCatalogStatus::fromRegistered($registered);

        $this->assertSame(29, $status->registeredCount());
        $this->assertSame(['roles'], $status->availableModules());
    }

    /**
     * @param  array<int, string>  $excludedNames
     * @return array<int, array{module: string, action: string}>
     */
    private function allOfficialCombinationsExcept(array $excludedNames): array
    {
        return array_values(array_filter(
            $this->allOfficialCombinations(),
            static fn (array $p): bool => ! in_array("{$p['module']}.{$p['action']}", $excludedNames, true),
        ));
    }

    /**
     * @return array<int, array{module: string, action: string}>
     */
    private function allOfficialCombinations(): array
    {
        $combinations = [];

        foreach (PermissionCatalog::all() as $module => $actions) {
            foreach ($actions as $action) {
                $combinations[] = ['module' => $module, 'action' => $action];
            }
        }

        return $combinations;
    }
}
