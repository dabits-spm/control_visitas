<?php
// app/servicios/reportes/ReportePersonalizado.php

require_once __DIR__ . '/PeriodosReporte.php';
require_once __DIR__ . '/../../models/Visita.php';
require_once __DIR__ . '/../../models/Departamento.php';
require_once __DIR__ . '/../../models/Usuario.php';

/**
 * Reporte personalizado: listado detallado de visitas con los filtros y las columnas
 * que elija el usuario.
 */
class ReportePersonalizado
{
    /** Columnas opcionales (fecha y visitante siempre se muestran). */
    public const COLUMNAS = [
        'horario'        => 'Entrada / Salida',
        'contacto'       => 'Contacto',
        'departamentos'  => 'Departamentos',
        'motivo'         => 'Motivo',
        'observaciones'  => 'Observaciones',
        'tipo'           => 'Tipo de visita',
        'estado'         => 'Estado',
        'registrado_por' => 'Registrada por',
    ];
    public const COLUMNAS_POR_DEFECTO = ['horario', 'departamentos', 'motivo', 'estado'];

    /** Rango de la URL (por defecto, el mes en curso). @return array [desde|null, hasta|null, errores] */
    public static function leerRango(): array
    {
        $hoy     = new DateTimeImmutable('today');
        $errores = [];
        $desde   = PeriodosReporte::leerFecha('desde', $hoy->format('Y-m-01'), 'inicial', $errores);
        $hasta   = PeriodosReporte::leerFecha('hasta', $hoy->format('Y-m-d'), 'final', $errores);

        if ($desde && $hasta) {
            if ($desde > $hasta) {
                $errores[] = 'La fecha inicial no puede ser posterior a la fecha final.';
            } elseif ($desde->diff($hasta)->y >= PeriodosReporte::MAX_ANIOS_REPORTE) {
                $errores[] = 'El rango de fechas no puede superar ' . PeriodosReporte::MAX_ANIOS_REPORTE . ' años.';
            }
        }
        return [$desde, $hasta, $errores];
    }

    /** Opciones de las listas de filtros: [estados, departamentos, usuarios] como [id => nombre]. */
    public static function opciones(): array
    {
        $estados       = array_column((new Visita())->listarEstados(), 'nombre_estado', 'id_estado');
        $departamentos = array_column((new Departamento())->listar(), 'nombre_departamento', 'id_departamento');
        $usuarios      = [];
        foreach ((new Usuario())->listar() as $u) {
            $usuarios[$u['id_usuario']] = $u['nombre'] . ' ' . $u['apellido'];
        }
        return [$estados, $departamentos, $usuarios];
    }

    /** Filtros de la URL; un valor que no existe en su lista se ignora. */
    public static function leerFiltros(array $estados, array $departamentos, array $usuarios): array
    {
        $leerOpcion = static function (string $campo, array $opciones): int {
            $valor = (int) ($_GET[$campo] ?? 0);
            return isset($opciones[$valor]) ? $valor : 0;
        };
        $tipoVisita = entradaTexto($_GET, 'tipo');
        return [
            'id_estado'       => $leerOpcion('estado', $estados),
            'tipo_visita'     => isset(Visita::TIPOS[$tipoVisita]) ? $tipoVisita : '',
            'id_departamento' => $leerOpcion('departamento', $departamentos),
            'id_usuario'      => $leerOpcion('usuario', $usuarios),
            'visitante'       => mb_substr(trim(entradaTexto($_GET, 'visitante')), 0, 100),
        ];
    }

    /** En la primera carga se usan las columnas por defecto; después, las que marque. */
    public static function leerColumnas(): array
    {
        if (!isset($_GET['generar'])) {
            return self::COLUMNAS_POR_DEFECTO;
        }
        $marcadas = is_string($_GET['columnas'] ?? null) ? [$_GET['columnas']] : entradaLista($_GET, 'columnas');
        return array_values(array_intersect(array_keys(self::COLUMNAS), $marcadas));
    }

    /**
     * Busca las visitas y arma los datos del reporte (totales, filtros aplicados y consulta para exportar).
     * $datos debe traer desde, hasta, filtros, estados, departamentos, usuarios y columnas.
     */
    public static function generar(array $datos): array
    {
        $filtros = $datos['filtros'];
        $visitas = (new Visita())->buscar(['desde' => $datos['desde'], 'hasta' => $datos['hasta']] + $filtros);

        $porEstado = array_fill_keys(array_keys($datos['estados']), 0);
        foreach ($visitas as $v) {
            $porEstado[(int) $v['id_estado']] = ($porEstado[(int) $v['id_estado']] ?? 0) + 1;
        }

        // Descripción de los filtros aplicados, para el encabezado del PDF y de los archivos
        $aplicados = [];
        if ($filtros['id_estado'])       $aplicados[] = 'Estado: ' . $datos['estados'][$filtros['id_estado']];
        if ($filtros['tipo_visita'])     $aplicados[] = 'Tipo: ' . Visita::TIPOS[$filtros['tipo_visita']];
        if ($filtros['id_departamento']) $aplicados[] = 'Departamento: ' . $datos['departamentos'][$filtros['id_departamento']];
        if ($filtros['id_usuario'])      $aplicados[] = 'Registradas por: ' . $datos['usuarios'][$filtros['id_usuario']];
        if ($filtros['visitante'] !== '') $aplicados[] = 'Visitante: "' . $filtros['visitante'] . '"';

        return [
            'visitas'          => $visitas,
            'porEstado'        => $porEstado,
            'totalPersonas'    => count(array_unique(array_column($visitas, 'cedula'))),
            'filtrosAplicados' => $aplicados,
            'consultaExportar' => array_filter([
                'generar'      => 1,
                'desde'        => $datos['desde'],
                'hasta'        => $datos['hasta'],
                'visitante'    => $filtros['visitante'],
                'estado'       => $filtros['id_estado'],
                'tipo'         => $filtros['tipo_visita'],
                'departamento' => $filtros['id_departamento'],
                'usuario'      => $filtros['id_usuario'],
                'columnas'     => $datos['columnas'],
            ], static fn ($v) => $v !== '' && $v !== 0 && $v !== []),
        ];
    }
}
