<?php

declare(strict_types=1);

namespace Src\Academic\AffinityCatalog\Presentation\Livewire;

use App\Livewire\Concerns\InteractsWithDataTable;
use App\Livewire\Concerns\InteractsWithExports;
use App\Models\Course;
use App\Models\Specialty;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use Src\Academic\AffinityCatalog\Application\UseCases\CreateAffinityCatalogVersionUseCase;
use Src\Academic\AffinityCatalog\Application\UseCases\ListAffinityCatalogVersionsForCourseUseCase;
use Src\Academic\AffinityCatalog\Application\UseCases\UpdateAffinityCatalogVersionUseCase;
use Src\Academic\AffinityCatalog\Domain\Contracts\AffinityCatalogVersionRepositoryInterface;
use Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion;
use Src\Academic\AffinityCatalog\Domain\Exceptions\CatalogVersionInUseException;
use Src\Academic\AffinityCatalog\Domain\Exceptions\CatalogVersionNotFoundException;
use Src\Academic\AffinityCatalog\Domain\Exceptions\OverlappingCatalogVersionException;
use Src\Academic\AffinityCatalog\Presentation\Livewire\Forms\AffinityCatalogVersionForm;
use Src\Shared\Export\Contracts\ExcelExporterInterface;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * DO-02: lists the versioned catalog for a selected course and lets the
 * Administrador publish a new version. No delete, ever — prior versions
 * stay. Editing an existing version is offered only while it has zero
 * verifications recorded against it (see UpdateAffinityCatalogVersionUseCase);
 * once cited, correcting a mistake means publishing a new version instead.
 */
class AffinityCatalogComponent extends Component
{
    use AuthorizesRequests;
    use InteractsWithDataTable;
    use InteractsWithExports;

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

    /**
     * Null while creating; the row's id while editing. Editing is only
     * ever offered for a version that has zero verifications recorded
     * against it yet — see UpdateAffinityCatalogVersionUseCase.
     */
    public ?int $editingId = null;

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

        $this->editingId = null;
        $this->form->reset();
        $this->form->courseId = $this->selectedCourseId;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEditModal(int $id, AffinityCatalogVersionRepositoryInterface $repository): void
    {
        $this->authorize('update', AffinityCatalogVersion::class);

        $version = $repository->find($id) ?? throw CatalogVersionNotFoundException::withId($id);

        if ($repository->hasVerifications($id)) {
            $this->dispatch('toast', variant: 'danger', text: __('This version already has verifications recorded and can no longer be edited — publish a new version instead.'));

            return;
        }

        $this->editingId = $id;
        $this->form->fromEntity($version);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function save(CreateAffinityCatalogVersionUseCase $createUseCase, UpdateAffinityCatalogVersionUseCase $updateUseCase): void
    {
        $this->authorize($this->editingId === null ? 'create' : 'update', AffinityCatalogVersion::class);
        $this->form->validate();

        try {
            $version = $this->editingId === null
                ? $createUseCase->handle($this->form->toDto(), auth()->user()?->id)
                : $updateUseCase->handle($this->editingId, $this->form->toDto(), auth()->user()?->id);
        } catch (OverlappingCatalogVersionException $e) {
            $this->addError('form.effectiveStartDate', $e->getMessage());

            return;
        } catch (CatalogVersionInUseException) {
            $this->showModal = false;
            $this->dispatch('toast', variant: 'danger', text: __('This version already has verifications recorded and can no longer be edited — publish a new version instead.'));

            return;
        }

        $this->selectedCourseId = $version->courseId();
        $this->showModal = false;
        $this->dispatch('toast', variant: 'success', text: $this->editingId === null
            ? __('Affinity catalog version published.')
            : __('Affinity catalog version updated.'));
    }

    public function exportPdf(PdfExporterInterface $exporter, ListAffinityCatalogVersionsForCourseUseCase $useCase, AffinityCatalogVersionRepositoryInterface $repository, ?string $search = null): StreamedResponse
    {
        $this->authorize('exportPdf', AffinityCatalogVersion::class);

        return $this->streamPdf(
            __('Affinity Catalog'),
            $this->exportHeaders(),
            $this->exportableRows($useCase, $repository, $search),
            Str::slug(__('Affinity Catalog')).'.pdf',
            $exporter,
            paperSize: 'letter',
        );
    }

    public function exportExcel(ExcelExporterInterface $exporter, ListAffinityCatalogVersionsForCourseUseCase $useCase, AffinityCatalogVersionRepositoryInterface $repository, ?string $search = null): StreamedResponse
    {
        $this->authorize('exportExcel', AffinityCatalogVersion::class);

        return $this->streamExcel(
            $this->exportHeaders(),
            $this->exportableRows($useCase, $repository, $search),
            Str::slug(__('Affinity Catalog')).'.xlsx',
            $exporter,
        );
    }

    public function render(ListAffinityCatalogVersionsForCourseUseCase $useCase, AffinityCatalogVersionRepositoryInterface $repository): View
    {
        $courses = Course::query()->orderBy('nombre')->get(['id', 'carrera_id', 'codigo', 'nombre'])->load('career');
        $specialties = Specialty::query()->orderBy('nombre')->get(['id', 'nombre']);

        $rows = $this->rowsForSelectedCourse($useCase, $repository);

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
    private function toRow(AffinityCatalogVersion $version, $specialtyNames, AffinityCatalogVersionRepositoryInterface $repository): array
    {
        return [
            'id' => $version->id(),
            'version' => $version->versionNumber(),
            'councilAgreement' => $version->councilAgreement(),
            'gazetteNumber' => $version->gazetteNumber(),
            'effectiveStartDate' => $version->effectiveStartDate()->format('Y-m-d'),
            'effectiveEndDate' => $version->effectiveEndDate()?->format('Y-m-d'),
            'specialties' => collect($version->specialtyIds())->map(fn ($id) => $specialtyNames->get($id, '—'))->implode(', '),
            // Editable only while nothing has cited it yet — see
            // UpdateAffinityCatalogVersionUseCase for why.
            'canEdit' => ! $repository->hasVerifications($version->id()),
        ];
    }

    /**
     * Shared by render() (feeds paginateRows()) and exportableRows()
     * (feeds filterRows()) — the one place that knows how to build a row
     * array for the currently selected course.
     *
     * @return array<int, array<string, mixed>>
     */
    private function rowsForSelectedCourse(ListAffinityCatalogVersionsForCourseUseCase $useCase, AffinityCatalogVersionRepositoryInterface $repository): array
    {
        if ($this->selectedCourseId === null) {
            return [];
        }

        $specialtyNames = Specialty::query()->orderBy('nombre')->get(['id', 'nombre'])->pluck('name', 'id');
        $versions = $useCase->handle($this->selectedCourseId);

        return array_map(
            fn (AffinityCatalogVersion $version) => $this->toRow($version, $specialtyNames, $repository),
            $versions,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function exportableRows(ListAffinityCatalogVersionsForCourseUseCase $useCase, AffinityCatalogVersionRepositoryInterface $repository, ?string $search): array
    {
        return $this->filterRows(
            $this->rowsForSelectedCourse($useCase, $repository),
            self::SEARCHABLE,
            filled($search) ? $search : $this->search,
        );
    }

    /**
     * @return array<int, array{key: string, label: string, format?: callable}>
     */
    private function exportHeaders(): array
    {
        return [
            ['key' => 'version', 'label' => __('Version')],
            ['key' => 'councilAgreement', 'label' => __('Council agreement')],
            ['key' => 'gazetteNumber', 'label' => __('Gazette number')],
            ['key' => 'effectiveStartDate', 'label' => __('Effective from')],
            ['key' => 'effectiveEndDate', 'label' => __('Effective until'), 'format' => fn (string $value): string => $value !== '' ? $value : __('Indefinite')],
            ['key' => 'specialties', 'label' => __('Affine specialties')],
        ];
    }
}
