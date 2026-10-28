<?php
// app/servicios/ValidadorVisita.php

require_once __DIR__ . '/../models/Visita.php';
require_once __DIR__ . '/../models/Departamento.php';

/**
 * Lectura y validación de los datos de una visita (registro, edición y reprogramación).
 * Solo valida: no guarda nada ni redirige.
 */
class ValidadorVisita
{
    /**
     * Lee y valida los datos propios de la visita (comunes a registrar y editar).
     * @return array [datos, departamentos, errores por campo]
     */
    public static function leerDatos(array $entrada): array
    {
        $datos = [
            'fecha_visita'  => trim(entradaTexto($entrada, 'fecha_visita')),
            'hora_entrada'  => normalizarHora(entradaTexto($entrada, 'hora_entrada')),
            'motivo_visita' => trim(entradaTexto($entrada, 'motivo_visita')),
            'observaciones' => trim(entradaTexto($entrada, 'observaciones')) ?: null,
        ];
        $departamentos = array_values(array_unique(array_filter(array_map('intval', entradaLista($entrada, 'departamentos')))));
        $errores = [];

        if (!self::fechaValida($datos['fecha_visita'])) {
            $errores['fecha_visita'] = 'Indique una fecha de visita válida.';
        }
        if (!self::horaValida($datos['hora_entrada'])) {
            $errores['hora_entrada'] = 'Indique una hora de entrada válida.';
        }
        if ($datos['motivo_visita'] === '') {
            $errores['motivo_visita'] = 'El motivo de la visita es obligatorio.';
        } elseif (mb_strlen($datos['motivo_visita']) > 255) {
            $errores['motivo_visita'] = 'El motivo no puede superar los 255 caracteres.';
        }
        if ($datos['observaciones'] !== null && mb_strlen($datos['observaciones']) > Visita::MAX_OBSERVACIONES) {
            $errores['observaciones'] = 'Las observaciones no pueden superar los ' . Visita::MAX_OBSERVACIONES . ' caracteres.';
        }
        if (empty($departamentos)) {
            $errores['departamentos'] = 'Seleccione al menos un departamento.';
        } else {
            $existentes = array_map('intval', array_column((new Departamento())->listar(), 'id_departamento'));
            if (array_diff($departamentos, $existentes)) {
                $errores['departamentos'] = 'Uno de los departamentos seleccionados no existe. Recargue la página.';
            }
        }
        return [$datos, $departamentos, $errores];
    }

    public static function horaValida(string $hora): bool
    {
        return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $hora);
    }

    public static function fechaValida(string $fecha): bool
    {
        $f = DateTime::createFromFormat('!Y-m-d', $fecha);
        return $f && $f->format('Y-m-d') === $fecha;
    }

    /** Fecha y hora de una cita al reprogramarla o editarla: a futuro y como máximo dentro de un año. */
    public static function validarFechaCita(string $fecha, string $hora): array
    {
        $errores = [];
        if (!self::fechaValida($fecha)) {
            $errores['fecha_visita'] = 'Indique una fecha válida para la cita.';
        } elseif ($fecha < date('Y-m-d')) {
            $errores['fecha_visita'] = 'La fecha de la cita no puede ser anterior a hoy.';
        } elseif ($fecha > date('Y-m-d', strtotime('+1 year'))) {
            $errores['fecha_visita'] = 'Una visita se puede programar con un año de anticipación como máximo.';
        }
        if (!self::horaValida($hora)) {
            $errores['hora_entrada'] = 'Indique una hora válida para la cita.';
        } elseif (!isset($errores['fecha_visita']) && $fecha . ' ' . substr($hora, 0, 5) < date('Y-m-d H:i')) {
            $errores['hora_entrada'] = 'Esa hora ya pasó. Elija una hora posterior.';
        }
        return $errores;
    }

    /** Al registrar una cita nueva: si ya pasó la hora, debe registrarse «Al momento». */
    public static function validarCitaNueva(array $datos): array
    {
        if ($datos['fecha_visita'] < date('Y-m-d')) {
            return ['fecha_visita' => 'La fecha de una visita programada no puede ser anterior a hoy.'];
        }
        if ($datos['fecha_visita'] > date('Y-m-d', strtotime('+1 year'))) {
            return ['fecha_visita' => 'Una visita se puede programar con un año de anticipación como máximo.'];
        }
        if ($datos['fecha_visita'] . ' ' . substr($datos['hora_entrada'], 0, 5) < date('Y-m-d H:i')) {
            return ['hora_entrada' => 'Esa hora ya pasó. Si el visitante ya llegó, elija «Al momento».'];
        }
        return [];
    }

    /**
     * Al editar una visita que ya ocurrió: la fecha no puede ser futura y la hora de salida
     * es obligatoria si finalizó (salvo cierre automático). Completa $datos['hora_salida'].
     */
    public static function validarVisitaOcurrida(array &$datos, array $visita, string $horaSalida, array $errores): array
    {
        if (!isset($errores['fecha_visita']) && $datos['fecha_visita'] > date('Y-m-d')) {
            $errores['fecha_visita'] = 'La fecha de una visita que ya ocurrió no puede ser futura.';
        }
        $obligatoria = (int) $visita['id_estado'] === Visita::ESTADO_FINALIZADA && !$visita['cierre_automatico'];
        $datos['hora_salida'] = normalizarHora($horaSalida) ?: null;

        if ($datos['hora_salida'] === null) {
            if ($obligatoria) {
                $errores['hora_salida'] = 'Una visita finalizada debe tener hora de salida.';
            }
        } elseif (!self::horaValida($datos['hora_salida'])) {
            $errores['hora_salida'] = 'Indique una hora de salida válida.';
        } elseif (!isset($errores['hora_entrada'])
                  && substr($datos['hora_salida'], 0, 5) < substr($datos['hora_entrada'], 0, 5)) {
            $errores['hora_salida'] = 'La hora de salida no puede ser anterior a la de entrada.';
        }
        return $errores;
    }
}
