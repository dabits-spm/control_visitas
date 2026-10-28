-- =====================================================================
-- migracion_05_version_1_6.sql
-- Actualiza una base de datos de la versión 1.5 a la 1.6.
-- Ejecutar UNA vez en phpMyAdmin (pestaña SQL) o con:
--   mysql --default-character-set=utf8mb4 -u root control_visitas < migracion_05_version_1_6.sql
-- Recomendado: exporte una copia de la base de datos antes de ejecutarla.
-- Se puede ejecutar más de una vez sin dañar los datos.
-- =====================================================================
USE control_visitas;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1. Nuevo estado: Reprogramada (una cita a la que se le cambió la fecha u hora)
-- ---------------------------------------------------------------------
INSERT IGNORE INTO estado_visita (id_estado, nombre_estado) VALUES (5, 'Reprogramada');

-- ---------------------------------------------------------------------
-- 2. Solo dos roles: Administrador (1) y Recepcionista (2)
-- ---------------------------------------------------------------------
-- Los usuarios con el rol Supervisor pasan a Recepcionista (el rol con menos
-- permisos). Si alguno debe ser Administrador, cámbielo después con:
--   UPDATE usuario SET id_rol = 1 WHERE `user` = 'nombre_de_usuario';
UPDATE usuario SET id_rol = 2 WHERE id_rol = 3;
DELETE FROM rol WHERE id_rol = 3;

-- ---------------------------------------------------------------------
-- 3. Revisión (solo consulta): usuarios y su rol
-- ---------------------------------------------------------------------
SELECT u.`user`, u.nombre, u.apellido, r.nombre_rol
FROM usuario u
INNER JOIN rol r ON r.id_rol = u.id_rol
ORDER BY r.id_rol, u.`user`;
