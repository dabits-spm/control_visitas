<?php
// app/controllers/VisitaRegistroController.php
// Registrar una visita nueva y enviar (o reenviar) el aviso por correo de una cita.

require_once __DIR__ . '/traits/BuscaVisita.php';
require_once __DIR__ . '/../servicios/RegistroVisita.php';
require_once __DIR__ . '/../servicios/AvisoCita.php';

class VisitaRegistroController extends Controlador
{
    use BuscaVisita;

    public function __construct()
    {
        $this->verificarSesion();
    }

    /** POST /visitas/guardar  -> procesa y almacena la nueva visita */
    public function guardar(): void
    {
        $this->validarCsrf();

        [$visitante, $datos, $departamentos, $errores] =
            RegistroVisita::validar($_POST, (int) $_SESSION['usuario']['id_usuario']);
        if ($errores) {
            $this->volverConErrores('visitas/crear', $errores);
        }

        try {
            [$id_visita, $mensaje] = RegistroVisita::guardar($visitante, $datos, $departamentos);
        } catch (Exception $excepcion) {
            $this->volverConErrores('visitas/crear', ['general' => $excepcion->getMessage()]);
        }

        $esCita = $datos['tipo_visita'] === Visita::TIPO_PROGRAMADA;
        if ($esCita) {
            $mensaje .= ' Programada para el ' . fechaLarga($datos['fecha_visita']) . ' a las ' . horaDoce($datos['hora_entrada']) . '.';
            AvisoCita::enviar($id_visita, $mensaje);
        } else {
            $mensaje .= ' La visita quedó «En proceso».';
        }

        $_SESSION['flash_ok'] = $mensaje;
        $this->redirigir($esCita ? 'visitas/programadas' : 'dashboard');
    }

    /** POST /visitas/aviso  -> envía (o reenvía) el aviso por correo de una visita programada */
    public function reenviarAviso(): void
    {
        $this->validarCsrf();
        $volver = $this->rutaRetorno();
        $visita = $this->obtenerVisita((int) entradaTexto($_POST, 'id_visita'), $volver);

        if (!Visita::esCita((int) $visita['id_estado'])) {
            $_SESSION['errores'] = ['Solo se envía el aviso de las visitas programadas o reprogramadas.'];
            $this->redirigir($volver);
        }

        $mensaje = '';
        $reprogramada = (int) $visita['id_estado'] === Visita::ESTADO_REPROGRAMADA;
        if (AvisoCita::enviar((int) $visita['id_visita'], $mensaje, $reprogramada)) {
            $_SESSION['flash_ok'] = trim($mensaje);
        }
        $this->redirigir($volver);
    }
}
