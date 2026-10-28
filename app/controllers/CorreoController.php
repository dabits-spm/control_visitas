<?php
// app/controllers/CorreoController.php
// «Configuración de correo» (solo administrador): datos de la cuenta SMTP, correo de
// prueba y envío inmediato de los recordatorios y avisos pendientes.

require_once __DIR__ . '/../utilidades/ConfiguracionCorreo.php';
require_once __DIR__ . '/../utilidades/RecordatorioCitas.php';
require_once __DIR__ . '/../servicios/EnvioCorreos.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Auditoria.php';

class CorreoController extends Controlador
{
    public function __construct()
    {
        $this->verificarSesion();
        $this->exigirPermiso('correo.configurar', 'dashboard');
    }

    /** GET /correo */
    public function index(): void
    {
        $visitas = new Visita();
        $cuenta  = (new Usuario())->obtenerPorId((int) $_SESSION['usuario']['id_usuario']);

        $datos = [
            'paginaActiva'    => 'correo',
            'config'          => ConfiguracionCorreo::actual(),
            'cifrados'        => ConfiguracionCorreo::CIFRADOS,
            'problemas'       => CorreoSmtp::problemas(),
            'sePuedeEscribir' => ConfiguracionCorreo::sePuedeEscribir(),
            'old'             => $_SESSION['old'] ?? null,
            'erroresCampos'   => $_SESSION['errores_campos'] ?? [],
            'detallePrueba'   => $_SESSION['detalle_prueba'] ?? [],
            'correoPrueba'    => $_SESSION['correo_prueba'] ?? ($cuenta['correo'] ?? ''),
            'proximas'        => $visitas->citasProximas(time()),
            'sinAviso'        => array_values(array_filter($visitas->citasSinAviso(date('Y-m-d')),
                static fn ($v) => strtotime($v['fecha_visita'] . ' ' . $v['hora_entrada']) > time())),
            'ultimaRevision'  => RecordatorioCitas::ultimaRevision(),
        ];
        unset($_SESSION['old'], $_SESSION['errores_campos'], $_SESSION['detalle_prueba'], $_SESSION['correo_prueba']);

        $this->render('correo/index', array_merge($datos, $this->tomarMensajes()));
    }

    /** POST /correo/guardar */
    public function guardar(): void
    {
        $this->validarCsrf();
        $datos   = ConfiguracionCorreo::normalizar($_POST);
        $errores = ConfiguracionCorreo::validar($datos);
        if ($datos['password'] === '' && CORREO_CLAVE === '') {
            $errores['password'] = 'Escriba la contraseña de aplicación de la cuenta.';
        }
        if ($errores) {
            $_SESSION['errores_campos'] = $errores;
            $_SESSION['errores'] = ['No se guardó la configuración. Revise los datos marcados en rojo.'];
            $old = $datos;
            unset($old['password']);   // la contraseña nunca se devuelve al formulario
            $_SESSION['old'] = $old;
            $this->redirigir('correo');
        }

        try {
            ConfiguracionCorreo::guardar($datos);
        } catch (RuntimeException $e) {
            $_SESSION['errores'] = [$e->getMessage()];
            $this->redirigir('correo');
        }

        $this->registrar('CONFIGURAR_CORREO', "Configuración de correo guardada: {$datos['username']} en {$datos['host']}:{$datos['port']}"
            . ($datos['password'] !== '' ? ' (contraseña cambiada)' : '') . ($datos['habilitado'] ? '' : ' · envío desactivado'));
        $_SESSION['flash_ok'] = $datos['habilitado']
            ? 'Configuración guardada. Envíe un correo de prueba para comprobar que funciona.'
            : 'Configuración guardada. El envío de correos quedó desactivado.';
        $this->redirigir('correo');
    }

    /** POST /correo/probar -> envía un correo de prueba */
    public function probar(): void
    {
        $this->validarCsrf();
        $destino = mb_substr(trim(entradaTexto($_POST, 'destino')), 0, 120);
        $_SESSION['correo_prueba'] = $destino;

        if (!filter_var($destino, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['errores'] = ['Escriba un correo válido para enviar la prueba.'];
            $this->redirigir('correo');
        }
        if (!CorreoSmtp::estaConfigurado()) {
            $_SESSION['errores'] = ['No se envió la prueba: ' . CorreoSmtp::motivoNoConfigurado()];
            $this->redirigir('correo');
        }

        [$error, $conversacion] = EnvioCorreos::prueba($destino, (int) $_SESSION['usuario']['id_usuario']);
        if ($error === null) {
            $_SESSION['flash_ok'] = "¡Listo! El correo de prueba salió hacia {$destino}. Revise la bandeja de entrada "
                . '(y la carpeta de spam). Desde ahora los avisos y recordatorios se envían solos.';
        } else {
            $_SESSION['errores'] = ['No se pudo enviar la prueba: ' . $error];
            $_SESSION['detalle_prueba'] = $conversacion;
        }
        $this->redirigir('correo');
    }

    /** POST /correo/recordatorios -> envía ya los recordatorios de las citas de las próximas 24 horas */
    public function recordatorios(): void
    {
        $this->validarCsrf();
        $r = RecordatorioCitas::procesar(50, true, (int) $_SESSION['usuario']['id_usuario']);

        if (!$r['revisado']) {
            $_SESSION['errores'] = [$r['motivo']];
        } elseif ($r['enviados'] === 0 && $r['fallidos'] === 0) {
            $_SESSION['flash_ok'] = 'No hay recordatorios pendientes: todas las citas de las próximas 24 horas ya lo recibieron.';
        } else {
            $mensaje = $r['enviados'] === 1 ? 'Se envió 1 recordatorio.' : "Se enviaron {$r['enviados']} recordatorios.";
            if ($r['fallidos']) {
                $_SESSION['errores'] = array_merge(["No se pudieron enviar {$r['fallidos']}:"], array_slice($r['errores'], 0, 5));
            }
            if ($r['enviados']) {
                $_SESSION['flash_ok'] = $mensaje;
            }
        }
        $this->redirigir('correo');
    }

    /** POST /correo/avisos -> envía los avisos de las citas que se registraron sin poder avisar */
    public function avisos(): void
    {
        $this->validarCsrf();
        if (!CorreoSmtp::estaConfigurado()) {
            $_SESSION['errores'] = ['No se enviaron los avisos: ' . CorreoSmtp::motivoNoConfigurado()];
            $this->redirigir('correo');
        }

        [$enviados, $errores] = EnvioCorreos::avisosPendientes((int) $_SESSION['usuario']['id_usuario']);

        if ($enviados) {
            $_SESSION['flash_ok'] = $enviados === 1 ? 'Se envió 1 aviso pendiente.' : "Se enviaron {$enviados} avisos pendientes.";
        } elseif (!$errores) {
            $_SESSION['flash_ok'] = 'No hay avisos pendientes.';
        }
        if ($errores) {
            $_SESSION['errores'] = array_merge(['Algunos avisos no se pudieron enviar:'], array_slice($errores, 0, 5));
        }
        $this->redirigir('correo');
    }

    private function registrar(string $accion, string $descripcion): void
    {
        (new Auditoria())->registrar($accion, mb_substr($descripcion, 0, 255), 'correo', (int) $_SESSION['usuario']['id_usuario']);
    }
}
