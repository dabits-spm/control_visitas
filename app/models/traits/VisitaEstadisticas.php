<?php
// app/models/traits/VisitaEstadisticas.php

/**
 * Visita: números del panel de control y consultas de los reportes.
 */
trait VisitaEstadisticas
{
    /** Visitas cuya fecha es hoy, ordenadas por hora de entrada. */
    public function listarDelDia(): array
    {
        $sql = $this->consultaBase() . "
                WHERE v.fecha_visita = CURDATE()
                GROUP BY v.id_visita
                ORDER BY v.hora_entrada DESC";

        return $this->db->query($sql)->fetchAll();
    }

    /** Cantidad de visitas registradas para hoy. */
    public function contarDelDia(): int
    {
        return (int) $this->db
            ->query("SELECT COUNT(*) FROM visita WHERE fecha_visita = CURDATE()")
            ->fetchColumn();
    }

    /** Cantidad de visitas en un estado determinado. */
    public function contarPorEstado(int $id_estado): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM visita WHERE id_estado = :estado");
        $stmt->execute([':estado' => $id_estado]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Visitas de un día por departamento, desglosadas por estado (gráfico del panel).
     * Incluye todos los departamentos, también los que no tienen visitas.
     */
    public function delDiaPorDepartamento(string $fecha): array
    {
        $stmt = $this->db->prepare(
            "SELECT d.id_departamento, d.nombre_departamento,
                    COUNT(v.id_visita)                                     AS total,
                    COALESCE(SUM(v.id_estado = " . self::ESTADO_PROGRAMADA . "), 0) AS programadas,
                    COALESCE(SUM(v.id_estado = " . self::ESTADO_REPROGRAMADA . "), 0) AS reprogramadas,
                    COALESCE(SUM(v.id_estado = " . self::ESTADO_EN_PROCESO . "), 0) AS en_proceso,
                    COALESCE(SUM(v.id_estado = " . self::ESTADO_FINALIZADA . "), 0) AS finalizadas,
                    COALESCE(SUM(v.id_estado = " . self::ESTADO_CANCELADA . "), 0)  AS canceladas
             FROM departamento d
             LEFT JOIN visita_departamento vd ON vd.id_departamento = d.id_departamento
             LEFT JOIN visita v               ON v.id_visita = vd.id_visita AND v.fecha_visita = :fecha
             GROUP BY d.id_departamento, d.nombre_departamento
             ORDER BY total DESC, d.nombre_departamento ASC"
        );
        $stmt->execute([':fecha' => $fecha]);

        return array_map(static function (array $fila): array {
            foreach (['total', 'programadas', 'reprogramadas', 'en_proceso', 'finalizadas', 'canceladas'] as $campo) {
                $fila[$campo] = (int) $fila[$campo];
            }
            return $fila;
        }, $stmt->fetchAll());
    }

    // ------------------------------------------------------------
    // Consultas para los reportes
    // ------------------------------------------------------------


    /**
     * Cantidad de visitas por periodo (día, semana o mes), desglosada por estado.
     * Solo devuelve los periodos que tienen visitas.
     *
     * @return array [inicio del periodo 'AAAA-MM-DD' => [total, programadas, reprogramadas, en_proceso, finalizadas, canceladas]]
     */
    public function resumenPorPeriodo(string $tipo, string $desde, string $hasta): array
    {
        $inicio = self::INICIO_PERIODO[$tipo] ?? self::INICIO_PERIODO['diario'];

        $sql = "SELECT {$inicio} AS periodo,
                       COUNT(*)                                   AS total,
                       SUM(v.id_estado = " . self::ESTADO_PROGRAMADA . ") AS programadas,
                       SUM(v.id_estado = " . self::ESTADO_REPROGRAMADA . ") AS reprogramadas,
                       SUM(v.id_estado = " . self::ESTADO_EN_PROCESO . ") AS en_proceso,
                       SUM(v.id_estado = " . self::ESTADO_FINALIZADA . ") AS finalizadas,
                       SUM(v.id_estado = " . self::ESTADO_CANCELADA . ") AS canceladas
                FROM visita v
                WHERE v.fecha_visita BETWEEN :desde AND :hasta
                GROUP BY periodo
                ORDER BY periodo";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);

        $resumen = [];
        foreach ($stmt->fetchAll() as $fila) {
            $resumen[$fila['periodo']] = array_map('intval', [
                'total'       => $fila['total'],
                'programadas' => $fila['programadas'],
                'reprogramadas' => $fila['reprogramadas'],
                'en_proceso'  => $fila['en_proceso'],
                'finalizadas' => $fila['finalizadas'],
                'canceladas'  => $fila['canceladas'],
            ]);
        }
        return $resumen;
    }

    /**
     * Visitas de un rango de fechas que cumplen los filtros indicados (reporte personalizado).
     *
     * @param array $filtros desde, hasta (obligatorios); id_estado, tipo_visita, id_departamento,
     *                       id_usuario, visitante (texto: cédula o nombre) opcionales
     */
    public function buscar(array $filtros): array
    {
        $condiciones = ['v.fecha_visita BETWEEN :desde AND :hasta'];
        $parametros  = [':desde' => $filtros['desde'], ':hasta' => $filtros['hasta']];

        if (!empty($filtros['id_estado'])) {
            $condiciones[] = 'v.id_estado = :estado';
            $parametros[':estado'] = (int) $filtros['id_estado'];
        }
        if (isset(self::TIPOS[$filtros['tipo_visita'] ?? ''])) {
            $condiciones[] = 'v.tipo_visita = :tipo';
            $parametros[':tipo'] = $filtros['tipo_visita'];
        }
        if (!empty($filtros['id_usuario'])) {
            $condiciones[] = 'v.id_usuario = :usuario';
            $parametros[':usuario'] = (int) $filtros['id_usuario'];
        }
        if (!empty($filtros['id_departamento'])) {
            // Con EXISTS la lista de departamentos de cada visita se muestra completa
            $condiciones[] = 'EXISTS (SELECT 1 FROM visita_departamento f
                                      WHERE f.id_visita = v.id_visita AND f.id_departamento = :depto)';
            $parametros[':depto'] = (int) $filtros['id_departamento'];
        }
        if (($filtros['visitante'] ?? '') !== '') {
            $texto = '%' . addcslashes($filtros['visitante'], '%_\\') . '%';
            $condiciones[] = "(vi.cedula LIKE :texto1 OR CONCAT(vi.nombre, ' ', vi.apellido) LIKE :texto2)";
            $parametros[':texto1'] = $texto;
            $parametros[':texto2'] = $texto;
        }

        $sql = $this->consultaBase() . "
                WHERE " . implode(' AND ', $condiciones) . "
                GROUP BY v.id_visita
                ORDER BY v.fecha_visita ASC, v.hora_entrada ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }

    /** Estados posibles de una visita (para listas desplegables). */
    public function listarEstados(): array
    {
        return $this->db
            ->query("SELECT id_estado, nombre_estado FROM estado_visita ORDER BY id_estado")
            ->fetchAll();
    }

    /** Cantidad de visitas recibidas por cada departamento en un rango de fechas. */
    public function totalesPorDepartamento(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT d.nombre_departamento, COUNT(DISTINCT v.id_visita) AS total
             FROM visita v
             INNER JOIN visita_departamento vd ON vd.id_visita = v.id_visita
             INNER JOIN departamento d         ON d.id_departamento = vd.id_departamento
             WHERE v.fecha_visita BETWEEN :desde AND :hasta
             GROUP BY d.id_departamento, d.nombre_departamento
             ORDER BY total DESC, d.nombre_departamento ASC"
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetchAll();
    }
}
