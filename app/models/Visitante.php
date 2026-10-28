<?php
// app/models/Visitante.php
// Modelo de los visitantes: consultas. La validación y la escritura están en app/models/traits/.

require_once __DIR__ . '/traits/VisitanteValidacion.php';
require_once __DIR__ . '/traits/VisitanteEscritura.php';

class Visitante
{
    use VisitanteValidacion;   // normalizar, validar, camposModificados
    use VisitanteEscritura;    // crear, actualizar, actualizarContacto

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConexion();
    }

    /** Nacionalidades admitidas en la cédula: V (venezolano) y E (extranjero). */
    public const NACIONALIDADES = ['V' => 'Venezolano', 'E' => 'Extranjero'];

    /** Campos de contacto: los puede actualizar cualquier rol al registrar una visita. */
    public const CAMPOS_CONTACTO = ['telefono', 'correo', 'procedencia'];

    // ------------------------------------------------------------
    // Consultas
    // ------------------------------------------------------------

    /** Lista todos los visitantes ordenados por apellido y nombre */
    public function listar(): array
    {
        $sql = "SELECT id_visitante, cedula, nombre, apellido, telefono, correo, procedencia
                FROM visitante
                ORDER BY apellido ASC, nombre ASC";

        return $this->db->query($sql)->fetchAll();
    }

    /** Condición de búsqueda por texto (cédula, nombre, teléfono, correo o procedencia). */
    private function condicionBusqueda(string $texto, array &$parametros): string
    {
        if ($texto === '') {
            return '';
        }
        $parametros[':texto'] = '%' . addcslashes($texto, '%_\\') . '%';
        return "WHERE CONCAT_WS(' ', vi.cedula, vi.nombre, vi.apellido, vi.telefono, vi.correo, vi.procedencia) LIKE :texto";
    }

    /** Cantidad de visitantes que coinciden con la búsqueda. */
    public function contarBusqueda(string $texto): int
    {
        $parametros = [];
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM visitante vi " . $this->condicionBusqueda($texto, $parametros));
        $stmt->execute($parametros);
        return (int) $stmt->fetchColumn();
    }

    /** Una página del listado de visitantes con su cantidad de visitas y la fecha de la última. */
    public function listarConResumen(string $texto = '', int $limite = 25, int $desplazamiento = 0): array
    {
        $parametros = [];
        $donde = $this->condicionBusqueda($texto, $parametros);

        $stmt = $this->db->prepare(
            "SELECT vi.id_visitante, vi.cedula, vi.nombre, vi.apellido, vi.telefono,
                    vi.correo, vi.procedencia,
                    (SELECT COUNT(*)            FROM visita v WHERE v.id_visitante = vi.id_visitante) AS total_visitas,
                    (SELECT MAX(v.fecha_visita) FROM visita v WHERE v.id_visitante = vi.id_visitante) AS ultima_visita
             FROM visitante vi
             {$donde}
             ORDER BY vi.apellido ASC, vi.nombre ASC
             LIMIT :limite OFFSET :desplazamiento"
        );
        foreach ($parametros as $nombre => $valor) {
            $stmt->bindValue($nombre, $valor);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':desplazamiento', $desplazamiento, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Cantidad total de visitantes registrados */
    public function contar(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM visitante")->fetchColumn();
    }

    /** Busca un visitante específico por su ID */
    public function obtenerPorId(int $id_visitante): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id_visitante, cedula, nombre, apellido, telefono, correo, procedencia
             FROM visitante
             WHERE id_visitante = :id"
        );
        $stmt->execute([':id' => $id_visitante]);
        $fila = $stmt->fetch();

        return $fila ?: null;
    }

    /** Busca un visitante por su número de cédula */
    public function buscarPorCedula(string $cedula): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id_visitante, cedula, nombre, apellido, telefono, correo, procedencia
             FROM visitante
             WHERE cedula = :cedula"
        );
        $stmt->execute([':cedula' => $cedula]);
        $fila = $stmt->fetch();

        return $fila ?: null;
    }

    /** Cantidad de visitas registradas para un visitante. */
    public function contarVisitas(int $id_visitante): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM visita WHERE id_visitante = :id");
        $stmt->execute([':id' => $id_visitante]);
        return (int) $stmt->fetchColumn();
    }

    /** Elimina un visitante que no tiene visitas (la llave foránea impide borrar uno con visitas). */
    public function eliminar(int $id_visitante): bool
    {
        if ($this->contarVisitas($id_visitante) > 0) {
            return false;
        }
        $stmt = $this->db->prepare("DELETE FROM visitante WHERE id_visitante = :id");
        $stmt->execute([':id' => $id_visitante]);
        return $stmt->rowCount() === 1;
    }
}
