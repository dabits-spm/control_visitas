-- =====================================================================
-- migracion_07_version_1_8.sql
-- Actualiza una base de datos de la versión 1.7 a la 1.8 (notificaciones y
-- recordatorios por correo 24 horas antes de cada cita).
-- Ejecutar UNA vez en phpMyAdmin (pestaña SQL) o con:
--   mysql --default-character-set=utf8mb4 -u root control_visitas < migracion_07_version_1_8.sql
-- Recomendado: exporte una copia de la base de datos antes de ejecutarla.
-- Se puede ejecutar más de una vez sin dañar los datos.
-- =====================================================================
USE control_visitas;
SET NAMES utf8mb4;

-- Cuándo se envió el recordatorio de cada cita (NULL = todavía no)
ALTER TABLE visita
    ADD COLUMN IF NOT EXISTS recordatorio_enviado DATETIME NULL AFTER aviso_enviado;

-- Las citas cuyo aviso de programación se envió dentro de las 24 horas previas a
-- la cita ya quedan recordadas (no reciben un segundo correo casi seguido).
UPDATE visita
SET recordatorio_enviado = aviso_enviado
WHERE recordatorio_enviado IS NULL
  AND aviso_enviado IS NOT NULL
  AND id_estado IN (1, 5)
  AND aviso_enviado >= TIMESTAMP(fecha_visita, hora_entrada) - INTERVAL 24 HOUR;

-- Control de los procesos automáticos (evita envíos duplicados)
CREATE TABLE IF NOT EXISTS proceso_automatico (
    nombre            VARCHAR(40) NOT NULL,
    ultima_ejecucion  DATETIME    NULL,
    PRIMARY KEY (nombre)
) ENGINE = InnoDB;

INSERT IGNORE INTO proceso_automatico (nombre, ultima_ejecucion) VALUES ('recordatorios', NULL);
