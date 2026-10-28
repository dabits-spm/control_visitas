<?php
// public/index.php  (controlador frontal)

require_once __DIR__ . '/../app/config/config.php';
require_once APP_PATH . '/config/Database.php';
require_once APP_PATH . '/helpers/funciones.php';
require_once APP_PATH . '/models/Usuario.php';   // roles y permisos, usados por puede()
require_once APP_PATH . '/controllers/Controlador.php';

// --- Sesión con cookie más segura ---
session_set_cookie_params([
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_cache_limiter('');   // las cabeceras de caché se envían abajo
session_start();

// --- Ninguna página del sistema se guarda en la caché del navegador ---
// Así, al cerrar sesión, los botones "Atrás" y "Adelante" no muestran pantallas
// guardadas: el navegador vuelve a pedirlas y el servidor exige iniciar sesión.
// (public/assets/js/01-sesion-barra.js completa esta protección para la caché "atrás/adelante").
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// --- Tabla de rutas (app/config/rutas.php) ---
$rutas = require APP_PATH . '/config/rutas.php';

// --- Obtener la URL solicitada ---
$url = $_GET['url'] ?? '';
if (!is_string($url)) {
    $url = '';
}
$url = trim($url, '/');

// La raíz del sistema (o "index.php") lleva al panel de control
if ($url === '' || $url === 'index.php') {
    header('Location: ' . url('dashboard'));
    exit;
}

// --- Buscar la ruta ---
$metodoHttp = $_SERVER['REQUEST_METHOD'];
$destino    = $rutas[$metodoHttp][$url] ?? null;

if ($destino === null) {
    http_response_code(404);
    exit('Página no encontrada.');
}

[$clase, $accion] = $destino;

// --- Cargar y ejecutar el controlador ---
$archivo = APP_PATH . '/controllers/' . $clase . '.php';

if (!is_file($archivo)) {
    http_response_code(404);
    exit('Página no encontrada.');
}

require_once $archivo;

$controlador = new $clase();
$controlador->$accion();
