<?php
// Variables recibidas desde VisitanteController::editar():
//   $visitante (datos guardados), $csrf, $errores (por campo), $old (datos a mostrar en el formulario)
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
?>
<section class="encabezado-pagina">
    <div>
        <h1>Editar Visitante</h1>
        <p>Corrija los datos personales de <strong><?= e($visitante['nombre'] . ' ' . $visitante['apellido']) ?></strong>. Los campos marcados con <span class="obligatorio">*</span> son obligatorios.</p>
    </div>
    <a class="boton boton-secundario" href="<?= e(url('visitantes/ver?id=' . (int) $visitante['id_visitante'])) ?>">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Volver a la ficha
    </a>
</section>

<?php require APP_PATH . '/views/parciales/alertas.php'; ?>

<form method="post" action="<?= e(url('visitantes/actualizar')) ?>" class="formulario" novalidate data-validar-formulario>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="id_visitante" value="<?= (int) $visitante['id_visitante'] ?>">

    <section class="panel seccion-formulario">
        <div class="seccion-titulo">
            <span class="paso">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
            </span>
            <div>
                <h2>Datos personales</h2>
                <p>Los cambios se reflejan en todas las visitas de esta persona.</p>
            </div>
        </div>

        <div class="rejilla rejilla-2">
            <div class="<?= $claseCampo('cedula') ?>">
                <label for="cedula">Cédula de identidad <span class="obligatorio">*</span></label>
                <input type="text" id="cedula" name="cedula" required maxlength="14" autocomplete="off"
                       placeholder="Ej: V-28123456 o E-81234567" value="<?= e($valor('cedula')) ?>" data-validar="cedula">
                <small class="campo-error" data-error-de="cedula"><?= e($error('cedula')) ?></small>
            </div>

            <div class="<?= $claseCampo('telefono') ?>">
                <label for="telefono">Teléfono <span class="obligatorio">*</span></label>
                <input type="tel" id="telefono" name="telefono" required maxlength="20" autocomplete="off"
                       placeholder="Ej: 0414-1234567 o +57 300 1234567" value="<?= e($valor('telefono')) ?>" data-validar="telefono">
                <small class="campo-error" data-error-de="telefono"><?= e($error('telefono')) ?></small>
            </div>

            <div class="<?= $claseCampo('nombre') ?>">
                <label for="nombre">Nombre <span class="obligatorio">*</span></label>
                <input type="text" id="nombre" name="nombre" required maxlength="60" autocomplete="off"
                       value="<?= e($valor('nombre')) ?>" data-validar="nombre" data-nombre-campo="nombre">
                <small class="campo-error" data-error-de="nombre"><?= e($error('nombre')) ?></small>
            </div>

            <div class="<?= $claseCampo('apellido') ?>">
                <label for="apellido">Apellido <span class="obligatorio">*</span></label>
                <input type="text" id="apellido" name="apellido" required maxlength="60" autocomplete="off"
                       value="<?= e($valor('apellido')) ?>" data-validar="nombre" data-nombre-campo="apellido">
                <small class="campo-error" data-error-de="apellido"><?= e($error('apellido')) ?></small>
            </div>

            <div class="<?= $claseCampo('correo') ?>">
                <label for="correo">Correo electrónico <span class="obligatorio">*</span></label>
                <input type="email" id="correo" name="correo" required maxlength="100" autocomplete="off"
                       placeholder="nombre@correo.com" value="<?= e($valor('correo')) ?>" data-validar="correo">
                <small class="campo-error" data-error-de="correo"><?= e($error('correo')) ?></small>
            </div>

            <div class="<?= $claseCampo('procedencia') ?>">
                <label for="procedencia">Procedencia <span class="opcional">(opcional)</span></label>
                <input type="text" id="procedencia" name="procedencia" maxlength="100"
                       placeholder="Empresa, institución o ciudad" value="<?= e($valor('procedencia')) ?>">
                <small class="campo-error" data-error-de="procedencia"><?= e($error('procedencia')) ?></small>
            </div>
        </div>
    </section>

    <div class="acciones-formulario">
        <a class="boton boton-secundario" href="<?= e(url('visitantes/ver?id=' . (int) $visitante['id_visitante'])) ?>">Cancelar</a>
        <button type="submit" class="boton boton-primario">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
            Guardar cambios
        </button>
    </div>
</form>
