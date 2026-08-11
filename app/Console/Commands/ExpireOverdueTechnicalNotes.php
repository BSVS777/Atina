<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Src\Academic\TeacherAssignment\Application\UseCases\ExpireOverdueTechnicalNotesUseCase;

/**
 * DO-02b: "El sistema marca automáticamente como vencida toda Nota
 * técnica cuya fecha límite ya pasó sin resolución registrada."
 */
class ExpireOverdueTechnicalNotes extends Command
{
    protected $signature = 'affinity:expire-overdue-technical-notes';

    protected $description = 'Marks pending-ratification Technical Notes past their SLA deadline as expired (DO-02b)';

    public function handle(ExpireOverdueTechnicalNotesUseCase $useCase): int
    {
        $count = $useCase->handle();
        $this->info("Marked {$count} Technical Note(s) as expired.");

        return self::SUCCESS;
    }
}
