<?php
// herramientas/probar_correo.php
// ---------------------------------------------------------------------
// Prueba la configuración de correo sin registrar visitas.
// Se ejecuta desde la consola, en la carpeta del proyecto:
//   C:\xampp\php\php.exe herramientas\probar_correo.php su_correo@gmail.com
// Envía un aviso de ejemplo y muestra la conversación con el servidor
// (sin la contraseña) para encontrar el problema si algo falla.
// ---------------------------------------------------------------------

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/../app/config/config.php';
require APP_PATH . '/helpers/funciones.php';
require APP_PATH . '/utilidades/AvisoVisita.php';

$destino = $argv[1] ?? '';
if (!filter_var($destino, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php herramientas/probar_correo.php destino@correo.com\n");
    exit(1);
}

echo "Servidor:   " . CORREO_HOST . ':' . CORREO_PUERTO . ' (' . (CORREO_SEGURIDAD ? strtoupper(CORREO_SEGURIDAD) : 'sin cifrado') . ")\n";
echo "Cuenta:     " . (CORREO_USUARIO ?: '(sin autenticación)') . "\n";
echo "Contraseña: " . (CORREO_CLAVE !== '' ? 'escrita (' . strlen(CORREO_CLAVE) . ' caracteres)' : 'VACÍA') . "\n";
echo "Remitente:  " . CORREO_NOMBRE . ' <' . CORREO_REMITENTE . ">\n";
if ((CORREO_SEGURIDAD === 'ssl' && (int) CORREO_PUERTO === 587) || (CORREO_SEGURIDAD === 'tls' && (int) CORREO_PUERTO === 465)) {
    echo "AVISO: con '" . CORREO_SEGURIDAD . "' se usa normalmente el puerto " . (CORREO_SEGURIDAD === 'ssl' ? 465 : 587) . ".\n";
}
echo "Habilitado: " . (CORREO_HABILITADO ? 'sí' : 'NO (revise app/config/correo.php)') . "\n\n";

if (!CorreoSmtp::estaConfigurado()) {
    fwrite(STDERR, "El envío de correos no está configurado:\n  - " . implode("\n  - ", CorreoSmtp::problemas())
        . "\nConfigúrelo en el sistema (administrador -> Configuración de correo) o en app/config/correo.php.\n");
    exit(1);
}

// Visita de ejemplo para mañana a las 10:00
$ejemplo = [
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
[$asunto, $html, $texto] = AvisoVisita::armar($ejemplo);

$cliente = CorreoSmtp::desdeConfiguracion();
try {
    $cliente->enviar($destino, $ejemplo['visitante'], '[PRUEBA] ' . $asunto, $html, $texto);
    echo "Correo enviado a {$destino}. Revise la bandeja de entrada (y la carpeta de spam).\n";
    exit(0);
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n\nConversación con el servidor:\n  " . implode("\n  ", $cliente->registro()) . "\n";
    exit(1);
}
