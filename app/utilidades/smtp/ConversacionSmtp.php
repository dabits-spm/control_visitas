<?php
// app/utilidades/smtp/ConversacionSmtp.php

/**
 * CorreoSmtp: conexión con el servidor (TLS/SSL), autenticación y diálogo SMTP
 * (cada comando y su respuesta queda en el registro, sin la contraseña).
 */
trait ConversacionSmtp
{
    private function conectar(): void
    {
        $opcionesSsl = [
            'verify_peer'      => true,
            'verify_peer_name' => true,
            'peer_name'        => $this->host,
            'SNI_enabled'      => true,
        ];
        if ($this->archivoCa !== '') {
            $opcionesSsl['cafile'] = $this->archivoCa;
        }

        $protocolo = $this->seguridad === 'ssl' ? 'ssl://' : 'tcp://';
        $this->conexion = @stream_socket_client(
            $protocolo . $this->host . ':' . $this->puerto,
            $codigoError,
            $mensajeError,
            $this->espera,
            STREAM_CLIENT_CONNECT,
            stream_context_create(['ssl' => $opcionesSsl])
        );

        if (!$this->conexion) {
            throw new RuntimeException(
                "No se pudo conectar con el servidor de correo {$this->host}:{$this->puerto}. "
                . 'Revise la conexión a internet y los datos de app/config/correo.php. '
                . trim((string) $mensajeError)
            );
        }
        stream_set_timeout($this->conexion, $this->espera);

        $this->leerRespuesta([220]);
        $this->saludar();

        if ($this->seguridad === 'tls') {
            $this->comando('STARTTLS', [220]);
            $metodo = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                $metodo |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }
            if (!@stream_socket_enable_crypto($this->conexion, true, $metodo)) {
                $detalle = error_get_last()['message'] ?? '';
                throw new RuntimeException(
                    'No se pudo establecer la conexión segura (TLS) con el servidor de correo. '
                    . (str_contains($detalle, 'certificate') ? 'Escriba la ruta de los certificados en "ca_file" (app/config/correo.php). ' : '')
                    . $detalle
                );
            }
            $this->saludar();   // después de STARTTLS hay que saludar de nuevo
        }

        if ($this->usuario !== '') {
            $this->comando('AUTH LOGIN', [334]);
            $this->comando(base64_encode($this->usuario), [334], '(usuario)');
            try {
                $this->comando(base64_encode($this->clave), [235], '(contraseña)');
            } catch (RuntimeException $e) {
                throw new RuntimeException(
                    'El servidor de correo rechazó el usuario o la contraseña. Con Gmail debe usar una '
                    . '"contraseña de aplicación", no la contraseña normal de la cuenta.'
                );
            }
        }
    }

    private function saludar(): void
    {
        $equipo = preg_replace('/[^A-Za-z0-9.\-]/', '', (string) gethostname()) ?: 'localhost';
        $this->comando('EHLO ' . $equipo, [250]);
    }

    /** Envía una línea y comprueba que el servidor responda con uno de los códigos esperados. */
    private function comando(string $linea, array $codigosEsperados, ?string $comoRegistrar = null): string
    {
        $this->registro[] = 'C: ' . ($comoRegistrar ?? (strlen($linea) > 200 ? '(mensaje)' : $linea));
        if (@fwrite($this->conexion, $linea . "\r\n") === false) {
            throw new RuntimeException('Se perdió la conexión con el servidor de correo.');
        }
        return $this->leerRespuesta($codigosEsperados);
    }

    /** Lee una respuesta (puede ocupar varias líneas: "250-..." hasta "250 ..."). */
    private function leerRespuesta(array $codigosEsperados): string
    {
        $respuesta = '';
        while (($linea = fgets($this->conexion, 1024)) !== false) {
            $respuesta .= $linea;
            if (strlen($linea) < 4 || $linea[3] === ' ') {
                break;
            }
        }
        $this->registro[] = 'S: ' . trim($respuesta);

        if ($respuesta === '') {
            $info = stream_get_meta_data($this->conexion);
            throw new RuntimeException(!empty($info['timed_out'])
                ? 'El servidor de correo no respondió a tiempo.'
                : 'El servidor de correo cerró la conexión.');
        }

        $codigo = (int) substr($respuesta, 0, 3);
        if (!in_array($codigo, $codigosEsperados, true)) {
            throw new RuntimeException('El servidor de correo respondió: ' . trim($respuesta));
        }
        return $respuesta;
    }

    private function desconectar(): void
    {
        if (is_resource($this->conexion)) {
            fclose($this->conexion);
        }
        $this->conexion = null;
    }
}
