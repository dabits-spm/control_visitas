-- =====================================================================
-- schema.sql - Sistema de Gestión de Control de Visitas
-- Motor: MySQL / MariaDB (InnoDB, utf8mb4)
-- =====================================================================

-- ---------------------------------------------------------------------
-- BLOQUE 1: Base de datos
-- ---------------------------------------------------------------------
DROP DATABASE IF EXISTS control_visitas;

CREATE DATABASE control_visitas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_spanish_ci;

USE control_visitas;

-- ---------------------------------------------------------------------
-- BLOQUE 2: Tablas catálogo (no dependen de ninguna otra)
-- ---------------------------------------------------------------------

-- Roles del sistema: Administrador y Recepcionista
CREATE TABLE rol (
    id_rol      TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_rol  VARCHAR(30)      NOT NULL,
    PRIMARY KEY (id_rol),
    UNIQUE KEY uq_rol_nombre (nombre_rol)
) ENGINE = InnoDB;

-- Estados fijos de una visita
CREATE TABLE estado_visita (
    id_estado      TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_estado  VARCHAR(30)      NOT NULL,
    PRIMARY KEY (id_estado),
    UNIQUE KEY uq_estado_nombre (nombre_estado)
) ENGINE = InnoDB;

-- Departamentos que se pueden visitar
CREATE TABLE departamento (
    id_departamento      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_departamento  VARCHAR(80)  NOT NULL,
    PRIMARY KEY (id_departamento),
    UNIQUE KEY uq_departamento_nombre (nombre_departamento)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- BLOQUE 3: Usuarios del sistema y visitantes
-- ---------------------------------------------------------------------

-- Personal que usa el sistema (administradores y recepcionistas).
-- Las cuentas de los recepcionistas se gestionan desde «Gestión de Usuarios».
CREATE TABLE usuario (
    id_usuario  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    cedula      VARCHAR(15)      NOT NULL,
    nombre      VARCHAR(60)      NOT NULL,
    apellido    VARCHAR(60)      NOT NULL,
    telefono    VARCHAR(20)      NOT NULL,
    correo      VARCHAR(100)     NULL,       -- se muestra en "Mi perfil"
    `user`      VARCHAR(40)      NOT NULL,   -- nombre de acceso
    `password`  VARCHAR(255)     NOT NULL,   -- hash generado con password_hash()
    estado      TINYINT(1)       NOT NULL DEFAULT 1,  -- 1 = activo, 0 = inactivo
    id_rol      TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_usuario_cedula (cedula),
    UNIQUE KEY uq_usuario_user (`user`),
    UNIQUE KEY uq_usuario_correo (correo),
    CONSTRAINT fk_usuario_rol
        FOREIGN KEY (id_rol) REFERENCES rol (id_rol)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE = InnoDB;

-- Personas que llegan de visita
CREATE TABLE visitante (
    id_visitante  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cedula        VARCHAR(15)  NOT NULL,       -- siempre con letra: V-12345678 (venezolano) o E-81234567 (extranjero)
    nombre        VARCHAR(60)  NOT NULL,
    apellido      VARCHAR(60)  NOT NULL,
    telefono      VARCHAR(20)  NOT NULL,       -- 0414-1234567, o +573001234567 si es extranjero
    correo        VARCHAR(100) NOT NULL,       -- obligatorio (validado también en PHP)
    procedencia   VARCHAR(100) NOT NULL DEFAULT '',  -- opcional: empresa, institución o ciudad
    PRIMARY KEY (id_visitante),
    UNIQUE KEY uq_visitante_cedula (cedula)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- BLOQUE 4: Visita (tabla central)
-- ---------------------------------------------------------------------
CREATE TABLE visita (
    id_visita      INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    fecha_visita   DATE             NOT NULL,
    hora_entrada   TIME             NOT NULL,
    hora_salida    TIME             NULL,      -- se llena al finalizar
    motivo_visita  VARCHAR(255)     NOT NULL,
    observaciones  TEXT             NULL,      -- opcional
    tipo_visita    ENUM('al_momento', 'programada') NOT NULL DEFAULT 'programada',
                                               -- al_momento: ya llegó | programada: cita futura
    id_usuario     INT UNSIGNED     NOT NULL,  -- quién registró la visita
    id_visitante   INT UNSIGNED     NOT NULL,
    id_estado      TINYINT UNSIGNED NOT NULL DEFAULT 1,  -- 1 Programada, 2 En proceso, 3 Finalizada, 4 Cancelada, 5 Reprogramada
    cierre_automatico TINYINT(1)    NOT NULL DEFAULT 0,  -- 1 = el sistema la finalizó por quedar abierta de un día anterior
    aviso_enviado  DATETIME         NULL,      -- cuándo se envió el aviso por correo (visitas programadas)
    recordatorio_enviado DATETIME   NULL,      -- cuándo se envió el recordatorio (24 horas antes de la cita)
    PRIMARY KEY (id_visita),
    KEY idx_visita_fecha (fecha_visita),
    KEY idx_visita_estado_fecha (id_estado, fecha_visita),  -- para el cierre automático
    CONSTRAINT fk_visita_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_visita_visitante
        FOREIGN KEY (id_visitante) REFERENCES visitante (id_visitante)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_visita_estado
        FOREIGN KEY (id_estado) REFERENCES estado_visita (id_estado)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- BLOQUE 5: Tabla pivote (visita <-> departamento, relación N:M)
-- ---------------------------------------------------------------------
CREATE TABLE visita_departamento (
    id_visita_departamento  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_departamento         INT UNSIGNED NOT NULL,
    id_visita               INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_visita_departamento),
    -- Una visita no puede repetir el mismo departamento
    UNIQUE KEY uq_visita_departamento (id_visita, id_departamento),
    CONSTRAINT fk_vd_departamento
        FOREIGN KEY (id_departamento) REFERENCES departamento (id_departamento)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    -- Si se elimina la visita, se eliminan sus asignaciones
    CONSTRAINT fk_vd_visita
        FOREIGN KEY (id_visita) REFERENCES visita (id_visita)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- BLOQUE 6: Auditoría (registro simple de acciones clave)
-- ---------------------------------------------------------------------
-- Sin llave foránea, igual que en el DER: así el historial se conserva
-- aunque se modifique o elimine el usuario.
CREATE TABLE auditoria (
    id_auditoria    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    accion          VARCHAR(30)     NOT NULL,   -- CREAR, EDITAR, ELIMINAR, CAMBIO_ESTADO, OBSERVACIONES, CIERRE_AUTOMATICO, AVISO_CORREO, RECORDATORIO, EXPORTAR, CAMBIO_CLAVE, LOGIN...
    descripcion     VARCHAR(255)    NOT NULL,
    tabla_afectada  VARCHAR(50)     NOT NULL,
    fecha_hora      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario      INT UNSIGNED    NOT NULL,
    PRIMARY KEY (id_auditoria),
    KEY idx_auditoria_fecha (fecha_hora),
    KEY idx_auditoria_usuario (id_usuario)
) ENGINE = InnoDB;

-- Control de los procesos automáticos (por ejemplo, los recordatorios por correo):
-- evita que varias pestañas o usuarios conectados los ejecuten a la vez.
CREATE TABLE proceso_automatico (
    nombre            VARCHAR(40) NOT NULL,
    ultima_ejecucion  DATETIME    NULL,
    PRIMARY KEY (nombre)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- BLOQUE 7: Datos semilla (reglas de negocio fijas)
-- ---------------------------------------------------------------------
-- Los IDs coinciden con las constantes de las clases Usuario y Visita en PHP.
INSERT INTO rol (id_rol, nombre_rol) VALUES
    (1, 'Administrador'),
    (2, 'Recepcionista');

INSERT INTO proceso_automatico (nombre, ultima_ejecucion) VALUES ('recordatorios', NULL);

INSERT INTO estado_visita (id_estado, nombre_estado) VALUES
    (1, 'Programada'),
    (2, 'En proceso'),
    (3, 'Finalizada'),
    (4, 'Cancelada'),
    (5, 'Reprogramada');

-- Departamentos de la institución
INSERT INTO departamento (nombre_departamento) VALUES
    ('Planificación y Talento Humano'),
    ('Servicio y Mantenimiento'),
    ('División de Informática'),
    ('Formación Profesional');

-- El primer usuario administrador se crea desde PHP para guardar
-- la contraseña con password_hash(). Ejemplo:
--   echo password_hash('TuClaveSegura', PASSWORD_DEFAULT);
-- y luego:
--   INSERT INTO usuario (cedula, nombre, apellido, telefono, correo, `user`, `password`, estado, id_rol)
--   VALUES ('V-10000000', 'Admin', 'Sistema', '0241-0000000', 'admin@correo.com', 'admin', '<hash aquí>', 1, 1);
-- Los recepcionistas los crea el administrador desde «Gestión de Usuarios».
