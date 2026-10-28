<?php
// app/servicios/reportes/ExportadorPeriodo.php

require_once __DIR__ . '/Exportador.php';
require_once __DIR__ . '/PeriodosReporte.php';

/** Descarga del reporte por día, semana o mes (Excel con dos hojas, o texto plano). */
class ExportadorPeriodo extends Exportador
{
    /** Reporte por día, semana o mes. */
    public static function exportar(string $formato, array $datos): void
    {
        $info    = PeriodosReporte::TIPOS[$datos['tipo']];
        $reporte = $datos['reporte'];
        $titulo  = $info['titulo'];
        $generado = self::lineaGenerado($datos['desde'], $datos['hasta']);
        $promedio = round($reporte['promedio'], 1);
        $pico     = $reporte['pico'] ? $reporte['maximo'] . ' (' . $reporte['pico']['etiqueta'] . ')' : 'Sin visitas en el periodo';

        $encabezados = [$info['periodo'], 'Programadas', 'Reprogramadas', 'En proceso', 'Finalizadas', 'Canceladas', 'Total'];
        $filas = array_map(static fn ($p) => [
            $p['etiqueta'], $p['programadas'], $p['reprogramadas'], $p['en_proceso'], $p['finalizadas'], $p['canceladas'], $p['total'],
        ], $reporte['periodos']);
        $t = $reporte['totales'];
        $pie = ['Total del periodo', $t['programadas'], $t['reprogramadas'], $t['en_proceso'], $t['finalizadas'], $t['canceladas'], $t['total']];

        $deptos = array_map(static fn ($d) => [$d['nombre_departamento'], (int) $d['total']], $reporte['departamentos']);

        self::registrarExportacion($formato, $datos['tituloPagina']);

        if ($formato === 'txt') {
            $txt = new TablaTexto();
            $txt->titulo(INSTITUCION_NOMBRE);
            $txt->linea($titulo);
            $txt->linea($generado);
            $txt->subtitulo('Resumen');
            $txt->pares([
                'Total de visitas' => $t['total'],
                'Promedio por ' . mb_strtolower($info['periodo'], 'UTF-8') => number_format($promedio, 1, ',', '.'),
                'Mayor afluencia'  => $pico,
            ]);
            $txt->subtitulo('Visitas por ' . mb_strtolower($info['periodo'], 'UTF-8'));
            $txt->tabla($encabezados, $filas, ['izq', 'der', 'der', 'der', 'der', 'der', 'der'], $pie);
            $txt->subtitulo('Visitas por departamento');
            if ($deptos) {
                $txt->tabla(['Departamento', 'Visitas'], $deptos, ['izq', 'der']);
                $txt->linea('Una visita dirigida a varios departamentos se cuenta en cada uno de ellos.');
            } else {
                $txt->linea('No hay visitas registradas en este periodo.');
            }
            $txt->descargar($datos['tituloPagina'] . '.txt');
        }

        $libro = new LibroExcel();
        $hoja  = $libro->agregarHoja('Visitas por ' . mb_strtolower($info['periodo'], 'UTF-8'), [30, 14, 15, 14, 14, 14, 12]);
        $hoja->combinar($hoja->fila([INSTITUCION_NOMBRE], 'titulo', 22), 0, 6);
        $hoja->combinar($hoja->fila([$titulo], 'negrita'), 0, 6);
        $hoja->combinar($hoja->fila([$generado], 'subtitulo'), 0, 6);
        $hoja->filaVacia();
        $hoja->fila(['Total de visitas', $t['total']], ['negrita', 'normal']);
        $hoja->fila(['Promedio por ' . mb_strtolower($info['periodo'], 'UTF-8'), $promedio], ['negrita', 'normal']);
        $hoja->combinar($hoja->fila(['Mayor afluencia', $pico], ['negrita', 'normal']), 1, 6);
        $hoja->filaVacia();
        $inicio = $hoja->fila($encabezados, 'encabezado');
        foreach ($filas as $fila) {
            $hoja->fila($fila);
        }
        $hoja->autofiltro($inicio, $hoja->siguienteFila() - 1, count($encabezados));
        $hoja->fila($pie, 'total');
        $hoja->fijarHasta($inicio);

        $hojaD = $libro->agregarHoja('Departamentos', [40, 12]);
        $hojaD->combinar($hojaD->fila(['Visitas por departamento'], 'titulo', 22), 0, 1);
        $hojaD->combinar($hojaD->fila([$generado], 'subtitulo'), 0, 1);
        $hojaD->filaVacia();
        $hojaD->fila(['Departamento', 'Visitas'], 'encabezado');
        foreach ($deptos as $fila) {
            $hojaD->fila($fila);
        }
        if (!$deptos) {
            $hojaD->fila(['No hay visitas registradas en este periodo.', null], 'nota');
        }
        $hojaD->filaVacia();
        $hojaD->fila(['Una visita dirigida a varios departamentos se cuenta en cada uno de ellos.'], 'nota');

        $libro->descargar($datos['tituloPagina'] . '.xlsx');
    }
}
