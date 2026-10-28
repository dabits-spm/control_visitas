<?php
// app/config/correo.ejemplo.php  (plantilla: el sistema usa app/config/correo.php)
// =====================================================================
// CONFIGURACIÓN CENTRALIZADA DEL CORREO (SMTP)
// ---------------------------------------------------------------------
// Con estos datos el sistema envía, de forma automática:
//   - el aviso al registrar una visita programada,
//   - el aviso con los datos nuevos al reprogramar una cita,
//   - el recordatorio 24 horas antes de cada cita.
//
// Mientras "username" y "from.address" estén vacíos, el sistema funciona
// normalmente pero no envía correos.
//
// Con Gmail (recomendado):
//   1. Active la verificación en dos pasos de la cuenta.
//   2. Cree una "contraseña de aplicación" en
//      https://myaccount.google.com/apppasswords
//   3. Escriba esa clave de 16 letras en "password" (sin espacios).
//      NO es la contraseña normal de la cuenta.
//
// Para probar sin registrar visitas, desde la carpeta del proyecto:
//   C:\xampp\php\php.exe herramientas\probar_correo.php su_correo@gmail.com
//
// IMPORTANTE: este archivo guarda una contraseña. Está en .gitignore para
// que no se suba a un repositorio; no lo comparta.
// =====================================================================

return [
    // false = no se envía ningún correo (útil para desactivarlo sin borrar los datos)
    'habilitado' => true,

    // Servidor SMTP
    'host'       => 'smtp.gmail.com',       // Host
    'port'       => 587,                    // Port: 587 con TLS · 465 con SSL
    'encryption' => 'tls',                  // Encryption: 'tls' (STARTTLS), 'ssl' o '' (sin cifrado)

    // Cuenta que envía los correos
    'username'   => '',                     // Username: p. ej. 'recepcion.inces@gmail.com'
    'password'   => '',                     // Password: contraseña de aplicación

    // Remitente (From) que verá el visitante
    'from' => [
        'address' => '',                    // vacío = el mismo "username" (Gmail lo exige así)
        'name'    => 'Control de Visitas - INCES Los Colorados',
    ],

    // Solo si aparece el error "certificate verify failed": ruta a un archivo de
    // certificados raíz. En XAMPP suele servir 'C:/xampp/apache/bin/curl-ca-bundle.crt'
    'ca_file'    => '',

    // Segundos máximos de espera por operación con el servidor
    'timeout'    => 15,
];
