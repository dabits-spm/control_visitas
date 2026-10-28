<?php
// app/controllers/DashboardController.php

require_once __DIR__ . '/../models/Visita.php';
require_once __DIR__ . '/../models/Visitante.php';
require_once __DIR__ . '/../utilidades/CorreoSmtp.php';

class DashboardController extends Controlador
{
    public function __construct()
    {
        $this->verificarSesion();
    }

    /** GET /dashboard -> panel de control con el resumen del día */
    public function index(): void
    {
        $modeloVisita  = new Visita();
        $visitasDelDia = $modeloVisita->listarDelDia();

        // Visitas de hoy por estado (gráfico circular)
        $porEstado = array_fill_keys(array_keys(Visita::COLORES_ESTADO), 0);
        foreach ($visitasDelDia as $v) {
            $porEstado[(int) $v['id_estado']]++;
        }

        $this->render('dashboard/index', array_merge([
            'paginaActiva'     => 'dashboard',
            'visitasHoy'       => count($visitasDelDia),
            'visitasEnProceso' => $modeloVisita->contarPorEstado(Visita::ESTADO_EN_PROCESO),
            'totalVisitantes'  => (new Visitante())->contar(),
            'visitasDelDia'    => $visitasDelDia,
            'porDepartamento'  => $modeloVisita->delDiaPorDepartamento(date('Y-m-d')),
            'porEstado'        => $porEstado,
            'nombresEstado'    => array_column($modeloVisita->listarEstados(), 'nombre_estado', 'id_estado'),
            'csrf'             => $this->generarCsrf(),
            'correoActivo'     => CorreoSmtp::estaConfigurado(),
        ], $this->tomarMensajes()));
    }
}
