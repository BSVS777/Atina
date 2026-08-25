<?php

namespace Tests\Unit\IdentityAccess;

use Src\IdentityAccess\Permission\Domain\ValueObjects\PermissionCatalog;
use Src\IdentityAccess\Permission\Domain\ValueObjects\PermissionCatalogStatus;
use Tests\TestCase;

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
