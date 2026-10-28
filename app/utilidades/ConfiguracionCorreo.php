<?php
// app/utilidades/ConfiguracionCorreo.php

/**
 * Lee y guarda app/config/correo.php desde la pantalla «Configuración de correo»
 * (solo administrador), para no tener que editar el archivo a mano.
 */
class ConfiguracionCorreo
{
    public const CIFRADOS = ['tls' => 'TLS (STARTTLS) · puerto 587', 'ssl' => 'SSL · puerto 465', '' => 'Sin cifrado'];

    /** Valores actuales (la contraseña nunca se devuelve, solo si hay una guardada). */
    public static function actual(): array
    {
        return [
            'habilitado' => (bool) CORREO_HABILITADO,
            'host'       => CORREO_HOST,
            'port'       => (int) CORREO_PUERTO,
            'encryption' => CORREO_SEGURIDAD,
            'username'   => CORREO_USUARIO,
            'tiene_clave' => CORREO_CLAVE !== '',
            'largo_clave' => strlen(CORREO_CLAVE),
            'from_address' => CORREO_REMITENTE === CORREO_USUARIO ? '' : CORREO_REMITENTE,
            'from_name'  => CORREO_NOMBRE,
            'ca_file'    => CORREO_CA_ARCHIVO,
            'timeout'    => (int) CORREO_ESPERA,
        ];
    }

    /** Normaliza los datos del formulario. */
    public static function normalizar(array $entrada): array
    {
        $texto = static fn (string $campo, int $max = 190): string =>
            mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]/u', '', entradaTexto($entrada, $campo))), 0, $max);
        $cifrado = entradaTexto($entrada, 'encryption');
        return [
            'habilitado'   => entradaTexto($entrada, 'habilitado') === '1',
            'host'         => strtolower($texto('host', 120)),
            'port'         => (int) entradaTexto($entrada, 'port'),
            'encryption'   => array_key_exists($cifrado, self::CIFRADOS) ? $cifrado : 'tls',
            'username'     => $texto('username', 120),
            'password'     => str_replace(' ', '', $texto('password', 120)),   // Gmail la muestra con espacios
            'from_address' => strtolower($texto('from_address', 120)),
            'from_name'    => $texto('from_name', 80),
        ];
    }

    /** @return array<string,string> errores por campo */
    public static function validar(array $d): array
    {
        $errores = [];
        if ($d['host'] === '' || !preg_match('/^[a-z0-9.-]+$/', $d['host'])) {
            $errores['host'] = 'Escriba el servidor de correo (con Gmail: smtp.gmail.com).';
        }
        if ($d['port'] < 1 || $d['port'] > 65535) {
            $errores['port'] = 'Escriba un puerto válido (587 con TLS, 465 con SSL).';
        }
        if ($d['username'] === '') {
            $errores['username'] = 'Escriba la cuenta de correo que enviará los avisos.';
        } elseif (str_contains($d['username'], '@') && !filter_var($d['username'], FILTER_VALIDATE_EMAIL)) {
            $errores['username'] = 'La cuenta de correo no es válida.';
        }
        if ($d['from_address'] !== '' && !filter_var($d['from_address'], FILTER_VALIDATE_EMAIL)) {
            $errores['from_address'] = 'El correo del remitente no es válido (o déjelo vacío).';
        }
        if ($d['from_address'] === '' && !filter_var($d['username'], FILTER_VALIDATE_EMAIL) && !isset($errores['username'])) {
            $errores['from_address'] = 'La cuenta no es un correo: escriba el correo del remitente.';
        }
        if ($d['from_name'] === '') {
            $errores['from_name'] = 'Escriba el nombre que verá el visitante.';
        }
        return $errores;
    }

    /**
     * Escribe app/config/correo.php. Si la contraseña viene vacía se conserva la guardada.
     * @throws RuntimeException si no se puede escribir el archivo
     */
    public static function guardar(array $d): void
    {
        $clave = $d['password'] !== '' ? $d['password'] : CORREO_CLAVE;
        $v = static fn ($valor): string => var_export($valor, true);

        $contenido = <<<PHP
<?php
// app/config/correo.php
// =====================================================================
// CONFIGURACIÓN CENTRALIZADA DEL CORREO (SMTP)
// Se puede cambiar desde el sistema: administrador -> «Configuración de correo».
// Guardado el {$v(date('d/m/Y h:i a'))}.
//
// Con estos datos se envían automáticamente: el aviso al registrar una cita,
// el aviso al reprogramarla y el recordatorio 24 horas antes de la cita.
// Con Gmail, "password" es una CONTRASEÑA DE APLICACIÓN
// (https://myaccount.google.com/apppasswords), no la contraseña normal.
//
// IMPORTANTE: este archivo guarda una contraseña. No lo comparta ni lo suba
// a un repositorio (está en .gitignore).
// =====================================================================

return [
    'habilitado' => {$v($d['habilitado'])},

    'host'       => {$v($d['host'])},
    'port'       => {$v($d['port'])},
    'encryption' => {$v($d['encryption'])},   // 'tls' (587), 'ssl' (465) o '' (sin cifrado)

    'username'   => {$v($d['username'])},
    'password'   => {$v($clave)},

    'from' => [
        'address' => {$v($d['from_address'])},   // vacío = el mismo "username"
        'name'    => {$v($d['from_name'])},
    ],

    'ca_file'    => {$v(CORREO_CA_ARCHIVO)},
    'timeout'    => {$v((int) CORREO_ESPERA)},
];

PHP;

        $temporal = CORREO_ARCHIVO . '.tmp';
        if (@file_put_contents($temporal, $contenido, LOCK_EX) === false || !@rename($temporal, CORREO_ARCHIVO)) {
            @unlink($temporal);
            throw new RuntimeException('No se pudo guardar app/config/correo.php. Revise que la carpeta app/config '
                . 'no sea de solo lectura.');
        }
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate(CORREO_ARCHIVO, true);   // que el próximo clic ya use los datos nuevos
        }
    }

    /** Indica si el archivo de configuración se puede escribir desde el sistema. */
    public static function sePuedeEscribir(): bool
    {
        return is_file(CORREO_ARCHIVO) ? is_writable(CORREO_ARCHIVO) : is_writable(dirname(CORREO_ARCHIVO));
    }
}
