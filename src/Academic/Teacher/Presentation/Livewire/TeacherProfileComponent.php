<?php

declare(strict_types=1);

namespace Src\Academic\Teacher\Presentation\Livewire;

use App\Models\Specialty;
use App\Models\Teacher;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Component;
use Src\Academic\AcademicCredential\Application\UseCases\EditAcademicCredentialUseCase;
use Src\Academic\AcademicCredential\Application\UseCases\FindAcademicCredentialUseCase;
use Src\Academic\AcademicCredential\Application\UseCases\ListAcademicCredentialsForTeacherUseCase;
use Src\Academic\AcademicCredential\Application\UseCases\RegisterAcademicCredentialUseCase;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\AcademicCredential\Domain\Entities\AcademicCredential;
use Src\Academic\AcademicCredential\Domain\Exceptions\DuplicateCredentialException;
use Src\Academic\AcademicCredential\Presentation\Livewire\Forms\AcademicCredentialForm;

/**
 * Teacher profile: read-only header (see TeacherComponent for why teacher
 * data has no create/edit/delete UI) plus academic credential management,
 * the only mutation surface for this module. Kept as one component instead
 * of a separate nested one for the credentials table — this page has
 * exactly one reason to mutate anything, splitting it would only add an
 * inter-component wiring problem with no real benefit.
 */
class TeacherProfileComponent extends Component
{
    use AuthorizesRequests;

    public Teacher $teacher;

    public bool $showModal = false;

    public ?int $editingId = null;

    public AcademicCredentialForm $form;

    public function mount(Teacher $teacher): void
    {
        $this->teacher = $teacher;
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', AcademicCredential::class);

        $this->editingId = null;
        $this->form->reset();
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEditModal(int $id, FindAcademicCredentialUseCase $useCase): void
    {
        $this->authorize('update', AcademicCredential::class);

        $credential = $useCase->handle($id);
        $this->editingId = $id;
        $this->form->fromEntity($credential);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function save(RegisterAcademicCredentialUseCase $registerUseCase, EditAcademicCredentialUseCase $editUseCase): void
    {
        $this->form->validate();
        $actorUserId = auth()->user()?->id;

        try {
            if ($this->editingId === null) {
                $this->authorize('create', AcademicCredential::class);
                $registerUseCase->handle($this->form->toDto($this->teacher->id), $actorUserId);
            } else {
                $this->authorize('update', AcademicCredential::class);
                $editUseCase->handle($this->editingId, $this->form->toDto($this->teacher->id), $actorUserId);
            }
        } catch (DuplicateCredentialException $e) {
            $this->addError('form.specialtyId', $e->getMessage());

            return;
        }

        $this->showModal = false;
        $this->dispatch('toast', variant: 'success', text: $this->editingId === null
            ? __('Academic credential created.')
            : __('Academic credential updated.'));
    }

    public function render(ListAcademicCredentialsForTeacherUseCase $listUseCase): View
    {
        $specialties = Specialty::query()->orderBy('name')->get(['id', 'name']);
        $specialtyNames = $specialties->pluck('name', 'id');

        $rows = array_map(
            fn (AcademicCredential $credential) => $this->toRow($credential, $specialtyNames),
            $listUseCase->handle($this->teacher->id),
        );

        return view('academic.teacher.livewire.teacher-profile-component', [
            'rows' => $rows,
            'specialties' => $specialties,
            'degreeLevels' => DegreeLevel::cases(),
            'canManage' => auth()->user()->can('create', AcademicCredential::class)
                || auth()->user()->can('update', AcademicCredential::class),
        ])->layout('components.layouts.dashboard', [
            'title' => $this->teacher->fullName(),
            'subtitle' => __('Teacher profile and academic credentials'),
        ]);
    }

    /**
     * @param  Collection<int, string>  $specialtyNames
     * @return array<string, mixed>
     */
    private function toRow(AcademicCredential $credential, Collection $specialtyNames): array
    {
        return [
            'id' => $credential->id(),
            'specialty' => $specialtyNames->get($credential->specialtyId(), '—'),
            'degreeLevel' => $credential->degreeLevel()->value,
            'institution' => $credential->institution(),
            'yearObtained' => $credential->yearObtained()->value(),
        ];
    }
}
