# Domain / NotaTecnica — DO-02b

Tabla `notas_tecnicas` del schema compartido: 1 nota técnica por
`asignacion_docente_id` (UNIQUE), `estado` ENUM `Ratificación pendiente,
Ratificada, Vencida, Rechazada`, `fecha_limite_ratificacion` (SLA),
`ratificada_at`, `archivo_id` NOT NULL (PDF firmado obligatorio).

Resuelve D12 (mismo registro, no paralelo), D13 (acción de ratificar existe:
transición a `Ratificada`) y D14 (vencida es estado terminal — no hay estado
de "reapertura" en el ENUM) de `Docs/DUDAS_LOGICA_NEGOCIO.md`.

Estado: no iniciado.
