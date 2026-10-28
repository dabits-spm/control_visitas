<?php
// Crear o editar la cuenta de un recepcionista (solo administrador).
// Variables recibidas desde UsuarioController::crear() / editar():
//   $cuenta (null al crear), $errores (por campo), $old (datos a mostrar), $reglasClave, $csrf
$esNueva = $cuenta === null;
$error = static fn (string $campo): string => $errores[$campo] ?? '';
$valor = static fn (string $campo): string => (string) ($old[$campo] ?? '');
$claseCampo = static fn (string $campo): string => isset($errores[$campo]) ? 'campo con-error' : 'campo';

$flashOk = null;
if (isset($errores['general'])) {
    $erroresAlerta = [$errores['general']];
} elseif (!empty($errores)) {
    $erroresAlerta = ['Hay ' . count($errores) . (count($errores) === 1 ? ' campo' : ' campos') . ' por corregir. Revise los mensajes en rojo.'];
} else {
    $erroresAlerta = [];
}

// Campo de texto con ícono
$campo = static function (string $id, string $etiqueta, string $tipo, array $extra = []) use ($error, $valor, $claseCampo): void { ?>
    <div class="<?= $claseCampo($id) ?>">
        <label for="<?= e($id) ?>"><?= e($etiqueta) ?> <span class="obligatorio">*</span></label>
        <input type="<?= e($tipo) ?>" id="<?= e($id) ?>" name="<?= e($id) ?>" required value="<?= e($valor($id)) ?>"
            <?php foreach ($extra as $atributo => $v): ?> <?= e($atributo) ?>="<?= e($v) ?>"<?php endforeach; ?>>
        <small class="campo-error" data-error-de="<?= e($id) ?>"><?= e($error($id)) ?></small>
    </div>
<?php };

// Campo de contraseña con botón para mostrarla
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
        <h1><?= $esNueva ? 'Nuevo recepcionista' : 'Editar recepcionista' ?></h1>
        <p><?= $esNueva
            ? 'Complete los datos personales y las credenciales de acceso. La cuenta se crea con el rol Recepcionista.'
            : 'Corrija los datos personales o el nombre de usuario de ' . e($cuenta['nombre'] . ' ' . $cuenta['apellido']) . '. La contraseña se cambia con «Restablecer contraseña».' ?></p>
    </div>
    <a class="boton boton-secundario" href="<?= e(url('usuarios')) ?>">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Volver a Gestión de Usuarios
    </a>
</section>

<?php require APP_PATH . '/views/parciales/alertas.php'; ?>

<form method="post" action="<?= e(url($esNueva ? 'usuarios/guardar' : 'usuarios/actualizar')) ?>" class="formulario" novalidate data-validar-formulario>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php if (!$esNueva): ?>
        <input type="hidden" name="id_usuario" value="<?= (int) $cuenta['id_usuario'] ?>">
    <?php endif; ?>

    <section class="panel seccion-formulario">
        <div class="seccion-titulo">
            <span class="paso">1</span>
            <div>
                <h2>Datos personales</h2>
                <p>Cédula con V o E (sin letra se toma como V). Teléfono venezolano o con código de país.</p>
            </div>
        </div>
        <div class="rejilla rejilla-2">
            <?php $campo('nombre', 'Nombre', 'text', ['maxlength' => 60, 'autocomplete' => 'off', 'data-validar' => 'nombre', 'data-nombre-campo' => 'nombre']); ?>
            <?php $campo('apellido', 'Apellido', 'text', ['maxlength' => 60, 'autocomplete' => 'off', 'data-validar' => 'nombre', 'data-nombre-campo' => 'apellido']); ?>
            <?php $campo('cedula', 'Cédula de identidad', 'text', ['maxlength' => 14, 'autocomplete' => 'off', 'placeholder' => 'Ej: V-28123456', 'data-validar' => 'cedula']); ?>
            <?php $campo('telefono', 'Teléfono', 'tel', ['maxlength' => 20, 'autocomplete' => 'off', 'placeholder' => 'Ej: 0414-1234567', 'data-validar' => 'telefono']); ?>
            <?php $campo('correo', 'Correo electrónico', 'email', ['maxlength' => 100, 'autocomplete' => 'off', 'placeholder' => 'nombre@correo.com', 'data-validar' => 'correo']); ?>
        </div>
    </section>

    <section class="panel seccion-formulario">
        <div class="seccion-titulo">
            <span class="paso">2</span>
            <div>
                <h2>Credenciales de acceso</h2>
                <p><?= $esNueva ? 'Con estos datos el recepcionista inicia sesión. Comuníquele la contraseña en persona.'
                                 : 'Nombre con el que el recepcionista inicia sesión.' ?></p>
            </div>
        </div>
        <div class="rejilla rejilla-2">
            <?php $campo('user', 'Nombre de usuario', 'text', ['maxlength' => 40, 'autocomplete' => 'off', 'placeholder' => 'Ej: maria.perez', 'data-validar' => 'requerido']); ?>
        </div>
        <?php if ($esNueva): ?>
            <div class="rejilla rejilla-2">
                <?php $campoClave('clave_nueva', 'Contraseña'); ?>
                <?php $campoClave('clave_confirmar', 'Repita la contraseña'); ?>
            </div>
            <ul class="reglas-clave" aria-label="Requisitos de la contraseña">
                <?php foreach ($reglasClave as $regla): ?><li><?= e($regla) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <div class="acciones-formulario">
        <a class="boton boton-secundario" href="<?= e(url('usuarios')) ?>">Cancelar</a>
        <button type="submit" class="boton boton-primario">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
            <?= $esNueva ? 'Crear recepcionista' : 'Guardar cambios' ?>
        </button>
    </div>
</form>
