<?php
// app/controllers/ReporteController.php
// Reportes por periodo (día, semana, mes) y reporte personalizado, en pantalla o descargados.
// La lógica está en app/servicios/reportes/.

require_once __DIR__ . '/../servicios/reportes/PeriodosReporte.php';
require_once __DIR__ . '/../servicios/reportes/ReportePersonalizado.php';
require_once __DIR__ . '/../servicios/reportes/ExportadorPeriodo.php';
require_once __DIR__ . '/../servicios/reportes/ExportadorPersonalizado.php';

class ReporteController extends Controlador
{
    public function __construct()
    {
        $this->verificarSesion();
    }

    /** GET /reportes?tipo=diario|semanal|mensual&desde=AAAA-MM-DD&hasta=AAAA-MM-DD[&formato=xlsx|txt] */
    public function index(): void
    {
        $tipo = entradaTexto($_GET, 'tipo') ?: 'diario';
        if (!isset(PeriodosReporte::TIPOS[$tipo])) {
            $tipo = 'diario';
        }
        // Si no se indican fechas, se usa el rango sugerido para el tipo elegido
        [$desde, $hasta, $errores] = PeriodosReporte::leerRango($tipo);

        $datos = [
            'paginaActiva' => 'reportes',
            'tipo'         => $tipo,
            'tipos'        => PeriodosReporte::TIPOS,
            'rangos'       => PeriodosReporte::rangosPorDefecto(),
            'desde'        => $desde ? $desde->format('Y-m-d') : entradaTexto($_GET, 'desde'),
            'hasta'        => $hasta ? $hasta->format('Y-m-d') : entradaTexto($_GET, 'hasta'),
            'errores'      => $errores,
            'flashOk'      => null,
            'reporte'      => null,
        ];

        if (empty($errores)) {
            $datos['reporte'] = PeriodosReporte::resumen($tipo, $desde, $hasta);
            // El título de la pestaña es el nombre que el navegador sugiere al guardar el PDF
            $datos['tituloPagina'] = sprintf('%s %s al %s', PeriodosReporte::TIPOS[$tipo]['titulo'],
                $desde->format('d-m-Y'), $hasta->format('d-m-Y'));
            $datos['consultaExportar'] = ['tipo' => $tipo, 'desde' => $datos['desde'], 'hasta' => $datos['hasta']];

            $formato = Exportador::formatoPedido();
            if ($formato !== null) {
                ExportadorPeriodo::exportar($formato, $datos);
            }
        }

        $this->render('reportes/index', $datos);
    }

    /**
     * GET /reportes/personalizado
     *   ?desde=&hasta=&estado=&tipo=&departamento=&usuario=&visitante=&columnas[]=...&generar=1
     */
    public function personalizado(): void
    {
        [$desde, $hasta, $errores] = ReportePersonalizado::leerRango();
        [$estados, $departamentos, $usuarios] = ReportePersonalizado::opciones();

        $datos = [
            'paginaActiva'  => 'reportes',
            'tituloPagina'  => 'Reporte personalizado de visitas',
            'desde'         => $desde ? $desde->format('Y-m-d') : entradaTexto($_GET, 'desde'),
            'hasta'         => $hasta ? $hasta->format('Y-m-d') : entradaTexto($_GET, 'hasta'),
            'filtros'       => ReportePersonalizado::leerFiltros($estados, $departamentos, $usuarios),
            'estados'       => $estados,
            'departamentos' => $departamentos,
            'usuarios'      => $usuarios,
            'columnasDisponibles' => ReportePersonalizado::COLUMNAS,
            'columnas'      => ReportePersonalizado::leerColumnas(),
            'errores'       => $errores,
            'flashOk'       => null,
            'visitas'       => null,
        ];

        if (empty($errores)) {
            // Lo que devuelve generar() (visitas, totales, filtros aplicados) reemplaza los valores vacíos
            $datos = ReportePersonalizado::generar($datos) + $datos;
            $datos['tituloPagina'] = sprintf('Reporte personalizado de visitas %s al %s',
                $desde->format('d-m-Y'), $hasta->format('d-m-Y'));

            $formato = Exportador::formatoPedido();
            if ($formato !== null) {
                ExportadorPersonalizado::exportar($formato, $datos);
            }
        }

        $this->render('reportes/personalizado', $datos);
    }
}
