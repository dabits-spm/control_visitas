<?php
// app/controllers/AuthController.php
// Inicio y cierre de sesión, y consulta del estado de la sesión (botones Atrás/Adelante).
// «Mi perfil» y «Cambiar contraseña» están en CuentaController.

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Auditoria.php';

class AuthController extends Controlador
{
    /** GET /login -> muestra el formulario */
    public function mostrarLogin(): void
    {
        // Si ya inició sesión, no tiene sentido mostrar el login de nuevo
        if (!empty($_SESSION['usuario'])) {
            $this->redirigir('dashboard');
        }

        $csrf  = $this->generarCsrf();
        $error = $_SESSION['login_error'] ?? null;
        $mensajeSalida = isset($_GET['salida']) ? 'Cerró la sesión correctamente.' : null;
        // La sesión se cerró sola porque el administrador cambió la cuenta
        $motivos = [
            'baja'  => 'Su cuenta fue dada de baja. Comuníquese con un administrador.',
            'clave' => 'Su contraseña fue cambiada. Inicie sesión con la contraseña nueva.',
        ];
        $error ??= $motivos[entradaTexto($_GET, 'motivo')] ?? null;
        $usuarioEscrito = $_SESSION['login_usuario'] ?? '';
        unset($_SESSION['login_error'], $_SESSION['login_usuario']);

        require APP_PATH . '/views/auth/login.php';
    }

    /** POST /login -> valida credenciales */
    public function autenticar(): void
    {
        // Valida el token de seguridad contra ataques CSRF
        $this->validarCsrf();

        $nombre_usuario = trim(entradaTexto($_POST, 'user'));
        $contrasena     = entradaTexto($_POST, 'password');
        $_SESSION['login_usuario'] = $nombre_usuario;

        // Validación de campos vacíos
        if ($nombre_usuario === '' || $contrasena === '') {
            $_SESSION['login_error'] = 'Ingrese su usuario y contraseña.';
            $this->redirigir('login');
        }

        try {
            // Intenta autenticar usando el modelo de Usuario
            $usuario = (new Usuario())->autenticar($nombre_usuario, $contrasena);
        } catch (Throwable $excepcion) {
            error_log('Error en login: ' . $excepcion->getMessage());
            $_SESSION['login_error'] = 'No se pudo procesar el acceso. Intente nuevamente.';
            $this->redirigir('login');
        }

        // Mismo mensaje si no existe o la clave es incorrecta (por seguridad)
        if ($usuario === null) {
            $_SESSION['login_error'] = 'Usuario o contraseña incorrectos.';
            $this->redirigir('login');
        }

        // Seguridad: regenera el ID de sesión para evitar ataques de fijación
        session_regenerate_id(true);
        unset($_SESSION['csrf'], $_SESSION['login_usuario']);
        $usuario['user'] = $nombre_usuario;
        $_SESSION['usuario'] = $usuario;
        // Al entrar se muestran las notificaciones de citas y tareas pendientes del día
        $_SESSION['notificaciones_inicio'] = true;

        // Registra el evento exitoso en la auditoría
        (new Auditoria())->registrar(
            'LOGIN',
            'Inicio de sesión de ' . $nombre_usuario,
            'usuario',
            (int) $usuario['id_usuario']
        );

        $this->redirigir('dashboard');
    }

    /**
     * GET /sesion/estado -> JSON {"activa": true|false}
     * La usa 01-sesion-barra.js cuando una página se vuelve a mostrar con los botones
     * "Atrás" o "Adelante": si la sesión ya no existe, envía al login.
     */
    public function estadoSesion(): void
    {
        // Además de existir, la cuenta debe seguir activa y con la misma contraseña
        $activa = !empty($_SESSION['usuario']) && $this->revisarCuenta() === null;
        $this->responderJson(['activa' => $activa]);
    }

    /** GET /logout -> ya no cierra la sesión (se hace por POST desde el modal); solo redirige. */
    public function salirGet(): void
    {
        $this->redirigir(empty($_SESSION['usuario']) ? 'login' : 'dashboard');
    }

    /** POST /logout -> cierra la sesión (formulario del modal "Cerrar sesión", con token CSRF) */
    public function salir(): void
    {
        if (empty($_SESSION['usuario'])) {
            $this->redirigir('login');
        }
        $this->validarCsrf();

        // Registra el cierre de sesión en la auditoría antes de borrar los datos
        (new Auditoria())->registrar(
            'LOGOUT',
            'Cierre de sesión',
            'usuario',
            (int) $_SESSION['usuario']['id_usuario']
        );

        // Limpia todas las variables de sesión
        $_SESSION = [];

        // Borra la cookie de sesión del navegador
        if (ini_get('session.use_cookies')) {
            $parametros_cookie = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $parametros_cookie['path'], $parametros_cookie['domain'],
                $parametros_cookie['secure'], $parametros_cookie['httponly']
            );
        }

        session_destroy();

        // Las páginas internas se envían con "no-store" (public/index.php): si el usuario
        // pulsa "Atrás", el navegador las vuelve a pedir y el servidor lo envía al login.
        $this->redirigir('login?salida=1');
    }
}
