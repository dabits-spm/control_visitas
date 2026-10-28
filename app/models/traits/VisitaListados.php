<?php
// app/models/traits/VisitaListados.php

/**
 * Visita: historial, visitas programadas, ficha del visitante y detalle de una visita.
 */
trait VisitaListados
{
    /**
     * Condiciones de un listado: alcance (historial o programadas), texto (cédula, nombre,
     * teléfono, departamento o motivo), estado y tipo. Devuelve el WHERE y llena $parametros.
     */
    private function condicionesListado(string $alcance, string $texto, int $id_estado, string $tipo, array &$parametros): string
    {
        $citas = implode(',', self::ESTADOS_CITA);
        // Las citas aparecen en el historial solo cuando ya ocurrieron (o se cancelaron)
        $condiciones = [$alcance === self::LISTADO_PROGRAMADAS ? "v.id_estado IN ({$citas})" : "v.id_estado NOT IN ({$citas})"];

        if ($texto !== '') {
            $parametros[':texto1'] = $parametros[':texto2'] = '%' . addcslashes($texto, '%_\\') . '%';
            $condiciones[] = "(CONCAT_WS(' ', vi.cedula, vi.nombre, vi.apellido, vi.telefono, v.motivo_visita) LIKE :texto1
                              OR EXISTS (SELECT 1 FROM visita_departamento fd
                                         INNER JOIN departamento dd ON dd.id_departamento = fd.id_departamento
                                         WHERE fd.id_visita = v.id_visita AND dd.nombre_departamento LIKE :texto2))";
        }
        if ($id_estado > 0) {
            $condiciones[] = 'v.id_estado = :estado';
            $parametros[':estado'] = $id_estado;
        }
        if (isset(self::TIPOS[$tipo])) {
            $condiciones[] = 'v.tipo_visita = :tipo';
            $parametros[':tipo'] = $tipo;
        }
        return 'WHERE ' . implode(' AND ', $condiciones);
    }

    /** Cantidad de visitas que cumplen los filtros de un listado. */
    public function contarListado(string $alcance, string $texto = '', int $id_estado = 0, string $tipo = ''): int
    {
        $parametros = [];
        $donde = $this->condicionesListado($alcance, $texto, $id_estado, $tipo, $parametros);
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM visita v
             INNER JOIN visitante vi ON vi.id_visitante = v.id_visitante
             {$donde}"
        );
        $stmt->execute($parametros);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Una página de un listado.
     * Historial: de la visita más reciente a la más antigua.
     * Programadas: de la cita más próxima a la más lejana.
     */
    public function listar(string $alcance, string $texto = '', int $id_estado = 0, string $tipo = '',
                           int $limite = 20, int $desplazamiento = 0): array
    {
        $parametros = [];
        $donde = $this->condicionesListado($alcance, $texto, $id_estado, $tipo, $parametros);
        $orden = $alcance === self::LISTADO_PROGRAMADAS
            ? 'v.fecha_visita ASC, v.hora_entrada ASC, v.id_visita ASC'
            : 'v.fecha_visita DESC, v.hora_entrada DESC, v.id_visita DESC';

        $stmt = $this->db->prepare($this->consultaBase() . "
                {$donde}
                GROUP BY v.id_visita
                ORDER BY {$orden}
                LIMIT :limite OFFSET :desplazamiento");
        foreach ($parametros as $nombre => $valor) {
            $stmt->bindValue($nombre, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':desplazamiento', $desplazamiento, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Todas las visitas de un visitante, de la más reciente a la más antigua (ficha del visitante). */
    public function listarPorVisitante(int $id_visitante): array
    {
        $stmt = $this->db->prepare($this->consultaBase() . "
                WHERE v.id_visitante = :id
                GROUP BY v.id_visita
                ORDER BY v.fecha_visita DESC, v.hora_entrada DESC, v.id_visita DESC");
        $stmt->execute([':id' => $id_visitante]);
        return $stmt->fetchAll();
    }

    /** Cantidad de citas pendientes (programadas o reprogramadas), para el menú lateral. */
    public function contarCitasPendientes(): int
    {
        return (int) $this->db
            ->query("SELECT COUNT(*) FROM visita WHERE id_estado IN (" . implode(',', self::ESTADOS_CITA) . ")")
            ->fetchColumn();
    }

    /** Una visita con los datos del visitante, el estado y los departamentos en texto. */
    public function obtenerDetalle(int $id_visita): ?array
    {
        $stmt = $this->db->prepare($this->consultaBase() . "
                WHERE v.id_visita = :id
                GROUP BY v.id_visita");
        $stmt->execute([':id' => $id_visita]);
        $visita = $stmt->fetch();

        return $visita ?: null;
    }

    public function obtenerPorId(int $id_visita): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM visita WHERE id_visita = :id");
        $stmt->execute([':id' => $id_visita]);
        $visita = $stmt->fetch();

        if (!$visita) {
            return null;
        }

        // IDs de departamentos asociados (para precargar el formulario de edición)
        $stmt = $this->db->prepare(
            "SELECT id_departamento FROM visita_departamento WHERE id_visita = :id"
        );
        $stmt->execute([':id' => $id_visita]);
        $visita['departamentos'] = array_column($stmt->fetchAll(), 'id_departamento');

        return $visita;
    }
}
