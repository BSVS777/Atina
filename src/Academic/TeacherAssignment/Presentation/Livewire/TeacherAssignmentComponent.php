<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Presentation\Livewire;

use App\Models\AffinityCatalogVersion;
use App\Models\CourseGroup;
use App\Models\Teacher;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;
use Src\Academic\TeacherAssignment\Application\DTOs\AssignmentOverview;
use Src\Academic\TeacherAssignment\Application\UseCases\AttachTechnicalNoteUseCase;
use Src\Academic\TeacherAssignment\Application\UseCases\DecideNoCatalogAssignmentUseCase;
use Src\Academic\TeacherAssignment\Application\UseCases\ListTeacherAssignmentsUseCase;
use Src\Academic\TeacherAssignment\Application\UseCases\ProposeTeacherAssignmentUseCase;
use Src\Academic\TeacherAssignment\Application\UseCases\RatifyTechnicalNoteUseCase;
use Src\Academic\TeacherAssignment\Application\UseCases\RejectTechnicalNoteUseCase;
use Src\Academic\TeacherAssignment\Domain\Entities\TeacherAssignment;
use Src\Academic\TeacherAssignment\Domain\Entities\TechnicalNote;
use Src\Academic\TeacherAssignment\Domain\Exceptions\InvalidAssignmentTransitionException;
use Src\Academic\TeacherAssignment\Domain\VerificationResult;
use Src\Academic\TeacherAssignment\Presentation\Livewire\Forms\ProposeAssignmentForm;
use Src\Academic\TeacherAssignment\Presentation\Livewire\Forms\TechnicalNoteForm;

/**
 * DO-02a/DO-02b/DO-02d in one screen: proposing a teacher for a course
 * group runs the automatic verification; from a blocked/no-catalog row,
 * the Coordinadora can attach a Technical Note or decide the no-catalog
 * case manually; the Administrador ratifies/rejects a pending note.
 */
class TeacherAssignmentComponent extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public bool $showProposeModal = false;

    public bool $showNoteModal = false;

    public ?int $activeAssignmentId = null;

    public ProposeAssignmentForm $proposeForm;

    public TechnicalNoteForm $noteForm;

    public function openProposeModal(): void
    {
        $this->authorize('create', TeacherAssignment::class);

        $this->proposeForm->reset();
        $this->resetValidation();
        $this->showProposeModal = true;
    }

    public function closeProposeModal(): void
    {
        $this->showProposeModal = false;
    }

    public function propose(ProposeTeacherAssignmentUseCase $useCase): void
    {
        $this->authorize('create', TeacherAssignment::class);
        $this->proposeForm->validate();

        /** @var CourseGroup $group */
        $group = CourseGroup::query()->with('academicTerm')->findOrFail($this->proposeForm->courseGroupId);

        $useCase->handle(
            $this->proposeForm->toDto($group->course_id, $group->academicTerm->start_date->toDateString()),
            auth()->user()?->id,
        );

        $this->showProposeModal = false;
        $this->dispatch('toast', variant: 'success', text: __('Verification executed.'));
    }

    public function openNoteModal(int $assignmentId): void
    {
        $this->authorize('create', TechnicalNote::class);

        $this->activeAssignmentId = $assignmentId;
        $this->noteForm->reset();
        $this->resetValidation();
        $this->showNoteModal = true;
    }

    public function closeNoteModal(): void
    {
        $this->showNoteModal = false;
    }

    public function attachTechnicalNote(AttachTechnicalNoteUseCase $useCase): void
    {
        $this->authorize('create', TechnicalNote::class);
        $this->noteForm->validate();

        try {
            $useCase->handle($this->noteForm->toDto((int) $this->activeAssignmentId), auth()->user()?->id);
        } catch (InvalidAssignmentTransitionException $e) {
            $this->addError('noteForm.document', $e->getMessage());

            return;
        }

        $this->showNoteModal = false;
        $this->dispatch('toast', variant: 'success', text: __('Technical note registered — ratification pending.'));
    }

    public function approveNoCatalog(int $assignmentId, DecideNoCatalogAssignmentUseCase $useCase): void
    {
        $this->authorize('decide', TeacherAssignment::class);
        $useCase->handle($assignmentId, approve: true, actorUserId: auth()->user()?->id);
        $this->dispatch('toast', variant: 'success', text: __('Assignment manually approved.'));
    }

    public function rejectNoCatalog(int $assignmentId, DecideNoCatalogAssignmentUseCase $useCase): void
    {
        $this->authorize('decide', TeacherAssignment::class);
        $useCase->handle($assignmentId, approve: false, actorUserId: auth()->user()?->id);
        $this->dispatch('toast', variant: 'success', text: __('Assignment manually rejected.'));
    }

    public function ratifyNote(int $noteId, RatifyTechnicalNoteUseCase $useCase): void
    {
        $this->authorize('approve', TechnicalNote::class);
        $useCase->handle($noteId, auth()->user()?->id);
        $this->dispatch('toast', variant: 'success', text: __('Technical note ratified.'));
    }

    public function rejectNote(int $noteId, RejectTechnicalNoteUseCase $useCase): void
    {
        $this->authorize('approve', TechnicalNote::class);
        $useCase->handle($noteId, auth()->user()?->id);
        $this->dispatch('toast', variant: 'success', text: __('Technical note rejected.'));
    }

    public function render(ListTeacherAssignmentsUseCase $useCase): View
    {
        $overviews = $useCase->handle();
        $teachers = Teacher::query()->orderBy('primer_apellido')->get();
        $groups = CourseGroup::query()->with(['course', 'academicTerm'])->get();

        $teacherNames = $teachers->mapWithKeys(fn (Teacher $teacher) => [$teacher->id => $teacher->fullName()]);
        $groupLabels = $groups->mapWithKeys(fn (CourseGroup $group) => [$group->id => $group->label()]);
        $catalogCitations = AffinityCatalogVersion::query()->get()->mapWithKeys(
            fn (AffinityCatalogVersion $version) => [$version->id => "v{$version->version} — {$version->acuerdo} / Gaceta {$version->numero_gaceta}"]
        );

        return view('academic.teacher-assignment.livewire.teacher-assignment-component', [
            'rows' => array_map(fn (AssignmentOverview $overview) => $this->toRow($overview, $teacherNames, $groupLabels, $catalogCitations), $overviews),
            'teachers' => $teachers,
            'groups' => $groups,
            'canPropose' => auth()->user()->can('create', TeacherAssignment::class),
            'canDecide' => auth()->user()->can('decide', TeacherAssignment::class),
            'canApproveNote' => auth()->user()->can('approve', TechnicalNote::class),
        ])->layout('components.layouts.dashboard', [
            'title' => __('Teaching Affinity Verification'),
            'subtitle' => __('Propose teachers for course groups and resolve exceptional cases'),
        ]);
    }

    /**
     * @param  Collection<int, string>  $teacherNames
     * @param  Collection<int, string>  $groupLabels
     * @param  Collection<int, non-falsy-string>  $catalogCitations
     * @return array<string, mixed>
     */
    private function toRow(AssignmentOverview $overview, $teacherNames, $groupLabels, $catalogCitations): array
    {
        $assignment = $overview->assignment;
        $verification = $overview->latestVerification;
        $note = $overview->technicalNote;

        return [
            'id' => $assignment->id(),
            'teacher' => $teacherNames->get($assignment->teacherId(), '—'),
            'group' => $groupLabels->get($assignment->courseGroupId(), '—'),
            'status' => $assignment->status()->value,
            'result' => $verification?->result()->value,
            'isProvisional' => $verification?->isProvisional() ?? false,
            'catalogCitation' => $verification?->catalogVersionId() !== null
                ? $catalogCitations->get($verification->catalogVersionId())
                : null,
            'canAttachNote' => $verification !== null
                && in_array($verification->result(), [VerificationResult::NotMatched, VerificationResult::NoCatalog], true)
                && $note === null,
            'canDecideNoCatalog' => $verification?->result() === VerificationResult::NoCatalog
                && ! $assignment->isDecided(),
            'note' => $note === null ? null : [
                'id' => $note->id(),
                'status' => $note->status()->value,
                'deadline' => $note->ratificationDeadline()->format('Y-m-d'),
                'isPending' => $note->status()->value === 'pending_ratification',
            ],
        ];
    }
}
