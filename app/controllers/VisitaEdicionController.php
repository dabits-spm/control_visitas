<?php
// app/controllers/VisitaEdicionController.php
// Editar y eliminar cualquier visita (solo administrador).

require_once __DIR__ . '/traits/BuscaVisita.php';
require_once __DIR__ . '/../servicios/ValidadorVisita.php';
require_once __DIR__ . '/../models/Departamento.php';
require_once __DIR__ . '/../models/Auditoria.php';

class VisitaEdicionController extends Controlador
{
    use BuscaVisita;

    private Visita $modeloVisita;

    public function __construct()
    {
        $this->verificarSesion();
        $this->modeloVisita = new Visita();
    }

    /** GET /visitas/editar?id=N  -> formulario de edición */
    public function editar(): void
    {
        $this->exigirPermiso('visitas.editar', 'visitas');
        $visita = $this->obtenerVisita((int) entradaTexto($_GET, 'id'), 'visitas');

        // Datos guardados, en el formato que esperan los campos del formulario
        $guardado = $this->modeloVisita->obtenerPorId((int) $visita['id_visita']);
        $actual = [
            'fecha_visita'  => $guardado['fecha_visita'],
            'hora_entrada'  => substr((string) $guardado['hora_entrada'], 0, 5),
            'hora_salida'   => substr((string) $guardado['hora_salida'], 0, 5),
            'motivo_visita' => $guardado['motivo_visita'],
            'observaciones' => $guardado['observaciones'],
            'departamentos' => $guardado['departamentos'],
        ];

        $datos = [
            'paginaActiva'  => 'visitas',
            'tituloPagina'  => 'Editar Visita',
            'visita'        => $visita,
            'departamentos' => (new Departamento())->listar(),
            'csrf'          => $this->generarCsrf(),
            'errores'       => $_SESSION['errores'] ?? [],
            'old'           => $_SESSION['old'] ?? $actual,
        ];
        unset($_SESSION['errores'], $_SESSION['old']);

        $this->render('visitas/editar', $datos);
    }

    /** POST /visitas/actualizar  -> guarda los cambios de una visita */
    public function actualizar(): void
    {
        $this->validarCsrf();
        $this->exigirPermiso('visitas.editar', 'visitas');

        $visita    = $this->obtenerVisita((int) entradaTexto($_POST, 'id_visita'), 'visitas');
        $id_visita = (int) $visita['id_visita'];
        $esCita    = Visita::esCita((int) $visita['id_estado']);
        $volver    = 'visitas/editar?id=' . $id_visita;

        [$datos, $departamentos, $errores] = ValidadorVisita::leerDatos($_POST);
        if ($esCita) {
            // Una cita pendiente: fecha y hora a futuro, todavía sin hora de salida
            if (!isset($errores['fecha_visita']) && !isset($errores['hora_entrada'])) {
                $errores += ValidadorVisita::validarFechaCita($datos['fecha_visita'], $datos['hora_entrada']);
            }
            $datos['hora_salida'] = null;
        } else {
            $errores = ValidadorVisita::validarVisitaOcurrida($datos, $visita, entradaTexto($_POST, 'hora_salida'), $errores);
        }
        if ($errores) {
            $this->volverConErrores($volver, $errores);
        }

        try {
            $this->modeloVisita->actualizar($id_visita, $datos, $departamentos);
        } catch (Exception $excepcion) {
            $this->volverConErrores($volver, ['general' => $excepcion->getMessage()]);
        }

        $this->auditar('EDITAR', "Visita #{$id_visita} de {$visita['visitante']} (cédula {$visita['cedula']}) editada");
        $_SESSION['flash_ok'] = 'Visita actualizada correctamente.';
        $this->redirigir($esCita ? 'visitas/programadas' : 'visitas');
    }

    /** POST /visitas/eliminar  -> borra cualquier visita */
    public function eliminar(): void
    {
        $this->validarCsrf();
        $volver = $this->rutaRetorno();
        $this->exigirPermiso('visitas.eliminar', $volver);

        $visita    = $this->obtenerVisita((int) entradaTexto($_POST, 'id_visita'), $volver);
        $id_visita = (int) $visita['id_visita'];

        if ($this->modeloVisita->eliminar($id_visita)) {
            // Se guarda el detalle en la auditoría porque la visita deja de existir
            $this->auditar('ELIMINAR', sprintf('Visita #%d eliminada: %s (cédula %s), %s, estado %s',
                $id_visita, $visita['visitante'], $visita['cedula'], fechaLarga($visita['fecha_visita']), $visita['estado']));
            $_SESSION['flash_ok'] = 'Visita eliminada.';
        } else {
            $_SESSION['errores'] = ['No se pudo eliminar la visita.'];
        }
        $this->redirigir($volver);
    }

    private function auditar(string $accion, string $detalle): void
    {
        (new Auditoria())->registrar($accion, mb_substr($detalle, 0, 255), 'visita', (int) $_SESSION['usuario']['id_usuario']);
    }
}
