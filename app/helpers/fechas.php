<?php
// app/helpers/fechas.php
// Formato de fechas y horas en español (incluye la hora en 12 horas con AM/PM).

/** Formatea una hora "HH:MM:SS" como "HH:MM". */
function horaCorta(?string $hora): string
{
    return $hora ? substr($hora, 0, 5) : '—';
}

/** Formatea una fecha "AAAA-MM-DD" como "DD/MM/AAAA". */
function fechaLarga(?string $fecha): string
{
    if (!$fecha) {
        return '—';
    }
    $f = DateTime::createFromFormat('Y-m-d', $fecha);
    return $f ? $f->format('d/m/Y') : $fecha;
}

/** Nombre del mes en español, en minúsculas: 10 -> "octubre". */
function nombreMes(int $numero): string
{
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
              'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    return $meses[$numero - 1] ?? '';
}

/**
 * Fecha escrita en español: "Viernes, 23 de octubre de 2026".
 * Recibe "AAAA-MM-DD" o un objeto de fecha; sin argumento usa la fecha de hoy.
 */
function fechaEnTexto(DateTimeInterface|string|null $fecha = null): string
{
    $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    if (is_string($fecha)) {
        $fecha = DateTime::createFromFormat('!Y-m-d', substr($fecha, 0, 10)) ?: new DateTime();
    }
    $fecha ??= new DateTime();

    $texto = sprintf(
        '%s, %d de %s de %s',
        $dias[(int) $fecha->format('w')],
        (int) $fecha->format('j'),
        nombreMes((int) $fecha->format('n')),
        $fecha->format('Y')
    );
    // Solo la primera letra en mayúscula
    return mb_strtoupper(mb_substr($texto, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($texto, 1, null, 'UTF-8');
}

/** Fecha de hoy escrita en español: "Viernes, 23 de octubre de 2026". */
function fechaHoyEnTexto(): string
{
    return fechaEnTexto();
}

/** Hora "HH:MM[:SS]" en formato de 12 horas: "14:30" -> "2:30 p. m." */
function horaDoce(?string $hora): string
{
    if (!$hora || !preg_match('/^(\d{1,2}):(\d{2})/', $hora, $p)) {
        return '—';
    }
    $h = (int) $p[1];
    $sufijo = $h < 12 ? 'a. m.' : 'p. m.';
    $h = $h % 12 ?: 12;
    return $h . ':' . $p[2] . ' ' . $sufijo;
}

/**
 * Convierte la hora escrita en el formulario a formato de 24 horas "HH:MM".
 * Acepta "14:30", "14:30:00", "2:30 PM", "02:30 pm", "2:30 p. m.", "2 PM"...
 * Si no se puede interpretar, devuelve el texto tal cual (la validación lo rechazará).
 */
function normalizarHora(string $hora): string
{
    $texto = trim($hora);
    $plano = strtolower(str_replace([' ', '.'], '', $texto));
    if (preg_match('/^(\d{1,2})(?::(\d{2}))?(?::\d{2})?(am|pm|a|p)?$/', $plano, $p)) {
        $h = (int) $p[1];
        $m = isset($p[2]) && $p[2] !== '' ? (int) $p[2] : 0;
        $periodo = $p[3] ?? '';
        if ($periodo !== '') {
            if ($h < 1 || $h > 12) {
                return $texto;
            }
            $h = ($h % 12) + (str_starts_with($periodo, 'p') ? 12 : 0);
        } elseif (!isset($p[2]) || $p[2] === '') {
            return $texto;   // "14" sin minutos ni AM/PM: ambiguo
        }
        if ($h > 23 || $m > 59) {
            return $texto;
        }
        return sprintf('%02d:%02d', $h, $m);
    }
    return $texto;
}
