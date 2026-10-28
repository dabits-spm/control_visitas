<?php
// app/utilidades/RecordatorioCitas.php

require_once __DIR__ . '/AvisoVisita.php';
require_once __DIR__ . '/../models/Visita.php';
require_once __DIR__ . '/../models/Auditoria.php';
require_once __DIR__ . '/../servicios/EnvioCorreos.php';

/**
 * Recordatorios automáticos por correo: 24 horas antes de cada cita pendiente
 * (programada o reprogramada) se envía un correo con fecha, hora, departamento,
 * motivo y ubicación.
 *
 * XAMPP no tiene tareas programadas propias, así que el proceso se ejecuta:
 *   - Solo, mientras alguien tenga el sistema abierto: 08-notificaciones.js lo pide en segundo
 *     plano cada pocos minutos (no hace esperar al usuario).
 *   - Con el Programador de tareas de Windows (recomendado), usando
 *     herramientas/enviar_recordatorios.php, para que salgan aunque nadie esté conectado.
 * Cada cita recibe un solo recordatorio; si se reprograma, recibe uno nuevo.
 */
class RecordatorioCitas
{
    /** Minutos mínimos entre dos revisiones automáticas desde el navegador. */
    public const MINUTOS_ENTRE_REVISIONES = 5;

    /**
     * Envía los recordatorios pendientes.
     * @param bool $forzar true = no respetar el intervalo entre revisiones (uso desde la consola)
     * @return array{revisado:bool, enviados:int, fallidos:int, motivo:string, errores:string[]}
     */
    public static function procesar(int $limite = 10, bool $forzar = false, int $idUsuario = 0): array
    {
        $resultado = ['revisado' => false, 'enviados' => 0, 'fallidos' => 0, 'motivo' => '', 'errores' => []];

        if (!CorreoSmtp::estaConfigurado()) {
            $resultado['motivo'] = ucfirst(CorreoSmtp::motivoNoConfigurado());
            return $resultado;
        }
        if (!$forzar && !self::turnoDisponible()) {
            $resultado['motivo'] = 'Ya se revisó hace menos de ' . self::MINUTOS_ENTRE_REVISIONES . ' minutos.';
            return $resultado;
        }
        $resultado['revisado'] = true;

        $visitas   = new Visita();
        $auditoria = new Auditoria();

        foreach ($visitas->citasParaRecordatorio(time(), $limite) as $idVisita) {
            if (!$visitas->reservarRecordatorio($idVisita)) {
                continue;   // otro proceso lo envió, o la cita ya no está pendiente
            }
            $visita = $visitas->obtenerDetalle($idVisita);
            $error  = $visita ? AvisoVisita::enviar($visita, AvisoVisita::RECORDATORIO) : 'la visita no existe.';

            if ($error === null) {
                $resultado['enviados']++;
                $auditoria->registrar('RECORDATORIO',
                    mb_substr("Recordatorio de la visita #{$idVisita} enviado a {$visita['correo']} (24 horas antes)", 0, 255),
                    'visita', $idUsuario);
                continue;
            }

            // Falló: la cita vuelve a quedar pendiente para la próxima revisión
            $visitas->liberarRecordatorio($idVisita);
            $resultado['fallidos']++;
            $resultado['errores'][] = "Visita #{$idVisita}: {$error}";

            // Si el problema es del servidor de correo (conexión, contraseña), los demás también fallarían
            if (EnvioCorreos::esFalloDelServidor($error)) {
                break;
            }
        }
        return $resultado;
    }

    /** Fecha y hora de la última revisión automática (o null si nunca se hizo). */
    public static function ultimaRevision(): ?string
    {
        $stmt = Database::getConexion()->prepare("SELECT ultima_ejecucion FROM proceso_automatico WHERE nombre = 'recordatorios'");
        $stmt->execute();
        $valor = $stmt->fetchColumn();
        return $valor ?: null;
    }

    /**
     * Turno de revisión: solo uno cada MINUTOS_ENTRE_REVISIONES minutos, aunque haya
     * varias pestañas o usuarios conectados (lo decide la base de datos).
     */
    private static function turnoDisponible(): bool
    {
        $db    = Database::getConexion();
        $ahora = date('Y-m-d H:i:s');

        $existe = $db->prepare("SELECT COUNT(*) FROM proceso_automatico WHERE nombre = 'recordatorios'");
        $existe->execute();
        if ((int) $existe->fetchColumn() === 0) {
            try {
                $db->prepare("INSERT INTO proceso_automatico (nombre, ultima_ejecucion) VALUES ('recordatorios', NULL)")->execute();
            } catch (PDOException $e) {
                // otro proceso la creó al mismo tiempo
            }
        }

        $stmt = $db->prepare(
            "UPDATE proceso_automatico SET ultima_ejecucion = :ahora
             WHERE nombre = 'recordatorios' AND (ultima_ejecucion IS NULL OR ultima_ejecucion <= :limite)"
        );
        $stmt->execute([':ahora' => $ahora, ':limite' => date('Y-m-d H:i:s', time() - self::MINUTOS_ENTRE_REVISIONES * 60)]);
        return $stmt->rowCount() === 1;
    }
}
