-- =====================================================================
-- Sistema Integrado de Gestión Académica y Docente — UTN Sede San Carlos
-- MySQL 8.x | Compatible con MySQL Workbench (Reverse Engineer / EER)
-- Convenciones Laravel: tablas en plural, PK `id`, FK `tabla_id`.
-- Nombramientos, continuidad/estabilidad, jornada de derecho y licencias
-- docentes: diferidos a fase 2 con RRHH. Ver nota de alcance al final.
-- =====================================================================
CREATE DATABASE IF NOT EXISTS gestion_academica_utn
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE gestion_academica_utn;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- SECCIÓN 1. NÚCLEO DE AUTENTICACIÓN
-- =====================================================================

-- 1.1 users (incluye columnas de dos factores de la migración 2025_08_14_170933)
CREATE TABLE users (
  id                        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name                      VARCHAR(255) NOT NULL,
  email                     VARCHAR(255) NOT NULL,
  email_verified_at         TIMESTAMP NULL DEFAULT NULL,
  password                  VARCHAR(255) NOT NULL,
  two_factor_secret         TEXT NULL,
  two_factor_recovery_codes TEXT NULL,
  two_factor_confirmed_at   TIMESTAMP NULL DEFAULT NULL,
  remember_token            VARCHAR(100) NULL DEFAULT NULL,
  created_at                TIMESTAMP NULL DEFAULT NULL,
  updated_at                TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY users_email_unique (email)
) ENGINE = InnoDB;

-- 1.2 password_reset_tokens
CREATE TABLE password_reset_tokens (
  email      VARCHAR(255) NOT NULL,
  token      VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (email)
) ENGINE = InnoDB;

-- 1.3 sessions
CREATE TABLE sessions (
  id            VARCHAR(255) NOT NULL,
  user_id       BIGINT UNSIGNED NULL DEFAULT NULL,
  ip_address    VARCHAR(45) NULL DEFAULT NULL,
  user_agent    TEXT NULL,
  payload       LONGTEXT NOT NULL,
  last_activity INT NOT NULL,
  PRIMARY KEY (id),
  KEY sessions_user_id_index (user_id),
  KEY sessions_last_activity_index (last_activity),
  CONSTRAINT fk_sessions_user_id
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- 1.4 passkeys (WebAuthn, con relación explícita a user_id)
CREATE TABLE passkeys (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id       BIGINT UNSIGNED NOT NULL,
  name          VARCHAR(255) NOT NULL,
  credential_id VARCHAR(255) NOT NULL,
  credential    JSON NOT NULL,
  last_used_at  TIMESTAMP NULL DEFAULT NULL,
  created_at    TIMESTAMP NULL DEFAULT NULL,
  updated_at    TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY passkeys_credential_id_unique (credential_id),
  KEY passkeys_user_id_index (user_id),
  CONSTRAINT fk_passkeys_user_id
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- =====================================================================
-- SECCIÓN 2. INFRAESTRUCTURA
-- =====================================================================

CREATE TABLE cache (
  `key`      VARCHAR(255) NOT NULL,
  value      MEDIUMTEXT NOT NULL,
  expiration BIGINT NOT NULL,
  PRIMARY KEY (`key`),
  KEY cache_expiration_index (expiration)
) ENGINE = InnoDB;

CREATE TABLE cache_locks (
  `key`      VARCHAR(255) NOT NULL,
  owner      VARCHAR(255) NOT NULL,
  expiration BIGINT NOT NULL,
  PRIMARY KEY (`key`),
  KEY cache_locks_expiration_index (expiration)
) ENGINE = InnoDB;

CREATE TABLE jobs (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  queue        VARCHAR(255) NOT NULL,
  payload      LONGTEXT NOT NULL,
  attempts     SMALLINT UNSIGNED NOT NULL,
  reserved_at  INT UNSIGNED NULL DEFAULT NULL,
  available_at INT UNSIGNED NOT NULL,
  created_at   INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY jobs_queue_index (queue)
) ENGINE = InnoDB;

CREATE TABLE job_batches (
  id             VARCHAR(255) NOT NULL,
  name           VARCHAR(255) NOT NULL,
  total_jobs     INT NOT NULL,
  pending_jobs   INT NOT NULL,
  failed_jobs    INT NOT NULL,
  failed_job_ids LONGTEXT NOT NULL,
  options        MEDIUMTEXT NULL,
  cancelled_at   INT NULL DEFAULT NULL,
  created_at     INT NOT NULL,
  finished_at    INT NULL DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE = InnoDB;

CREATE TABLE failed_jobs (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uuid       VARCHAR(255) NOT NULL,
  connection VARCHAR(255) NOT NULL,
  queue      VARCHAR(255) NOT NULL,
  payload    LONGTEXT NOT NULL,
  exception  LONGTEXT NOT NULL,
  failed_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY failed_jobs_uuid_unique (uuid),
  KEY failed_jobs_connection_queue_failed_at_index (connection, queue, failed_at)
) ENGINE = InnoDB;

-- =====================================================================
-- SECCIÓN 3. RBAC
-- =====================================================================

CREATE TABLE roles (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(60) NOT NULL,
  description VARCHAR(255) NULL DEFAULT NULL,
  created_at  TIMESTAMP NULL DEFAULT NULL,
  updated_at  TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY roles_name_unique (name)
) ENGINE = InnoDB;

CREATE TABLE permissions (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(80) NOT NULL,
  description VARCHAR(255) NULL DEFAULT NULL,
  created_at  TIMESTAMP NULL DEFAULT NULL,
  updated_at  TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY permissions_name_unique (name)
) ENGINE = InnoDB;

-- Pivote users <-> roles. PK compuesta (user_id primero): resolver
CREATE TABLE role_user (
  user_id    BIGINT UNSIGNED NOT NULL,
  role_id    BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (user_id, role_id),
  KEY role_user_role_id_index (role_id),
  CONSTRAINT fk_role_user_user_id
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_role_user_role_id
    FOREIGN KEY (role_id) REFERENCES roles (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- Pivote roles <-> permissions. PK (role_id primero): la expansión
CREATE TABLE permission_role (
  role_id       BIGINT UNSIGNED NOT NULL,
  permission_id BIGINT UNSIGNED NOT NULL,
  created_at    TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (role_id, permission_id),
  KEY permission_role_permission_id_index (permission_id),
  CONSTRAINT fk_permission_role_role_id
    FOREIGN KEY (role_id) REFERENCES roles (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_permission_role_permission_id
    FOREIGN KEY (permission_id) REFERENCES permissions (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- Pivote users <-> permissions (PERMISOS DIRECTOS/EXTRA por usuario).
CREATE TABLE permission_user (
  user_id       BIGINT UNSIGNED NOT NULL,
  permission_id BIGINT UNSIGNED NOT NULL,
  otorgado_por  BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Quién concedió el permiso extra',
  created_at    TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (user_id, permission_id),
  KEY permission_user_permission_id_index (permission_id),
  KEY permission_user_otorgado_por_index (otorgado_por),
  CONSTRAINT fk_permission_user_user_id
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_permission_user_permission_id
    FOREIGN KEY (permission_id) REFERENCES permissions (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_permission_user_otorgado_por
    FOREIGN KEY (otorgado_por) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

-- =====================================================================
-- SECCIÓN 4. CATÁLOGOS ACADÉMICOS
-- =====================================================================

-- 4.1 Carreras (las 14 carreras del Manual de Atinencias en alcance)
CREATE TABLE carreras (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre     VARCHAR(150) NOT NULL,
  activa     TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY carreras_nombre_unique (nombre)
) ENGINE = InnoDB;

-- 4.2 Unidades ejecutoras (columna "# Unidad Ejecutora" de la hoja ITI)
CREATE TABLE unidades_ejecutoras (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo     CHAR(10) NOT NULL,
  nombre     VARCHAR(150) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY unidades_ejecutoras_codigo_unique (codigo)
) ENGINE = InnoDB;

-- 4.3 Metas presupuestarias (columnas "# Meta" / "Nombre Meta": 013001 Diplomado, 013002 Bachillerato)
CREATE TABLE metas (
  id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  unidad_ejecutora_id  BIGINT UNSIGNED NOT NULL,
  codigo               CHAR(6) NOT NULL,
  nombre               VARCHAR(100) NOT NULL,
  created_at           TIMESTAMP NULL DEFAULT NULL,
  updated_at           TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY metas_unidad_codigo_unique (unidad_ejecutora_id, codigo),
  CONSTRAINT fk_metas_unidad_ejecutora_id
    FOREIGN KEY (unidad_ejecutora_id) REFERENCES unidades_ejecutoras (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

-- 4.4 Períodos académicos (cuatrimestres; ej. III Cuatrimestre 2025)
CREATE TABLE periodos_academicos (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  anio         SMALLINT UNSIGNED NOT NULL,
  cuatrimestre TINYINT UNSIGNED NOT NULL COMMENT '1, 2 o 3',
  fecha_inicio DATE NOT NULL,
  fecha_fin    DATE NOT NULL,
  created_at   TIMESTAMP NULL DEFAULT NULL,
  updated_at   TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY periodos_academicos_anio_cuatrimestre_unique (anio, cuatrimestre),
  CONSTRAINT chk_periodos_cuatrimestre CHECK (cuatrimestre BETWEEN 1 AND 3)
) ENGINE = InnoDB;

-- 4.5 Recintos físicos (la reasignación masiva de grupos se hace por recinto)
CREATE TABLE recintos (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre     VARCHAR(80) NOT NULL,
  es_propio  TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0 = alquilado/convenio (UNED, Santa Fe)',
  activo     TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY recintos_nombre_unique (nombre)
) ENGINE = InnoDB;

-- 4.6 Aulas y espacios físicos
CREATE TABLE aulas (
  id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  recinto_id           BIGINT UNSIGNED NULL DEFAULT NULL,
  nombre               VARCHAR(30) NOT NULL,
  piso                 VARCHAR(10) NULL DEFAULT NULL,
  tipo                 ENUM('Aula regular','Laboratorio de cómputo','Laboratorio de ciencias',
                            'Laboratorio de idiomas','Auditorio','Otro')
                       NOT NULL DEFAULT 'Aula regular',
  capacidad            SMALLINT UNSIGNED NULL DEFAULT NULL,
  no_disponible_desde  DATE NULL DEFAULT NULL COMMENT 'No disponible a partir de esta fecha',
  created_at           TIMESTAMP NULL DEFAULT NULL,
  updated_at           TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY aulas_nombre_unique (nombre),
  KEY aulas_recinto_id_index (recinto_id),
  KEY aulas_tipo_capacidad_index (tipo, capacidad),
  CONSTRAINT fk_aulas_recinto_id
    FOREIGN KEY (recinto_id) REFERENCES recintos (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

-- 4.6b Equipamiento como catálogo N:M (permite filtrar aulas por equipo)
CREATE TABLE equipamientos (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre     VARCHAR(80) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY equipamientos_nombre_unique (nombre)
) ENGINE = InnoDB;

CREATE TABLE aula_equipamiento (
  aula_id         BIGINT UNSIGNED NOT NULL,
  equipamiento_id BIGINT UNSIGNED NOT NULL,
  cantidad        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  created_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (aula_id, equipamiento_id),
  KEY aula_equipamiento_equipamiento_index (equipamiento_id),
  CONSTRAINT fk_aula_equipamiento_aula_id
    FOREIGN KEY (aula_id) REFERENCES aulas (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_aula_equipamiento_equipamiento_id
    FOREIGN KEY (equipamiento_id) REFERENCES equipamientos (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- 4.7 Modalidades: catálogo maestro; requiere_resolucion condiciona su uso
CREATE TABLE modalidades (
  id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre              VARCHAR(40) NOT NULL,
  requiere_resolucion TINYINT(1) NOT NULL DEFAULT 0,
  created_at          TIMESTAMP NULL DEFAULT NULL,
  updated_at          TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY modalidades_nombre_unique (nombre)
) ENGINE = InnoDB;

-- 4.8 Cursos (carrera_id NULL = curso de servicio transversal)
CREATE TABLE cursos (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  carrera_id            BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL cuando es curso de servicio transversal',
  codigo                VARCHAR(30) NOT NULL COMMENT 'Ej.: ITI-224, ITIEL-13',
  nombre                VARCHAR(150) NOT NULL,
  es_servicio           TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = curso transversal administrado por Docencia',
  es_cuello_botella     TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = curso pinned: prioridad de horario y aula',
  requiere_laboratorio  TINYINT(1) NOT NULL DEFAULT 0,
  tipo_laboratorio      ENUM('Laboratorio de cómputo','Laboratorio de ciencias','Laboratorio de idiomas')
                        NULL DEFAULT NULL COMMENT 'Tipo de laboratorio requerido',
  activo                TINYINT(1) NOT NULL DEFAULT 1,
  created_at            TIMESTAMP NULL DEFAULT NULL,
  updated_at            TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY cursos_codigo_unique (codigo),
  KEY cursos_carrera_id_index (carrera_id),
  CONSTRAINT fk_cursos_carrera_id
    FOREIGN KEY (carrera_id) REFERENCES carreras (id)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT chk_cursos_servicio_carrera
    CHECK (es_servicio = 1 OR carrera_id IS NOT NULL)
) ENGINE = InnoDB;

-- 4.9 Resoluciones de modalidad por curso (adjunto vía `archivos`)
CREATE TABLE resoluciones_modalidad (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  curso_id          BIGINT UNSIGNED NOT NULL,
  modalidad_id      BIGINT UNSIGNED NOT NULL,
  numero_resolucion VARCHAR(60) NOT NULL COMMENT 'Ej.: Resolución/acuerdo de vicerrectoría',
  organo_aprobador  VARCHAR(120) NOT NULL,
  vigencia_inicio   DATE NOT NULL,
  vigencia_fin      DATE NULL DEFAULT NULL COMMENT 'NULL = vigencia indefinida',
  created_at        TIMESTAMP NULL DEFAULT NULL,
  updated_at        TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY resoluciones_modalidad_curso_modalidad_numero_unique (curso_id, modalidad_id, numero_resolucion),
  KEY resoluciones_modalidad_curso_vigencia_index (curso_id, vigencia_inicio, vigencia_fin),
  KEY resoluciones_modalidad_modalidad_id_index (modalidad_id),
  CONSTRAINT fk_resoluciones_modalidad_curso_id
    FOREIGN KEY (curso_id) REFERENCES cursos (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_resoluciones_modalidad_modalidad_id
    FOREIGN KEY (modalidad_id) REFERENCES modalidades (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

-- =====================================================================
-- SECCIÓN 4C. REPOSITORIO CURRICULAR
-- =====================================================================

-- 4C.1 Planes de estudio (Vigente/Terminal; los Terminal exigen fecha de cierre)
CREATE TABLE planes_estudio (
  id                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  carrera_id             BIGINT UNSIGNED NOT NULL,
  nombre                 VARCHAR(120) NOT NULL COMMENT 'Ej.: Plan 2023, Plan 2025',
  anio_implementacion    YEAR NOT NULL,
  clasificacion          ENUM('Vigente','Terminal') NOT NULL DEFAULT 'Vigente',
  fecha_cierre_matricula DATE NULL DEFAULT NULL COMMENT 'Obligatoria solo para planes Terminal',
  created_at             TIMESTAMP NULL DEFAULT NULL,
  updated_at             TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY planes_estudio_carrera_nombre_unique (carrera_id, nombre),
  KEY planes_estudio_clasificacion_index (clasificacion),
  CONSTRAINT fk_planes_estudio_carrera_id
    FOREIGN KEY (carrera_id) REFERENCES carreras (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_planes_terminal_fecha
    CHECK (clasificacion = 'Vigente' OR fecha_cierre_matricula IS NOT NULL)
) ENGINE = InnoDB;

-- 4C.2 Niveles del plan (nivel 1, 2, 3, ... por cuatrimestre)
CREATE TABLE niveles (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  plan_estudio_id BIGINT UNSIGNED NOT NULL,
  numero          TINYINT UNSIGNED NOT NULL,
  created_at      TIMESTAMP NULL DEFAULT NULL,
  updated_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY niveles_plan_numero_unique (plan_estudio_id, numero),
  CONSTRAINT fk_niveles_plan_estudio_id
    FOREIGN KEY (plan_estudio_id) REFERENCES planes_estudio (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- 4C.3 Pivote curso <-> nivel: estructura del plan con créditos por plan
CREATE TABLE curso_nivel (
  nivel_id   BIGINT UNSIGNED NOT NULL,
  curso_id   BIGINT UNSIGNED NOT NULL,
  creditos   TINYINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (nivel_id, curso_id),
  KEY curso_nivel_curso_id_index (curso_id),
  CONSTRAINT fk_curso_nivel_nivel_id
    FOREIGN KEY (nivel_id) REFERENCES niveles (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_curso_nivel_curso_id
    FOREIGN KEY (curso_id) REFERENCES cursos (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

-- 4C.4 Requisitos entre cursos del mismo plan
CREATE TABLE requisitos (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  plan_estudio_id   BIGINT UNSIGNED NOT NULL,
  curso_requerido_id BIGINT UNSIGNED NOT NULL COMMENT 'Curso que debe aprobarse primero',
  curso_exige_id    BIGINT UNSIGNED NOT NULL COMMENT 'Curso que exige el requisito',
  created_at        TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY requisitos_plan_par_unique (plan_estudio_id, curso_requerido_id, curso_exige_id),
  KEY requisitos_curso_exige_index (curso_exige_id),
  KEY requisitos_curso_requerido_index (curso_requerido_id),
  CONSTRAINT fk_requisitos_plan_estudio_id
    FOREIGN KEY (plan_estudio_id) REFERENCES planes_estudio (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_requisitos_curso_requerido_id
    FOREIGN KEY (curso_requerido_id) REFERENCES cursos (id)
    ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT fk_requisitos_curso_exige_id
    FOREIGN KEY (curso_exige_id) REFERENCES cursos (id)
    ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT chk_requisitos_distintos CHECK (curso_requerido_id <> curso_exige_id)
) ENGINE = InnoDB;

-- 4C.5 Equiparaciones entre planes; anticiclos y adjunto obligatorio en capa app
CREATE TABLE equiparaciones (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  curso_origen_id    BIGINT UNSIGNED NOT NULL COMMENT 'Curso del plan anterior',
  curso_destino_id   BIGINT UNSIGNED NOT NULL COMMENT 'Curso equivalente del plan nuevo',
  sentido            ENUM('Anterior a nuevo','Nuevo a anterior','Bidireccional') NOT NULL,
  numero_resolucion  VARCHAR(60) NOT NULL,
  estado             ENUM('Vigente','Sustituida') NOT NULL DEFAULT 'Vigente',
  sustituida_por_id  BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Equiparación que prevalece (RC-02)',
  created_at         TIMESTAMP NULL DEFAULT NULL,
  updated_at         TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY equiparaciones_par_resolucion_unique (curso_origen_id, curso_destino_id, numero_resolucion),
  KEY equiparaciones_curso_destino_index (curso_destino_id),
  KEY equiparaciones_estado_index (estado),
  KEY equiparaciones_sustituida_por_index (sustituida_por_id),
  CONSTRAINT fk_equiparaciones_curso_origen_id
    FOREIGN KEY (curso_origen_id) REFERENCES cursos (id)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_equiparaciones_curso_destino_id
    FOREIGN KEY (curso_destino_id) REFERENCES cursos (id)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_equiparaciones_sustituida_por_id
    FOREIGN KEY (sustituida_por_id) REFERENCES equiparaciones (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_equiparaciones_distintos CHECK (curso_origen_id <> curso_destino_id)
) ENGINE = InnoDB;

-- =====================================================================
-- SECCIÓN 4D. ESTUDIANTES
-- =====================================================================

CREATE TABLE estudiantes (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id          BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Cuenta de acceso al portal',
  cedula           VARCHAR(12) NOT NULL,
  nombre           VARCHAR(60) NOT NULL,
  primer_apellido  VARCHAR(60) NOT NULL,
  segundo_apellido VARCHAR(60) NULL DEFAULT NULL,
  activo           TINYINT(1) NOT NULL DEFAULT 1,
  created_at       TIMESTAMP NULL DEFAULT NULL,
  updated_at       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY estudiantes_cedula_unique (cedula),
  UNIQUE KEY estudiantes_user_id_unique (user_id),
  KEY estudiantes_apellidos_index (primer_apellido, segundo_apellido),
  CONSTRAINT fk_estudiantes_user_id
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

-- Población por plan/nivel (RC-01: "cuántos estudiantes activos por plan y nivel")
CREATE TABLE estudiante_plan (
  estudiante_id   BIGINT UNSIGNED NOT NULL,
  plan_estudio_id BIGINT UNSIGNED NOT NULL,
  nivel_actual    TINYINT UNSIGNED NULL DEFAULT NULL,
  created_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (estudiante_id, plan_estudio_id),
  KEY estudiante_plan_plan_nivel_index (plan_estudio_id, nivel_actual),
  CONSTRAINT fk_estudiante_plan_estudiante_id
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_estudiante_plan_plan_estudio_id
    FOREIGN KEY (plan_estudio_id) REFERENCES planes_estudio (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- Historial académico interno simplificado (RC-02b acredita aquí por
CREATE TABLE historial_academico (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  estudiante_id         BIGINT UNSIGNED NOT NULL,
  curso_id              BIGINT UNSIGNED NOT NULL,
  periodo_academico_id  BIGINT UNSIGNED NULL DEFAULT NULL,
  estado                ENUM('Aprobado','Reprobado','Acreditado por equiparación',
                             'Acreditado por convalidación','Requisito levantado') NOT NULL,
  nota                  DECIMAL(5,2) NULL DEFAULT NULL,
  equiparacion_id       BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Resolución de referencia de la acreditación',
  created_at            TIMESTAMP NULL DEFAULT NULL,
  updated_at            TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY historial_academico_estudiante_curso_index (estudiante_id, curso_id),
  KEY historial_academico_curso_estado_index (curso_id, estado),
  KEY historial_academico_periodo_index (periodo_academico_id),
  KEY historial_academico_equiparacion_index (equiparacion_id),
  CONSTRAINT fk_historial_academico_estudiante_id
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_historial_academico_curso_id
    FOREIGN KEY (curso_id) REFERENCES cursos (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_historial_academico_periodo_id
    FOREIGN KEY (periodo_academico_id) REFERENCES periodos_academicos (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_historial_academico_equiparacion_id
    FOREIGN KEY (equiparacion_id) REFERENCES equiparaciones (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

-- =====================================================================
-- SECCIÓN 5. ATINENCIAS
-- =====================================================================

-- 5.1 Especialidades/grados habilitantes (listas "- ..." del Manual)
CREATE TABLE especialidades (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre     VARCHAR(180) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY especialidades_nombre_unique (nombre)
) ENGINE = InnoDB;

-- 5.2 Catálogo de atinencias por curso, versionado (acuerdo + Gaceta + vigencia)
CREATE TABLE catalogos_atinencia (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  curso_id        BIGINT UNSIGNED NOT NULL,
  version         SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  acuerdo         VARCHAR(120) NOT NULL COMMENT 'Acuerdo del Consejo Universitario (obligatorio)',
  numero_gaceta   VARCHAR(60) NOT NULL COMMENT 'Número de La Gaceta (obligatorio)',
  vigencia_inicio DATE NOT NULL,
  vigencia_fin    DATE NULL DEFAULT NULL,
  created_at      TIMESTAMP NULL DEFAULT NULL,
  updated_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY catalogos_atinencia_curso_version_unique (curso_id, version),
  KEY catalogos_atinencia_curso_vigencia_index (curso_id, vigencia_inicio),
  CONSTRAINT fk_catalogos_atinencia_curso_id
    FOREIGN KEY (curso_id) REFERENCES cursos (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- 5.3 Pivote: especialidades atinentes por versión de catálogo
CREATE TABLE catalogo_atinencia_especialidad (
  catalogo_atinencia_id BIGINT UNSIGNED NOT NULL,
  especialidad_id       BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (catalogo_atinencia_id, especialidad_id),
  KEY cat_atin_esp_especialidad_id_index (especialidad_id),
  CONSTRAINT fk_cat_atin_esp_catalogo_id
    FOREIGN KEY (catalogo_atinencia_id) REFERENCES catalogos_atinencia (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_cat_atin_esp_especialidad_id
    FOREIGN KEY (especialidad_id) REFERENCES especialidades (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

-- =====================================================================
-- SECCIÓN 6. DOCENTES Y ATESTADOS
-- =====================================================================

-- 6.1 Puestos (columna "Puesto": Profesor 2/3/4, Profesor Especialista 1)
CREATE TABLE puestos (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre     VARCHAR(60) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY puestos_nombre_unique (nombre)
) ENGINE = InnoDB;

-- 6.2 Docentes (columnas "Cédula" y "Docente" de la hoja ITI)
CREATE TABLE docentes (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id          BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Cuenta de acceso opcional',
  puesto_id        BIGINT UNSIGNED NOT NULL,
  cedula           VARCHAR(12) NOT NULL,
  nombre           VARCHAR(60) NOT NULL,
  primer_apellido  VARCHAR(60) NOT NULL,
  segundo_apellido VARCHAR(60) NULL DEFAULT NULL,
  jornada_estimada DECIMAL(3,2) NULL DEFAULT NULL COMMENT 'Indicativa; sujeta a confirmación de RRHH',
  activo           TINYINT(1) NOT NULL DEFAULT 1,
  created_at       TIMESTAMP NULL DEFAULT NULL,
  updated_at       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY docentes_cedula_unique (cedula),
  UNIQUE KEY docentes_user_id_unique (user_id),
  KEY docentes_puesto_id_index (puesto_id),
  KEY docentes_apellidos_index (primer_apellido, segundo_apellido),
  CONSTRAINT fk_docentes_user_id
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_docentes_puesto_id
    FOREIGN KEY (puesto_id) REFERENCES puestos (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

-- 6.3 Atestados académicos: base de la verificación de atinencia
CREATE TABLE atestados (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  docente_id      BIGINT UNSIGNED NOT NULL,
  especialidad_id BIGINT UNSIGNED NOT NULL,
  grado           ENUM('Diplomado','Bachillerato','Licenciatura','Maestría','Doctorado') NOT NULL,
  institucion     VARCHAR(150) NOT NULL,
  anio_obtencion  YEAR NOT NULL,
  created_at      TIMESTAMP NULL DEFAULT NULL,
  updated_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY atestados_docente_especialidad_grado_unique (docente_id, especialidad_id, grado),
  KEY atestados_especialidad_id_index (especialidad_id),
  CONSTRAINT fk_atestados_docente_id
    FOREIGN KEY (docente_id) REFERENCES docentes (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_atestados_especialidad_id
    FOREIGN KEY (especialidad_id) REFERENCES especialidades (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

-- =====================================================================
-- SECCIÓN 7. OFERTA ACADÉMICA
-- =====================================================================

-- 7.1 Grupos: instancia de un curso en un período (columnas "Grupo", "Cupo", "Modalidad", "Aula")
CREATE TABLE grupos (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  curso_id              BIGINT UNSIGNED NOT NULL,
  periodo_academico_id  BIGINT UNSIGNED NOT NULL,
  meta_id               BIGINT UNSIGNED NOT NULL,
  aula_id               BIGINT UNSIGNED NULL DEFAULT NULL,
  modalidad_id          BIGINT UNSIGNED NOT NULL,
  numero                TINYINT UNSIGNED NOT NULL COMMENT 'Número de grupo (1, 2, ...)',
  cupo                  SMALLINT UNSIGNED NOT NULL,
  matricula_estimada    SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'Demanda esperada pre-matrícula',
  matricula_real        SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'Se llena tras la matrícula; NULL = aún sin datos',
  estado                ENUM('Necesidad solicitada','Borrador','Enviado al CONTA','Consolidado',
                             'Enviado a RRHH','Confirmado por RRHH','Cerrado')
                        NOT NULL DEFAULT 'Borrador',
  created_at            TIMESTAMP NULL DEFAULT NULL,
  updated_at            TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY grupos_curso_periodo_numero_unique (curso_id, periodo_academico_id, numero),
  KEY grupos_periodo_estado_index (periodo_academico_id, estado),
  KEY grupos_meta_id_index (meta_id),
  KEY grupos_aula_id_index (aula_id),
  KEY grupos_modalidad_id_index (modalidad_id),
  CONSTRAINT fk_grupos_curso_id
    FOREIGN KEY (curso_id) REFERENCES cursos (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_grupos_periodo_academico_id
    FOREIGN KEY (periodo_academico_id) REFERENCES periodos_academicos (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_grupos_meta_id
    FOREIGN KEY (meta_id) REFERENCES metas (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_grupos_aula_id
    FOREIGN KEY (aula_id) REFERENCES aulas (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_grupos_modalidad_id
    FOREIGN KEY (modalidad_id) REFERENCES modalidades (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

-- 7.1b Pivote carrera<->grupo: solo para grupos de servicio compartidos
CREATE TABLE carrera_grupo (
  grupo_id   BIGINT UNSIGNED NOT NULL,
  carrera_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (grupo_id, carrera_id),
  KEY carrera_grupo_carrera_id_index (carrera_id),
  CONSTRAINT fk_carrera_grupo_grupo_id
    FOREIGN KEY (grupo_id) REFERENCES grupos (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_carrera_grupo_carrera_id
    FOREIGN KEY (carrera_id) REFERENCES carreras (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- 7.1c Historial de estados del grupo
CREATE TABLE grupo_estados_historial (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  grupo_id        BIGINT UNSIGNED NOT NULL,
  estado_anterior VARCHAR(30) NULL DEFAULT NULL,
  estado_nuevo    VARCHAR(30) NOT NULL,
  user_id         BIGINT UNSIGNED NULL DEFAULT NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY grupo_estados_historial_grupo_fecha_index (grupo_id, created_at),
  KEY grupo_estados_historial_user_id_index (user_id),
  CONSTRAINT fk_grupo_estados_historial_grupo_id
    FOREIGN KEY (grupo_id) REFERENCES grupos (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_grupo_estados_historial_user_id
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

-- 7.2 Horarios normalizados (columna "Horario": "Lunes 08:00-11:59")
CREATE TABLE horarios (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  grupo_id    BIGINT UNSIGNED NOT NULL,
  dia         ENUM('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo') NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin    TIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY horarios_grupo_dia_inicio_unique (grupo_id, dia, hora_inicio),
  KEY horarios_dia_horas_index (dia, hora_inicio, hora_fin),
  CONSTRAINT fk_horarios_grupo_id
    FOREIGN KEY (grupo_id) REFERENCES grupos (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_horarios_rango CHECK (hora_fin > hora_inicio)
) ENGINE = InnoDB;

-- 7.3 Asignación docente por grupo (foto del cuatrimestre, no historial RRHH)
CREATE TABLE asignaciones_docentes (
  id                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  grupo_id               BIGINT UNSIGNED NOT NULL,
  docente_id             BIGINT UNSIGNED NOT NULL,
  jornada                DECIMAL(3,2) NOT NULL COMMENT 'Fracción de jornada, ej. 0.25',
  condicion_nombramiento ENUM('Interino','Propiedad') NOT NULL DEFAULT 'Interino',
  quincena               VARCHAR(20) NULL DEFAULT NULL,
  numero_accion_personal VARCHAR(30) NULL DEFAULT NULL,
  observacion            VARCHAR(255) NULL DEFAULT NULL,
  estado                 ENUM('Propuesta','Confirmada','Rechazada') NOT NULL DEFAULT 'Propuesta',
  created_at             TIMESTAMP NULL DEFAULT NULL,
  updated_at             TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY asignaciones_docentes_grupo_docente_unique (grupo_id, docente_id),
  -- Detección de docente duplicado entre carreras en el mismo período
  KEY asignaciones_docentes_docente_estado_index (docente_id, estado),
  KEY asignaciones_docentes_estado_index (estado),
  CONSTRAINT fk_asignaciones_docentes_grupo_id
    FOREIGN KEY (grupo_id) REFERENCES grupos (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_asignaciones_docentes_docente_id
    FOREIGN KEY (docente_id) REFERENCES docentes (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_asignaciones_jornada CHECK (jornada > 0 AND jornada <= 1)
) ENGINE = InnoDB;

-- 7.3b Historial de cambios de asignación, respaldado por número de oficio
CREATE TABLE asignacion_cambios (
  id                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  asignacion_docente_id  BIGINT UNSIGNED NOT NULL,
  tipo_cambio            ENUM('Docente','Horario','Aula','Jornada','Estado','Otro') NOT NULL,
  docente_anterior_id    BIGINT UNSIGNED NULL DEFAULT NULL,
  docente_nuevo_id       BIGINT UNSIGNED NULL DEFAULT NULL,
  numero_oficio          VARCHAR(30) NULL DEFAULT NULL COMMENT 'Oficio/acuerdo que respalda el cambio',
  descripcion            VARCHAR(255) NOT NULL,
  user_id                BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Usuario que registró el cambio',
  created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY asignacion_cambios_asignacion_fecha_index (asignacion_docente_id, created_at),
  KEY asignacion_cambios_oficio_index (numero_oficio),
  KEY asignacion_cambios_docente_anterior_index (docente_anterior_id),
  KEY asignacion_cambios_docente_nuevo_index (docente_nuevo_id),
  KEY asignacion_cambios_user_id_index (user_id),
  CONSTRAINT fk_asignacion_cambios_asignacion_id
    FOREIGN KEY (asignacion_docente_id) REFERENCES asignaciones_docentes (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_asignacion_cambios_docente_anterior_id
    FOREIGN KEY (docente_anterior_id) REFERENCES docentes (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_asignacion_cambios_docente_nuevo_id
    FOREIGN KEY (docente_nuevo_id) REFERENCES docentes (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_asignacion_cambios_user_id
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

-- 7.4 Verificaciones de atinencia: resultado auditable por asignación
CREATE TABLE verificaciones_atinencia (
  id                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  asignacion_docente_id  BIGINT UNSIGNED NOT NULL,
  catalogo_atinencia_id  BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Versión de catálogo aplicada; NULL si Sin catálogo',
  user_id                BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Usuario que ejecutó/aprobó la verificación',
  resultado              ENUM('Atinente','No Atinente','Nota técnica','Sin catálogo') NOT NULL,
  es_provisional         TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = provisional por vigencia futura',
  justificacion          TEXT NULL,
  created_at             TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY verificaciones_asignacion_fecha_index (asignacion_docente_id, created_at),
  KEY verificaciones_resultado_index (resultado),
  KEY verificaciones_catalogo_id_index (catalogo_atinencia_id),
  KEY verificaciones_user_id_index (user_id),
  CONSTRAINT fk_verificaciones_asignacion_id
    FOREIGN KEY (asignacion_docente_id) REFERENCES asignaciones_docentes (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_verificaciones_catalogo_id
    FOREIGN KEY (catalogo_atinencia_id) REFERENCES catalogos_atinencia (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_verificaciones_user_id
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

-- =====================================================================
-- SECCIÓN 7B. RESERVAS Y BLOQUEOS DE AULAS
-- =====================================================================

CREATE TABLE reservas_aulas (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  aula_id      BIGINT UNSIGNED NOT NULL,
  user_id      BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Usuario que registró la reserva',
  tipo         ENUM('Reserva','Bloqueo administrativo') NOT NULL DEFAULT 'Reserva',
  solicitante  VARCHAR(120) NULL DEFAULT NULL COMMENT 'NULL en bloqueos administrativos',
  motivo       VARCHAR(255) NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin    DATE NULL DEFAULT NULL COMMENT 'NULL = reserva de un solo día',
  dias_semana  SET('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo')
               NULL DEFAULT NULL COMMENT 'Días que aplica dentro del rango; NULL = todos/día único',
  hora_inicio  TIME NOT NULL,
  hora_fin     TIME NOT NULL,
  estado       ENUM('Solicitada','Aprobada','Rechazada','Cancelada') NOT NULL DEFAULT 'Solicitada',
  created_at   TIMESTAMP NULL DEFAULT NULL,
  updated_at   TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY reservas_aulas_aula_fechas_index (aula_id, fecha_inicio, fecha_fin),
  KEY reservas_aulas_estado_index (estado),
  KEY reservas_aulas_user_id_index (user_id),
  CONSTRAINT fk_reservas_aulas_aula_id
    FOREIGN KEY (aula_id) REFERENCES aulas (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_reservas_aulas_user_id
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_reservas_aulas_rango_horas CHECK (hora_fin > hora_inicio),
  CONSTRAINT chk_reservas_aulas_rango_fechas CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio)
) ENGINE = InnoDB;

-- =====================================================================
-- SECCIÓN 7C. SOLICITUDES ESTUDIANTILES
-- =====================================================================

-- 7C.1 Reglas de levantamiento por curso, evaluadas en orden
CREATE TABLE reglas_levantamiento (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  curso_id           BIGINT UNSIGNED NOT NULL,
  orden              TINYINT UNSIGNED NOT NULL COMMENT 'Orden de evaluación del motor',
  tipo               ENUM('Requisito aprobado con nota mínima','Créditos o cursos acumulados',
                          'Pertenencia a plan terminal','Siempre revisión manual') NOT NULL,
  curso_requisito_id BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Parámetro del tipo (a)',
  nota_minima        DECIMAL(5,2) NULL DEFAULT NULL COMMENT 'Parámetro N del tipo (a)',
  minimo_acumulado   SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'Parámetro K del tipo (b)',
  activo             TINYINT(1) NOT NULL DEFAULT 1,
  created_at         TIMESTAMP NULL DEFAULT NULL,
  updated_at         TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY reglas_levantamiento_curso_orden_unique (curso_id, orden),
  KEY reglas_levantamiento_curso_requisito_index (curso_requisito_id),
  CONSTRAINT fk_reglas_levantamiento_curso_id
    FOREIGN KEY (curso_id) REFERENCES cursos (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_reglas_levantamiento_curso_requisito_id
    FOREIGN KEY (curso_requisito_id) REFERENCES cursos (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- 7C.2 Convalidaciones históricas (precedentes por institución + curso externo)
CREATE TABLE convalidaciones_historicas (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  institucion       VARCHAR(150) NOT NULL,
  curso_externo     VARCHAR(150) NOT NULL,
  curso_id          BIGINT UNSIGNED NOT NULL COMMENT 'Curso interno UTN equivalente',
  resultado         ENUM('Aprobada','Denegada') NOT NULL,
  numero_resolucion VARCHAR(60) NOT NULL COMMENT 'Resolución de referencia del precedente',
  created_at        TIMESTAMP NULL DEFAULT NULL,
  updated_at        TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY convalidaciones_historicas_busqueda_index (institucion, curso_externo),
  KEY convalidaciones_historicas_curso_id_index (curso_id),
  CONSTRAINT fk_convalidaciones_historicas_curso_id
    FOREIGN KEY (curso_id) REFERENCES cursos (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

-- 7C.3 Solicitudes (levantamientos y convalidaciones; adjuntos vía `archivos`)
CREATE TABLE solicitudes (
  id                          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  estudiante_id               BIGINT UNSIGNED NOT NULL,
  tipo                        ENUM('Levantamiento de requisito','Convalidación') NOT NULL,
  curso_id                    BIGINT UNSIGNED NOT NULL COMMENT 'Curso a matricular / curso interno al que aspira',
  curso_requisito_id          BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Requisito no cumplido',
  institucion_origen          VARCHAR(150) NULL DEFAULT NULL COMMENT 'Solo convalidación',
  curso_externo               VARCHAR(150) NULL DEFAULT NULL COMMENT 'Solo convalidación',
  convalidacion_historica_id  BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Precedente encontrado',
  resultado_motor             ENUM('Procede automáticamente','No procede','Requiere revisión manual')
                              NULL DEFAULT NULL COMMENT 'Primer resultado concluyente del motor',
  regla_incumplida_id         BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Regla que produjo No procede',
  estado                      ENUM('Pendiente de revisión','En revisión','Aprobada','Denegada')
                              NOT NULL DEFAULT 'Pendiente de revisión',
  fecha_estimada_resolucion   DATE NULL DEFAULT NULL COMMENT 'Si no se ingresa en 24h la app asigna 5 días hábiles',
  revisor_id                  BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Usuario revisor (Docencia/Comisión)',
  created_at                  TIMESTAMP NULL DEFAULT NULL,
  updated_at                  TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY solicitudes_bandeja_index (tipo, estado, created_at),
  KEY solicitudes_estudiante_index (estudiante_id, estado),
  KEY solicitudes_curso_id_index (curso_id),
  KEY solicitudes_curso_requisito_index (curso_requisito_id),
  KEY solicitudes_convalidacion_hist_index (convalidacion_historica_id),
  KEY solicitudes_regla_incumplida_index (regla_incumplida_id),
  KEY solicitudes_revisor_id_index (revisor_id),
  CONSTRAINT fk_solicitudes_estudiante_id
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_solicitudes_curso_id
    FOREIGN KEY (curso_id) REFERENCES cursos (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_solicitudes_curso_requisito_id
    FOREIGN KEY (curso_requisito_id) REFERENCES cursos (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_solicitudes_convalidacion_historica_id
    FOREIGN KEY (convalidacion_historica_id) REFERENCES convalidaciones_historicas (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_solicitudes_regla_incumplida_id
    FOREIGN KEY (regla_incumplida_id) REFERENCES reglas_levantamiento (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_solicitudes_revisor_id
    FOREIGN KEY (revisor_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

-- 7C.4 Historial de estados de la solicitud (auditoría + base de notificación)
CREATE TABLE solicitud_estados_historial (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  solicitud_id    BIGINT UNSIGNED NOT NULL,
  estado_anterior VARCHAR(30) NULL DEFAULT NULL,
  estado_nuevo    VARCHAR(30) NOT NULL,
  comentario      VARCHAR(255) NULL DEFAULT NULL COMMENT 'Justificación del cambio; obligatoria en denegaciones (capa app)',
  user_id         BIGINT UNSIGNED NULL DEFAULT NULL,
  notificado_at   TIMESTAMP NULL DEFAULT NULL COMMENT 'Momento del correo al estudiante',
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY solicitud_estados_solicitud_fecha_index (solicitud_id, created_at),
  KEY solicitud_estados_user_id_index (user_id),
  CONSTRAINT fk_solicitud_estados_solicitud_id
    FOREIGN KEY (solicitud_id) REFERENCES solicitudes (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_solicitud_estados_user_id
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

-- =====================================================================
-- SECCIÓN 8. GESTIÓN DOCUMENTAL
-- =====================================================================

-- 8.1 Archivos: metadatos con relación polimórfica
CREATE TABLE archivos (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uuid            CHAR(36) NOT NULL COMMENT 'Identificador público para URL de descarga firmada',
  user_id         BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Usuario que subió el archivo',
  archivable_type VARCHAR(120) NOT NULL COMMENT 'Clase del modelo dueño (App\\Models\\NotaTecnica, ...)',
  archivable_id   BIGINT UNSIGNED NOT NULL,
  tipo_documento  VARCHAR(60) NOT NULL COMMENT 'Criterio Técnico, Resolución, Certificación, Reporte, ...',
  nombre_original VARCHAR(255) NOT NULL,
  disco           VARCHAR(30) NOT NULL DEFAULT 'local',
  ruta            VARCHAR(255) NOT NULL,
  mime_type       VARCHAR(100) NOT NULL,
  tamano_bytes    INT UNSIGNED NOT NULL,
  hash_sha256     CHAR(64) NOT NULL COMMENT 'Integridad y detección de duplicados',
  created_at      TIMESTAMP NULL DEFAULT NULL,
  updated_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY archivos_uuid_unique (uuid),
  UNIQUE KEY archivos_disco_ruta_unique (disco, ruta),
  KEY archivos_archivable_index (archivable_type, archivable_id),
  KEY archivos_tipo_documento_index (tipo_documento),
  KEY archivos_user_id_index (user_id),
  CONSTRAINT fk_archivos_user_id
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_archivos_tamano CHECK (tamano_bytes > 0)
) ENGINE = InnoDB;

-- 8.2 Notas técnicas: archivo_id NOT NULL fuerza el PDF firmado como condición
CREATE TABLE notas_tecnicas (
  id                         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  asignacion_docente_id      BIGINT UNSIGNED NOT NULL,
  archivo_id                 BIGINT UNSIGNED NOT NULL COMMENT 'PDF del criterio técnico firmado (obligatorio)',
  user_id                    BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Coordinadora que registró la nota',
  fecha_limite_ratificacion  DATE NOT NULL COMMENT 'SLA de ratificación',
  estado                     ENUM('Ratificación pendiente','Ratificada','Vencida','Rechazada')
                             NOT NULL DEFAULT 'Ratificación pendiente',
  ratificada_at              TIMESTAMP NULL DEFAULT NULL,
  created_at                 TIMESTAMP NULL DEFAULT NULL,
  updated_at                 TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY notas_tecnicas_asignacion_unique (asignacion_docente_id),
  KEY notas_tecnicas_sla_index (estado, fecha_limite_ratificacion),
  KEY notas_tecnicas_archivo_id_index (archivo_id),
  KEY notas_tecnicas_user_id_index (user_id),
  CONSTRAINT fk_notas_tecnicas_asignacion_id
    FOREIGN KEY (asignacion_docente_id) REFERENCES asignaciones_docentes (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_notas_tecnicas_archivo_id
    FOREIGN KEY (archivo_id) REFERENCES archivos (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_notas_tecnicas_user_id
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

-- 8.3 Bitácora de descargas: quién descargó qué, cuándo y desde dónde
CREATE TABLE descargas_archivos (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  archivo_id BIGINT UNSIGNED NOT NULL,
  user_id    BIGINT UNSIGNED NULL DEFAULT NULL,
  ip_address VARCHAR(45) NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY descargas_archivos_archivo_fecha_index (archivo_id, created_at),
  KEY descargas_archivos_user_id_index (user_id),
  CONSTRAINT fk_descargas_archivos_archivo_id
    FOREIGN KEY (archivo_id) REFERENCES archivos (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_descargas_archivos_user_id
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

-- 8.4 Auditoría polimórfica; `cambios` guarda el diff JSON {campo:{antes,despues}}
CREATE TABLE auditorias (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id        BIGINT UNSIGNED NULL DEFAULT NULL,
  auditable_type VARCHAR(120) NOT NULL COMMENT 'Clase del modelo (App\\Models\\Atestado, ...)',
  auditable_id   BIGINT UNSIGNED NOT NULL,
  accion         ENUM('Creación','Modificación','Eliminación') NOT NULL,
  cambios        JSON NULL,
  ip_address     VARCHAR(45) NULL DEFAULT NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY auditorias_auditable_index (auditable_type, auditable_id, created_at),
  KEY auditorias_user_id_index (user_id),
  CONSTRAINT fk_auditorias_user_id
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SECCIÓN 9. DATOS SEMILLA
-- =====================================================================

-- 9.1 Carreras en alcance
INSERT INTO carreras (nombre, created_at) VALUES
  ('Administración y Gestión de Recursos Humanos', NOW()),
  ('Administración Aduanera', NOW()),
  ('Ingeniería en Tecnologías de Información - Tecnologías de Información', NOW()),
  ('Ingeniería del Software - Tecnologías Informáticas', NOW()),
  ('Contabilidad y Finanzas - Contaduría Pública', NOW()),
  ('Asistencia Administrativa', NOW()),
  ('Inglés como Lengua Extranjera', NOW()),
  ('Administración Agroindustrial', NOW()),
  ('Gestión de Centros de Servicios Compartidos', NOW()),
  ('Ingeniería en Mantenimiento Agroindustrial Sostenible - Mantenimiento Agroindustrial Sostenible', NOW()),
  ('Ingeniería en Gestión Ambiental', NOW()),
  ('Ingeniería en Salud Ocupacional y Ambiente - Salud Ocupacional', NOW()),
  ('Ingeniería en Tecnología de Alimentos - Tecnología de Alimentos', NOW()),
  ('Administración del Comercio Exterior', NOW());

-- 9.2 Unidad ejecutora y metas presentes en la hoja "ITI"
INSERT INTO unidades_ejecutoras (codigo, nombre, created_at) VALUES
  ('0610207005', 'Ingeniería en Tecnologías de la Información', NOW());

INSERT INTO metas (unidad_ejecutora_id, codigo, nombre, created_at) VALUES
  (1, '013001', 'Diplomado', NOW()),
  (1, '013002', 'Bachillerato', NOW());

-- 9.3 Período de la oferta analizada
INSERT INTO periodos_academicos (anio, cuatrimestre, fecha_inicio, fecha_fin, created_at) VALUES
  (2025, 3, '2025-09-01', '2025-12-19', NOW());

-- 9.3a Equipamientos base
INSERT INTO equipamientos (nombre, created_at) VALUES
  ('Proyector', NOW()),
  ('Computadoras', NOW()),
  ('Aire acondicionado', NOW()),
  ('Pizarra inteligente', NOW());

-- 9.3b Recintos (v2 — caso Santa Fe: recinto alquilado)
INSERT INTO recintos (nombre, es_propio, created_at) VALUES
  ('Campus Central San Carlos', 1, NOW()),
  ('Recinto Santa Fe',          0, NOW());

-- 9.3c Catálogo maestro de modalidades (v2 — RC-03: Presencial es el valor
INSERT INTO modalidades (nombre, requiere_resolucion, created_at) VALUES
  ('Presencial',         0, NOW()),
  ('Híbrido',            1, NOW()),
  ('Virtual',            1, NOW()),
  ('Tutoría',            1, NOW()),
  ('Aprendizaje Remoto', 1, NOW());

-- 9.4 Roles y permisos base (RBAC alineado a la lógica de negocio y a los
INSERT INTO roles (name, description, created_at) VALUES
  ('Administrador',            'Gestión total: catálogo de atinencias, usuarios y configuración', NOW()),
  ('Coordinadora de Docencia', 'Registra atestados, consolida y gestiona asignaciones docentes', NOW()),
  ('Docente',                  'Consulta su perfil, atestados y asignaciones', NOW()),
  ('Consulta',                 'Acceso de solo lectura a la oferta académica', NOW()),
  ('Director de Carrera',      'Registra la oferta, planes y resoluciones de su propia carrera', NOW()),
  ('Coordinador CONTA',        'Consolida la oferta de las carreras de su área', NOW()),
  ('Recursos Humanos',         'Lectura de la oferta consolidada; sin acceso a atinencias', NOW()),
  ('Estudiante',               'Presenta y da seguimiento a sus propias solicitudes', NOW()),
  ('Comisión Técnica',         'Revisa y resuelve solicitudes de convalidación', NOW());

INSERT INTO permissions (name, description, created_at) VALUES
  ('atestados.gestionar',      'Crear y editar atestados de docentes', NOW()),
  ('catalogo.gestionar',       'Crear versiones del catálogo de atinencias', NOW()),
  ('oferta.gestionar',         'Crear grupos, horarios y asignaciones', NOW()),
  ('atinencia.verificar',      'Ejecutar verificaciones de atinencia', NOW()),
  ('nota_tecnica.aprobar',     'Aprobar la vía excepcional de Nota Técnica', NOW()),
  ('oferta.consultar',         'Consultar la oferta académica', NOW()),
  ('usuarios.gestionar',       'Administrar usuarios, roles y permisos', NOW()),
  ('archivos.subir',           'Adjuntar documentos a los módulos', NOW()),
  ('archivos.descargar',       'Descargar documentos adjuntos y reportes', NOW()),
  ('resoluciones.gestionar',   'Registrar resoluciones de modalidad por curso', NOW()),
  ('reservas.gestionar',       'Registrar y aprobar préstamos de aulas', NOW()),
  ('oferta.consolidar',        'Consolidar la oferta y mover grupos de estado', NOW()),
  ('planes.gestionar',         'Administrar planes de estudio, niveles y requisitos', NOW()),
  ('equiparaciones.gestionar', 'Registrar equiparaciones entre planes', NOW()),
  ('solicitudes.crear',        'Presentar solicitudes estudiantiles', NOW()),
  ('solicitudes.revisar',      'Revisar y resolver solicitudes estudiantiles', NOW());

INSERT INTO permission_role (role_id, permission_id, created_at) VALUES
  -- Administrador: todos los permisos
  (1, 1, NOW()), (1, 2, NOW()), (1, 3, NOW()), (1, 4, NOW()),
  (1, 5, NOW()), (1, 6, NOW()), (1, 7, NOW()), (1, 8, NOW()),
  (1, 9, NOW()), (1, 10, NOW()), (1, 11, NOW()), (1, 12, NOW()),
  (1, 13, NOW()), (1, 14, NOW()), (1, 15, NOW()), (1, 16, NOW()),
  -- Coordinadora de Docencia
  (2, 1, NOW()), (2, 3, NOW()), (2, 4, NOW()), (2, 6, NOW()),
  (2, 8, NOW()), (2, 9, NOW()), (2, 10, NOW()), (2, 11, NOW()),
  (2, 12, NOW()), (2, 13, NOW()), (2, 14, NOW()), (2, 16, NOW()),
  -- Docente
  (3, 6, NOW()), (3, 9, NOW()),
  -- Consulta
  (4, 6, NOW()),
  -- Director de Carrera: oferta, planes y resoluciones de su carrera
  (5, 3, NOW()), (5, 6, NOW()), (5, 8, NOW()), (5, 9, NOW()),
  (5, 10, NOW()), (5, 13, NOW()), (5, 14, NOW()),
  -- Coordinador CONTA: lectura + consolidación de su área
  (6, 6, NOW()), (6, 9, NOW()), (6, 12, NOW()),
  -- Recursos Humanos: solo lectura de la oferta consolidada
  (7, 6, NOW()), (7, 9, NOW()),
  -- Estudiante: presenta solicitudes y adjunta documentos
  (8, 15, NOW()), (8, 8, NOW()),
  -- Comisión Técnica: revisa convalidaciones
  (9, 16, NOW()), (9, 9, NOW());

-- =====================================================================
-- NOTA DE ALCANCE
-- Fase 2 (pendiente de RRHH): nombramientos, continuidad/estabilidad,
-- jornada de derecho y licencias docentes. jornada_estimada es indicativa.
-- Población estudiantil REAL diferida; el expediente simulado sí aplica.
-- En capa de aplicación por diseño: solapamiento de intervalos [ini, fin),
-- anticiclos de equiparaciones y adjuntos obligatorios.
-- =====================================================================
