<?php
// app/models/traits/UsuarioCuentas.php

/**
 * Usuario: listado, alta y edición de las cuentas de los recepcionistas (Gestión de Usuarios).
 */
trait UsuarioCuentas
{
    /** Condiciones del listado de recepcionistas (texto y estado). */
    private function condicionesRecepcionistas(string $texto, string $estado, array &$parametros): string
    {
        $condiciones = ['u.id_rol = ' . self::ROL_RECEPCIONISTA];
        if ($texto !== '') {
            $parametros[':texto'] = '%' . addcslashes($texto, '%_\\') . '%';
            $condiciones[] = "CONCAT_WS(' ', u.cedula, u.nombre, u.apellido, u.telefono, u.correo, u.`user`) LIKE :texto";
        }
        if ($estado === 'activos') {
            $condiciones[] = 'u.estado = 1';
        } elseif ($estado === 'inactivos') {
            $condiciones[] = 'u.estado = 0';
        }
        return 'WHERE ' . implode(' AND ', $condiciones);
    }

    public function contarRecepcionistas(string $texto = '', string $estado = ''): int
    {
        $parametros = [];
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuario u " . $this->condicionesRecepcionistas($texto, $estado, $parametros));
        $stmt->execute($parametros);
        return (int) $stmt->fetchColumn();
    }

    /** Una página del listado de recepcionistas, con la cantidad de visitas que registró cada uno. */
    public function listarRecepcionistas(string $texto = '', string $estado = '', int $limite = 20, int $desplazamiento = 0): array
    {
        $parametros = [];
        $donde = $this->condicionesRecepcionistas($texto, $estado, $parametros);
        $stmt = $this->db->prepare(
            "SELECT u.id_usuario, u.cedula, u.nombre, u.apellido, u.telefono, u.correo, u.`user`, u.estado,
                    (SELECT COUNT(*) FROM visita v WHERE v.id_usuario = u.id_usuario) AS total_visitas
             FROM usuario u
             {$donde}
             ORDER BY u.estado DESC, u.apellido ASC, u.nombre ASC
             LIMIT :limite OFFSET :desplazamiento"
        );
        foreach ($parametros as $nombre => $valor) {
            $stmt->bindValue($nombre, $valor);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':desplazamiento', $desplazamiento, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Revisa si cédula, correo o nombre de usuario ya pertenecen a otra cuenta.
     * @return array<string,string> errores por campo
     */
    public function buscarDuplicados(array $datos, int $excluirId = 0): array
    {
        $mensajes = [
            'cedula' => 'Ya existe otra cuenta con esa cédula.',
            'correo' => 'Ya existe otra cuenta con ese correo.',
            'user'   => 'Ese nombre de usuario ya está en uso.',
        ];
        $errores = [];
        foreach ($mensajes as $campo => $mensaje) {
            $columna = $campo === 'user' ? '`user`' : $campo;
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuario WHERE {$columna} = :valor AND id_usuario <> :id");
            $stmt->execute([':valor' => $datos[$campo], ':id' => $excluirId]);
            if ((int) $stmt->fetchColumn() > 0) {
                $errores[$campo] = $mensaje;
            }
        }
        return $errores;
    }

    /**
     * @param array $datos cedula, nombre, apellido, telefono, correo, user, password, id_rol
     * @return int ID del usuario creado
     */
    public function crear(array $datos): int
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO usuario
                    (cedula, nombre, apellido, telefono, correo, `user`, `password`, estado, id_rol)
                 VALUES
                    (:cedula, :nombre, :apellido, :telefono, :correo, :user, :password, 1, :id_rol)"
            );
            $stmt->execute([
                ':cedula'   => $datos['cedula'],
                ':nombre'   => $datos['nombre'],
                ':apellido' => $datos['apellido'],
                ':telefono' => $datos['telefono'],
                ':correo'   => $datos['correo'],
                ':user'     => $datos['user'],
                // Cifra la contraseña de forma segura usando el algoritmo por defecto de PHP
                ':password' => password_hash($datos['password'], PASSWORD_DEFAULT),
                ':id_rol'   => $datos['id_rol'],
            ]);

            return (int) $this->db->lastInsertId();
        } catch (PDOException $excepcion) {
            if ($excepcion->getCode() === '23000') {
                throw new Exception('Ya existe una cuenta con esa cédula, correo o nombre de usuario.');
            }
            error_log('Error al crear usuario: ' . $excepcion->getMessage());
            throw new Exception('No se pudo registrar el usuario.');
        }
    }

    /**
     * Actualiza los datos personales y el nombre de usuario. El rol no cambia aquí
     * y la contraseña se cambia por separado con cambiarPassword().
     */
    public function actualizar(int $id_usuario, array $datos): bool
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE usuario
                 SET cedula = :cedula, nombre = :nombre, apellido = :apellido,
                     telefono = :telefono, correo = :correo, `user` = :user
                 WHERE id_usuario = :id"
            );
            return $stmt->execute([
                ':cedula'   => $datos['cedula'],
                ':nombre'   => $datos['nombre'],
                ':apellido' => $datos['apellido'],
                ':telefono' => $datos['telefono'],
                ':correo'   => $datos['correo'],
                ':user'     => $datos['user'],
                ':id'       => $id_usuario,
            ]);
        } catch (PDOException $excepcion) {
            if ($excepcion->getCode() === '23000') {
                throw new Exception('Ya existe otra cuenta con esa cédula, correo o nombre de usuario.');
            }
            error_log('Error al actualizar usuario: ' . $excepcion->getMessage());
            throw new Exception('No se pudo actualizar el usuario.');
        }
    }
}
