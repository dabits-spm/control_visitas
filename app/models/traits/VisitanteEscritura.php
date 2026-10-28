<?php
// app/models/traits/VisitanteEscritura.php

/**
 * Visitante: registrar y actualizar los datos de un visitante.
 */
trait VisitanteEscritura
{
    /**
     * @param array $datos cedula, nombre, apellido, telefono, correo, procedencia
     * @return int ID del visitante creado
     */
    public function crear(array $datos): int
    {
        try {
            $sql = "INSERT INTO visitante (cedula, nombre, apellido, telefono, correo, procedencia)
                    VALUES (:cedula, :nombre, :apellido, :telefono, :correo, :procedencia)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':cedula'      => $datos['cedula'],
                ':nombre'      => $datos['nombre'],
                ':apellido'    => $datos['apellido'],
                ':telefono'    => $datos['telefono'],
                ':correo'      => $datos['correo'],
                ':procedencia' => $datos['procedencia'] ?? '',
            ]);

            return (int) $this->db->lastInsertId();
        } catch (PDOException $excepcion) {
            if ($excepcion->getCode() === '23000') {
                throw new Exception('Ya existe un visitante registrado con esa cédula.');
            }
            error_log('Error al crear visitante: ' . $excepcion->getMessage());
            throw new Exception('No se pudo registrar el visitante.');
        }
    }

    /** Actualiza solo los datos de contacto (teléfono, correo y procedencia). */
    public function actualizarContacto(int $id_visitante, array $datos): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE visitante
             SET telefono = :telefono, correo = :correo, procedencia = :procedencia
             WHERE id_visitante = :id"
        );
        return $stmt->execute([
            ':telefono'    => $datos['telefono'],
            ':correo'      => $datos['correo'],
            ':procedencia' => $datos['procedencia'] ?? '',
            ':id'          => $id_visitante,
        ]);
    }

    /** Actualiza los datos de un visitante existente */
    public function actualizar(int $id_visitante, array $datos): bool
    {
        try {
            $sql = "UPDATE visitante
                    SET cedula = :cedula,
                        nombre = :nombre,
                        apellido = :apellido,
                        telefono = :telefono,
                        correo = :correo,
                        procedencia = :procedencia
                    WHERE id_visitante = :id";

            $stmt = $this->db->prepare($sql);

            return $stmt->execute([
                ':cedula'      => $datos['cedula'],
                ':nombre'      => $datos['nombre'],
                ':apellido'    => $datos['apellido'],
                ':telefono'    => $datos['telefono'],
                ':correo'      => $datos['correo'],
                ':procedencia' => $datos['procedencia'] ?? '',
                ':id'          => $id_visitante,
            ]);
        } catch (PDOException $excepcion) {
            if ($excepcion->getCode() === '23000') {
                throw new Exception('Ya existe otro visitante con esa cédula.');
            }
            error_log('Error al actualizar visitante: ' . $excepcion->getMessage());
            throw new Exception('No se pudo actualizar el visitante.');
        }
    }
}
