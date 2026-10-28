<?php
// Plantilla: barra lateral, barra superior y apertura del contenido de las páginas internas.
// Variables: $paginaActiva ('dashboard' | 'visitas' | 'programadas' | 'crear' | 'visitantes' | 'reportes' | 'usuarios' | 'perfil' | 'clave'),
//            $tituloPagina (opcional), $csrf, $citasPendientes
$usuarioSesion = $_SESSION['usuario'] ?? null;
$paginaActiva  = $paginaActiva ?? '';
$titulos = [
    'dashboard'   => 'Panel de Control',
    'visitas'     => 'Historial de Visitas',
    'programadas' => 'Visitas Programadas',
    'crear'       => 'Registrar Visita',
    'visitantes'  => 'Visitantes',
    'reportes'    => 'Reportes de Visitas',
    'clave'       => 'Cambiar contraseña',
    'perfil'      => 'Mi perfil',
    'usuarios'    => 'Gestión de Usuarios',
    'notificaciones' => 'Notificaciones',
    'correo'      => 'Configuración de correo',
];
$resumenNotificaciones ??= ['alertas' => 0, 'urgentes' => 0, 'seguimiento' => 0, 'tareas' => 0, 'total' => 0];
$notificacionesPendientes ??= [];
$tituloPagina = $tituloPagina ?? ($titulos[$paginaActiva] ?? 'Inicio');

// Opciones de la barra lateral, en este orden
$opcionesMenu = [
    ['dashboard',   'dashboard',           'Panel de Control',     '<path d="M3 3h7v9H3zM14 3h7v5h-7zM14 12h7v9h-7zM3 16h7v5H3z"/>'],
    ['visitas',     'visitas',             'Historial de Visitas', '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l4 2"/>'],
    ['programadas', 'visitas/programadas', 'Visitas Programadas',  '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/>'],
    ['visitantes',  'visitantes',          'Visitantes',           '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>'],
    ['reportes',    'reportes',            'Reportes',             '<path d="M3 3v18h18"/><path d="M7 16v-4M12 16V8M17 16v-7"/>'],
    ['notificaciones', 'notificaciones',   'Notificaciones',       '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/>'],
];
// Solo el administrador ve (y puede usar) la gestión de usuarios
if (puede('usuarios.gestionar')) {
    $opcionesMenu[] = ['usuarios', 'usuarios', 'Gestión de Usuarios',
        '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>'];
}
if (puede('correo.configurar')) {
    $opcionesMenu[] = ['correo', 'correo', 'Configuración de correo',
        '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>'];
}
// "Registrar visita" se considera parte del panel de control en el menú
$opcionActiva = $paginaActiva === 'crear' ? 'dashboard' : $paginaActiva;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tituloPagina) ?> - Control de Visitas</title>
    <?php foreach (hojasDeEstilo() as $hoja): ?>
    <link rel="stylesheet" href="<?= e($hoja) ?>">
    <?php endforeach; ?>
    <script>
        // Antes de dibujar la página: aplica la barra lateral contraída si el usuario la dejó así
        try {
            if (window.innerWidth > 960 && localStorage.getItem('cv_barra_lateral') === 'cerrada') {
                document.documentElement.classList.add('barra-lateral-cerrada');
            }
        } catch (e) {}
    </script>
</head>
<body class="con-barra-lateral"
      data-requiere-sesion
      data-url-sesion="<?= e(url('sesion/estado')) ?>"
      data-url-login="<?= e(url('login')) ?>"
      data-url-notificaciones="<?= e(url('notificaciones')) ?>"
      data-url-estado-notificaciones="<?= e(url('notificaciones/estado')) ?>"
      data-url-recordatorios="<?= e(url('notificaciones/recordatorios')) ?>"
      data-csrf="<?= e($csrf ?? '') ?>"
      data-claves-notificaciones="<?= e(implode(',', array_column($notificacionesPendientes ?? [], 'clave'))) ?>">

<a class="saltar-contenido" href="#contenido">Ir al contenido</a>

<aside class="barra-lateral" id="barra-lateral" aria-label="Menú principal">
    <a class="barra-lateral-marca" href="<?= e(url('dashboard')) ?>">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4h2a2 2 0 0 1 2 2v10"/><path d="M2 21h20M8 7h4M8 11h4M8 15h4M16 13h2M16 17h2"/></svg>
        <span>Control de Visitas</span>
    </a>

    <nav class="barra-lateral-menu">
        <?php foreach ($opcionesMenu as [$clave, $ruta, $texto, $icono]): ?>
            <a href="<?= e(url($ruta)) ?>" class="<?= $opcionActiva === $clave ? 'activo' : '' ?>"
               <?= $opcionActiva === $clave ? 'aria-current="page"' : '' ?>>
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><?= $icono ?></svg>
                <span><?= e($texto) ?></span>
                <?php if ($clave === 'programadas' && !empty($citasPendientes)): ?>
                    <span class="barra-lateral-contador" title="Citas pendientes"><?= (int) $citasPendientes ?></span>
                <?php endif; ?>
                <?php if ($clave === 'notificaciones'): ?>
                    <span class="barra-lateral-contador contador-alerta<?= $resumenNotificaciones['urgentes'] ? ' contador-urgente' : '' ?>"
                          title="Alertas pendientes" data-contador-notificaciones
                          <?= $resumenNotificaciones['alertas'] ? '' : 'hidden' ?>><?= (int) $resumenNotificaciones['alertas'] ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="barra-lateral-pie">
        <button type="button" class="barra-lateral-salir" data-abrir-modal="modal-salir">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
            <span>Cerrar sesión</span>
        </button>
        <p class="barra-lateral-version">Control de Visitas 2.0</p>
    </div>
</aside>
<div class="barra-lateral-fondo" data-alternar-barra-lateral aria-hidden="true"></div>

<div class="area-principal">
    <header class="barra-superior">
        <button type="button" class="boton-barra-lateral" data-alternar-barra-lateral
                aria-controls="barra-lateral" aria-expanded="true" aria-label="Ocultar el menú lateral">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <span class="barra-superior-titulo"><?= e($tituloPagina) ?></span>

        <?php if ($usuarioSesion): ?>
            <details class="menu-notificaciones" data-menu-notificaciones>
                <summary aria-label="Notificaciones pendientes">
                    <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/></svg>
                    <span class="campana-contador<?= $resumenNotificaciones['urgentes'] ? ' contador-urgente' : '' ?>" data-contador-notificaciones
                          <?= $resumenNotificaciones['alertas'] ? '' : 'hidden' ?>><?= (int) $resumenNotificaciones['alertas'] ?></span>
                </summary>
                <div class="menu-notificaciones-panel">
                    <div class="menu-notificaciones-cabecera">
                        <strong>Pendientes de hoy</strong>
                        <a href="<?= e(url('notificaciones')) ?>">Ver todas</a>
                    </div>
                    <?php $listaNotificaciones = $notificacionesPendientes; $limiteNotificaciones = 6;
                          require APP_PATH . '/views/parciales/lista_notificaciones.php'; ?>
                </div>
            </details>

            <details class="menu-usuario">
                <summary aria-label="Opciones de la cuenta">
                    <span class="avatar-usuario" aria-hidden="true"><?= e(iniciales($usuarioSesion['nombre'] . ' ' . $usuarioSesion['apellido'])) ?></span>
                    <span class="menu-usuario-nombre">
                        <?= e($usuarioSesion['nombre'] . ' ' . $usuarioSesion['apellido']) ?>
                        <small><?= e($usuarioSesion['nombre_rol']) ?></small>
                    </span>
                    <svg class="icono icono-flecha" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </summary>
                <div class="menu-usuario-lista">
                    <p class="menu-usuario-cuenta">Usuario: <strong><?= e($usuarioSesion['user'] ?? '') ?></strong></p>
                    <a href="<?= e(url('cuenta')) ?>">
                        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
                        Mi perfil
                    </a>
                    <?php if (puede('cuenta.cambiar_clave')): ?>
                        <a href="<?= e(url('cuenta/clave')) ?>">
                            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            Cambiar contraseña
                        </a>
                    <?php endif; ?>
                </div>
            </details>
        <?php endif; ?>
    </header>

    <main class="contenedor" id="contenido">
