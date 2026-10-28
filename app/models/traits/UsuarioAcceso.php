<?php
// app/models/traits/UsuarioAcceso.php

/**
 * Usuario: contraseña, alta/baja y eliminación de una cuenta.
 */
trait UsuarioAcceso
{
    /** Comprueba la contraseña de un usuario activo (para confirmar cambios sensibles). */
    public function verificarClave(int $id_usuario, string $contrasena): bool
    {
        $stmt = $this->db->prepare("SELECT `password` FROM usuario WHERE id_usuario = :id AND estado = 1");
        $stmt->execute([':id' => $id_usuario]);
        $hash = $stmt->fetchColumn();

        return is_string($hash) && password_verify($contrasena, $hash);
    }

    /** Cambia y cifra la contraseña de un usuario. Devuelve la huella nueva (ver huellaClave). */
    public function cambiarPassword(int $id_usuario, string $nueva_contrasena): string
    {
        $hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE usuario SET `password` = :password WHERE id_usuario = :id");
        $stmt->execute([':password' => $hash, ':id' => $id_usuario]);

        return self::huellaClave($hash);
    }

    /**
     * Activa (1) o da de baja (0) un usuario sin borrarlo, para conservar
     * el historial de visitas y auditoría.
     */
    public function cambiarEstado(int $id_usuario, int $estado): bool
    {
        $stmt = $this->db->prepare("UPDATE usuario SET estado = :estado WHERE id_usuario = :id");

        return $stmt->execute([':estado' => $estado === 1 ? 1 : 0, ':id' => $id_usuario]);
    }

    /** Cantidad de visitas registradas por un usuario. */
    public function contarVisitas(int $id_usuario): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM visita WHERE id_usuario = :id");
        $stmt->execute([':id' => $id_usuario]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Elimina una cuenta que no registró visitas (las visitas guardan quién las registró).
     * Las cuentas con visitas se dan de baja con cambiarEstado().
     */
    public function eliminar(int $id_usuario): bool
    {
        if ($this->contarVisitas($id_usuario) > 0) {
            return false;
        }
        $stmt = $this->db->prepare("DELETE FROM usuario WHERE id_usuario = :id");
        $stmt->execute([':id' => $id_usuario]);
        return $stmt->rowCount() === 1;
    }
}
