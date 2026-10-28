<?php
// app/utilidades/AvisoVisita.php

require_once __DIR__ . '/CorreoSmtp.php';

/**
 * Correo que avisa al visitante que tiene una visita programada (o que su visita
 * fue reprogramada para otra fecha u hora).
 * Recibe el detalle de la visita tal como lo devuelve Visita::obtenerDetalle().
 */
class AvisoVisita
{
    // Tipos de correo
    public const PROGRAMADA   = 'programada';     // al registrar la cita
    public const REPROGRAMADA = 'reprogramada';   // al cambiarle la fecha u hora
    public const RECORDATORIO = 'recordatorio';   // 24 horas antes de la cita

    /**
     * Envía el aviso. Devuelve null si salió bien, o el motivo del error.
     */
    public static function enviar(array $visita, bool|string $tipo = self::PROGRAMADA): ?string
    {
        if (!CorreoSmtp::estaConfigurado()) {
            return CorreoSmtp::motivoNoConfigurado();
        }
        if (empty($visita['correo'])) {
            return 'el visitante no tiene correo registrado.';
        }

        [$asunto, $html, $texto] = self::armar($visita, $tipo);

        try {
            CorreoSmtp::desdeConfiguracion()->enviar(
                $visita['correo'],
                $visita['visitante'],
                $asunto,
                $html,
                $texto
            );
            return null;
        } catch (Throwable $excepcion) {
            error_log('Error al enviar aviso de visita #' . $visita['id_visita'] . ': ' . $excepcion->getMessage());
            return $excepcion->getMessage();
        }
    }

    /** @return array{0:string,1:string,2:string} asunto, HTML y texto plano */
    /** Visita de ejemplo (mañana a las 10:00) para los correos de prueba. */
    public static function ejemplo(string $destino): array
    {
        return [
            'id_visita'          => 0,
            'visitante'          => 'Visitante de Prueba',
            'visitante_nombre'   => 'Visitante',
            'visitante_apellido' => 'de Prueba',
            'correo'             => $destino,
            'fecha_visita'       => date('Y-m-d', strtotime('+1 day')),
            'hora_entrada'       => '10:00:00',
            'departamentos'      => 'División de Informática',
            'motivo_visita'      => 'Prueba de configuración del correo',
        ];
    }

    /**
     * @param bool|string $tipo PROGRAMADA, REPROGRAMADA o RECORDATORIO (true = REPROGRAMADA, por compatibilidad)
     */
    public static function armar(array $visita, bool|string $tipo = self::PROGRAMADA): array
    {
        if (is_bool($tipo)) {
            $tipo = $tipo ? self::REPROGRAMADA : self::PROGRAMADA;
        }
        $fecha  = fechaEnTexto($visita['fecha_visita']);
        $hora   = horaDoce($visita['hora_entrada']);
        $nombre = trim(($visita['visitante_nombre'] ?? '') . ' ' . ($visita['visitante_apellido'] ?? '')) ?: $visita['visitante'];
        $deptos = $visita['departamentos'] ?: '—';
        $motivo = $visita['motivo_visita'];
        $institucion = INSTITUCION_NOMBRE;
        $ubicacion   = defined('INSTITUCION_UBICACION') && INSTITUCION_UBICACION !== '' ? INSTITUCION_UBICACION : INSTITUCION_DIRECCION;

        // "hoy", "mañana" o la fecha completa, para el recordatorio
        $cuando = match ($visita['fecha_visita']) {
            date('Y-m-d')                       => 'hoy',
            date('Y-m-d', strtotime('+1 day'))  => 'mañana',
            default                             => 'el ' . mb_strtolower(mb_substr($fecha, 0, 1)) . mb_substr($fecha, 1),
        };

        [$asunto, $frase, $fraseHtml] = match ($tipo) {
            self::REPROGRAMADA => [
                "Visita reprogramada: {$fecha}, {$hora}",
                "Le informamos que su visita a {$institucion} fue reprogramada. Estos son los nuevos datos:",
                'Le informamos que su visita fue <strong>reprogramada</strong>. Estos son los nuevos datos:',
            ],
            self::RECORDATORIO => [
                "Recordatorio: su visita es {$cuando} a las {$hora}",
                "Le recordamos que tiene una visita programada en {$institucion} {$cuando} a las {$hora}.",
                'Le recordamos que tiene una <strong>visita programada ' . e($cuando) . ' a las ' . e($hora) . '</strong>.',
            ],
            default => [
                "Visita programada: {$fecha}, {$hora}",
                "Le informamos que tiene una visita programada en {$institucion}.",
                'Le informamos que tiene una <strong>visita programada</strong>.',
            ],
        };
        $direccion = $ubicacion;   // se muestra como "Ubicación"

        // --- Texto plano (lo ven los programas que no muestran HTML) ---
        $texto = "Estimado(a) {$nombre}:\n\n"
            . $frase . "\n\n"
            . "Fecha: {$fecha}\n"
            . "Hora: {$hora}\n"
            . "Departamento(s): {$deptos}\n"
            . "Motivo: {$motivo}\n"
            . ($direccion !== '' ? "Ubicación: {$direccion}\n" : '')
            . "\nAl llegar, presente su cédula de identidad en la recepción.\n"
            . "Si no puede asistir, comuníquese con la institución.\n\n"
            . "Este es un mensaje automático, por favor no lo responda.\n";

        // --- HTML (estilos en línea: los programas de correo ignoran las hojas de estilo) ---
        $fila = static fn (string $etiqueta, string $valor): string =>
            '<tr><td style="padding:8px 12px;color:#5f6570;font-size:14px;white-space:nowrap;vertical-align:top">' . e($etiqueta) . '</td>'
            . '<td style="padding:8px 12px;color:#1d1f24;font-size:14px;font-weight:600">' . e($valor) . '</td></tr>';

        $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>' . e($asunto) . '</title></head>'
            . '<body style="margin:0;padding:24px;background:#f4f5f8;font-family:Segoe UI,Arial,sans-serif">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e3e5ea">'
            . '<tr><td style="background:#5c0010;color:#ffffff;padding:18px 24px;font-size:18px;font-weight:600">' . e($institucion) . '</td></tr>'
            . '<tr><td style="padding:24px">'
            . '<p style="margin:0 0 12px;font-size:15px;color:#1d1f24">Estimado(a) <strong>' . e($nombre) . '</strong>:</p>'
            . '<p style="margin:0 0 18px;font-size:15px;color:#1d1f24">' . $fraseHtml . '</p>'
            . '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;background:#fde8ea;border-radius:6px;border-left:4px solid #a8101f">'
            . $fila('Fecha', $fecha)
            . $fila('Hora', $hora)
            . $fila('Departamento(s)', $deptos)
            . $fila('Motivo', $motivo)
            . ($direccion !== '' ? $fila('Ubicación', $direccion) : '')
            . '</table>'
            . '<p style="margin:18px 0 6px;font-size:14px;color:#1d1f24">Al llegar, presente su <strong>cédula de identidad</strong> en la recepción.</p>'
            . '<p style="margin:0;font-size:14px;color:#1d1f24">Si no puede asistir, comuníquese con la institución.</p>'
            . '</td></tr>'
            . '<tr><td style="padding:14px 24px;background:#f4f5f8;color:#8a909b;font-size:12px">Este es un mensaje automático del sistema de Control de Visitas, por favor no lo responda.</td></tr>'
            . '</table></body></html>';

        return [$asunto, $html, $texto];
    }
}
