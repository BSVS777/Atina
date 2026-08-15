<?php

declare(strict_types=1);

namespace Src\Academic\Teacher\Presentation\Livewire;

use App\Livewire\Concerns\InteractsWithDataTable;
use App\Models\Teacher;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

/**
 * Read-only by design: the source module this was ported from treats the
 * teacher as an existing external reference (no create/edit/delete UI here
 * — see Docs/DIARIO_DECISIONES_IA.md). Managing academic credentials is the
 * only mutation surface, on the profile page (TeacherProfileComponent).
 */
class TeacherComponent extends Component
{
    use InteractsWithDataTable;

    protected string $tableMode = 'client';

    public function mount(): void
    {
        $this->sortKey = 'last_name';
    }

    public function render(): View
    {
        $view = $this->isServerMode()
            ? $this->renderServerMode()
            : $this->renderClientMode();

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
            'rows' => $this->query()->get()->map($this->toRow(...))->all(),
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
    private function query(): Builder
    {
        $columns = ['national_id' => 'cedula', 'last_name' => 'primer_apellido'];
        $column = $columns[$this->sortKey] ?? $columns['last_name'];
        $direction = $this->sortDir === 'desc' ? 'desc' : 'asc';

        return Teacher::query()
            ->with('position')
            ->withCount('academicCredentials')
            ->when($this->search !== '', function ($query) {
                $term = "%{$this->search}%";
                $query->where(function ($query) use ($term) {
                    $query->where('nombre', 'like', $term)
                        ->orWhere('primer_apellido', 'like', $term)
                        ->orWhere('segundo_apellido', 'like', $term)
                        ->orWhere('cedula', 'like', $term);
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
}
