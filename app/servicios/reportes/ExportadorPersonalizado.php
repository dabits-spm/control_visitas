<?php
// app/servicios/reportes/ExportadorPersonalizado.php

require_once __DIR__ . '/Exportador.php';

/** Descarga del reporte personalizado (listado detallado con las columnas elegidas). */
class ExportadorPersonalizado extends Exportador
{
    /**
     * Columnas del reporte personalizado para exportar: [título, función que da el valor].
     * En el archivo, "Entrada / Salida" y "Contacto" se separan en dos columnas cada una.
     */
    private static function columnasExportacion(array $columnas): array
    {
        $lista = [
            ['N°',        static fn ($v, $i) => $i + 1, 6],
            ['Fecha',     static fn ($v) => fechaLarga($v['fecha_visita']), 12],
            ['Visitante', static fn ($v) => $v['visitante'], 26],
            ['Cédula',    static fn ($v) => $v['cedula'], 14],
        ];
        $opcionales = [
            'horario'        => [['Entrada', static fn ($v) => horaCorta($v['hora_entrada']), 9],
                                 ['Salida',  static fn ($v) => horaCorta($v['hora_salida']), 9]],
            'contacto'       => [['Teléfono', static fn ($v) => $v['telefono'], 15],
                                 ['Correo',   static fn ($v) => $v['correo'] ?: '—', 28]],
            'departamentos'  => [['Departamentos', static fn ($v) => $v['departamentos'] ?? '—', 30]],
            'motivo'         => [['Motivo', static fn ($v) => $v['motivo_visita'], 34]],
            'observaciones'  => [['Observaciones', static fn ($v) => Visita::textoObservaciones($v['observaciones']), 30]],
            'tipo'           => [['Tipo de visita', static fn ($v) => Visita::TIPOS[$v['tipo_visita']] ?? '—', 16]],
            'estado'         => [['Estado', static fn ($v) => $v['estado'] . (!empty($v['cierre_automatico']) ? ' (automático)' : ''), 14]],
            'registrado_por' => [['Registrada por', static fn ($v) => $v['registrado_por'], 22]],
        ];
        foreach ($columnas as $columna) {
            foreach ($opcionales[$columna] ?? [] as $definicion) {
                $lista[] = $definicion;
            }
        }
        return $lista;
    }

    /** Reporte personalizado (listado detallado con filtros). */
    public static function exportar(string $formato, array $datos): void
    {
        $definiciones = self::columnasExportacion($datos['columnas']);
        $encabezados  = array_column($definiciones, 0);
        $filas = [];
        foreach ($datos['visitas'] as $i => $v) {
            $filas[] = array_map(static fn ($d) => $d[1]($v, $i), $definiciones);
        }

        $titulo   = 'Reporte personalizado de visitas';
        $generado = self::lineaGenerado($datos['desde'], $datos['hasta']);
        $filtros  = 'Filtros: ' . ($datos['filtrosAplicados'] ? implode(' · ', $datos['filtrosAplicados']) : 'ninguno (todas las visitas del periodo)');
        $resumen  = ['Visitas: ' . count($datos['visitas']), 'Visitantes distintos: ' . $datos['totalPersonas']];
        foreach ($datos['estados'] as $id => $nombre) {
            $resumen[] = $nombre . ': ' . ($datos['porEstado'][$id] ?? 0);
        }

        self::registrarExportacion($formato, $datos['tituloPagina']);

        if ($formato === 'txt') {
            $txt = new TablaTexto();
            $txt->titulo(INSTITUCION_NOMBRE);
            $txt->linea($titulo);
            $txt->linea($generado);
            $txt->linea($filtros);
            $txt->linea(implode(' · ', $resumen));
            $txt->linea();
            if ($filas) {
                $txt->tabla($encabezados, $filas, ['der']);
            } else {
                $txt->linea('No hay visitas que cumplan con los filtros seleccionados.');
            }
            $txt->descargar($datos['tituloPagina'] . '.txt');
        }

        $ultima = count($encabezados) - 1;
        $libro  = new LibroExcel();
        $hoja   = $libro->agregarHoja('Visitas', array_column($definiciones, 2));
        $hoja->combinar($hoja->fila([INSTITUCION_NOMBRE], 'titulo', 22), 0, $ultima);
        $hoja->combinar($hoja->fila([$titulo], 'negrita'), 0, $ultima);
        $hoja->combinar($hoja->fila([$generado], 'subtitulo'), 0, $ultima);
        $hoja->combinar($hoja->fila([$filtros], 'subtitulo'), 0, $ultima);
        $hoja->combinar($hoja->fila([implode(' · ', $resumen)], 'subtitulo'), 0, $ultima);
        $hoja->filaVacia();
        $inicio = $hoja->fila($encabezados, 'encabezado');
        foreach ($filas as $fila) {
            $hoja->fila($fila);
        }
        if ($filas) {
            $hoja->autofiltro($inicio, $hoja->siguienteFila() - 1, count($encabezados));
        } else {
            $hoja->fila(['No hay visitas que cumplan con los filtros seleccionados.'], 'nota');
        }
        $hoja->fijarHasta($inicio);

        $libro->descargar($datos['tituloPagina'] . '.xlsx');
    }
}
