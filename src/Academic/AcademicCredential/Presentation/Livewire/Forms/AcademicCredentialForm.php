<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Presentation\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;
use Src\Academic\AcademicCredential\Application\DTOs\AcademicCredentialDTO;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\AcademicCredential\Domain\Entities\AcademicCredential;

class AcademicCredentialForm extends Form
{
    public ?int $specialtyId = null;

    public string $degreeLevel = '';

    public string $institution = '';

    public ?int $yearObtained = null;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'specialtyId' => ['required', 'integer', 'exists:especialidades,id'],
            'degreeLevel' => ['required', Rule::enum(DegreeLevel::class)],
            'institution' => ['required', 'string', 'max:150'],
            'yearObtained' => ['required', 'integer', 'min:1950', 'max:'.date('Y')],
        ];
    }

    public function fromEntity(AcademicCredential $credential): void
    {
        $this->specialtyId = $credential->specialtyId();
        $this->degreeLevel = $credential->degreeLevel()->value;
        $this->institution = $credential->institution();
        $this->yearObtained = $credential->yearObtained()->value();
    }

    public function toDto(int $teacherId): AcademicCredentialDTO
    {
        return new AcademicCredentialDTO(
            teacherId: $teacherId,
            specialtyId: (int) $this->specialtyId,
            degreeLevel: DegreeLevel::from($this->degreeLevel),
            institution: $this->institution,
            yearObtained: (int) $this->yearObtained,
        );
    }
}
