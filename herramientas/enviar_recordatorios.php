<?php
// herramientas/enviar_recordatorios.php
// ---------------------------------------------------------------------
// Envía los recordatorios por correo de las citas que empiezan dentro de
// las próximas 24 horas (cada cita recibe uno solo).
//
// Mientras alguien tenga el sistema abierto, los recordatorios salen solos.
// Para que también salgan cuando nadie está conectado (noches, fines de
// semana), programe este archivo en Windows cada 15 minutos. En una consola
// abierta "como administrador":
//
//   schtasks /create /sc minute /mo 15 /tn "Control de Visitas - Recordatorios" ^
//     /tr "C:\xampp\php\php.exe C:\xampp\htdocs\control_visitas\herramientas\enviar_recordatorios.php"
//
// MySQL (XAMPP) debe estar encendido. Para probarlo a mano:
//   C:\xampp\php\php.exe herramientas\enviar_recordatorios.php
// ---------------------------------------------------------------------

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/../app/config/config.php';
require APP_PATH . '/config/Database.php';
require APP_PATH . '/helpers/funciones.php';
require APP_PATH . '/utilidades/RecordatorioCitas.php';

$resultado = RecordatorioCitas::procesar(50, true, 0);

$linea = date('Y-m-d H:i:s') . ' - ';
if (!$resultado['revisado']) {
    echo $linea . 'No se revisó: ' . $resultado['motivo'] . PHP_EOL;
    exit(1);
}
echo $linea . "Recordatorios enviados: {$resultado['enviados']}, con error: {$resultado['fallidos']}" . PHP_EOL;
foreach ($resultado['errores'] as $error) {
    echo '  ' . $error . PHP_EOL;
}
exit($resultado['fallidos'] > 0 ? 1 : 0);
