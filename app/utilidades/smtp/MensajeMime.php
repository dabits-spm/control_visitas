<?php
// app/utilidades/smtp/MensajeMime.php

/**
 * CorreoSmtp: armado del mensaje (cabeceras y cuerpo MIME en texto y HTML).
 */
trait MensajeMime
{
    private function armarMensaje(string $para, string $nombrePara, string $asunto, string $html, string $texto): string
    {
        $separador = 'cv_' . bin2hex(random_bytes(12));
        $dominio   = substr(strrchr($this->remitente, '@') ?: '@localhost', 1);

        $cabeceras = [
            'Date: ' . date('r'),
            'From: ' . $this->direccion($this->remitente, $this->nombreRemitente),
            'To: ' . $this->direccion($para, $nombrePara),
            'Subject: ' . $this->codificar($asunto),
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $dominio . '>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $separador . '"',
        ];

        $cuerpo = "--{$separador}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($texto), 76, "\r\n")
            . "--{$separador}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html), 76, "\r\n")
            . "--{$separador}--";

        $mensaje = implode("\r\n", $cabeceras) . "\r\n\r\n" . $cuerpo;

        // Una línea que empieza con "." debe duplicarlo (regla del protocolo SMTP)
        return preg_replace('/^\./m', '..', $mensaje);
    }

    private function direccion(string $correo, string $nombre): string
    {
        $correo = $this->sinSaltos($correo);
        $nombre = $this->sinSaltos($nombre);
        return $nombre === '' ? "<{$correo}>" : $this->codificar($nombre) . " <{$correo}>";
    }

    /** Codifica un texto con acentos para una cabecera: =?UTF-8?B?...?= */
    private function codificar(string $texto): string
    {
        $texto = $this->sinSaltos($texto);
        return preg_match('/[^\x20-\x7E]/', $texto)
            ? '=?UTF-8?B?' . base64_encode($texto) . '?='
            : $texto;
    }

    /** Evita que un salto de línea en un dato agregue cabeceras falsas. */
    private function sinSaltos(string $texto): string
    {
        return trim(str_replace(["\r", "\n"], ' ', $texto));
    }
}
