<?php
// app/servicios/AvisoCita.php

require_once __DIR__ . '/../models/Visita.php';
require_once __DIR__ . '/../models/Auditoria.php';
require_once __DIR__ . '/../utilidades/AvisoVisita.php';

/**
 * Envía el aviso por correo de una cita (al registrarla, reprogramarla o reenviarlo),
 * lo deja registrado y arma el mensaje para el usuario.
 * Si el correo falla, la visita queda guardada igual y se muestra una advertencia.
 */
class AvisoCita
{
    /**
     * @param string $mensaje mensaje de éxito para el usuario; se le agrega «Se envió el aviso a ...»
     * @return bool true si el correo salió
     */
    public static function enviar(int $id_visita, string &$mensaje, bool $reprogramada = false): bool
    {
        if (!CorreoSmtp::estaConfigurado()) {
            $_SESSION['flash_aviso'] = 'No se envió el aviso por correo: ' . CorreoSmtp::motivoNoConfigurado();
            return false;
        }

        $visitas = new Visita();
        $visita  = $visitas->obtenerDetalle($id_visita);
        $error   = $visita ? AvisoVisita::enviar($visita, $reprogramada) : 'la visita no existe.';

        if ($error === null) {
            $visitas->marcarAvisoEnviado($id_visita);
            (new Auditoria())->registrar(
                'AVISO_CORREO',
                mb_substr("Aviso de la visita #{$id_visita} enviado a {$visita['correo']}", 0, 255),
                'visita',
                (int) $_SESSION['usuario']['id_usuario']
            );
            $mensaje .= " Se envió el aviso a {$visita['correo']}.";
            return true;
        }

        $_SESSION['flash_aviso'] = 'No se pudo enviar el aviso por correo: ' . $error
            . ' Puede reenviarlo desde el listado de visitas.';
        return false;
    }
}
