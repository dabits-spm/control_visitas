<?php
// app/controllers/UsuarioController.php
// Gestión de Usuarios (solo administrador): listado, alta y edición de los recepcionistas.
// Contraseña, baja/reactivación y eliminación están en UsuarioAccesoController.

require_once __DIR__ . '/traits/GestionaRecepcionistas.php';

class UsuarioController extends Controlador
{
    use GestionaRecepcionistas;

    private const POR_PAGINA = 20;
    /** Las contraseñas nunca se devuelven al formulario. */
    private const CLAVES = ['clave_nueva', 'clave_confirmar'];
    private const FILTRO_ESTADOS = ['activos' => 'Activos', 'inactivos' => 'Dados de baja'];

    /** GET /usuarios?q=&estado=activos|inactivos&pagina=N -> listado de recepcionistas */
    public function index(): void
    {
        $texto  = mb_substr(trim(entradaTexto($_GET, 'q')), 0, 100);
        $estado = entradaTexto($_GET, 'estado');
        $estado = isset(self::FILTRO_ESTADOS[$estado]) ? $estado : '';

        $total = $this->modelo->contarRecepcionistas($texto, $estado);
        $pag   = paginacion($total, (int) entradaTexto($_GET, 'pagina'), self::POR_PAGINA);

        $this->render('usuarios/index', array_merge([
            'paginaActiva'  => 'usuarios',
            'usuarios'      => $this->modelo->listarRecepcionistas($texto, $estado, $pag['porPagina'], $pag['desplazamiento']),
            'paginacion'    => $pag,
            'filtros'       => ['q' => $texto, 'estado' => $estado],
            'filtroEstados' => self::FILTRO_ESTADOS,
            'hayUsuarios'   => $total > 0 || $texto !== '' || $estado !== '',
        ], $this->tomarMensajes()));
    }

    /** GET /usuarios/crear -> formulario de nuevo recepcionista */
    public function crear(): void
    {
        $this->mostrarFormulario(null);
    }

    /** GET /usuarios/editar?id=N -> formulario con los datos del recepcionista */
    public function editar(): void
    {
        $this->mostrarFormulario($this->obtenerRecepcionista((int) entradaTexto($_GET, 'id')));
    }

    private function mostrarFormulario(?array $cuenta): void
    {
        $datos = [
            'paginaActiva' => 'usuarios',
            'tituloPagina' => $cuenta ? 'Editar recepcionista' : 'Nuevo recepcionista',
            'cuenta'       => $cuenta,
            'errores'      => $_SESSION['errores'] ?? [],
            'old'          => $_SESSION['old'] ?? ($cuenta ?? []),
            'reglasClave'  => Usuario::REGLAS_CLAVE,
        ];
        unset($_SESSION['errores'], $_SESSION['old']);

        $this->render('usuarios/formulario', $datos);
    }

    /** POST /usuarios/guardar -> crea la cuenta de un recepcionista */
    public function guardar(): void
    {
        $this->validarCsrf();

        $datos   = Usuario::normalizar($_POST);
        $errores = Usuario::validar($datos);
        $errores += $this->validarClaves($datos['user']);

        // Cédula, correo o usuario ya usados por otra cuenta (se informa junto con los demás errores)
        $errores += $this->modelo->buscarDuplicados($datos);
        if ($errores) {
            $this->volverConErrores('usuarios/crear', $errores, self::CLAVES);
        }

        try {
            $id = $this->modelo->crear($datos + [
                'password' => entradaTexto($_POST, 'clave_nueva'),
                'id_rol'   => Usuario::ROL_RECEPCIONISTA,
            ]);
        } catch (Exception $excepcion) {
            $this->volverConErrores('usuarios/crear', ['general' => $excepcion->getMessage()], self::CLAVES);
        }

        $this->registrar('CREAR', "Recepcionista #{$id} creado: {$datos['nombre']} {$datos['apellido']} (usuario {$datos['user']})");
        $_SESSION['flash_ok'] = "Cuenta de {$datos['nombre']} {$datos['apellido']} creada. Ya puede iniciar sesión con el usuario «{$datos['user']}».";
        $this->redirigir('usuarios');
    }

    /** POST /usuarios/actualizar -> guarda los datos personales y el nombre de usuario */
    public function actualizar(): void
    {
        $this->validarCsrf();
        $cuenta = $this->obtenerRecepcionista((int) entradaTexto($_POST, 'id_usuario'));
        $id     = (int) $cuenta['id_usuario'];
        $volver = 'usuarios/editar?id=' . $id;

        $datos   = Usuario::normalizar($_POST);
        $errores = Usuario::validar($datos);
        $errores += $this->modelo->buscarDuplicados($datos, $id);
        if ($errores) {
            $this->volverConErrores($volver, $errores, self::CLAVES);
        }

        try {
            $this->modelo->actualizar($id, $datos);
        } catch (Exception $excepcion) {
            $this->volverConErrores($volver, ['general' => $excepcion->getMessage()], self::CLAVES);
        }

        $cambios = Visitante::camposModificados($cuenta, $datos, ['cedula', 'nombre', 'apellido', 'telefono', 'correo']);
        if ($cuenta['user'] !== $datos['user']) {
            $cambios[] = 'usuario';
        }
        $this->registrar('EDITAR', "Recepcionista #{$id} ({$cuenta['user']}) editado"
            . ($cambios ? ': ' . implode(', ', $cambios) : ' sin cambios'));
        $_SESSION['flash_ok'] = "Datos de {$datos['nombre']} {$datos['apellido']} actualizados.";
        $this->redirigir('usuarios');
    }
}
