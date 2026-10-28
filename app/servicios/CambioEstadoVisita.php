<?php
// app/servicios/CambioEstadoVisita.php

require_once __DIR__ . '/ValidadorVisita.php';
require_once __DIR__ . '/AvisoCita.php';
require_once __DIR__ . '/../models/Visita.php';
require_once __DIR__ . '/../models/Auditoria.php';

/**
 * Cambio de estado de una visita (iniciar, reprogramar, finalizar, cancelar, reabrir),
 * con las reglas de cada rol, la auditoría y el aviso por correo al reprogramar.
 */
class CambioEstadoVisita
{
    /** Motivo por el que no se permite el cambio, o null si está permitido. */
    public static function motivoRechazo(array $visita, int $id_estado, bool $esAdministrador): ?string
    {
        $actual = (int) $visita['id_estado'];
        if (!Visita::esActiva($actual) && !puede('visitas.estado_cerradas')) {
            return 'Una visita finalizada o cancelada solo la puede cambiar un administrador.';
        }
        if (!Visita::transicionValida($actual, $id_estado, $esAdministrador)) {
            return 'Ese cambio de estado no está permitido para esta visita.';
        }
        return null;
    }

    /**
     * Nueva fecha y hora al reprogramar.
     * @return array [fecha, hora "HH:MM:00", errores (ya con el texto para el usuario)]
     */
    public static function leerReprogramacion(array $post): array
    {
        $fecha = trim(entradaTexto($post, 'fecha_visita'));
        $hora  = normalizarHora(entradaTexto($post, 'hora_entrada'));
        $errores = ValidadorVisita::validarFechaCita($fecha, $hora);
        if ($errores) {
            return [$fecha, $hora, array_values(array_map(static fn ($m) => 'No se reprogramó: ' . lcfirst($m), $errores))];
        }
        return [$fecha, substr($hora, 0, 5) . ':00', []];
    }

    /**
     * Aplica el cambio y lo deja en la auditoría.
     * @return string|null mensaje para el usuario, o null si la visita cambió mientras tanto
     */
    public static function aplicar(array $visita, int $id_estado, ?string $nuevaFecha, ?string $nuevaHora): ?string
    {
        $visitas = new Visita();
        if (!$visitas->cambiarEstado($visita, $id_estado, $nuevaFecha, $nuevaHora)) {
            return null;
        }

        $id_visita = (int) $visita['id_visita'];
        $actual    = (int) $visita['id_estado'];
        $nombres   = array_column($visitas->listarEstados(), 'nombre_estado', 'id_estado');
        $detalle   = "Visita #{$id_visita} de {$visita['visitante']}: {$nombres[$actual]} → {$nombres[$id_estado]}";
        $citaOriginal = ' (cita del ' . fechaLarga($visita['fecha_visita']) . ' ' . horaCorta($visita['hora_entrada']) . ')';
        $mensaje   = 'Estado de la visita actualizado.';

        if ($id_estado === Visita::ESTADO_EN_PROCESO && Visita::esCita($actual)) {
            // La fecha y hora pasan a ser las de llegada: se deja constancia de la cita original
            $detalle .= $citaOriginal;
        } elseif ($id_estado === Visita::ESTADO_REPROGRAMADA) {
            $detalle .= $citaOriginal . ' para el ' . fechaLarga($nuevaFecha) . ' ' . horaCorta($nuevaHora);
            $mensaje = 'Visita reprogramada para el ' . fechaLarga($nuevaFecha) . ' a las ' . horaDoce($nuevaHora) . '.';
            AvisoCita::enviar($id_visita, $mensaje, true);
        }

        (new Auditoria())->registrar('CAMBIO_ESTADO', mb_substr($detalle, 0, 255), 'visita', (int) $_SESSION['usuario']['id_usuario']);
        return $mensaje;
    }
}
