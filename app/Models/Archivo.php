<?php

namespace App\Models;

use Database\Factories\ArchivoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Tabla propiedad del módulo "Gestión Documental" (transversal, otro grupo).
 * Se define aquí, mínimo, porque notas_tecnicas.archivo_id la referencia
 * (el PDF firmado del criterio técnico es obligatorio, ver D13).
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
class Archivo extends Model
{
    /** @use HasFactory<ArchivoFactory> */
    use HasFactory;

    protected $table = 'archivos';

    protected $fillable = [
        'uuid', 'user_id', 'archivable_type', 'archivable_id',
        'tipo_documento', 'nombre_original', 'disco', 'ruta',
        'mime_type', 'tamano_bytes', 'hash_sha256',
    ];
}
