-- =====================================================================
-- migracion_06_version_1_7.sql
-- Actualiza una base de datos de la versión 1.6 a la 1.7.
-- Ejecutar UNA vez en phpMyAdmin (pestaña SQL) o con:
--   mysql --default-character-set=utf8mb4 -u root control_visitas < migracion_06_version_1_7.sql
-- Recomendado: exporte una copia de la base de datos antes de ejecutarla.
-- Se puede ejecutar más de una vez sin dañar los datos.
-- =====================================================================
USE control_visitas;
SET NAMES utf8mb4;

-- Correo de cada cuenta (se muestra en "Mi perfil" y se edita en "Gestión de Usuarios").
-- Las cuentas existentes quedan sin correo hasta que el administrador lo complete.
ALTER TABLE usuario
    ADD COLUMN IF NOT EXISTS correo VARCHAR(100) NULL AFTER telefono;

-- Dos cuentas no pueden tener el mismo correo (las que no tienen correo no cuentan)
CREATE UNIQUE INDEX IF NOT EXISTS uq_usuario_correo ON usuario (correo);

-- Revisión (solo consulta): cuentas a las que les falta el correo
SELECT `user`, nombre, apellido FROM usuario WHERE correo IS NULL OR correo = '';
