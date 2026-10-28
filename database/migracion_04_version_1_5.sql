-- =====================================================================
-- migracion_04_version_1_5.sql
-- Actualiza una base de datos de la versión 1.4 a la 1.5.
-- Ejecutar UNA vez en phpMyAdmin (pestaña SQL) o con:
--   mysql --default-character-set=utf8mb4 -u root control_visitas < migracion_04_version_1_5.sql
-- Recomendado: exporte una copia de la base de datos antes de ejecutarla.
-- =====================================================================
USE control_visitas;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1. Nuevas columnas de la visita
-- ---------------------------------------------------------------------
-- cierre_automatico: 1 si el sistema la finalizó por quedar abierta de un día anterior
-- aviso_enviado:     fecha y hora en que se envió el aviso por correo
ALTER TABLE visita
    ADD COLUMN IF NOT EXISTS cierre_automatico TINYINT(1) NOT NULL DEFAULT 0 AFTER id_estado,
    ADD COLUMN IF NOT EXISTS aviso_enviado     DATETIME   NULL              AFTER cierre_automatico;

-- Índice para que el cierre automático sea rápido aunque haya muchas visitas
CREATE INDEX IF NOT EXISTS idx_visita_estado_fecha ON visita (id_estado, fecha_visita);

-- ---------------------------------------------------------------------
-- 2. Cédulas: siempre con letra (V- o E-)
-- ---------------------------------------------------------------------
-- Antes "12345678" y "V-12345678" se guardaban como dos personas distintas.
-- Ahora una cédula sin letra se considera venezolana (V-).

-- 2a. Si la misma persona quedó registrada dos veces, sus visitas pasan
--     al registro que ya tenía la letra V-
UPDATE visita v
INNER JOIN visitante duplicado ON duplicado.id_visitante = v.id_visitante
INNER JOIN visitante original  ON original.cedula = CONCAT('V-', duplicado.cedula)
SET v.id_visitante = original.id_visitante
WHERE duplicado.cedula REGEXP '^[0-9]+$';

-- 2b. Se borra el registro duplicado (ya no tiene visitas)
DELETE duplicado
FROM visitante duplicado
INNER JOIN visitante original ON original.cedula = CONCAT('V-', duplicado.cedula)
WHERE duplicado.cedula REGEXP '^[0-9]+$';

-- 2c. Las cédulas que quedan sin letra pasan a V-
UPDATE visitante
SET cedula = CONCAT('V-', cedula)
WHERE cedula REGEXP '^[0-9]+$';

-- ---------------------------------------------------------------------
-- 3. Teléfonos venezolanos con un solo formato: 0414-1234567
-- ---------------------------------------------------------------------
UPDATE visitante
SET telefono = CONCAT(SUBSTRING(telefono, 1, 4), '-', SUBSTRING(telefono, 5))
WHERE telefono REGEXP '^0[0-9]{10}$';

UPDATE visitante
SET telefono = CONCAT('0', SUBSTRING(telefono, 4, 3), '-', SUBSTRING(telefono, 7))
WHERE telefono REGEXP '^\\+58[0-9]{10}$';

-- ---------------------------------------------------------------------
-- 4. Revisión (solo consulta, no cambia nada)
-- ---------------------------------------------------------------------
-- Visitantes con un tipo de cédula que el sistema ya no acepta (J, G, P...).
-- Corrija sus datos desde la opción «Visitantes» si aparece alguno.
SELECT id_visitante, cedula, nombre, apellido
FROM visitante
WHERE cedula NOT REGEXP '^[VE]-[0-9]{6,10}$';
