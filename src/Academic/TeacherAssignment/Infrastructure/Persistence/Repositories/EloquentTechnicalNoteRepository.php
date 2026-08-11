<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Infrastructure\Persistence\Repositories;

use App\Models\Archivo;
use App\Models\TechnicalNote as TechnicalNoteModel;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Src\Academic\TeacherAssignment\Domain\Contracts\TechnicalNoteRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Contracts\UploadedDocument;
use Src\Academic\TeacherAssignment\Domain\Entities\TechnicalNote;
use Src\Academic\TeacherAssignment\Domain\TechnicalNoteStatus;
use Src\Academic\TeacherAssignment\Infrastructure\Persistence\Casts\TechnicalNoteStatusCast;

final class EloquentTechnicalNoteRepository implements TechnicalNoteRepositoryInterface
{
    public function find(int $id): ?TechnicalNote
    {
        $model = TechnicalNoteModel::query()->with('archivo')->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function forAssignment(int $teacherAssignmentId): ?TechnicalNote
    {
        $model = TechnicalNoteModel::query()->with('archivo')->where('asignacion_docente_id', $teacherAssignmentId)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function pendingRatification(): array
    {
        /** @var Collection<int, TechnicalNoteModel> $models */
        $models = TechnicalNoteModel::query()
            ->with('archivo')
            ->where('estado', TechnicalNoteStatusCast::toDatabaseValue(TechnicalNoteStatus::PendingRatification))
            ->get();

        return $models->map($this->toDomain(...))->all();
    }

    public function save(TechnicalNote $note, ?UploadedDocument $document = null): TechnicalNote
    {
        $model = $note->id() !== null
            ? TechnicalNoteModel::query()->findOrFail($note->id())
            : new TechnicalNoteModel;

        if ($document !== null) {
            $archivo = Archivo::query()->create([
                'archivable_type' => TechnicalNoteModel::class,
                'archivable_id' => 0,
                'tipo_documento' => 'Criterio Técnico',
                'nombre_original' => $document->originalFileName,
                'disco' => 'local',
                'ruta' => $document->storagePath,
                'mime_type' => $document->mimeType,
                'tamano_bytes' => $document->sizeBytes,
                'hash_sha256' => $document->hashSha256,
                'user_id' => $note->submittedByUserId(),
            ]);
            $model->archivo_id = $archivo->id;
        }

        $model->fill([
            'asignacion_docente_id' => $note->teacherAssignmentId(),
            'user_id' => $note->submittedByUserId(),
            'fecha_limite_ratificacion' => $note->ratificationDeadline()->format('Y-m-d'),
            'estado' => $note->status(),
            'ratificada_at' => $note->ratifiedAt()?->format('Y-m-d H:i:s'),
        ]);
        $model->save();

        if ($document !== null) {
            $model->archivo->update(['archivable_id' => $model->id]);
        }

        return $this->toDomain($model);
    }

    private function toDomain(TechnicalNoteModel $model): TechnicalNote
    {
        return new TechnicalNote(
            id: $model->id,
            teacherAssignmentId: $model->asignacion_docente_id,
            documentPath: $model->archivo->ruta,
            submittedByUserId: $model->user_id,
            ratificationDeadline: new DateTimeImmutable($model->fecha_limite_ratificacion->toDateString()),
            status: $model->estado,
            ratifiedAt: $model->ratificada_at !== null ? new DateTimeImmutable($model->ratificada_at->toDateTimeString()) : null,
        );
    }
}
