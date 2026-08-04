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
