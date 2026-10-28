-- =====================================================================
-- migracion_03_tipo_visita.sql
-- Agrega el tipo de visita: "al_momento" (el visitante ya llegó) o
-- "programada" (cita a futuro). Se puede ejecutar más de una vez.
-- Las visitas anteriores quedan como "programada".
-- =====================================================================
USE control_visitas;

ALTER TABLE visita
    ADD COLUMN IF NOT EXISTS tipo_visita ENUM('al_momento', 'programada')
        NOT NULL DEFAULT 'programada' AFTER observaciones;
