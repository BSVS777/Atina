<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Infrastructure\Persistence\Repositories;

use App\Models\AffinityVerification as AffinityVerificationModel;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Src\Academic\TeacherAssignment\Domain\Contracts\AffinityVerificationRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Entities\AffinityVerification;

final class EloquentAffinityVerificationRepository implements AffinityVerificationRepositoryInterface
{
    public function forAssignment(int $teacherAssignmentId): array
    {
        /** @var Collection<int, AffinityVerificationModel> $models */
        $models = AffinityVerificationModel::query()
            ->where('asignacion_docente_id', $teacherAssignmentId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return $models->map($this->toDomain(...))->all();
    }

    public function latestForAssignment(int $teacherAssignmentId): ?AffinityVerification
    {
        $model = AffinityVerificationModel::query()
            ->where('asignacion_docente_id', $teacherAssignmentId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function save(AffinityVerification $verification): AffinityVerification
    {
        $model = new AffinityVerificationModel;
        $model->fill([
            'asignacion_docente_id' => $verification->teacherAssignmentId(),
            'catalogo_atinencia_id' => $verification->catalogVersionId(),
            'atestado_id' => $verification->matchedCredentialId(),
            'user_id' => $verification->performedByUserId(),
            'resultado' => $verification->result(),
            'es_provisional' => $verification->isProvisional(),
            'justificacion' => $verification->justification(),
        ]);
        $model->save();

        return $this->toDomain($model);
    }

    private function toDomain(AffinityVerificationModel $model): AffinityVerification
    {
        return new AffinityVerification(
            id: $model->id,
            teacherAssignmentId: $model->asignacion_docente_id,
            catalogVersionId: $model->catalogo_atinencia_id,
            matchedCredentialId: $model->atestado_id,
            performedByUserId: $model->user_id,
            result: $model->resultado,
            isProvisional: (bool) $model->es_provisional,
            justification: $model->justificacion,
            performedAt: new DateTimeImmutable($model->created_at->toDateTimeString()),
        );
    }
}
