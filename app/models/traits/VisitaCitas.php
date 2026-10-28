<?php
// app/models/traits/VisitaCitas.php

/**
 * Visita: citas pendientes, avisos y recordatorios por correo (24 horas antes) y consultas de las notificaciones.
 */
trait VisitaCitas
{
    /**
     * Registra la fecha y hora en que se envió el aviso por correo.
     * Si la cita es dentro de las próximas 24 horas, ese aviso también cuenta como
     * recordatorio (el visitante no recibe dos correos casi seguidos).
     */
    public function marcarAvisoEnviado(int $id_visita): void
    {
        $ahora = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("SELECT fecha_visita, hora_entrada FROM visita WHERE id_visita = :id");
        $stmt->execute([':id' => $id_visita]);
        $cita = $stmt->fetch();

        $sql = "UPDATE visita SET aviso_enviado = :ahora";
        if ($cita && strtotime($cita['fecha_visita'] . ' ' . $cita['hora_entrada']) <= time() + self::HORAS_RECORDATORIO * 3600) {
            $sql .= ", recordatorio_enviado = COALESCE(recordatorio_enviado, :ahora2)";
        }
        $stmt = $this->db->prepare($sql . " WHERE id_visita = :id");
        $parametros = [':ahora' => $ahora, ':id' => $id_visita];
        if (str_contains($sql, ':ahora2')) {
            $parametros[':ahora2'] = $ahora;
        }
        $stmt->execute($parametros);
    }

    // ------------------------------------------------------------
    // Recordatorios por correo (24 horas antes de la cita)
    // ------------------------------------------------------------

    /**
     * Citas pendientes (programadas o reprogramadas) que empiezan dentro de las
     * próximas 24 horas y todavía no recibieron el recordatorio.
     * @return int[] ids de las visitas, de la más próxima a la más lejana
     */
    public function citasParaRecordatorio(int $ahora, int $limite = 20): array
    {
        $limiteTiempo = $ahora + self::HORAS_RECORDATORIO * 3600;
        $stmt = $this->db->prepare(
            "SELECT id_visita, fecha_visita, hora_entrada
             FROM visita
             WHERE id_estado IN (" . implode(',', self::ESTADOS_CITA) . ")
               AND recordatorio_enviado IS NULL
               AND fecha_visita BETWEEN :desde AND :hasta
             ORDER BY fecha_visita ASC, hora_entrada ASC"
        );
        $stmt->execute([':desde' => date('Y-m-d', $ahora), ':hasta' => date('Y-m-d', $limiteTiempo)]);

        $ids = [];
        foreach ($stmt->fetchAll() as $fila) {
            $momento = strtotime($fila['fecha_visita'] . ' ' . $fila['hora_entrada']);
            // Solo las que aún no empiezan y empiezan dentro de las próximas 24 horas
            if ($momento > $ahora && $momento <= $limiteTiempo) {
                $ids[] = (int) $fila['id_visita'];
                if (count($ids) >= $limite) {
                    break;
                }
            }
        }
        return $ids;
    }

    /**
     * Marca el recordatorio de una cita como enviado antes de enviarlo, para que dos
     * procesos a la vez no lo envíen dos veces. Devuelve false si otro ya lo tomó
     * o si la cita dejó de estar pendiente.
     */
    public function reservarRecordatorio(int $id_visita): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE visita SET recordatorio_enviado = :ahora
             WHERE id_visita = :id AND recordatorio_enviado IS NULL
               AND id_estado IN (" . implode(',', self::ESTADOS_CITA) . ")"
        );
        $stmt->execute([':ahora' => date('Y-m-d H:i:s'), ':id' => $id_visita]);
        return $stmt->rowCount() === 1;
    }

    /** Si el envío falló, la cita vuelve a quedar pendiente de recordatorio. */
    public function liberarRecordatorio(int $id_visita): void
    {
        $stmt = $this->db->prepare("UPDATE visita SET recordatorio_enviado = NULL WHERE id_visita = :id");
        $stmt->execute([':id' => $id_visita]);
    }

    // ------------------------------------------------------------
    // Consultas para las notificaciones internas (solo registros pendientes)
    // ------------------------------------------------------------

    /** Citas de un día que siguen pendientes (programadas o reprogramadas), por hora. */
    public function citasPendientesDelDia(string $fecha): array
    {
        $stmt = $this->db->prepare($this->consultaBase() . "
                WHERE v.fecha_visita = :fecha AND v.id_estado IN (" . implode(',', self::ESTADOS_CITA) . ")
                GROUP BY v.id_visita
                ORDER BY v.hora_entrada ASC, v.id_visita ASC");
        $stmt->execute([':fecha' => $fecha]);
        return $stmt->fetchAll();
    }

    /** Visitas de un día que siguen en curso (falta finalizarlas). */
    public function enCursoDelDia(string $fecha): array
    {
        $stmt = $this->db->prepare($this->consultaBase() . "
                WHERE v.fecha_visita = :fecha AND v.id_estado = " . self::ESTADO_EN_PROCESO . "
                GROUP BY v.id_visita
                ORDER BY v.hora_entrada ASC, v.id_visita ASC");
        $stmt->execute([':fecha' => $fecha]);
        return $stmt->fetchAll();
    }

    /**
     * Citas pendientes que empiezan dentro de las próximas horas (con sus datos),
     * para ver en «Configuración de correo» a quién le toca el recordatorio.
     */
    public function citasProximas(int $ahora, int $horas = self::HORAS_RECORDATORIO): array
    {
        $limiteTiempo = $ahora + $horas * 3600;
        $stmt = $this->db->prepare($this->consultaBase() . "
                WHERE v.id_estado IN (" . implode(',', self::ESTADOS_CITA) . ")
                  AND v.fecha_visita BETWEEN :desde AND :hasta
                GROUP BY v.id_visita
                ORDER BY v.fecha_visita ASC, v.hora_entrada ASC");
        $stmt->execute([':desde' => date('Y-m-d', $ahora), ':hasta' => date('Y-m-d', $limiteTiempo)]);
        return array_values(array_filter($stmt->fetchAll(), static function (array $v) use ($ahora, $limiteTiempo): bool {
            $momento = strtotime($v['fecha_visita'] . ' ' . $v['hora_entrada']);
            return $momento > $ahora && $momento <= $limiteTiempo;
        }));
    }

    /** Citas pendientes desde una fecha cuyo aviso por correo no se pudo enviar. */
    public function citasSinAviso(string $desde): array
    {
        $stmt = $this->db->prepare($this->consultaBase() . "
                WHERE v.fecha_visita >= :desde AND v.aviso_enviado IS NULL
                  AND v.id_estado IN (" . implode(',', self::ESTADOS_CITA) . ")
                GROUP BY v.id_visita
                ORDER BY v.fecha_visita ASC, v.hora_entrada ASC");
        $stmt->execute([':desde' => $desde]);
        return $stmt->fetchAll();
    }
}
