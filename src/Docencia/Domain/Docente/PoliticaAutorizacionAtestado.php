<?php

namespace Atina\Docencia\Domain\Docente;

/**
 * RN-04: solo Administrador o Coordinadora de Docencia modifican atestados.
 * Se expresa como un permiso, no como nombres de rol hardcodeados: la
 * asignación real rol → permiso (`atestados.gestionar`) vive en el RBAC
 * (tablas `roles`/`permission_role`), no en el dominio.
 */
final class PoliticaAutorizacionAtestado
{
    public const PERMISO_GESTIONAR = 'atestados.gestionar';

    /**
     * @param  list<string>  $permisosDelActor
     */
    public static function puedeGestionar(array $permisosDelActor): bool
    {
        return in_array(self::PERMISO_GESTIONAR, $permisosDelActor, true);
    }
}
