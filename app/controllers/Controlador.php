<?php
// app/controllers/Controlador.php
// Clase base: concentra lo que todos los controladores repiten
// (vistas, redirecciones, permisos, mensajes y token CSRF).
// La verificación de la sesión está en app/controllers/traits/ControlSesion.php.

require_once __DIR__ . '/traits/ControlSesion.php';
// La plantilla (barra lateral, campana y modales) usa estas clases en todas las páginas
require_once __DIR__ . '/../models/Visita.php';
require_once __DIR__ . '/../models/Notificacion.php';
require_once __DIR__ . '/../utilidades/CorreoSmtp.php';

abstract class Controlador
{
    use ControlSesion;   // verificarSesion(), revisarCuenta(), cerrarSesionForzada()

    /** Páginas a las que se puede volver después de una acción, y sus filtros permitidos. */
    private const RETORNOS = [
        'dashboard'           => [],
        'visitas'             => ['q', 'estado', 'tipo', 'pagina'],
        'visitas/programadas' => ['q', 'pagina'],
        'visitantes/ver'      => ['id'],
        'usuarios'            => ['q', 'estado', 'pagina'],
        'notificaciones'      => [],
    ];

    /**
     * Ruta a la que se vuelve después de una acción (campo oculto "volver"),
     * con los filtros del listado si los tenía. Solo se aceptan páginas conocidas.
     */
    protected function rutaRetorno(string $porDefecto = 'visitas'): string
    {
        $volver = entradaTexto($_POST, 'volver');
        if (!array_key_exists($volver, self::RETORNOS)) {
            return $porDefecto;
        }

        parse_str(entradaTexto($_POST, 'volver_filtros'), $filtros);
        $filtros = array_intersect_key($filtros, array_flip(self::RETORNOS[$volver]));
        $filtros = array_filter($filtros, static fn ($valor) => is_string($valor) && $valor !== '');

        return $volver . ($filtros ? '?' . http_build_query($filtros) : '');
    }

    /**
     * Exige que el rol del usuario tenga un permiso (ver Usuario::PERMISOS).
     * Si no lo tiene, vuelve a la página indicada con un mensaje de error.
     */
    protected function exigirPermiso(string $permiso, string $volver): void
    {
        if (!puede($permiso)) {
            $_SESSION['errores'] = ['Su rol no tiene permiso para realizar esta acción.'];
            $this->redirigir($volver);
        }
    }

    /**
     * Dibuja una vista dentro de la plantilla (barra lateral + barra superior + pie).
     * La plantilla necesita el token CSRF (formularios de los modales, como el de
     * cerrar sesión) y la cantidad de citas pendientes (aviso del menú lateral).
     */
    protected function render(string $vista, array $datos = []): void
    {
        $datos['csrf'] ??= $this->generarCsrf();
        if (!isset($datos['citasPendientes']) && !empty($_SESSION['usuario'])) {
            $datos['citasPendientes'] = (new Visita())->contarCitasPendientes();
        }

        // Notificaciones pendientes (campana de la barra superior y menú lateral)
        if (!empty($_SESSION['usuario'])) {
            $datos['notificacionesPendientes'] ??= (new Notificacion())->pendientes();
            $datos['resumenNotificaciones'] = Notificacion::resumir($datos['notificacionesPendientes']);

            // Justo después de iniciar sesión se muestran las citas y tareas pendientes del día
            $datos['mostrarNotificacionesInicio'] = !empty($_SESSION['notificaciones_inicio']);
            unset($_SESSION['notificaciones_inicio']);
        }
        extract($datos);
        require APP_PATH . '/views/layouts/header.php';
        require APP_PATH . "/views/{$vista}.php";
        require APP_PATH . '/views/layouts/footer.php';
    }

    protected function redirigir(string $ruta): void
    {
        header('Location: ' . url($ruta));
        exit;
    }

    /** Responde en formato JSON (para las consultas hechas con fetch). */
    protected function responderJson(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function generarCsrf(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    protected function validarCsrf(mixed $token = null): void
    {
        $token = is_string($token) ? $token : entradaTexto($_POST, 'csrf');
        if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
            http_response_code(403);
            exit('Token de seguridad inválido. Recargue la página e intente de nuevo.');
        }
    }

    /** Mensajes de una sola lectura (se borran después de mostrarse). */
    protected function tomarMensajes(): array
    {
        $mensajes = [
            'flashOk'    => $_SESSION['flash_ok'] ?? null,
            'flashAviso' => $_SESSION['flash_aviso'] ?? null,
            'errores'    => $_SESSION['errores'] ?? [],
        ];
        unset($_SESSION['flash_ok'], $_SESSION['flash_aviso'], $_SESSION['errores']);
        return $mensajes;
    }

    /** Vuelve al formulario con los errores y los datos escritos (menos los campos indicados, p. ej. contraseñas). */
    protected function volverConErrores(string $ruta, array $errores, array $sinDevolver = []): never
    {
        $old = datosFormulario($_POST);
        foreach ($sinDevolver as $campo) {
            unset($old[$campo]);
        }
        $_SESSION['errores'] = $errores;
        $_SESSION['old']     = $old;
        $this->redirigir($ruta);
        exit;
    }
}
