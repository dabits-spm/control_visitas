-- =====================================================================
-- datos_prueba.sql - SOLO PARA DESARROLLO
-- Ejecutar DESPUÉS de schema.sql:  USE control_visitas;
-- Usuarios:    admin (Administrador), recepcion (Recepcionista)
-- Contraseña:  Admin12345 para los dos (cámbiela antes de usar el sistema en serio)
-- =====================================================================
USE control_visitas;

INSERT INTO usuario (cedula, nombre, apellido, telefono, correo, `user`, `password`, estado, id_rol)
VALUES ('V-10000000', 'Admin', 'Sistema', '0241-0000000', 'admin@correo.com', 'admin',
        '$2y$10$PL4.8Xtsy3BzeimmLKPPLe6JvGBxUiQ56SIPxA0/iw5WtI1KkMEUy', 1, 1),
       ('V-10000002', 'Ramón', 'Recepción', '0414-0000002', 'recepcion@correo.com', 'recepcion',
        '$2y$10$PL4.8Xtsy3BzeimmLKPPLe6JvGBxUiQ56SIPxA0/iw5WtI1KkMEUy', 1, 2);

INSERT INTO visitante (cedula, nombre, apellido, telefono, correo, procedencia)
VALUES ('V-12345678', 'María', 'Pérez', '0414-1234567', 'maria.perez@correo.com', 'Empresa de prueba');
