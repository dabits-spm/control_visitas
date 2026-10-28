<?php
// app/config/Database.php

/**
 * Conexión PDO mediante patrón Singleton.
 * Requiere que app/config/config.php defina:
 *   DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET
 */
class Database
{
    private static ?PDO $instancia = null;

    // Evita instanciación directa y clonación
    private function __construct() {}
    private function __clone() {}

    public static function getConexion(): PDO
    {
        if (self::$instancia === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            $opciones = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // lanza excepciones
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // arreglos asociativos
                PDO::ATTR_EMULATE_PREPARES   => false,                    // prepared statements reales
            ];

            try {
                self::$instancia = new PDO($dsn, DB_USER, DB_PASS, $opciones);

                // CURDATE(), CURTIME() y NOW() usan la misma zona horaria que PHP
                // (date_default_timezone_set en config.php). Ej: SET time_zone = '-04:00'
                self::$instancia->exec("SET time_zone = '" . date('P') . "'");
            } catch (PDOException $e) {
                // No exponer detalles al usuario; registrar en el log del servidor
                error_log('Error de conexión BD: ' . $e->getMessage());
                http_response_code(500);
                exit('Error interno: no se pudo conectar a la base de datos.');
            }
        }

        return self::$instancia;
    }
}

/* ---------------------------------------------------------------
 * app/config/config.php (ejemplo)
 * ---------------------------------------------------------------
 * <?php
 * define('DB_HOST',    'localhost');
 * define('DB_NAME',    'control_visitas');
 * define('DB_USER',    'root');
 * define('DB_PASS',    '');
 * define('DB_CHARSET', 'utf8mb4');
 * date_default_timezone_set('America/Caracas');
 * --------------------------------------------------------------- */
