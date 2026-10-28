<?php
// app/controllers/UsuarioAccesoController.php
// Gestión de Usuarios (solo administrador): restablecer la contraseña, dar de baja o
// reactivar y eliminar las cuentas de los recepcionistas.

require_once __DIR__ . '/traits/GestionaRecepcionistas.php';

class UsuarioAccesoController extends Controlador
{
    use GestionaRecepcionistas;

    /** GET /usuarios/clave?id=N -> formulario para restablecer la contraseña */
    public function mostrarClave(): void
    {
        $cuenta = $this->obtenerRecepcionista((int) entradaTexto($_GET, 'id'));
        $datos = [
            'paginaActiva' => 'usuarios',
            'tituloPagina' => 'Restablecer contraseña',
            'cuenta'       => $cuenta,
            'errores'      => $_SESSION['errores'] ?? [],
            'reglasClave'  => Usuario::REGLAS_CLAVE,
        ];
        unset($_SESSION['errores']);

        $this->render('usuarios/clave', $datos);
    }

    /** POST /usuarios/clave -> guarda la contraseña nueva del recepcionista */
    public function cambiarClave(): void
    {
        $this->validarCsrf();
        $cuenta = $this->obtenerRecepcionista((int) entradaTexto($_POST, 'id_usuario'));
        $id     = (int) $cuenta['id_usuario'];

        $errores = $this->validarClaves($cuenta['user']);
        if ($errores) {
            $_SESSION['errores'] = $errores;   // las contraseñas nunca se devuelven al formulario
            $this->redirigir('usuarios/clave?id=' . $id);
        }

        // Al cambiar la huella de la contraseña, cualquier sesión abierta de ese recepcionista se cierra
        $this->modelo->cambiarPassword($id, entradaTexto($_POST, 'clave_nueva'));
        $this->registrar('CAMBIO_CLAVE', "Contraseña del recepcionista #{$id} ({$cuenta['user']}) restablecida por el administrador");

        $_SESSION['flash_ok'] = "Contraseña de {$cuenta['nombre']} {$cuenta['apellido']} restablecida. "
            . 'Comuníquesela en persona; si tenía una sesión abierta, se cerró.';
        $this->redirigir('usuarios');
    }

    /** POST /usuarios/estado -> da de baja (estado=0) o reactiva (estado=1) una cuenta */
    public function cambiarEstado(): void
    {
        $this->validarCsrf();
        $volver = $this->rutaRetorno('usuarios');
        $cuenta = $this->obtenerRecepcionista((int) entradaTexto($_POST, 'id_usuario'), $volver);
        $activar = entradaTexto($_POST, 'estado') === '1';

        if ((int) $cuenta['estado'] === ($activar ? 1 : 0)) {
            $_SESSION['errores'] = [$activar ? 'La cuenta ya está activa.' : 'La cuenta ya estaba dada de baja.'];
            $this->redirigir($volver);
        }

        $this->modelo->cambiarEstado((int) $cuenta['id_usuario'], $activar ? 1 : 0);
        $nombre = "{$cuenta['nombre']} {$cuenta['apellido']}";
        $this->registrar($activar ? 'ACTIVAR' : 'DAR_DE_BAJA',
            "Recepcionista #{$cuenta['id_usuario']} ({$cuenta['user']}) " . ($activar ? 'reactivado' : 'dado de baja'));

        $_SESSION['flash_ok'] = $activar
            ? "La cuenta de {$nombre} está activa de nuevo."
            : "La cuenta de {$nombre} fue dada de baja: ya no puede iniciar sesión y, si estaba conectado, su sesión se cerró.";
        $this->redirigir($volver);
    }

    /** POST /usuarios/eliminar -> borra una cuenta que no registró visitas */
    public function eliminar(): void
    {
        $this->validarCsrf();
        $cuenta = $this->obtenerRecepcionista((int) entradaTexto($_POST, 'id_usuario'));
        $id     = (int) $cuenta['id_usuario'];

        if (!$this->modelo->eliminar($id)) {
            $_SESSION['errores'] = ["{$cuenta['nombre']} {$cuenta['apellido']} registró visitas, por eso su cuenta no se puede eliminar "
                . '(el historial debe conservar quién registró cada visita). Use «Dar de baja».'];
            $this->redirigir('usuarios');
        }

        $this->registrar('ELIMINAR', "Recepcionista #{$id} eliminado: {$cuenta['nombre']} {$cuenta['apellido']} (usuario {$cuenta['user']})");
        $_SESSION['flash_ok'] = "Cuenta de {$cuenta['nombre']} {$cuenta['apellido']} eliminada.";
        $this->redirigir('usuarios');
    }
}
