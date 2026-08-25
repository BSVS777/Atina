<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Infrastructure\Persistence\Repositories;

use App\Models\AcademicCredential as AcademicCredentialModel;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Src\Academic\AcademicCredential\Domain\Contracts\AcademicCredentialRepositoryInterface;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\AcademicCredential\Domain\Entities\AcademicCredential;
use Src\Academic\AcademicCredential\Domain\Exceptions\CorruptCredentialRecordException;
use Src\Academic\AcademicCredential\Domain\StudyPeriod;
use Src\Academic\AcademicCredential\Infrastructure\Persistence\Casts\DegreeLevelCast;

/**
 * The only place (besides the Eloquent model itself) aware that the
 * underlying table is the professor-provided `atestados` with Spanish
 * column names — see AcademicCredential (App\Models) docblock.
 */
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
            ->where('docente_id', $teacherId)
            ->orderBy('fecha_fin', 'desc')
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
            ->where('docente_id', $teacherId)
            ->where('especialidad_id', $specialtyId)
            ->where('grado', DegreeLevelCast::toDatabaseValue($degreeLevel))
            ->when($exceptCredentialId !== null, fn ($query) => $query->whereKeyNot($exceptCredentialId))
            ->exists();
    }

    public function save(AcademicCredential $credential): AcademicCredential
    {
        $model = $credential->id() !== null
            ? AcademicCredentialModel::query()->findOrFail($credential->id())
            : new AcademicCredentialModel;

        $model->fill([
            'docente_id' => $credential->teacherId(),
            'especialidad_id' => $credential->specialtyId(),
            'grado' => $credential->degreeLevel(),
            'institucion' => $credential->institution(),
            'fecha_inicio' => $credential->studyPeriod()->startDate()->format('Y-m-d'),
            'fecha_fin' => $credential->studyPeriod()->endDate()->format('Y-m-d'),
        ]);
        $model->save();

        return $this->toDomain($model);
    }

    private function toDomain(AcademicCredentialModel $model): AcademicCredential
    {
        if ($model->getRawOriginal('fecha_inicio') === null || $model->getRawOriginal('fecha_fin') === null) {
            throw CorruptCredentialRecordException::missingStudyPeriod($model->id);
        }

        return new AcademicCredential(
            id: $model->id,
            teacherId: $model->docente_id,
            specialtyId: $model->especialidad_id,
            degreeLevel: $model->grado,
            institution: $model->institucion,
            studyPeriod: new StudyPeriod(
                new DateTimeImmutable($model->fecha_inicio->format('Y-m-d')),
                new DateTimeImmutable($model->fecha_fin->format('Y-m-d')),
            ),
        );
    }
}
