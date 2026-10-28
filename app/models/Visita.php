<?php
// app/models/Visita.php
// Modelo de las visitas. Esta clase reúne las reglas (estados, tipos, transiciones) y la
// consulta base; las operaciones están separadas por tema en app/models/traits/.

require_once __DIR__ . '/traits/VisitaEscritura.php';
require_once __DIR__ . '/traits/VisitaEstados.php';
require_once __DIR__ . '/traits/VisitaCitas.php';
require_once __DIR__ . '/traits/VisitaListados.php';
require_once __DIR__ . '/traits/VisitaEstadisticas.php';

class Visita
{
    use VisitaEscritura;      // crear, actualizar, eliminar
    use VisitaEstados;        // cambiar estado, observaciones, cierre automático
    use VisitaCitas;          // citas pendientes, avisos y recordatorios por correo
    use VisitaListados;       // historial, programadas, detalle
    use VisitaEstadisticas;   // panel de control y reportes

    // Estados fijos (deben coincidir con los datos semilla de ESTADO_VISITA)
    public const ESTADO_PROGRAMADA   = 1;
    public const ESTADO_EN_PROCESO   = 2;
    public const ESTADO_FINALIZADA   = 3;
    public const ESTADO_CANCELADA    = 4;
    public const ESTADO_REPROGRAMADA = 5;

    /** Clase CSS de cada estado (insignias y gráficos), en el orden en que se muestran. */
    public const COLORES_ESTADO = [
        self::ESTADO_PROGRAMADA   => 'programada',
        self::ESTADO_REPROGRAMADA => 'reprogramada',
        self::ESTADO_EN_PROCESO   => 'en-proceso',
        self::ESTADO_FINALIZADA   => 'finalizada',
        self::ESTADO_CANCELADA    => 'cancelada',
    ];

    /** Citas que todavía no ocurren: no aparecen en el historial. */
    public const ESTADOS_CITA = [self::ESTADO_PROGRAMADA, self::ESTADO_REPROGRAMADA];

    /** Visitas activas: el recepcionista puede cambiar su estado y sus observaciones. */
    public const ESTADOS_ACTIVOS = [self::ESTADO_PROGRAMADA, self::ESTADO_REPROGRAMADA, self::ESTADO_EN_PROCESO];

    /** Visitas cerradas: solo el administrador las modifica. */
    public const ESTADOS_CERRADOS = [self::ESTADO_FINALIZADA, self::ESTADO_CANCELADA];

    // Tipos de visita (deben coincidir con el ENUM de la columna visita.tipo_visita)
    public const TIPO_AL_MOMENTO = 'al_momento';   // el visitante ya llegó: inicia "En proceso"
    public const TIPO_PROGRAMADA = 'programada';   // cita a futuro: inicia "Programada"
    public const TIPOS = [
        self::TIPO_AL_MOMENTO => 'Al momento',
        self::TIPO_PROGRAMADA => 'Cita programada',
    ];

    /** Largo máximo de las observaciones. */
    public const MAX_OBSERVACIONES = 1000;

    /** Horas de anticipación del recordatorio por correo. */
    public const HORAS_RECORDATORIO = 24;

    /** Listados disponibles: el historial (sin citas pendientes) y las visitas programadas. */
    public const LISTADO_HISTORIAL   = 'historial';
    public const LISTADO_PROGRAMADAS = 'programadas';

    /**
     * Expresión SQL que da la fecha de inicio de cada periodo.
     * Lista blanca: nunca se arma SQL con el texto que envía el usuario.
     */
    private const INICIO_PERIODO = [
        'diario'  => "v.fecha_visita",
        'semanal' => "DATE_SUB(v.fecha_visita, INTERVAL WEEKDAY(v.fecha_visita) DAY)", // lunes
        'mensual' => "DATE_FORMAT(v.fecha_visita, '%Y-%m-01')",
    ];

    /**
     * Cambios de estado que puede hacer cualquier usuario:
     * [estado actual => [estado nuevo => texto de la acción]].
     * "Reprogramar" pide una nueva fecha y hora.
     */
    private const TRANSICIONES = [
        self::ESTADO_PROGRAMADA => [
            self::ESTADO_EN_PROCESO   => 'Iniciar visita (En proceso)',
            self::ESTADO_REPROGRAMADA => 'Reprogramar…',
            self::ESTADO_CANCELADA    => 'Cancelar visita',
        ],
        self::ESTADO_REPROGRAMADA => [
            self::ESTADO_EN_PROCESO   => 'Iniciar visita (En proceso)',
            self::ESTADO_REPROGRAMADA => 'Reprogramar de nuevo…',
            self::ESTADO_CANCELADA    => 'Cancelar visita',
        ],
        self::ESTADO_EN_PROCESO => [
            self::ESTADO_FINALIZADA => 'Finalizar visita',
            self::ESTADO_CANCELADA  => 'Cancelar visita',
        ],
        self::ESTADO_FINALIZADA => [],
        self::ESTADO_CANCELADA  => [],
    ];

    /** Cambios adicionales que solo puede hacer el administrador (sin restricciones). */
    private const TRANSICIONES_ADMINISTRADOR = [
        self::ESTADO_PROGRAMADA => [
            self::ESTADO_FINALIZADA => 'Marcar como finalizada',
        ],
        self::ESTADO_REPROGRAMADA => [
            self::ESTADO_FINALIZADA => 'Marcar como finalizada',
        ],
        self::ESTADO_FINALIZADA => [
            self::ESTADO_EN_PROCESO   => 'Reabrir (En proceso)',
            self::ESTADO_CANCELADA    => 'Marcar como cancelada',
            self::ESTADO_REPROGRAMADA => 'Reprogramar…',
        ],
        self::ESTADO_CANCELADA => [
            self::ESTADO_EN_PROCESO   => 'Reabrir (En proceso)',
            self::ESTADO_FINALIZADA   => 'Marcar como finalizada',
            self::ESTADO_REPROGRAMADA => 'Reprogramar…',
        ],
    ];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConexion();
    }

    /** Acciones disponibles para una visita según su estado y si quien la ve es administrador. */
    public static function transicionesDesde(int $id_estado, bool $esAdministrador = false): array
    {
        $acciones = self::TRANSICIONES[$id_estado] ?? [];
        if ($esAdministrador) {
            $acciones += self::TRANSICIONES_ADMINISTRADOR[$id_estado] ?? [];
        }
        return $acciones;
    }

    /** Indica si se puede pasar de un estado a otro. */
    public static function transicionValida(int $desde, int $hacia, bool $esAdministrador = false): bool
    {
        return isset(self::transicionesDesde($desde, $esAdministrador)[$hacia]);
    }

    public static function esActiva(int $id_estado): bool
    {
        return in_array($id_estado, self::ESTADOS_ACTIVOS, true);
    }

    public static function esCita(int $id_estado): bool
    {
        return in_array($id_estado, self::ESTADOS_CITA, true);
    }

    /** Observaciones para mostrar: "Ninguna" si están vacías. */
    public static function textoObservaciones(?string $observaciones): string
    {
        $observaciones = trim((string) $observaciones);
        return $observaciones === '' ? 'Ninguna' : $observaciones;
    }

    /** Columnas y uniones comunes a los listados. */
    private function consultaBase(): string
    {
        return "SELECT v.id_visita, v.fecha_visita, v.hora_entrada, v.hora_salida,
                       v.motivo_visita, v.observaciones, v.tipo_visita, v.id_estado,
                       v.cierre_automatico, v.aviso_enviado, v.recordatorio_enviado, v.id_visitante,
                       CONCAT(vi.nombre, ' ', vi.apellido) AS visitante,
                       vi.nombre AS visitante_nombre, vi.apellido AS visitante_apellido,
                       vi.cedula, vi.telefono, vi.correo,
                       e.nombre_estado AS estado,
                       CONCAT(u.nombre, ' ', u.apellido) AS registrado_por,
                       GROUP_CONCAT(d.nombre_departamento ORDER BY d.nombre_departamento
                                    SEPARATOR ', ') AS departamentos
                FROM visita v
                INNER JOIN visitante vi           ON vi.id_visitante = v.id_visitante
                INNER JOIN estado_visita e        ON e.id_estado = v.id_estado
                INNER JOIN usuario u              ON u.id_usuario = v.id_usuario
                LEFT  JOIN visita_departamento vd ON vd.id_visita = v.id_visita
                LEFT  JOIN departamento d         ON d.id_departamento = vd.id_departamento";
    }
}
