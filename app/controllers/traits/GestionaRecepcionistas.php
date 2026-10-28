<?php
// app/controllers/traits/GestionaRecepcionistas.php
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../../models/Visitante.php';
require_once __DIR__ . '/../../models/Auditoria.php';

/**
 * Gestión de Usuarios (solo administrador): lo común a sus controladores. Exige el permiso,
 * busca la cuenta del recepcionista, valida la contraseña nueva y registra en la auditoría.
 */
trait GestionaRecepcionistas
{
    protected Usuario $modelo;
    protected Auditoria $auditoria;

    public function __construct()
    {
        $this->verificarSesion();
        // Todo el módulo es exclusivo del administrador
        $this->exigirPermiso('usuarios.gestionar', 'dashboard');
        $this->modelo    = new Usuario();
        $this->auditoria = new Auditoria();
    }

    // ------------------------------------------------------------

    /** Busca la cuenta indicada; solo se gestionan cuentas de recepcionistas. */
    protected function obtenerRecepcionista(int $id, string $volver = 'usuarios'): array
    {
        $cuenta = $id > 0 ? $this->modelo->obtenerPorId($id) : null;
        if (!$cuenta || (int) $cuenta['id_rol'] !== Usuario::ROL_RECEPCIONISTA) {
            $_SESSION['errores'] = ['El recepcionista indicado no existe.'];
            $this->redirigir($volver);
        }
        return $cuenta;
    }

    /** Valida los campos clave_nueva y clave_confirmar del formulario. */
    protected function validarClaves(string $nombreUsuario): array
    {
        $nueva     = entradaTexto($_POST, 'clave_nueva');
        $confirmar = entradaTexto($_POST, 'clave_confirmar');
        $errores   = [];

        $error = Usuario::validarClaveNueva($nueva, $nombreUsuario);
        if ($error !== null) {
            $errores['clave_nueva'] = $error;
        }
        if ($confirmar === '') {
            $errores['clave_confirmar'] = 'Repita la contraseña.';
        } elseif (!hash_equals($nueva, $confirmar)) {
            $errores['clave_confirmar'] = 'Las contraseñas no coinciden.';
        }
        return $errores;
    }

    protected function registrar(string $accion, string $descripcion): void
    {
        $this->auditoria->registrar($accion, mb_substr($descripcion, 0, 255), 'usuario', (int) $_SESSION['usuario']['id_usuario']);
    }
}
