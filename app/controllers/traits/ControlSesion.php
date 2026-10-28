<?php
// app/controllers/traits/ControlSesion.php
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../../models/Visita.php';
require_once __DIR__ . '/../../models/Auditoria.php';

/**
 * Controladores: exige una sesión iniciada y vigente (cuenta activa y misma contraseña) y
 * cierra las visitas de días anteriores que quedaron abiertas.
 */
trait ControlSesion
{
    /**
     * Exige una sesión iniciada; si no la hay, envía al login.
     * Con la sesión iniciada, cierra las visitas de días anteriores que quedaron abiertas.
     */
    protected function verificarSesion(): void
    {
        if (empty($_SESSION['usuario'])) {
            $this->redirigir('login');
        }
        $motivo = $this->revisarCuenta();
        if ($motivo !== null) {
            $this->cerrarSesionForzada($motivo);
        }
        $this->cerrarVisitasVencidas();
    }

    /**
     * Revisa en la base de datos la cuenta de la sesión abierta.
     * Devuelve null si sigue vigente, o el motivo por el que ya no sirve:
     *   'baja'  -> la cuenta fue dada de baja o eliminada por el administrador
     *   'clave' -> la contraseña cambió después de iniciar la sesión
     * Si sigue vigente, actualiza en la sesión el nombre y el rol (por si el administrador los cambió).
     */
    protected function revisarCuenta(): ?string
    {
        $cuenta = (new Usuario())->datosSesion((int) $_SESSION['usuario']['id_usuario']);
        if ($cuenta === null || (int) $cuenta['estado'] !== 1) {
            return 'baja';
        }
        // Sesiones iniciadas antes de la versión 1.7 no tienen huella: se les asigna la actual
        $_SESSION['usuario']['huella'] ??= $cuenta['huella'];
        if (!hash_equals($_SESSION['usuario']['huella'], $cuenta['huella'])) {
            return 'clave';
        }

        foreach (['nombre', 'apellido', 'user', 'id_rol', 'nombre_rol'] as $campo) {
            $_SESSION['usuario'][$campo] = $campo === 'id_rol' ? (int) $cuenta[$campo] : $cuenta[$campo];
        }
        return null;
    }

    /** Cierra la sesión porque la cuenta ya no es válida y envía al login con el motivo. */
    protected function cerrarSesionForzada(string $motivo): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parametros = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $parametros['path'], $parametros['domain'],
                      $parametros['secure'], $parametros['httponly']);
        }
        session_destroy();
        $this->redirigir('login?motivo=' . $motivo);
    }

    /**
     * Regla de negocio: una visita "Programada" o "En proceso" de un día anterior
     * se marca "Finalizada" automáticamente (ej: la del 30/10 se cierra el 31/10).
     * Es una consulta pequeña, así no hace falta programar tareas en el servidor.
     */
    private function cerrarVisitasVencidas(): void
    {
        try {
            $cerradas = (new Visita())->cerrarVencidas();
        } catch (Throwable $excepcion) {
            error_log('Error en el cierre automático de visitas: ' . $excepcion->getMessage());
            return;
        }

        if ($cerradas > 0) {
            (new Auditoria())->registrar(
                'CIERRE_AUTOMATICO',
                $cerradas . ($cerradas === 1 ? ' visita de días anteriores finalizada' : ' visitas de días anteriores finalizadas')
                    . ' automáticamente',
                'visita',
                (int) $_SESSION['usuario']['id_usuario']
            );
        }
    }
}
