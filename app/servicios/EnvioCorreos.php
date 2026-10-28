<?php
// app/servicios/EnvioCorreos.php

require_once __DIR__ . '/../models/Visita.php';
require_once __DIR__ . '/../models/Auditoria.php';
require_once __DIR__ . '/../utilidades/AvisoVisita.php';

/**
 * Envíos de correo pedidos desde «Configuración de correo»: el correo de prueba y los
 * avisos de las citas que se registraron mientras el correo no estaba configurado.
 */
class EnvioCorreos
{
    /** Cuántos avisos pendientes se envían como máximo por clic (para no hacer esperar demasiado). */
    public const MAX_AVISOS_POR_CLIC = 30;

    /** Indica si un error es del servidor de correo (conexión, contraseña): los demás envíos también fallarían. */
    public static function esFalloDelServidor(string $error): bool
    {
        return (bool) preg_match('/conectar|contraseña|usuario|TLS|no respondió|cerró la conexión/i', $error);
    }

    /**
     * Envía un aviso de ejemplo a $destino.
     * @return array [null si salió bien o el mensaje de error, conversación con el servidor (sin la contraseña)]
     */
    public static function prueba(string $destino, int $idUsuario): array
    {
        $ejemplo = AvisoVisita::ejemplo($destino);
        [$asunto, $html, $texto] = AvisoVisita::armar($ejemplo);
        $cliente = CorreoSmtp::desdeConfiguracion();
        try {
            $cliente->enviar($destino, $ejemplo['visitante'], '[PRUEBA] ' . $asunto, $html, $texto);
        } catch (Throwable $e) {
            return [$e->getMessage(), array_slice($cliente->registro(), -14)];
        }
        (new Auditoria())->registrar('PRUEBA_CORREO', mb_substr("Correo de prueba enviado a {$destino}", 0, 255), 'correo', $idUsuario);
        return [null, []];
    }

    /**
     * Envía los avisos de las citas próximas que todavía no lo recibieron.
     * @return array [cantidad enviada, errores por visitante]
     */
    public static function avisosPendientes(int $idUsuario): array
    {
        $visitas   = new Visita();
        $auditoria = new Auditoria();
        $enviados  = 0;
        $errores   = [];
        foreach ($visitas->citasSinAviso(date('Y-m-d')) as $v) {
            if (strtotime($v['fecha_visita'] . ' ' . $v['hora_entrada']) <= time()) {
                continue;   // la hora ya pasó
            }
            if ($enviados + count($errores) >= self::MAX_AVISOS_POR_CLIC) {
                break;
            }
            $error = AvisoVisita::enviar($v, (int) $v['id_estado'] === Visita::ESTADO_REPROGRAMADA);
            if ($error === null) {
                $visitas->marcarAvisoEnviado((int) $v['id_visita']);
                $auditoria->registrar('AVISO_CORREO',
                    mb_substr("Aviso pendiente de la visita #{$v['id_visita']} enviado a {$v['correo']}", 0, 255), 'visita', $idUsuario);
                $enviados++;
                continue;
            }
            $errores[] = "{$v['visitante']}: {$error}";
            if (self::esFalloDelServidor($error)) {
                break;
            }
        }
        return [$enviados, $errores];
    }
}
