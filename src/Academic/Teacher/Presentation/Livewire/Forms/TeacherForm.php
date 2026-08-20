<?php

declare(strict_types=1);

namespace Src\Academic\Teacher\Presentation\Livewire\Forms;

use Livewire\Form;
use Src\Academic\Teacher\Application\DTOs\TeacherDTO;

class TeacherForm extends Form
{
    public ?int $positionId = null;

    public string $nationalId = '';

    public string $firstName = '';

    public string $lastName = '';

    public ?string $secondLastName = null;

    public ?string $estimatedWorkload = null;

    public bool $active = true;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'positionId' => ['required', 'integer', 'exists:puestos,id'],
            'nationalId' => ['required', 'string', 'max:12', 'unique:docentes,cedula'],
            'firstName' => ['required', 'string', 'max:60'],
            'lastName' => ['required', 'string', 'max:60'],
            'secondLastName' => ['nullable', 'string', 'max:60'],
            'estimatedWorkload' => ['nullable', 'numeric', 'between:0,1'],
            'active' => ['boolean'],
        ];
    }

    public function toDto(): TeacherDTO
    {
        return new TeacherDTO(
            positionId: (int) $this->positionId,
            nationalId: $this->nationalId,
            firstName: $this->firstName,
            lastName: $this->lastName,
            secondLastName: $this->secondLastName,
            estimatedWorkload: $this->estimatedWorkload,
            active: $this->active,
        );
    }
}
