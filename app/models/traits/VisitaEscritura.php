<?php
// app/models/traits/VisitaEscritura.php

/**
 * Visita: registrar, editar y eliminar visitas con sus departamentos (transacciones).
 */
trait VisitaEscritura
{
    /**
     * Crea una visita y la asocia a uno o varios departamentos.
     * Todo ocurre en una transacción: si falla algo, no se guarda nada.
     *
     * @param array $datos        fecha_visita, hora_entrada, motivo_visita, observaciones,
     *                            tipo_visita, id_usuario, id_visitante, id_estado
     * @param int[] $departamentos IDs de departamentos (tabla pivote)
     * @return int ID de la visita creada
     * @throws Exception si la operación falla
     */
    public function crear(array $datos, array $departamentos): int
    {
        // Si el controlador ya abrió una transacción (para guardar también al
        // visitante), se usa esa; si no, la visita abre y cierra la suya.
        $transaccionPropia = !$this->db->inTransaction();

        try {
            if ($transaccionPropia) {
                $this->db->beginTransaction();
            }

            // 1) Insertar la visita principal
            $sql = "INSERT INTO visita
                        (fecha_visita, hora_entrada, motivo_visita, observaciones,
                         tipo_visita, id_usuario, id_visitante, id_estado)
                    VALUES
                        (:fecha_visita, :hora_entrada, :motivo_visita, :observaciones,
                         :tipo_visita, :id_usuario, :id_visitante, :id_estado)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':fecha_visita'  => $datos['fecha_visita'],
                ':hora_entrada'  => $datos['hora_entrada'],
                ':motivo_visita' => $datos['motivo_visita'],
                ':observaciones' => $datos['observaciones'] ?? null,
                ':tipo_visita'   => $datos['tipo_visita'] ?? self::TIPO_PROGRAMADA,
                ':id_usuario'    => $datos['id_usuario'],
                ':id_visitante'  => $datos['id_visitante'],
                ':id_estado'     => $datos['id_estado'] ?? self::ESTADO_PROGRAMADA,
            ]);

            $id_visita = (int) $this->db->lastInsertId();

            // 2) Inserción múltiple en la tabla pivote de departamentos
            $this->asignarDepartamentos($id_visita, $departamentos);

            // Si todo sale bien, confirma los cambios en la base de datos
            if ($transaccionPropia) {
                $this->db->commit();
            }
            return $id_visita;

        } catch (Throwable $excepcion) {
            // Si ocurre cualquier error, revierte toda la operación (rollback)
            if ($transaccionPropia && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Error al crear visita: ' . $excepcion->getMessage());
            throw new Exception('No se pudo registrar la visita.');
        }
    }

    /**
     * Inserta N filas en visita_departamento reutilizando un solo
     * prepared statement. Debe llamarse dentro de una transacción.
     */
    private function asignarDepartamentos(int $id_visita, array $departamentos): void
    {
        // Eliminar duplicados y valores inválidos
        $departamentos = array_unique(array_filter(array_map('intval', $departamentos)));

        if (empty($departamentos)) {
            throw new InvalidArgumentException('La visita debe tener al menos un departamento.');
        }

        $stmt = $this->db->prepare(
            "INSERT INTO visita_departamento (id_visita, id_departamento)
             VALUES (:id_visita, :id_departamento)"
        );

        foreach ($departamentos as $id_departamento) {
            $stmt->execute([
                ':id_visita'       => $id_visita,
                ':id_departamento' => $id_departamento,
            ]);
        }
    }

    /**
     * Reemplaza los departamentos de una visita (útil al editar).
     */
    public function actualizarDepartamentos(int $id_visita, array $departamentos): bool
    {
        try {
            $this->db->beginTransaction();

            // Borra las asociaciones viejas
            $del = $this->db->prepare("DELETE FROM visita_departamento WHERE id_visita = :id");
            $del->execute([':id' => $id_visita]);

            // Inserta las nuevas
            $this->asignarDepartamentos($id_visita, $departamentos);

            $this->db->commit();
            return true;
        } catch (Throwable $excepcion) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Error al actualizar departamentos: ' . $excepcion->getMessage());
            return false;
        }
    }

    /**
     * Actualiza los datos de una visita y reemplaza sus departamentos.
     * El visitante y el estado no se modifican aquí.
     *
     * @param array $datos fecha_visita, hora_entrada, hora_salida, motivo_visita, observaciones
     * @throws Exception si la operación falla
     */
    public function actualizar(int $id_visita, array $datos, array $departamentos): void
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "UPDATE visita
                 SET fecha_visita  = :fecha_visita,
                     hora_entrada  = :hora_entrada,
                     hora_salida   = :hora_salida,
                     motivo_visita = :motivo_visita,
                     observaciones = :observaciones
                 WHERE id_visita = :id"
            );
            $stmt->execute([
                ':fecha_visita'  => $datos['fecha_visita'],
                ':hora_entrada'  => $datos['hora_entrada'],
                ':hora_salida'   => $datos['hora_salida'] ?? null,
                ':motivo_visita' => $datos['motivo_visita'],
                ':observaciones' => $datos['observaciones'] ?? null,
                ':id'            => $id_visita,
            ]);

            $del = $this->db->prepare("DELETE FROM visita_departamento WHERE id_visita = :id");
            $del->execute([':id' => $id_visita]);
            $this->asignarDepartamentos($id_visita, $departamentos);

            $this->db->commit();
        } catch (Throwable $excepcion) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Error al actualizar visita: ' . $excepcion->getMessage());
            throw new Exception('No se pudo actualizar la visita.');
        }
    }

    /**
     * Elimina una visita (sus departamentos se borran en cascada).
     * Solo el administrador llega aquí (ver VisitaEdicionController::eliminar).
     */
    public function eliminar(int $id_visita): bool
    {
        $stmt = $this->db->prepare("DELETE FROM visita WHERE id_visita = :id");
        $stmt->execute([':id' => $id_visita]);
        return $stmt->rowCount() === 1;
    }
}
