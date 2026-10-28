<?php
// app/models/Auditoria.php

class Auditoria
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConexion();
    }

    /**
     * Registro simple de auditoría. Se invoca desde los controladores.
     *
     * Ejemplo:
     *   (new Auditoria())->registrar('CREAR', 'Visita #15 registrada', 'visita', $idUsuario);
     */
    public function registrar(string $accion, string $descripcion, string $tablaAfectada, int $idUsuario): bool
    {
        try {
            $sql = "INSERT INTO auditoria (accion, descripcion, tabla_afectada, fecha_hora, id_usuario)
                    VALUES (:accion, :descripcion, :tabla, NOW(), :id_usuario)";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':accion'      => $accion,
                ':descripcion' => $descripcion,
                ':tabla'       => $tablaAfectada,
                ':id_usuario'  => $idUsuario,
            ]);
        } catch (PDOException $e) {
            // La auditoría nunca debe romper el flujo principal
            error_log('Error al registrar auditoría: ' . $e->getMessage());
            return false;
        }
    }
}
