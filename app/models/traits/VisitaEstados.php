<?php
// app/models/traits/VisitaEstados.php

/**
 * Visita: cambios de estado, observaciones y cierre automático de las visitas vencidas.
 */
trait VisitaEstados
{
    /**
     * Cambia el estado de una visita. $visita es la fila actual (obtenerDetalle).
     *  - Iniciar una cita ("En proceso") guarda la fecha y la hora reales de llegada.
     *  - Reabrir una visita cerrada ("En proceso") borra la hora de salida.
     *  - Finalizar guarda la hora de salida si la visita es de hoy y aún no la tiene.
     *  - Reprogramar guarda la nueva fecha y hora ($nuevaFecha, $nuevaHora).
     * La condición "id_estado = estado actual" evita que dos personas cambien
     * la misma visita al mismo tiempo.
     */
    public function cambiarEstado(array $visita, int $id_estado, ?string $nuevaFecha = null, ?string $nuevaHora = null): bool
    {
        $estadoActual = (int) $visita['id_estado'];
        $sql = "UPDATE visita SET id_estado = :estado, cierre_automatico = 0";
        $parametros = [':estado' => $id_estado, ':id' => (int) $visita['id_visita'], ':actual' => $estadoActual];

        if ($id_estado === self::ESTADO_EN_PROCESO && self::esCita($estadoActual)) {
            $sql .= ", fecha_visita = :fecha, hora_entrada = :hora, hora_salida = NULL";
            $parametros[':fecha'] = date('Y-m-d');
            $parametros[':hora']  = date('H:i:s');
        } elseif ($id_estado === self::ESTADO_EN_PROCESO) {
            $sql .= ", hora_salida = NULL";
        } elseif ($id_estado === self::ESTADO_FINALIZADA
                  && empty($visita['hora_salida']) && $visita['fecha_visita'] === date('Y-m-d')) {
            $sql .= ", hora_salida = :hora";
            $parametros[':hora'] = date('H:i:s');
        } elseif ($id_estado === self::ESTADO_REPROGRAMADA) {
            // Nueva fecha: hace falta un aviso nuevo y, más adelante, un recordatorio nuevo
            $sql .= ", fecha_visita = :fecha, hora_entrada = :hora, hora_salida = NULL, aviso_enviado = NULL, recordatorio_enviado = NULL";
            $parametros[':fecha'] = $nuevaFecha;
            $parametros[':hora']  = $nuevaHora;
        }

        $sql .= " WHERE id_visita = :id AND id_estado = :actual";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->rowCount() === 1;
    }

    /** Guarda las observaciones de una visita (null o vacío = ninguna). */
    public function actualizarObservaciones(int $id_visita, ?string $observaciones): bool
    {
        $stmt = $this->db->prepare("UPDATE visita SET observaciones = :obs WHERE id_visita = :id");
        $stmt->execute([':obs' => $observaciones, ':id' => $id_visita]);
        return $stmt->rowCount() === 1 || $this->obtenerEstado($id_visita) !== null;
    }

    /**
     * Cierre automático: las visitas de días anteriores que siguen "Programadas",
     * "Reprogramadas" o "En proceso" pasan a "Finalizada" y quedan marcadas con cierre_automatico = 1.
     * Ej: una visita del 30 de octubre se finaliza sola a partir del 31.
     *
     * @return int cantidad de visitas cerradas
     */
    public function cerrarVencidas(): int
    {
        $stmt = $this->db->prepare(
            "UPDATE visita
             SET id_estado = :finalizada, cierre_automatico = 1
             WHERE id_estado IN (:programada, :reprogramada, :en_proceso)
               AND fecha_visita < :hoy"
        );
        $stmt->execute([
            ':finalizada'   => self::ESTADO_FINALIZADA,
            ':programada'   => self::ESTADO_PROGRAMADA,
            ':reprogramada' => self::ESTADO_REPROGRAMADA,
            ':en_proceso'   => self::ESTADO_EN_PROCESO,
            ':hoy'        => date('Y-m-d'),
        ]);
        return $stmt->rowCount();
    }

    /** Devuelve el estado actual de una visita (o null si no existe). */
    public function obtenerEstado(int $id_visita): ?int
    {
        $stmt = $this->db->prepare("SELECT id_estado FROM visita WHERE id_visita = :id");
        $stmt->execute([':id' => $id_visita]);
        $estado = $stmt->fetchColumn();

        return $estado === false ? null : (int) $estado;
    }
}
