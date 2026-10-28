<?php
// app/controllers/VisitaEstadoController.php
// Cambiar el estado de una visita y editar sus observaciones.
// Recepcionista: solo visitas activas (programadas, reprogramadas o en proceso).
// Administrador: cualquier visita.

require_once __DIR__ . '/traits/BuscaVisita.php';
require_once __DIR__ . '/../servicios/CambioEstadoVisita.php';
require_once __DIR__ . '/../models/Auditoria.php';

class VisitaEstadoController extends Controlador
{
    use BuscaVisita;

    public function __construct()
    {
        $this->verificarSesion();
    }

    /**
     * POST /visitas/estado  -> cambia el estado (iniciar, reprogramar, finalizar, cancelar...)
     * Para reprogramar se envían además fecha_visita y hora_entrada.
     */
    public function cambiarEstado(): void
    {
        $this->validarCsrf();
        $volver    = $this->rutaRetorno();
        $visita    = $this->obtenerVisita((int) entradaTexto($_POST, 'id_visita'), $volver);
        $id_estado = (int) entradaTexto($_POST, 'id_estado');

        $rechazo = CambioEstadoVisita::motivoRechazo($visita, $id_estado, $this->esAdministrador());
        if ($rechazo !== null) {
            $_SESSION['errores'] = [$rechazo];
            $this->redirigir($volver);
        }

        $nuevaFecha = $nuevaHora = null;
        if ($id_estado === Visita::ESTADO_REPROGRAMADA) {
            [$nuevaFecha, $nuevaHora, $errores] = CambioEstadoVisita::leerReprogramacion($_POST);
            if ($errores) {
                $_SESSION['errores'] = $errores;
                $this->redirigir($volver);
            }
        }

        $mensaje = CambioEstadoVisita::aplicar($visita, $id_estado, $nuevaFecha, $nuevaHora);
        if ($mensaje === null) {
            $_SESSION['errores'] = ['No se pudo actualizar el estado: la visita cambió mientras tanto. Revise el listado.'];
            $this->redirigir($volver);
        }
        $_SESSION['flash_ok'] = $mensaje;
        // Una cita reprogramada desde el historial vuelve a la lista de programadas
        if ($id_estado === Visita::ESTADO_REPROGRAMADA
            && str_starts_with($volver, 'visitas') && !str_starts_with($volver, 'visitas/programadas')) {
            $volver = 'visitas/programadas';
        }
        $this->redirigir($volver);
    }

    /**
     * POST /visitas/observaciones  -> agrega o corrige las observaciones de una visita.
     * Recepcionista: solo mientras la visita está activa. Administrador: en cualquier momento.
     */
    public function observaciones(): void
    {
        $this->validarCsrf();
        $volver = $this->rutaRetorno();
        $visita = $this->obtenerVisita((int) entradaTexto($_POST, 'id_visita'), $volver);

        if (!Visita::esActiva((int) $visita['id_estado']) && !puede('visitas.observaciones_cerradas')) {
            $_SESSION['errores'] = ['Las observaciones de una visita finalizada o cancelada solo las modifica un administrador.'];
            $this->redirigir($volver);
        }

        $texto = trim(str_replace("\r\n", "\n", entradaTexto($_POST, 'observaciones')));
        if (mb_strlen($texto) > Visita::MAX_OBSERVACIONES) {
            $_SESSION['errores'] = ['Las observaciones no pueden superar los ' . Visita::MAX_OBSERVACIONES . ' caracteres.'];
            $this->redirigir($volver);
        }

        (new Visita())->actualizarObservaciones((int) $visita['id_visita'], $texto === '' ? null : $texto);
        (new Auditoria())->registrar(
            'OBSERVACIONES',
            mb_substr("Observaciones de la visita #{$visita['id_visita']} de {$visita['visitante']} "
                . ($texto === '' ? 'borradas' : 'actualizadas'), 0, 255),
            'visita',
            (int) $_SESSION['usuario']['id_usuario']
        );

        $_SESSION['flash_ok'] = $texto === '' ? 'Observaciones borradas.' : 'Observaciones guardadas.';
        $this->redirigir($volver);
    }
}
