<?php
// app/controllers/CuentaController.php
// «Mi perfil» (tarjeta de solo lectura) y cambio de la contraseña propia (solo administrador).

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Auditoria.php';

class CuentaController extends Controlador
{
    /**
     * GET /cuenta -> "Mi perfil": tarjeta con los datos de la cuenta (solo lectura).
     * El recepcionista no puede modificarlos; el administrador además ve "Cambiar contraseña".
     */
    public function perfil(): void
    {
        $this->verificarSesion();

        $this->render('auth/perfil', array_merge([
            'paginaActiva' => 'perfil',
            'tituloPagina' => 'Mi perfil',
            'cuenta'       => (new Usuario())->obtenerPorId((int) $_SESSION['usuario']['id_usuario']),
        ], $this->tomarMensajes()));
    }

    /** GET /cuenta/clave -> formulario para cambiar la contraseña propia (solo administrador) */
    public function mostrarCambioClave(): void
    {
        $this->verificarSesion();
        // El recepcionista no cambia su contraseña: lo hace el administrador
        $this->exigirPermiso('cuenta.cambiar_clave', 'cuenta');

        $datos = [
            'paginaActiva' => 'clave',
            'tituloPagina' => 'Cambiar contraseña',
            'csrf'         => $this->generarCsrf(),
            'errores'      => $_SESSION['errores'] ?? [],
            'reglasClave'  => Usuario::REGLAS_CLAVE,
        ];
        unset($_SESSION['errores']);

        $this->render('auth/cambiar_clave', $datos);
    }

    /** POST /cuenta/clave -> valida la contraseña actual y guarda la nueva */
    public function cambiarClave(): void
    {
        $this->verificarSesion();
        $this->validarCsrf();
        $this->exigirPermiso('cuenta.cambiar_clave', 'cuenta');

        $idUsuario = (int) $_SESSION['usuario']['id_usuario'];
        $actual    = entradaTexto($_POST, 'clave_actual');
        $nueva     = entradaTexto($_POST, 'clave_nueva');
        $confirmar = entradaTexto($_POST, 'clave_confirmar');
        $modelo    = new Usuario();

        $errores = [];
        if ($actual === '') {
            $errores['clave_actual'] = 'Escriba su contraseña actual.';
        } elseif (!$modelo->verificarClave($idUsuario, $actual)) {
            $errores['clave_actual'] = 'La contraseña actual no es correcta.';
        }

        $errorNueva = Usuario::validarClaveNueva($nueva, (string) ($_SESSION['usuario']['user'] ?? ''));
        if ($errorNueva !== null) {
            $errores['clave_nueva'] = $errorNueva;
        } elseif ($actual !== '' && hash_equals($actual, $nueva)) {
            $errores['clave_nueva'] = 'La contraseña nueva debe ser distinta de la actual.';
        }

        if ($confirmar === '') {
            $errores['clave_confirmar'] = 'Repita la contraseña nueva.';
        } elseif (!hash_equals($nueva, $confirmar)) {
            $errores['clave_confirmar'] = 'Las contraseñas no coinciden.';
        }

        if ($errores) {
            // Las contraseñas nunca se devuelven al formulario
            $_SESSION['errores'] = $errores;
            $this->redirigir('cuenta/clave');
        }

        // La huella nueva mantiene abierta esta sesión; cualquier otra sesión de la cuenta se cierra
        $_SESSION['usuario']['huella'] = $modelo->cambiarPassword($idUsuario, $nueva);
        session_regenerate_id(true);   // la sesión anterior deja de servir

        (new Auditoria())->registrar('CAMBIO_CLAVE', 'Cambio de contraseña propia', 'usuario', $idUsuario);

        $_SESSION['flash_ok'] = 'Su contraseña se cambió correctamente. Úsela la próxima vez que inicie sesión.';
        $this->redirigir('cuenta');
    }
}
