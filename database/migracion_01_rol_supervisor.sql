-- =====================================================================
-- migracion_01_rol_supervisor.sql
-- (HISTÓRICA: desde la versión 1.6 el rol Supervisor ya no existe; la
--  migracion_05 lo elimina. Ejecútela solo si actualiza desde una versión
--  anterior a la 1.3, y luego aplique las demás en orden.)
-- Agrega el rol "Supervisor" a una base de datos ya creada con una
-- versión anterior de schema.sql. Se puede ejecutar más de una vez.
-- =====================================================================
USE control_visitas;

INSERT IGNORE INTO rol (id_rol, nombre_rol) VALUES (3, 'Supervisor');

-- Opcional: usuarios de prueba para cada rol (contraseña: Admin12345).
-- Quite los comentarios si los necesita.
-- INSERT IGNORE INTO usuario (cedula, nombre, apellido, telefono, `user`, `password`, estado, id_rol)
-- VALUES ('V-00000001', 'Sofía', 'Supervisora', '0000-0000001', 'supervisor',
--         '$2y$10$PL4.8Xtsy3BzeimmLKPPLe6JvGBxUiQ56SIPxA0/iw5WtI1KkMEUy', 1, 3),
--        ('V-00000002', 'Ramón', 'Recepción', '0000-0000002', 'recepcion',
--         '$2y$10$PL4.8Xtsy3BzeimmLKPPLe6JvGBxUiQ56SIPxA0/iw5WtI1KkMEUy', 1, 2);
