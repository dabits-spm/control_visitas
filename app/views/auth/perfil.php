<?php
// Mi perfil: tarjeta con los datos de la cuenta, de solo lectura.
// Variables recibidas desde CuentaController::perfil(): $cuenta, $flashOk, $flashAviso, $errores
$nombreCompleto = $cuenta['nombre'] . ' ' . $cuenta['apellido'];
$puedeCambiarClave = puede('cuenta.cambiar_clave');
?>
<section class="encabezado-pagina">
    <div>
        <h1>Mi perfil</h1>
        <p>Datos de la cuenta con la que inició sesión.</p>
    </div>
</section>

<?php require APP_PATH . '/views/parciales/alertas.php'; ?>

<section class="panel tarjeta-perfil">
    <div class="perfil-cabecera">
        <span class="avatar avatar-grande" aria-hidden="true"><?= e(iniciales($nombreCompleto)) ?></span>
        <div>
            <h2><?= e($nombreCompleto) ?></h2>
            <p class="perfil-rol"><?= e($cuenta['nombre_rol']) ?> · usuario <strong><?= e($cuenta['user']) ?></strong></p>
        </div>
        <?php $estadoCuenta = $cuenta['estado']; require APP_PATH . '/views/parciales/estado_cuenta.php'; ?>
    </div>

    <dl class="ficha-datos perfil-datos">
        <div><dt>Nombre</dt><dd><?= e($cuenta['nombre']) ?></dd></div>
        <div><dt>Apellido</dt><dd><?= e($cuenta['apellido']) ?></dd></div>
        <div><dt>Cédula</dt><dd class="texto-mono"><?= e($cuenta['cedula']) ?></dd></div>
        <div><dt>Teléfono</dt><dd><?= e($cuenta['telefono'] ?: '—') ?></dd></div>
        <div><dt>Correo</dt><dd><?= e($cuenta['correo'] ?: '—') ?></dd></div>
        <div><dt>Estado de la cuenta</dt><dd><?php $estadoCuenta = $cuenta['estado']; require APP_PATH . '/views/parciales/estado_cuenta.php'; ?></dd></div>
    </dl>

    <?php if ($puedeCambiarClave): ?>
        <div class="perfil-acciones">
            <a class="boton boton-secundario" href="<?= e(url('cuenta/clave')) ?>">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Cambiar contraseña
            </a>
        </div>
    <?php else: ?>
        <p class="perfil-nota">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            Estos datos y su contraseña solo los puede modificar un administrador. Si necesita corregir algo, comuníquese con él.
        </p>
    <?php endif; ?>
</section>
