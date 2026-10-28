<?php
// app/config/config.php

// --- Base de datos ---
define('DB_HOST',    'localhost');
define('DB_NAME',    'control_visitas');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// --- Rutas ---
// Carpeta /app (un nivel arriba de /config)
define('APP_PATH', dirname(__DIR__));

// URL pública del sistema, SIN barra final.
// Se calcula sola a partir de la carpeta donde está public/index.php,
// así funciona igual en "control_visitas", "control_visitas2", etc.
// Si prefieres fijarla a mano, reemplaza el bloque por:
//   define('BASE_URL', 'http://localhost/control_visitas/public');
$carpetaPublica = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
define('BASE_URL', rtrim($carpetaPublica, '/'));
unset($carpetaPublica);

// --- Zona horaria ---
// La conexión a la base de datos usa esta misma zona (ver Database.php),
// así PHP y MariaDB coinciden en la fecha y la hora actuales.
date_default_timezone_set('America/Caracas');

// --- Institución (aparece en el inicio de sesión, los reportes y los correos) ---
define('INSTITUCION_NOMBRE',      'Inces Los Colorados');
define('INSTITUCION_NOMBRE_COMPLETO', 'Instituto Nacional de Capacitación y Educación Socialista - INCES');
define('INSTITUCION_SEDE',        'Sede Los Colorados');
// Foto de fondo del panel izquierdo del inicio de sesión (dentro de public/)
define('INSTITUCION_FOTO_LOGIN',  'assets/img/sede-inces-los-colorados.jpg');
define('INSTITUCION_UBICACION',   'Inces Los Colorados, Valencia, Carabobo, Venezuela');
define('INSTITUCION_DIRECCION',   'Valencia, Carabobo, Venezuela');   // se muestra en el correo de aviso

// --- Correo (SMTP) ---
// Todos los parámetros se editan en app/config/correo.php: host, port, username,
// password, encryption (tls/ssl) y from (remitente).
// También se acepta el formato de versiones anteriores (define('CORREO_HOST', ...)).
define('CORREO_ARCHIVO', __DIR__ . '/correo.php');
$configCorreo = is_file(CORREO_ARCHIVO) ? require CORREO_ARCHIVO : null;
if (is_array($configCorreo)) {
    $cifrado = strtolower(trim((string) ($configCorreo['encryption'] ?? 'tls')));
    $cifrado = match ($cifrado) {
        'ssl', 'smtps'        => 'ssl',
        '', 'none', 'ninguna' => '',
        default               => 'tls',      // 'tls' o 'starttls'
    };
    $usuarioCorreo = trim((string) ($configCorreo['username'] ?? ''));
    define('CORREO_HABILITADO', (bool) ($configCorreo['habilitado'] ?? true));
    define('CORREO_HOST',       trim((string) ($configCorreo['host'] ?? '')));
    define('CORREO_PUERTO',     (int) ($configCorreo['port'] ?? ($cifrado === 'ssl' ? 465 : 587)));
    define('CORREO_SEGURIDAD',  $cifrado);
    define('CORREO_USUARIO',    $usuarioCorreo);
    define('CORREO_CLAVE',      str_replace(' ', '', (string) ($configCorreo['password'] ?? '')));
    define('CORREO_REMITENTE',  trim((string) ($configCorreo['from']['address'] ?? '')) ?: $usuarioCorreo);
    define('CORREO_NOMBRE',     trim((string) ($configCorreo['from']['name'] ?? '')) ?: 'Control de Visitas');
    define('CORREO_CA_ARCHIVO', trim((string) ($configCorreo['ca_file'] ?? '')));
    define('CORREO_ESPERA',     max(5, (int) ($configCorreo['timeout'] ?? 15)));
    unset($cifrado, $usuarioCorreo);
}
unset($configCorreo);
defined('CORREO_HABILITADO')   || define('CORREO_HABILITADO', false);
defined('CORREO_HOST')         || define('CORREO_HOST', 'smtp.gmail.com');
defined('CORREO_PUERTO')       || define('CORREO_PUERTO', 587);
defined('CORREO_SEGURIDAD')    || define('CORREO_SEGURIDAD', 'tls');
defined('CORREO_USUARIO')      || define('CORREO_USUARIO', '');
defined('CORREO_CLAVE')        || define('CORREO_CLAVE', '');
defined('CORREO_REMITENTE')    || define('CORREO_REMITENTE', CORREO_USUARIO);
defined('CORREO_NOMBRE')       || define('CORREO_NOMBRE', 'Control de Visitas');
defined('CORREO_CA_ARCHIVO')   || define('CORREO_CA_ARCHIVO', '');
defined('CORREO_ESPERA')       || define('CORREO_ESPERA', 15);

// --- Errores (solo para desarrollo) ---
// En producción: display_errors = 0 y registrar en el log.
ini_set('display_errors', '1');
error_reporting(E_ALL);
