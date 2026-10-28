<?php
// Variables recibidas desde VisitaController::crear():
//   $departamentos, $csrf, $errores (por campo), $old (datos escritos antes del error), $correoActivo
$deptosMarcados = array_map('intval', (array) ($old['departamentos'] ?? []));
// Por defecto "al momento": es el caso más común en recepción
$tipoVisita = ($old['tipo_visita'] ?? '') === Visita::TIPO_PROGRAMADA ? Visita::TIPO_PROGRAMADA : Visita::TIPO_AL_MOMENTO;

$error = static fn (string $campo): string => $errores[$campo] ?? '';
$valor = static fn (string $campo, string $porDefecto = ''): string => (string) ($old[$campo] ?? $porDefecto);
$claseCampo = static fn (string $campo): string => isset($errores[$campo]) ? 'campo con-error' : 'campo';

// Arriba solo se muestra un resumen; el detalle aparece debajo de cada campo
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
        <h1>Registrar Nueva Visita</h1>
        <p>Complete los datos del visitante y de la visita. Los campos marcados con <span class="obligatorio">*</span> son obligatorios.</p>
    </div>
    <a class="boton boton-secundario" href="<?= e(url($tipoVisita === Visita::TIPO_PROGRAMADA ? 'visitas/programadas' : 'dashboard')) ?>">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Volver
    </a>
</section>

<?php require APP_PATH . '/views/parciales/alertas.php'; ?>

<form method="post" action="<?= e(url('visitas/guardar')) ?>" class="formulario" novalidate data-validar-formulario>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

    <!-- 1. Datos personales del visitante -->
    <?php require APP_PATH . '/views/visitas/secciones/datos_visitante.php'; ?>

    <!-- 2. Datos de la visita -->
    <section class="panel seccion-formulario">
        <div class="seccion-titulo">
            <span class="paso">2</span>
            <div>
                <h2>Datos de la visita</h2>
                <p>Indique si el visitante ya llegó o si es una cita, a qué departamento va y el motivo.</p>
            </div>
        </div>

        <fieldset class="<?= $claseCampo('tipo_visita') ?>">
            <legend>Tipo de visita <span class="obligatorio">*</span></legend>
            <div class="opciones-tipo" data-tipo-visita>
                <label class="opcion-tipo">
                    <input type="radio" name="tipo_visita" value="<?= e(Visita::TIPO_AL_MOMENTO) ?>"
                           <?= $tipoVisita === Visita::TIPO_AL_MOMENTO ? 'checked' : '' ?>>
                    <span>
                        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M13 2 3 14h9l-1 8 10-12h-9z"/></svg>
                        <strong>Al momento</strong>
                        <small>El visitante ya llegó. Se registra con la fecha y hora actuales y queda «En proceso».</small>
                    </span>
                </label>
                <label class="opcion-tipo">
                    <input type="radio" name="tipo_visita" value="<?= e(Visita::TIPO_PROGRAMADA) ?>"
                           <?= $tipoVisita === Visita::TIPO_PROGRAMADA ? 'checked' : '' ?>>
                    <span>
                        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/></svg>
                        <strong>Programada</strong>
                        <small>Es una cita. Indique la fecha y hora en que llegará; queda «Programada»<?= $correoActivo ? ' y se le avisa por correo' : '' ?>.</small>
                    </span>
                </label>
            </div>
            <small class="campo-error" data-error-de="tipo_visita"><?= e($error('tipo_visita')) ?></small>
        </fieldset>

        <!-- Solo para visitas programadas; las "al momento" usan la fecha y hora del servidor -->
        <div class="rejilla rejilla-2" data-campos-programada <?= $tipoVisita === Visita::TIPO_PROGRAMADA ? '' : 'hidden' ?>>
            <div class="<?= $claseCampo('fecha_visita') ?>">
                <label for="fecha_visita">Fecha de la cita <span class="obligatorio">*</span></label>
                <input type="date" id="fecha_visita" name="fecha_visita" required min="<?= e(date('Y-m-d')) ?>"
                       max="<?= e(date('Y-m-d', strtotime('+1 year'))) ?>"
                       value="<?= e($valor('fecha_visita', date('Y-m-d'))) ?>" data-validar="requerido">
                <small class="campo-error" data-error-de="fecha_visita"><?= e($error('fecha_visita')) ?></small>
            </div>

            <div class="<?= $claseCampo('hora_entrada') ?>">
                <label for="hora_entrada">Hora de la cita <span class="obligatorio">*</span></label>
                <input type="time" id="hora_entrada" name="hora_entrada" required
                       value="<?= e($valor('hora_entrada', date('H:i'))) ?>" data-validar="requerido">
                <small class="campo-error" data-error-de="hora_entrada"><?= e($error('hora_entrada')) ?></small>
            </div>
        </div>

        <fieldset class="<?= $claseCampo('departamentos') ?> grupo-departamentos" data-grupo-obligatorio="departamentos">
            <legend>Departamento(s) a visitar <span class="obligatorio">*</span></legend>
            <div class="chips">
                <?php foreach ($departamentos as $d): ?>
                    <label class="chip">
                        <input type="checkbox" name="departamentos[]"
                               value="<?= (int) $d['id_departamento'] ?>"
                               <?= in_array((int) $d['id_departamento'], $deptosMarcados, true) ? 'checked' : '' ?>>
                        <span>
                            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            <?= e($d['nombre_departamento']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <small class="campo-error" data-error-de="departamentos"><?= e($error('departamentos')) ?></small>
        </fieldset>

        <div class="<?= $claseCampo('motivo_visita') ?>">
            <label for="motivo_visita">Motivo de la visita <span class="obligatorio">*</span></label>
            <input type="text" id="motivo_visita" name="motivo_visita" maxlength="255" required
                   value="<?= e($valor('motivo_visita')) ?>" placeholder="Ej: Trámite administrativo"
                   data-validar="requerido">
            <small class="campo-error" data-error-de="motivo_visita"><?= e($error('motivo_visita')) ?></small>
        </div>

        <div class="campo">
            <label for="observaciones">Observaciones <span class="opcional">(opcional; también se pueden agregar después)</span></label>
            <textarea id="observaciones" name="observaciones" rows="3" maxlength="<?= Visita::MAX_OBSERVACIONES ?>"
                      placeholder="Detalles adicionales..."><?= e($valor('observaciones')) ?></textarea>
            <small class="campo-error" data-error-de="observaciones"><?= e($error('observaciones')) ?></small>
        </div>
    </section>

    <div class="acciones-formulario">
        <a class="boton boton-secundario" href="<?= e(url('dashboard')) ?>">Cancelar</a>
        <button type="submit" class="boton boton-primario">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
            Guardar visita
        </button>
    </div>
</form>
