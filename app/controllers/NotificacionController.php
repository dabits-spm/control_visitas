<?php
// app/controllers/NotificacionController.php
// Notificaciones internas (administrador y recepcionista) y disparo de los
// recordatorios por correo de las citas.

require_once __DIR__ . '/../models/Notificacion.php';
require_once __DIR__ . '/../utilidades/RecordatorioCitas.php';

class NotificacionController extends Controlador
{
    private Notificacion $notificaciones;

    public function __construct()
    {
        $this->verificarSesion();
        $this->notificaciones = new Notificacion();
    }

    /** GET /notificaciones -> bandeja de alertas y tareas pendientes del día */
    public function index(): void
    {
        $pendientes = $this->notificaciones->pendientes();

        $grupos = array_fill_keys(array_keys(Notificacion::TIPOS), []);
        foreach ($pendientes as $n) {
            $grupos[$n['tipo']][] = $n;
        }

        $this->render('notificaciones/index', array_merge([
            'paginaActiva' => 'notificaciones',
            'grupos'       => array_filter($grupos),
            'resumen'      => Notificacion::resumir($pendientes),
            'correoActivo' => CorreoSmtp::estaConfigurado(),
            'notificacionesPendientes' => $pendientes,
        ], $this->tomarMensajes()));
    }

    /**
     * GET /notificaciones/estado -> JSON con las notificaciones pendientes.
     * 08-notificaciones.js lo consulta cada minuto para actualizar la campana y avisar cuando
     * una cita pasa 30 minutos de su hora sin cambio de estado.
     */
    public function estado(): void
    {
        $pendientes = $this->notificaciones->pendientes();
        $this->responderJson([
            'resumen' => Notificacion::resumir($pendientes),
            'items'   => array_map([Notificacion::class, 'paraJson'], $pendientes),
        ]);
    }

    /**
     * POST /notificaciones/recordatorios -> envía los recordatorios por correo pendientes.
     * 08-notificaciones.js lo llama en segundo plano; el usuario no espera el envío.
     */
    public function recordatorios(): void
    {
        $this->validarCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        $idUsuario = (int) $_SESSION['usuario']['id_usuario'];

        // Se libera la sesión antes de enviar: así las demás páginas del usuario no
        // quedan esperando mientras sale el correo.
        session_write_close();

        $resultado = RecordatorioCitas::procesar(5, false, $idUsuario);
        unset($resultado['errores']);   // el detalle queda en el registro de errores de PHP
        $this->responderJson($resultado);
    }
}
