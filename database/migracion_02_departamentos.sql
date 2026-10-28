-- =====================================================================
-- migracion_02_departamentos.sql
-- Reemplaza los departamentos de ejemplo por los de la institución.
-- Se renombran (no se borran) para conservar las visitas ya asociadas.
-- Ejecutar con:  mysql --default-character-set=utf8mb4 ...
-- =====================================================================
USE control_visitas;
SET NAMES utf8mb4;

UPDATE departamento SET nombre_departamento = 'Planificación y Talento Humano' WHERE id_departamento = 1; -- antes: Recursos Humanos
UPDATE departamento SET nombre_departamento = 'Servicio y Mantenimiento'       WHERE id_departamento = 2; -- antes: Administración
UPDATE departamento SET nombre_departamento = 'División de Informática'        WHERE id_departamento = 3; -- antes: Tecnología
UPDATE departamento SET nombre_departamento = 'Formación Profesional'          WHERE id_departamento = 4; -- antes: Contabilidad

-- "Dirección General" ya no se usa. Solo se borra si no tiene visitas asociadas
-- (la llave foránea lo impediría de todos modos).
DELETE FROM departamento
WHERE id_departamento = 5
  AND NOT EXISTS (SELECT 1 FROM visita_departamento WHERE id_departamento = 5);
