<?php

declare(strict_types=1);

namespace Src\Academic\Teacher\Presentation\Livewire;

use App\Livewire\Concerns\InteractsWithDataTable;
use App\Livewire\Concerns\InteractsWithExports;
use App\Models\Position;
use App\Models\Teacher;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Component;
use Src\Academic\Teacher\Application\UseCases\CreateTeacherUseCase;
use Src\Academic\Teacher\Presentation\Livewire\Forms\TeacherForm;
use Src\Shared\Export\Contracts\ExcelExporterInterface;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Create-only by design: the source module this was ported from treats the
 * teacher as an existing external reference, so edit/delete stay out of
 * scope here (see Docs/DIARIO_DECISIONES_IA.md). Managing academic
 * credentials remains a separate mutation surface, on the profile page
 * (TeacherProfileComponent).
 */
class TeacherComponent extends Component
{
    use AuthorizesRequests;
    use InteractsWithDataTable;
    use InteractsWithExports;

    protected string $tableMode = 'client';

    public bool $showModal = false;

    public TeacherForm $form;

    public function mount(): void
    {
        $this->sortKey = 'last_name';
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Teacher::class);

        $this->form->reset();
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function save(CreateTeacherUseCase $useCase): void
    {
        $this->authorize('create', Teacher::class);

        $this->form->validate();

        $useCase->handle($this->form->toDto());

        $this->showModal = false;
        $this->refreshTable($this->freshRows());
        $this->dispatch('toast', variant: 'success', text: __('Teacher created.'));
    }

    public function exportPdf(PdfExporterInterface $exporter, ?string $search = null): StreamedResponse
    {
        $this->authorize('exportPdf', Teacher::class);

        return $this->streamPdf(
            __('Teachers'),
            $this->exportHeaders(),
            $this->exportableRows($search),
            Str::slug(__('Teachers')).'.pdf',
            $exporter,
            paperSize: 'letter',
        );
    }

    public function exportExcel(ExcelExporterInterface $exporter, ?string $search = null): StreamedResponse
    {
        $this->authorize('exportExcel', Teacher::class);

        return $this->streamExcel(
            $this->exportHeaders(),
            $this->exportableRows($search),
            Str::slug(__('Teachers')).'.xlsx',
            $exporter,
        );
    }

    public function render(): View
    {
        $view = $this->isServerMode()
            ? $this->renderServerMode()
            : $this->renderClientMode();

        $view = $view->with('positions', Position::query()->orderBy('nombre')->get());

        /** @disregard P1013 Livewire registers ->layout() as a runtime macro */
        return $view->layout('components.layouts.dashboard', [
            'title' => __('Teachers'),
            'subtitle' => __('Active teaching staff and their academic load'),
        ]);
    }

    private function renderClientMode(): View
    {
        return view('academic.teacher.livewire.teacher-component', [
            'tableMode' => 'client',
            'rows' => $this->freshRows(),
        ]);
    }

    private function renderServerMode(): View
    {
        $paginator = $this->query()->paginate(perPage: $this->perPage, page: $this->page);

        return view('academic.teacher.livewire.teacher-component', [
            'tableMode' => 'server',
            'teachers' => $paginator,
        ]);
    }

    /**
     * @return Builder<Teacher>
     */
    private function query(?string $search = null): Builder
    {
        $columns = ['national_id' => 'cedula', 'last_name' => 'primer_apellido'];
        $column = $columns[$this->sortKey] ?? $columns['last_name'];
        $direction = $this->sortDir === 'desc' ? 'desc' : 'asc';
        $term = $search ?? $this->search;

        return Teacher::query()
            ->with('position')
            ->withCount('academicCredentials')
            ->when($term !== '', function ($query) use ($term) {
                $like = "%{$term}%";
                $query->where(function ($query) use ($like) {
                    $query->where('nombre', 'like', $like)
                        ->orWhere('primer_apellido', 'like', $like)
                        ->orWhere('segundo_apellido', 'like', $like)
                        ->orWhere('cedula', 'like', $like);
                });
            })
            ->orderBy($column, $direction);
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(Teacher $teacher): array
    {
        return [
            'id' => $teacher->id,
            'nationalId' => $teacher->national_id,
            'fullName' => $teacher->fullName(),
            'position' => $teacher->position->name,
            'credentialsCount' => $teacher->academic_credentials_count,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function freshRows(): array
    {
        return $this->query()->get()->map($this->toRow(...))->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function exportableRows(?string $search): array
    {
        return $this->query($search)->get()->map($this->toRow(...))->all();
    }

    /**
     * @return array<int, array{key: string, label: string, format?: callable}>
     */
    private function exportHeaders(): array
    {
        return [
            ['key' => 'nationalId', 'label' => __('National ID')],
            ['key' => 'fullName', 'label' => __('Name')],
            ['key' => 'position', 'label' => __('Position')],
            ['key' => 'credentialsCount', 'label' => __('Credentials')],
        ];
    }
}
