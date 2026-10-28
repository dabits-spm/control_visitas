<?php
// Restablecer la contraseña de un recepcionista (solo administrador).
// Variables recibidas desde UsuarioAccesoController::mostrarClave(): $cuenta, $errores, $reglasClave, $csrf
$error = static fn (string $campo): string => $errores[$campo] ?? '';
$claseCampo = static fn (string $campo): string => isset($errores[$campo]) ? 'campo con-error' : 'campo';
$flashOk = null;
$erroresAlerta = empty($errores) ? [] : ['No se cambió la contraseña. Revise los mensajes en rojo.'];
$nombreCompleto = $cuenta['nombre'] . ' ' . $cuenta['apellido'];

$campoClave = static function (string $id, string $etiqueta) use ($error, $claseCampo): void { ?>
    <div class="<?= $claseCampo($id) ?>">
        <label for="<?= e($id) ?>"><?= e($etiqueta) ?> <span class="obligatorio">*</span></label>
        <div class="control-icono">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input type="password" id="<?= e($id) ?>" name="<?= e($id) ?>" required maxlength="72" autocomplete="new-password" data-validar="requerido">
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
        <h1>Restablecer contraseña</h1>
        <p>Nueva contraseña para <strong><?= e($nombreCompleto) ?></strong> (usuario <span class="texto-mono"><?= e($cuenta['user']) ?></span>).</p>
    </div>
    <a class="boton boton-secundario" href="<?= e(url('usuarios')) ?>">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Volver a Gestión de Usuarios
    </a>
</section>

<?php require APP_PATH . '/views/parciales/alertas.php'; ?>

<form method="post" action="<?= e(url('usuarios/clave')) ?>" class="formulario formulario-angosto" novalidate data-validar-formulario>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="id_usuario" value="<?= (int) $cuenta['id_usuario'] ?>">

    <section class="panel seccion-formulario">
        <div class="seccion-titulo">
            <span class="paso">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </span>
            <div>
                <h2>Contraseña nueva</h2>
                <p>Si el recepcionista tiene una sesión abierta, se cerrará. Comuníquele la contraseña en persona.</p>
            </div>
        </div>
        <?php $campoClave('clave_nueva', 'Contraseña nueva'); ?>
        <?php $campoClave('clave_confirmar', 'Repita la contraseña nueva'); ?>
        <ul class="reglas-clave" aria-label="Requisitos de la contraseña">
            <?php foreach ($reglasClave as $regla): ?><li><?= e($regla) ?></li><?php endforeach; ?>
        </ul>
    </section>

    <div class="acciones-formulario">
        <a class="boton boton-secundario" href="<?= e(url('usuarios')) ?>">Cancelar</a>
        <button type="submit" class="boton boton-primario">Guardar contraseña</button>
    </div>
</form>
