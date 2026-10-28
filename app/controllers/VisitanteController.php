<?php
// app/controllers/VisitanteController.php

require_once __DIR__ . '/../models/Visitante.php';
require_once __DIR__ . '/../models/Visita.php';
require_once __DIR__ . '/../models/Auditoria.php';

class VisitanteController extends Controlador
{
    /** Visitantes por página en el listado */
    private const POR_PAGINA = 25;

    private Visitante $modeloVisitante;

    public function __construct()
    {
        $this->verificarSesion();
        $this->modeloVisitante = new Visitante();
    }

    /** GET /visitantes?q=texto&pagina=N -> listado de visitantes (todos los roles pueden consultarlo) */
    public function index(): void
    {
        $texto = mb_substr(trim(entradaTexto($_GET, 'q')), 0, 100);
        $total = $this->modeloVisitante->contarBusqueda($texto);
        $pag   = paginacion($total, (int) entradaTexto($_GET, 'pagina'), self::POR_PAGINA);

        $this->render('visitantes/index', array_merge([
            'paginaActiva'   => 'visitantes',
            'visitantes'     => $this->modeloVisitante->listarConResumen($texto, $pag['porPagina'], $pag['desplazamiento']),
            'paginacion'     => $pag,
            'filtros'        => ['q' => $texto],
            'hayVisitantes'  => $total > 0 || $texto !== '',
        ], $this->tomarMensajes()));
    }

    /** GET /visitantes/ver?id=N -> ficha del visitante con todas sus visitas */
    public function ver(): void
    {
        $visitante = $this->modeloVisitante->obtenerPorId((int) entradaTexto($_GET, 'id'));
        if (!$visitante) {
            $_SESSION['errores'] = ['El visitante indicado no existe.'];
            $this->redirigir('visitantes');
        }

        $this->render('visitantes/ver', array_merge([
            'paginaActiva' => 'visitantes',
            'tituloPagina' => $visitante['nombre'] . ' ' . $visitante['apellido'],
            'visitante'    => $visitante,
            'visitas'      => (new Visita())->listarPorVisitante((int) $visitante['id_visitante']),
        ], $this->tomarMensajes()));
    }

    /** POST /visitantes/eliminar -> borra un visitante sin visitas (solo administrador) */
    public function eliminar(): void
    {
        $this->validarCsrf();
        $this->exigirPermiso('visitantes.eliminar', 'visitantes');

        $id_visitante = (int) entradaTexto($_POST, 'id_visitante');
        $visitante    = $this->modeloVisitante->obtenerPorId($id_visitante);
        if (!$visitante) {
            $_SESSION['errores'] = ['El visitante indicado no existe.'];
            $this->redirigir('visitantes');
        }

        if (!$this->modeloVisitante->eliminar($id_visitante)) {
            $_SESSION['errores'] = ['No se puede eliminar un visitante que tiene visitas registradas. Elimine primero sus visitas.'];
            $this->redirigir('visitantes/ver?id=' . $id_visitante);
        }

        (new Auditoria())->registrar(
            'ELIMINAR',
            mb_substr("Visitante #{$id_visitante} eliminado: {$visitante['nombre']} {$visitante['apellido']} (cédula {$visitante['cedula']})", 0, 255),
            'visitante',
            (int) $_SESSION['usuario']['id_usuario']
        );
        $_SESSION['flash_ok'] = 'Visitante eliminado.';
        $this->redirigir('visitantes');
    }

    /** GET /visitantes/editar?id=N -> formulario con los datos personales (todos los roles) */
    public function editar(): void
    {
        $this->exigirPermiso('visitantes.editar', 'visitantes');

        $visitante = $this->modeloVisitante->obtenerPorId((int) entradaTexto($_GET, 'id'));
        if (!$visitante) {
            $_SESSION['errores'] = ['El visitante indicado no existe.'];
            $this->redirigir('visitantes');
        }

        $datos = [
            'paginaActiva' => 'visitantes',
            'tituloPagina' => 'Editar Visitante',
            'visitante'    => $visitante,
            'csrf'         => $this->generarCsrf(),
            'errores'      => $_SESSION['errores'] ?? [],
            // Si hubo errores se muestran los datos que escribió; si no, los guardados
            'old'          => $_SESSION['old'] ?? $visitante,
        ];
        unset($_SESSION['errores'], $_SESSION['old']);

        $this->render('visitantes/editar', $datos);
    }

    /** POST /visitantes/actualizar -> guarda los cambios */
    public function actualizar(): void
    {
        $this->validarCsrf();
        $this->exigirPermiso('visitantes.editar', 'visitantes');

        $id_visitante = (int) entradaTexto($_POST, 'id_visitante');
        $anterior     = $this->modeloVisitante->obtenerPorId($id_visitante);
        if (!$anterior) {
            $_SESSION['errores'] = ['El visitante indicado no existe.'];
            $this->redirigir('visitantes');
        }

        $visitante = Visitante::normalizar($_POST);
        $errores   = Visitante::validar($visitante);
        $volver    = 'visitantes/editar?id=' . $id_visitante;

        if ($errores) {
            $_SESSION['errores'] = $errores;
            $_SESSION['old']     = datosFormulario($_POST);
            $this->redirigir($volver);
        }

        try {
            $this->modeloVisitante->actualizar($id_visitante, $visitante);
        } catch (Exception $excepcion) {
            $_SESSION['errores'] = ['general' => $excepcion->getMessage()];
            $_SESSION['old']     = datosFormulario($_POST);
            $this->redirigir($volver);
        }

        $cambios = Visitante::camposModificados($anterior, $visitante);
        (new Auditoria())->registrar(
            'EDITAR',
            mb_substr("Visitante #{$id_visitante} (cédula {$anterior['cedula']}) actualizado"
                . ($cambios ? ': ' . implode(', ', $cambios) : ' sin cambios'), 0, 255),
            'visitante',
            (int) $_SESSION['usuario']['id_usuario']
        );

        $_SESSION['flash_ok'] = "Datos de {$visitante['nombre']} {$visitante['apellido']} actualizados.";
        $this->redirigir('visitantes/ver?id=' . $id_visitante);
    }
}
