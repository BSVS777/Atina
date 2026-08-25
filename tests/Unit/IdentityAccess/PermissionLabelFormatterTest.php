<?php

namespace Tests\Unit\IdentityAccess;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\IdentityAccess\Permission\Presentation\Support\PermissionLabelFormatter;

/**
 * Pure label composition for the Spanish UI. No business rule lives
 * here — the point of these tests is that an unmapped module or action
 * still renders something readable instead of a blank checkbox.
 */
class PermissionLabelFormatterTest extends TestCase
{
    #[DataProvider('humanPhrases')]
    public function test_a_permission_reads_as_a_spanish_phrase(string $module, string $action, string $expected): void
    {
        $this->assertSame($expected, PermissionLabelFormatter::forHumans($module, $action));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function humanPhrases(): array
    {
        return [
            'crud action drops the preposition' => ['roles', 'edit', 'Editar roles'],
            'export action keeps the preposition' => ['roles', 'export_pdf', 'Exportar PDF de roles'],
            'excel export keeps the preposition' => ['permissions', 'export_excel', 'Exportar Excel de permisos'],
            'institutional verb' => ['atinencia', 'verificar', 'Verificar atinencia'],
            'multi word module is lowercased' => ['nota_tecnica', 'aprobar', 'Aprobar nota técnica'],
        ];
    }

    public function test_an_unmapped_action_falls_back_to_a_readable_form(): void
    {
        $this->assertSame('Custom action', PermissionLabelFormatter::actionLabel('custom_action'));
    }

    public function test_an_unmapped_module_falls_back_to_its_raw_key(): void
    {
        $this->assertSame('legado', PermissionLabelFormatter::moduleLabel('legado'));
    }

    public function test_a_legacy_permission_still_renders_a_non_empty_phrase(): void
    {
        $this->assertSame('Custom action legado', PermissionLabelFormatter::forHumans('legado', 'custom_action'));
    }
}
