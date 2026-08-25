<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Presentation\Livewire\Forms;

use DateTimeImmutable;
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

    public string $startDate = '';

    public string $endDate = '';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'specialtyId' => ['required', 'integer', 'exists:especialidades,id'],
            'degreeLevel' => ['required', Rule::enum(DegreeLevel::class)],
            'institution' => ['required', 'string', 'max:150', 'regex:/\p{L}/u'],
            'startDate' => ['required', 'date', 'after_or_equal:1950-01-01', 'before_or_equal:endDate'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'institution.regex' => __('The institution must contain letters, not only numbers.'),
            'startDate.before_or_equal' => __('The start date must be before or the same as the end date.'),
            'endDate.after_or_equal' => __('The end date must be after or the same as the start date.'),
            'endDate.before_or_equal' => __('The end date cannot be in the future.'),
        ];
    }

    public function fromEntity(AcademicCredential $credential): void
    {
        $this->specialtyId = $credential->specialtyId();
        $this->degreeLevel = $credential->degreeLevel()->value;
        $this->institution = $credential->institution();
        $this->startDate = $credential->studyPeriod()->startDate()->format('Y-m-d');
        $this->endDate = $credential->studyPeriod()->endDate()->format('Y-m-d');
    }

    public function toDto(int $teacherId): AcademicCredentialDTO
    {
        return new AcademicCredentialDTO(
            teacherId: $teacherId,
            specialtyId: (int) $this->specialtyId,
            degreeLevel: DegreeLevel::from($this->degreeLevel),
            institution: $this->institution,
            startDate: new DateTimeImmutable($this->startDate),
            endDate: new DateTimeImmutable($this->endDate),
        );
    }
}
