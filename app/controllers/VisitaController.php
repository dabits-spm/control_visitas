<?php
// app/controllers/VisitaController.php
// Consultas de visitas: historial, visitas programadas, formulario de registro y
// búsqueda de un visitante por cédula. Las acciones están en:
//   VisitaRegistroController (registrar y reenviar el aviso),
//   VisitaEstadoController   (cambiar estado y observaciones),
//   VisitaEdicionController  (editar y eliminar, solo administrador).

require_once __DIR__ . '/../models/Visita.php';
require_once __DIR__ . '/../models/Visitante.php';
require_once __DIR__ . '/../models/Departamento.php';
require_once __DIR__ . '/../utilidades/CorreoSmtp.php';

class VisitaController extends Controlador
{
    /** Visitas por página en el historial y en las visitas programadas */
    private const POR_PAGINA = 20;

    /** Filtro de estado del historial: valor en la URL => id del estado */
    public const FILTRO_ESTADOS = [
        'en-proceso' => Visita::ESTADO_EN_PROCESO,
        'finalizada' => Visita::ESTADO_FINALIZADA,
        'cancelada'  => Visita::ESTADO_CANCELADA,
    ];

    private Visita $modeloVisita;

    public function __construct()
    {
        $this->verificarSesion();
        $this->modeloVisita = new Visita();
    }

    /**
     * GET /visitas?q=texto&estado=finalizada&tipo=programada&pagina=2  -> historial de visitas
     * Muestra las visitas que ya ocurrieron (en proceso, finalizadas o canceladas),
     * de la más reciente a la más antigua. Las citas pendientes están en /visitas/programadas.
     */
    public function index(): void
    {
        $texto  = mb_substr(trim(entradaTexto($_GET, 'q')), 0, 100);
        $estado = entradaTexto($_GET, 'estado');
        $estado = isset(self::FILTRO_ESTADOS[$estado]) ? $estado : '';
        $tipo   = entradaTexto($_GET, 'tipo');
        $tipo   = isset(Visita::TIPOS[$tipo]) ? $tipo : '';
        $idEstado = self::FILTRO_ESTADOS[$estado] ?? 0;

        $total = $this->modeloVisita->contarListado(Visita::LISTADO_HISTORIAL, $texto, $idEstado, $tipo);
        $pag   = paginacion($total, (int) entradaTexto($_GET, 'pagina'), self::POR_PAGINA);

        $this->render('visitas/index', array_merge([
            'paginaActiva' => 'visitas',
            'visitas'      => $this->modeloVisita->listar(Visita::LISTADO_HISTORIAL, $texto, $idEstado, $tipo,
                                                          $pag['porPagina'], $pag['desplazamiento']),
            'paginacion'   => $pag,
            'filtros'      => ['q' => $texto, 'estado' => $estado, 'tipo' => $tipo],
            'hayVisitas'   => $total > 0 || $texto !== '' || $estado !== '' || $tipo !== '',
            'correoActivo' => CorreoSmtp::estaConfigurado(),
        ], $this->tomarMensajes()));
    }

    /** GET /visitas/programadas?q=texto&pagina=N  -> citas pendientes (programadas o reprogramadas) */
    public function programadas(): void
    {
        $texto = mb_substr(trim(entradaTexto($_GET, 'q')), 0, 100);
        $total = $this->modeloVisita->contarListado(Visita::LISTADO_PROGRAMADAS, $texto);
        $pag   = paginacion($total, (int) entradaTexto($_GET, 'pagina'), self::POR_PAGINA);

        $this->render('visitas/programadas', array_merge([
            'paginaActiva' => 'programadas',
            'visitas'      => $this->modeloVisita->listar(Visita::LISTADO_PROGRAMADAS, $texto, 0, '',
                                                          $pag['porPagina'], $pag['desplazamiento']),
            'paginacion'   => $pag,
            'filtros'      => ['q' => $texto],
            'hayVisitas'   => $total > 0 || $texto !== '',
            'correoActivo' => CorreoSmtp::estaConfigurado(),
        ], $this->tomarMensajes()));
    }

    /** GET /visitas/crear  -> formulario de registro */
    public function crear(): void
    {
        $datos = [
            'paginaActiva'  => 'crear',
            'departamentos' => (new Departamento())->listar(),
            'csrf'          => $this->generarCsrf(),
            'errores'       => $_SESSION['errores'] ?? [],
            // Sin datos previos, se puede llegar con la cédula o el tipo ya elegidos
            // (desde la ficha del visitante o desde "Visitas programadas")
            'old'           => $_SESSION['old'] ?? array_filter([
                'cedula'      => mb_substr(entradaTexto($_GET, 'cedula'), 0, 15),
                'tipo_visita' => entradaTexto($_GET, 'tipo') === Visita::TIPO_PROGRAMADA ? Visita::TIPO_PROGRAMADA : '',
            ]),
            'correoActivo'  => CorreoSmtp::estaConfigurado(),
        ];
        unset($_SESSION['errores'], $_SESSION['old']);

        $this->render('visitas/crear', $datos);
    }

    /**
     * GET /visitantes/buscar?cedula=...  -> JSON
     * Si la cédula ya está registrada, el formulario se autocompleta con sus datos.
     */
    public function buscarVisitante(): void
    {
        $cedula = Visitante::normalizarCedula(entradaTexto($_GET, 'cedula'));

        if (!preg_match('/^[VE]-\d{6,10}$/', $cedula)) {
            $this->responderJson(['encontrado' => false]);
        }

        $visitante = (new Visitante())->buscarPorCedula($cedula);

        if (!$visitante) {
            $this->responderJson(['encontrado' => false, 'cedula' => $cedula]);
        }

        $this->responderJson([
            'encontrado' => true,
            'cedula'     => $cedula,
            // Quien no puede editar visitantes solo puede cambiar los datos de contacto
            'puedeEditarNombre' => puede('visitantes.editar'),
            'visitante'  => [
                'nombre'      => $visitante['nombre'],
                'apellido'    => $visitante['apellido'],
                'telefono'    => $visitante['telefono'],
                'correo'      => $visitante['correo'] ?? '',
                'procedencia' => $visitante['procedencia'] ?? '',
            ],
        ]);
    }
}
