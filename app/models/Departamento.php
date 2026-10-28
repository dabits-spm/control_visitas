<?php
// app/models/Departamento.php

class Departamento
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConexion();
    }

    /** Lista todos los departamentos ordenados alfabéticamente */
    public function listar(): array
    {
        $sql = "SELECT id_departamento, nombre_departamento
                FROM departamento
                ORDER BY nombre_departamento ASC";

        return $this->db->query($sql)->fetchAll();
    }

    /** Busca un departamento específico por su ID */
    public function obtenerPorId(int $id_departamento): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id_departamento, nombre_departamento
             FROM departamento
             WHERE id_departamento = :id"
        );
        $stmt->execute([':id' => $id_departamento]);
        $fila = $stmt->fetch();

        return $fila ?: null;
    }

    /** Registra un nuevo departamento */
    public function crear(string $nombre): int
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO departamento (nombre_departamento) VALUES (:nombre)"
            );
            $stmt->execute([':nombre' => $nombre]);

            return (int) $this->db->lastInsertId();
        } catch (PDOException $excepcion) {
            // Controla el error de duplicidad (clave única en la base de datos)
            if ($excepcion->getCode() === '23000') {
                throw new Exception('Ya existe un departamento con ese nombre.');
            }
            error_log('Error al crear departamento: ' . $excepcion->getMessage());
            throw new Exception('No se pudo registrar el departamento.');
        }
    }

    /** Actualiza el nombre de un departamento existente */
    public function actualizar(int $id_departamento, string $nombre): bool
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE departamento
                 SET nombre_departamento = :nombre
                 WHERE id_departamento = :id"
            );

            return $stmt->execute([':nombre' => $nombre, ':id' => $id_departamento]);
        } catch (PDOException $excepcion) {
            if ($excepcion->getCode() === '23000') {
                throw new Exception('Ya existe un departamento con ese nombre.');
            }
            error_log('Error al actualizar departamento: ' . $excepcion->getMessage());
            throw new Exception('No se pudo actualizar el departamento.');
        }
    }

    /** Elimina un departamento por su ID */
    public function eliminar(int $id_departamento): bool
    {
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM departamento WHERE id_departamento = :id"
            );

            return $stmt->execute([':id' => $id_departamento]);
        } catch (PDOException $excepcion) {
            // Si hay restricción de integridad (ON DELETE RESTRICT), fallará si tiene visitas asociadas
            error_log('Error al eliminar departamento: ' . $excepcion->getMessage());
            return false;
        }
    }
}