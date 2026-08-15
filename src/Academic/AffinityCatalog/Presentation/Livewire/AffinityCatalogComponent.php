<?php

declare(strict_types=1);

namespace Src\Academic\AffinityCatalog\Presentation\Livewire;

use App\Livewire\Concerns\InteractsWithDataTable;
use App\Models\Course;
use App\Models\Specialty;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Component;
use Src\Academic\AffinityCatalog\Application\UseCases\CreateAffinityCatalogVersionUseCase;
use Src\Academic\AffinityCatalog\Application\UseCases\ListAffinityCatalogVersionsForCourseUseCase;
use Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion;
use Src\Academic\AffinityCatalog\Domain\Exceptions\OverlappingCatalogVersionException;
use Src\Academic\AffinityCatalog\Presentation\Livewire\Forms\AffinityCatalogVersionForm;

/**
 * DO-02: lists the versioned catalog for a selected course and lets the
 * Administrador publish a new version. There is no edit/delete — every
 * change is a brand new version (see AffinityCatalogVersionPolicy).
 */
class AffinityCatalogComponent extends Component
{
    use AuthorizesRequests;
    use InteractsWithDataTable;

    /**
     * Row keys the search box matches against. Deliberately excludes
     * 'id' (an internal surrogate the user never sees) and
     * 'effectiveEndDate' (null on the version currently in force, so
     * searching it would silently drop exactly the row that matters most).
     */
    private const SEARCHABLE = [
        'version',
        'councilAgreement',
        'gazetteNumber',
        'effectiveStartDate',
        'specialties',
    ];

    /**
     * 'server' rather than 'client': these rows come from an Application
     * UseCase returning Domain entities mapped to arrays, not an Eloquent
     * collection, and each row carries the full comma-separated specialty
     * list. Filtering server-side keeps that payload off every render.
     */
    protected string $tableMode = 'server';

    public ?int $selectedCourseId = null;

    public bool $showModal = false;

    public AffinityCatalogVersionForm $form;

    public function mount(): void
    {
        $this->selectedCourseId = Course::query()->orderBy('nombre')->value('id');
    }

    /**
     * Switching course replaces the whole result set, so page and search
     * state carried over from the previous course is meaningless.
     */
    public function updatedSelectedCourseId(): void
    {
        $this->page = 1;
        $this->search = '';
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', AffinityCatalogVersion::class);

        $this->form->reset();
        $this->form->courseId = $this->selectedCourseId;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function save(CreateAffinityCatalogVersionUseCase $useCase): void
    {
        $this->authorize('create', AffinityCatalogVersion::class);
        $this->form->validate();

        try {
            $version = $useCase->handle($this->form->toDto(), auth()->user()?->id);
        } catch (OverlappingCatalogVersionException $e) {
            $this->addError('form.effectiveStartDate', $e->getMessage());

            return;
        }

        $this->selectedCourseId = $version->courseId();
        $this->showModal = false;
        $this->dispatch('toast', variant: 'success', text: __('Affinity catalog version published.'));
    }

    public function render(ListAffinityCatalogVersionsForCourseUseCase $useCase): View
    {
        $courses = Course::query()->orderBy('nombre')->get(['id', 'carrera_id', 'codigo', 'nombre'])->load('career');
        $specialties = Specialty::query()->orderBy('nombre')->get(['id', 'nombre']);

        $versions = $this->selectedCourseId !== null ? $useCase->handle($this->selectedCourseId) : [];
        $specialtyNames = $specialties->pluck('name', 'id');

        $rows = array_map(
            fn (AffinityCatalogVersion $version) => $this->toRow($version, $specialtyNames),
            $versions,
        );

        return view('academic.affinity-catalog.livewire.affinity-catalog-component', [
            'courses' => $courses,
            'specialties' => $specialties,
            'tableMode' => $this->tableMode(),
            'versions' => $this->paginateRows($rows, self::SEARCHABLE),
            'canManage' => auth()->user()->can('create', AffinityCatalogVersion::class),
            'selectedCourse' => $courses->firstWhere('id', $this->selectedCourseId),
        ])->layout('components.layouts.dashboard', [
            'title' => __('Affinity Catalog'),
            'subtitle' => __('Versioned catalog of career/course academic affinity'),
        ]);
    }

    /**
     * @param  Collection<int, string>  $specialtyNames
     * @return array<string, mixed>
     */
    private function toRow(AffinityCatalogVersion $version, $specialtyNames): array
    {
        return [
            'id' => $version->id(),
            'version' => $version->versionNumber(),
            'councilAgreement' => $version->councilAgreement(),
            'gazetteNumber' => $version->gazetteNumber(),
            'effectiveStartDate' => $version->effectiveStartDate()->format('Y-m-d'),
            'effectiveEndDate' => $version->effectiveEndDate()?->format('Y-m-d'),
            'specialties' => collect($version->specialtyIds())->map(fn ($id) => $specialtyNames->get($id, '—'))->implode(', '),
        ];
    }
}
