<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Presentation\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;
use Src\Academic\TeacherAssignment\Application\DTOs\ProposeTeacherAssignmentDTO;

class ProposeAssignmentForm extends Form
{
    public ?int $teacherId = null;

    public ?int $courseGroupId = null;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'teacherId' => ['required', 'integer', 'exists:docentes,id'],
            'courseGroupId' => [
                'required',
                'integer',
                'exists:grupos,id',
                Rule::unique('asignaciones_docentes', 'grupo_id')->where('docente_id', $this->teacherId),
            ],
        ];
    }

    public function toDto(int $courseId, string $targetDate): ProposeTeacherAssignmentDTO
    {
        return new ProposeTeacherAssignmentDTO(
            courseGroupId: (int) $this->courseGroupId,
            teacherId: (int) $this->teacherId,
            courseId: $courseId,
            targetDate: $targetDate,
        );
    }
}
