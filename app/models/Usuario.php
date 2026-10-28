<?php
// app/models/Usuario.php
// Modelo de las cuentas. Esta clase reúne los roles, los permisos, el inicio de sesión y
// las consultas básicas; el resto está separado por tema en app/models/traits/.

require_once __DIR__ . '/traits/UsuarioValidacion.php';
require_once __DIR__ . '/traits/UsuarioCuentas.php';
require_once __DIR__ . '/traits/UsuarioAcceso.php';

class Usuario
{
    use UsuarioValidacion;   // reglas de la contraseña, normalizar y validar datos
    use UsuarioCuentas;      // listado, alta y edición de recepcionistas
    use UsuarioAcceso;       // contraseña, alta/baja y eliminación

    // Deben coincidir con los datos semilla de la tabla rol.
    // El sistema maneja dos roles: Administrador y Recepcionista.
    public const ROL_ADMINISTRADOR = 1;
    public const ROL_RECEPCIONISTA = 2;

    /**
     * Qué roles pueden realizar cada acción protegida.
     *  - Recepcionista: registra visitas, cambia el estado de las visitas activas
     *    (programadas, reprogramadas o en proceso), edita observaciones mientras la
     *    visita está activa y corrige los datos personales de los visitantes.
     *    No modifica su propia cuenta (ni contraseña ni datos): lo hace el administrador.
     *  - Administrador: además edita, elimina y cambia el estado de cualquier visita,
     *    edita observaciones en cualquier momento, elimina visitantes sin visitas,
     *    gestiona las cuentas de los recepcionistas y cambia su propia contraseña.
     */
    private const PERMISOS = [
        'visitas.editar'                 => [self::ROL_ADMINISTRADOR],
        'visitas.eliminar'               => [self::ROL_ADMINISTRADOR],
        'visitas.estado_cerradas'        => [self::ROL_ADMINISTRADOR],
        'visitas.observaciones_cerradas' => [self::ROL_ADMINISTRADOR],
        'visitantes.editar'              => [self::ROL_ADMINISTRADOR, self::ROL_RECEPCIONISTA],
        'visitantes.eliminar'            => [self::ROL_ADMINISTRADOR],
        'usuarios.gestionar'             => [self::ROL_ADMINISTRADOR],
        'correo.configurar'              => [self::ROL_ADMINISTRADOR],
        'cuenta.cambiar_clave'           => [self::ROL_ADMINISTRADOR],
    ];

    /** Indica si un rol tiene permiso para una acción (ver PERMISOS). */
    public static function rolPuede(int $id_rol, string $permiso): bool
    {
        return in_array($id_rol, self::PERMISOS[$permiso] ?? [], true);
    }

    /** Reglas de la contraseña, para mostrarlas en los formularios. */
    public const REGLAS_CLAVE = [
        'Entre 8 y 72 caracteres.',
        'Al menos una letra y un número.',
        'Que no contenga el nombre de usuario.',
    ];


    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConexion();
    }

    /**
     * Valida las credenciales. Devuelve los datos del usuario (sin la
     * contraseña) o null si el usuario no existe, está inactivo o la
     * contraseña es incorrecta.
     */
    public function autenticar(string $nombre_usuario, string $contrasena): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id_usuario, u.nombre, u.apellido, u.`password`, u.estado,
                    u.id_rol, r.nombre_rol
             FROM usuario u
             INNER JOIN rol r ON r.id_rol = u.id_rol
             WHERE u.`user` = :user"
        );
        $stmt->execute([':user' => $nombre_usuario]);
        $usuario = $stmt->fetch();

        // Si el usuario no existe o su estado no es activo (1), rechaza el acceso
        if (!$usuario || (int) $usuario['estado'] !== 1) {
            return null;
        }

        // Verifica que la contraseña escrita coincida con el hash de la base de datos
        if (!password_verify($contrasena, $usuario['password'])) {
            return null;
        }

        // Nunca se devuelve el hash de la contraseña hacia el resto de la aplicación
        $usuario['huella'] = self::huellaClave($usuario['password']);
        unset($usuario['password'], $usuario['estado']);

        return $usuario;
    }

    /**
     * Datos actuales de la cuenta de una sesión abierta (se consulta en cada página):
     * si fue dada de baja, eliminada o se cambió su contraseña, la sesión se cierra.
     * @return array|null null si la cuenta ya no existe
     */
    public function datosSesion(int $id_usuario): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id_usuario, u.nombre, u.apellido, u.`user`, u.`password`, u.estado,
                    u.id_rol, r.nombre_rol
             FROM usuario u
             INNER JOIN rol r ON r.id_rol = u.id_rol
             WHERE u.id_usuario = :id"
        );
        $stmt->execute([':id' => $id_usuario]);
        $fila = $stmt->fetch();
        if (!$fila) {
            return null;
        }
        $fila['huella'] = self::huellaClave($fila['password']);
        unset($fila['password']);
        return $fila;
    }

    /** Lista todos los usuarios registrados ordenados por apellido y nombre (filtros de reportes) */
    public function listar(): array
    {
        $sql = "SELECT u.id_usuario, u.cedula, u.nombre, u.apellido, u.telefono, u.correo,
                       u.`user`, u.estado, r.nombre_rol
                FROM usuario u
                INNER JOIN rol r ON r.id_rol = u.id_rol
                ORDER BY u.apellido ASC, u.nombre ASC";

        return $this->db->query($sql)->fetchAll();
    }

    /** Busca un usuario específico por su ID (sin la contraseña) */
    public function obtenerPorId(int $id_usuario): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id_usuario, u.cedula, u.nombre, u.apellido, u.telefono, u.correo, u.`user`,
                    u.estado, u.id_rol, r.nombre_rol
             FROM usuario u
             INNER JOIN rol r ON r.id_rol = u.id_rol
             WHERE u.id_usuario = :id"
        );
        $stmt->execute([':id' => $id_usuario]);
        $fila = $stmt->fetch();

        return $fila ?: null;
    }
}
