<?php

namespace App\Models;

use Database\Factories\ArchivoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Maps to the professor-provided `archivos` table (institutional schema,
 * not owned by this project) — the generic attachment mechanism the
 * whole institutional schema uses for any document. This module only
 * uses it for the Technical Note's signed PDF (DO-02b). Only
 * EloquentTechnicalNoteRepository creates rows here — see
 * Docs/DIARIO_DECISIONES_IA.md.
 *
 * @property int $id
 * @property string $uuid
 * @property int|null $user_id
 * @property string $archivable_type
 * @property int $archivable_id
 * @property string $tipo_documento
 * @property string $nombre_original
 * @property string $disco
 * @property string $ruta
 * @property string $mime_type
 * @property int $tamano_bytes
 * @property string $hash_sha256
 */
#[Fillable(['uuid', 'user_id', 'archivable_type', 'archivable_id', 'tipo_documento', 'nombre_original', 'disco', 'ruta', 'mime_type', 'tamano_bytes', 'hash_sha256'])]
class Archivo extends Model
{
    /** @use HasFactory<ArchivoFactory> */
    use HasFactory;

    protected $table = 'archivos';

    protected static function booted(): void
    {
        static::creating(function (self $archivo): void {
            $archivo->uuid ??= (string) Str::uuid();
        });
    }
}
