<?php

declare(strict_types=1);

namespace Src\Academic\AffinityCatalog\Presentation\Livewire\Forms;

use Livewire\Form;
use Src\Academic\AffinityCatalog\Application\DTOs\AffinityCatalogVersionDTO;

class AffinityCatalogVersionForm extends Form
{
    public ?int $courseId = null;

    public string $councilAgreement = '';

    public string $gazetteNumber = '';

    public string $effectiveStartDate = '';

    public ?string $effectiveEndDate = null;

    /** @var array<int, int> */
    public array $specialtyIds = [];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'courseId' => ['required', 'integer', 'exists:cursos,id'],
            'councilAgreement' => ['required', 'string', 'max:120'],
            'gazetteNumber' => ['required', 'string', 'max:60'],
            'effectiveStartDate' => ['required', 'date'],
            'effectiveEndDate' => ['nullable', 'date', 'after_or_equal:effectiveStartDate'],
            'specialtyIds' => ['required', 'array', 'min:1'],
            'specialtyIds.*' => ['integer', 'exists:especialidades,id'],
        ];
    }

    public function toDto(): AffinityCatalogVersionDTO
    {
        return new AffinityCatalogVersionDTO(
            courseId: (int) $this->courseId,
            councilAgreement: $this->councilAgreement,
            gazetteNumber: $this->gazetteNumber,
            effectiveStartDate: $this->effectiveStartDate,
            effectiveEndDate: $this->effectiveEndDate !== '' ? $this->effectiveEndDate : null,
            specialtyIds: $this->specialtyIds,
        );
    }
}
