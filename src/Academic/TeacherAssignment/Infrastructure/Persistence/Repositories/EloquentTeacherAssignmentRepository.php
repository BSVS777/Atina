<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Infrastructure\Persistence\Repositories;

use App\Models\TeacherAssignment as TeacherAssignmentModel;
use Illuminate\Database\Eloquent\Collection;
use Src\Academic\TeacherAssignment\Domain\Contracts\TeacherAssignmentRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Entities\TeacherAssignment;
use Src\Academic\TeacherAssignment\Domain\ProposalStatus;

final class EloquentTeacherAssignmentRepository implements TeacherAssignmentRepositoryInterface
{
    public function find(int $id): ?TeacherAssignment
    {
        $model = TeacherAssignmentModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function all(): array
    {
        /** @var Collection<int, TeacherAssignmentModel> $models */
        $models = TeacherAssignmentModel::query()->with(['group.course', 'group.academicTerm', 'teacher'])->orderByDesc('id')->get();

        return $models->map($this->toDomain(...))->all();
    }

    public function save(TeacherAssignment $assignment): TeacherAssignment
    {
        $model = $assignment->id() !== null
            ? TeacherAssignmentModel::query()->findOrFail($assignment->id())
            : new TeacherAssignmentModel;

        $model->fill([
            'grupo_id' => $assignment->courseGroupId(),
            'docente_id' => $assignment->teacherId(),
            'estado' => $assignment->status(),
        ]);
        $model->jornada ??= 1.00;
        $model->save();

        return $this->toDomain($model);
    }

    private function toDomain(TeacherAssignmentModel $model): TeacherAssignment
    {
        return new TeacherAssignment(
            id: $model->id,
            courseGroupId: $model->grupo_id,
            teacherId: $model->docente_id,
            status: $model->estado ?? ProposalStatus::Proposed,
        );
    }
}
