<?php
// Variables recibidas desde CuentaController::mostrarCambioClave():
//   $csrf, $errores (por campo), $reglasClave
$error = static fn (string $campo): string => $errores[$campo] ?? '';
$claseCampo = static fn (string $campo): string => isset($errores[$campo]) ? 'campo con-error' : 'campo';

$flashOk = null;
$erroresAlerta = empty($errores) ? [] : ['No se cambió la contraseña. Revise los mensajes en rojo.'];

// Campo de contraseña con botón para mostrarla
$campoClave = static function (string $id, string $etiqueta, string $autocompletar) use ($error, $claseCampo): void { ?>
    <div class="<?= $claseCampo($id) ?>">
        <label for="<?= e($id) ?>"><?= e($etiqueta) ?> <span class="obligatorio">*</span></label>
        <div class="control-icono">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input type="password" id="<?= e($id) ?>" name="<?= e($id) ?>" required maxlength="72"
                   autocomplete="<?= e($autocompletar) ?>" data-validar="requerido">
            <button type="button" class="ver-clave" aria-label="Mostrar contraseña" data-ver-clave="<?= e($id) ?>">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
        </div>
        <small class="campo-error" data-error-de="<?= e($id) ?>"><?= e($error($id)) ?></small>
    </div>
<?php };
?>
<section class="encabezado-pagina">
    <div>
        <h1>Cambiar contraseña</h1>
        <p>Cuenta <strong><?= e($_SESSION['usuario']['user'] ?? '') ?></strong>. Por seguridad, primero confirme su contraseña actual.</p>
    </div>
    <a class="boton boton-secundario" href="<?= e(url('cuenta')) ?>">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Volver a Mi perfil
    </a>
</section>

<?php require APP_PATH . '/views/parciales/alertas.php'; ?>

<form method="post" action="<?= e(url('cuenta/clave')) ?>" class="formulario formulario-angosto" novalidate data-validar-formulario>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

    <section class="panel seccion-formulario">
        <div class="seccion-titulo">
            <span class="paso">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </span>
            <div>
                <h2>Nueva contraseña</h2>
                <p>Al guardarla, la usará desde su próximo inicio de sesión.</p>
            </div>
        </div>

        <?php $campoClave('clave_actual', 'Contraseña actual', 'current-password'); ?>
        <?php $campoClave('clave_nueva', 'Contraseña nueva', 'new-password'); ?>
        <?php $campoClave('clave_confirmar', 'Repita la contraseña nueva', 'new-password'); ?>

        <ul class="reglas-clave" aria-label="Requisitos de la contraseña">
            <?php foreach ($reglasClave as $regla): ?>
                <li><?= e($regla) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <div class="acciones-formulario">
        <a class="boton boton-secundario" href="<?= e(url('cuenta')) ?>">Cancelar</a>
        <button type="submit" class="boton boton-primario">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
            Guardar contraseña
        </button>
    </div>
</form>
