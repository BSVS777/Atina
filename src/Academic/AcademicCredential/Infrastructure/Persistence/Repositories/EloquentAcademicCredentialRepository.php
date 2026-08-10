<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Infrastructure\Persistence\Repositories;

use App\Models\AcademicCredential as AcademicCredentialModel;
use Illuminate\Database\Eloquent\Collection;
use Src\Academic\AcademicCredential\Domain\Contracts\AcademicCredentialRepositoryInterface;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\AcademicCredential\Domain\Entities\AcademicCredential;
use Src\Academic\AcademicCredential\Domain\YearObtained;

final class EloquentAcademicCredentialRepository implements AcademicCredentialRepositoryInterface
{
    public function find(int $id): ?AcademicCredential
    {
        $model = AcademicCredentialModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function forTeacher(int $teacherId): array
    {
        /** @var Collection<int, AcademicCredentialModel> $models */
        $models = AcademicCredentialModel::query()
            ->where('teacher_id', $teacherId)
            ->orderBy('year_obtained', 'desc')
            ->get();

        return $models->map($this->toDomain(...))->all();
    }

    public function existsForTeacherSpecialtyDegree(
        int $teacherId,
        int $specialtyId,
        DegreeLevel $degreeLevel,
        ?int $exceptCredentialId = null,
    ): bool {
        return AcademicCredentialModel::query()
            ->where('teacher_id', $teacherId)
            ->where('specialty_id', $specialtyId)
            ->where('degree_level', $degreeLevel)
            ->when($exceptCredentialId !== null, fn ($query) => $query->whereKeyNot($exceptCredentialId))
            ->exists();
    }

    public function save(AcademicCredential $credential): AcademicCredential
    {
        $model = $credential->id() !== null
            ? AcademicCredentialModel::query()->findOrFail($credential->id())
            : new AcademicCredentialModel;

        $model->fill([
            'teacher_id' => $credential->teacherId(),
            'specialty_id' => $credential->specialtyId(),
            'degree_level' => $credential->degreeLevel(),
            'institution' => $credential->institution(),
            'year_obtained' => $credential->yearObtained()->value(),
        ]);
        $model->save();

        return $this->toDomain($model);
    }

    private function toDomain(AcademicCredentialModel $model): AcademicCredential
    {
        return new AcademicCredential(
            id: $model->id,
            teacherId: $model->teacher_id,
            specialtyId: $model->specialty_id,
            degreeLevel: $model->degree_level,
            institution: $model->institution,
            yearObtained: new YearObtained($model->year_obtained),
        );
    }
}
