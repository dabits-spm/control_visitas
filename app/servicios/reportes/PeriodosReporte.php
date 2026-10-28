<?php
// app/servicios/reportes/PeriodosReporte.php

require_once __DIR__ . '/../../models/Visita.php';

/**
 * Reportes por día, semana o mes: tipos, rangos de fechas, validación del rango y
 * armado de la serie completa de periodos con sus totales.
 */
class PeriodosReporte
{
    /** Tipos de reporte: nombre del periodo, duración (DateInterval) y título. */
    public const TIPOS = [
        'diario'  => ['periodo' => 'Día',    'plural' => 'días',    'paso' => 'P1D', 'titulo' => 'Reporte de visitas diarias'],
        'semanal' => ['periodo' => 'Semana', 'plural' => 'semanas', 'paso' => 'P1W', 'titulo' => 'Reporte de visitas semanales'],
        'mensual' => ['periodo' => 'Mes',    'plural' => 'meses',   'paso' => 'P1M', 'titulo' => 'Reporte de visitas mensuales'],
    ];

    /** Límites para que el reporte no crezca sin control. */
    private const MAX_DIAS_REPORTE_DIARIO = 366;
    public const MAX_ANIOS_REPORTE        = 5;

    /** Lee el rango de la URL y lo valida. @return array [desde|null, hasta|null, errores] */
    public static function leerRango(string $tipo): array
    {
        $rangos  = self::rangosPorDefecto();
        $errores = [];
        $desde   = self::leerFecha('desde', $rangos[$tipo]['desde'], 'inicial', $errores);
        $hasta   = self::leerFecha('hasta', $rangos[$tipo]['hasta'], 'final', $errores);

        if ($desde && $hasta) {
            if ($desde > $hasta) {
                $errores[] = 'La fecha inicial no puede ser posterior a la fecha final.';
            } elseif ($tipo === 'diario' && $desde->diff($hasta)->days >= self::MAX_DIAS_REPORTE_DIARIO) {
                $errores[] = 'El reporte diario admite como máximo ' . self::MAX_DIAS_REPORTE_DIARIO
                           . ' días. Para rangos más largos use el reporte semanal o mensual.';
            } elseif ($desde->diff($hasta)->y >= self::MAX_ANIOS_REPORTE) {
                $errores[] = 'El rango de fechas no puede superar ' . self::MAX_ANIOS_REPORTE . ' años.';
            }
        }
        return [$desde, $hasta, $errores];
    }

    /** Periodos, totales, promedio, pico y visitas por departamento del rango. */
    public static function resumen(string $tipo, DateTimeImmutable $desde, DateTimeImmutable $hasta): array
    {
        $modeloVisita = new Visita();
        $conteos  = $modeloVisita->resumenPorPeriodo($tipo, $desde->format('Y-m-d'), $hasta->format('Y-m-d'));
        $periodos = self::armarPeriodos($tipo, $desde, $hasta, $conteos);

        $totales = ['total' => 0, 'programadas' => 0, 'reprogramadas' => 0, 'en_proceso' => 0, 'finalizadas' => 0, 'canceladas' => 0];
        foreach ($periodos as $periodo) {
            foreach ($totales as $clave => $_) {
                $totales[$clave] += $periodo[$clave];
            }
        }

        $maximo = max(array_column($periodos, 'total') ?: [0]);
        $pico   = null;
        foreach ($periodos as $periodo) {
            if ($maximo > 0 && $periodo['total'] === $maximo) {
                $pico = $periodo;
                break;
            }
        }

        return [
            'periodos'      => $periodos,
            'totales'       => $totales,
            'maximo'        => $maximo,
            'promedio'      => count($periodos) ? $totales['total'] / count($periodos) : 0,
            'pico'          => $pico,
            'departamentos' => $modeloVisita->totalesPorDepartamento($desde->format('Y-m-d'), $hasta->format('Y-m-d')),
        ];
    }

    /** Rangos sugeridos: el mes en curso (diario), las últimas 8 semanas y el año en curso. */
    public static function rangosPorDefecto(): array
    {
        $hoy   = new DateTimeImmutable('today');
        $lunes = self::lunesDeLaSemana($hoy);

        return [
            'diario'  => ['desde' => $hoy->format('Y-m-01'), 'hasta' => $hoy->format('Y-m-d')],
            'semanal' => ['desde' => $lunes->modify('-7 weeks')->format('Y-m-d'), 'hasta' => $hoy->format('Y-m-d')],
            'mensual' => ['desde' => $hoy->format('Y-01-01'), 'hasta' => $hoy->format('Y-m-d')],
        ];
    }

    /**
     * Lee una fecha AAAA-MM-DD de la URL. Si viene vacía usa el valor por defecto;
     * si no es válida agrega un mensaje de error y devuelve null.
     */
    public static function leerFecha(string $campo, string $porDefecto, string $nombre, array &$errores): ?DateTimeImmutable
    {
        $valor = $_GET[$campo] ?? '';
        if (!is_string($valor) || trim($valor) === '') {
            $valor = $porDefecto;
        }
        $valor = trim($valor);

        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        if (!$fecha || $fecha->format('Y-m-d') !== $valor) {
            $errores[] = "La fecha {$nombre} no es válida.";
            return null;
        }
        return $fecha;
    }

    public static function lunesDeLaSemana(DateTimeImmutable $fecha): DateTimeImmutable
    {
        return $fecha->modify('-' . ((int) $fecha->format('N') - 1) . ' days');
    }

    /**
     * Genera todos los periodos del rango, incluidos los que no tienen visitas (en cero),
     * para que el reporte muestre la serie completa sin huecos.
     */
    public static function armarPeriodos(string $tipo, DateTimeImmutable $desde, DateTimeImmutable $hasta, array $conteos): array
    {
        $inicio = match ($tipo) {
            'semanal' => self::lunesDeLaSemana($desde),
            'mensual' => $desde->modify('first day of this month'),
            default   => $desde,
        };
        $paso  = new DateInterval(self::TIPOS[$tipo]['paso']);
        $vacio = ['total' => 0, 'programadas' => 0, 'reprogramadas' => 0, 'en_proceso' => 0, 'finalizadas' => 0, 'canceladas' => 0];

        $periodos = [];
        for ($fecha = $inicio; $fecha <= $hasta; $fecha = $fecha->add($paso)) {
            // El primer y el último periodo se recortan al rango pedido
            $fin = $fecha->add($paso)->modify('-1 day');
            $periodos[] = [
                'etiqueta' => self::etiquetaPeriodo($tipo, max($fecha, $desde), min($fin, $hasta)),
            ] + ($conteos[$fecha->format('Y-m-d')] ?? $vacio);
        }
        return $periodos;
    }

    public static function etiquetaPeriodo(string $tipo, DateTimeImmutable $inicio, DateTimeImmutable $fin): string
    {
        $dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

        return match ($tipo) {
            'semanal' => 'Del ' . $inicio->format('d/m/Y') . ' al ' . $fin->format('d/m/Y'),
            'mensual' => ucfirst(nombreMes((int) $inicio->format('n'))) . ' ' . $inicio->format('Y'),
            default   => $dias[(int) $inicio->format('w')] . ' ' . $inicio->format('d/m/Y'),
        };
    }
}
