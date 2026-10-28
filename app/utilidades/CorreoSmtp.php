<?php
// app/utilidades/CorreoSmtp.php

/**
 * Envío de correos por SMTP sin librerías externas.
 *
 * XAMPP no trae un servidor de correo, así que la función mail() de PHP no
 * funciona. Esta clase se conecta directamente a un servidor SMTP (por
 * ejemplo Gmail) usando las extensiones openssl y sockets que ya trae PHP.
 *
 * Uso:
 *   $correo = CorreoSmtp::desdeConfiguracion();
 *   $correo->enviar('ana@correo.com', 'Ana Ruiz', 'Asunto', '<p>HTML</p>', 'Texto plano');
 *
 * Lanza RuntimeException con un mensaje en español si algo falla.
 */
require_once __DIR__ . '/smtp/ConversacionSmtp.php';
require_once __DIR__ . '/smtp/MensajeMime.php';

class CorreoSmtp
{
    use ConversacionSmtp;   // conectar, autenticar y dialogar con el servidor
    use MensajeMime;        // cabeceras y cuerpo del mensaje

    private $conexion = null;
    private array $registro = [];   // conversación con el servidor (sin contraseñas), para diagnóstico

    public function __construct(
        private string $host,
        private int $puerto,
        private string $seguridad,      // 'tls' (STARTTLS), 'ssl' o '' (sin cifrado)
        private string $usuario,
        private string $clave,
        private string $remitente,
        private string $nombreRemitente,
        private string $archivoCa = '',
        private int $espera = 15        // segundos máximos por operación
    ) {}

    /** Crea el cliente con los datos de app/config/correo.php (host, port, encryption, username, password, from). */
    public static function desdeConfiguracion(): self
    {
        return new self(
            CORREO_HOST,
            (int) CORREO_PUERTO,
            CORREO_SEGURIDAD,
            CORREO_USUARIO,
            CORREO_CLAVE,
            CORREO_REMITENTE,
            CORREO_NOMBRE,
            CORREO_CA_ARCHIVO,
            (int) CORREO_ESPERA
        );
    }

    /** Indica si el envío de correos está activado y tiene los datos mínimos. */
    public static function estaConfigurado(): bool
    {
        return self::problemas() === [];
    }

    /**
     * Lo que falta para poder enviar correos (vacío = todo listo).
     * @return string[]
     */
    public static function problemas(): array
    {
        $problemas = [];
        if (!is_file(CORREO_ARCHIVO)) {
            $problemas[] = 'No existe el archivo de configuración app/config/correo.php.';
        }
        if (!CORREO_HABILITADO) {
            $problemas[] = 'El envío de correos está desactivado.';
        }
        if (CORREO_HOST === '') {
            $problemas[] = 'Falta el servidor de correo (host).';
        }
        if (CORREO_USUARIO === '') {
            $problemas[] = 'Falta la cuenta de correo que envía los avisos (username).';
        } elseif (CORREO_CLAVE === '') {
            $problemas[] = 'Falta la contraseña de aplicación de la cuenta (password).';
        }
        if (CORREO_USUARIO !== '' && filter_var(CORREO_REMITENTE, FILTER_VALIDATE_EMAIL) === false) {
            $problemas[] = 'El correo del remitente no es válido.';
        }
        return $problemas;
    }

    /** Mensaje para mostrar cuando no se puede enviar porque falta configurar el correo. */
    public static function motivoNoConfigurado(): string
    {
        $problemas = self::problemas();
        $detalle = $problemas ? ' ' . implode(' ', $problemas) : '';
        return 'el envío de correos no está configurado.' . $detalle
            . ' El administrador lo configura en «Configuración de correo».';
    }

    public function registro(): array
    {
        return $this->registro;
    }

    public function enviar(string $para, string $nombrePara, string $asunto, string $html, string $texto): void
    {
        $para = $this->sinSaltos($para);
        if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('La dirección de correo del destinatario no es válida.');
        }

        try {
            $this->conectar();
            $this->comando('MAIL FROM:<' . $this->sinSaltos($this->remitente) . '>', [250]);
            $this->comando('RCPT TO:<' . $para . '>', [250, 251]);
            $this->comando('DATA', [354]);
            $this->comando($this->armarMensaje($para, $nombrePara, $asunto, $html, $texto) . "\r\n.", [250]);
            $this->comando('QUIT', [221]);
        } finally {
            $this->desconectar();
        }
    }
}
