<?php
// Variables recibidas desde VisitaEdicionController::editar():
//   $visita (detalle: visitante, cédula, estado...), $departamentos, $csrf,
//   $errores (por campo), $old (datos a mostrar en el formulario)
$deptosMarcados = array_map('intval', (array) ($old['departamentos'] ?? []));
// Solo el administrador llega aquí; puede editar visitas en cualquier estado.
//  - Cita pendiente (programada o reprogramada): fecha y hora a futuro, sin hora de salida.
//  - Visita que ya ocurrió: fecha hasta hoy; la hora de salida es obligatoria si finalizó
//    (salvo las de cierre automático).
$esCita         = Visita::esCita((int) $visita['id_estado']);
$esFinalizada   = (int) $visita['id_estado'] === Visita::ESTADO_FINALIZADA && empty($visita['cierre_automatico']);
$volverA        = $esCita ? 'visitas/programadas' : 'visitas';

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
        <h1>Editar Visita #<?= (int) $visita['id_visita'] ?></h1>
        <p>Corrija los datos de la visita. El estado se cambia desde el listado y los datos personales desde la ficha del visitante.</p>
    </div>
    <a class="boton boton-secundario" href="<?= e(url($volverA)) ?>">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Volver al listado
    </a>
</section>

<?php require APP_PATH . '/views/parciales/alertas.php'; ?>

<form method="post" action="<?= e(url('visitas/actualizar')) ?>" class="formulario" novalidate data-validar-formulario>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="id_visita" value="<?= (int) $visita['id_visita'] ?>">

    <!-- 1. Visitante (solo lectura) -->
    <section class="panel seccion-formulario">
        <div class="seccion-titulo">
            <span class="paso">1</span>
            <div>
                <h2>Visitante</h2>
                <p>Para corregir sus datos personales use la <a href="<?= e(url('visitantes/ver?id=' . (int) $visita['id_visitante'])) ?>">ficha del visitante</a>.</p>
            </div>
        </div>

        <div class="resumen-visita">
            <div class="celda-visitante">
                <span class="avatar" aria-hidden="true"><?= e(iniciales($visita['visitante'])) ?></span>
                <div>
                    <strong><?= e($visita['visitante']) ?></strong>
                    <small class="texto-mono"><?= e($visita['cedula']) ?></small>
                </div>
            </div>
            <div>
                <small class="texto-suave">Estado</small>
                <span class="insignia insignia-<?= e(claseEstado($visita['estado'])) ?>"><?= e($visita['estado']) ?></span>
            </div>
            <div>
                <small class="texto-suave">Tipo de visita</small>
                <span><?= e(Visita::TIPOS[$visita['tipo_visita']] ?? '—') ?></span>
            </div>
            <?php if (!empty($visita['cierre_automatico'])): ?>
                <div>
                    <small class="texto-suave">Cierre</small>
                    <span title="Quedó abierta de un día anterior y el sistema la finalizó">Automático</span>
                </div>
            <?php endif; ?>
            <div>
                <small class="texto-suave">Registrada por</small>
                <span><?= e($visita['registrado_por']) ?></span>
            </div>
        </div>
    </section>

    <!-- 2. Datos de la visita -->
    <section class="panel seccion-formulario">
        <div class="seccion-titulo">
            <span class="paso">2</span>
            <div>
                <h2>Datos de la visita</h2>
                <p>Los campos marcados con <span class="obligatorio">*</span> son obligatorios.</p>
            </div>
        </div>

        <div class="rejilla <?= $esCita ? 'rejilla-2' : 'rejilla-3' ?>">
            <div class="<?= $claseCampo('fecha_visita') ?>">
                <label for="fecha_visita"><?= $esCita ? 'Fecha de la cita' : 'Fecha de la visita' ?> <span class="obligatorio">*</span></label>
                <input type="date" id="fecha_visita" name="fecha_visita" required
                       <?= $esCita ? 'min="' . e(date('Y-m-d')) . '"' : 'max="' . e(date('Y-m-d')) . '"' ?>
                       value="<?= e($valor('fecha_visita')) ?>" data-validar="requerido">
                <small class="campo-error" data-error-de="fecha_visita"><?= e($error('fecha_visita')) ?></small>
            </div>

            <div class="<?= $claseCampo('hora_entrada') ?>">
                <label for="hora_entrada"><?= $esCita ? 'Hora de la cita' : 'Hora de entrada' ?> <span class="obligatorio">*</span></label>
                <input type="time" id="hora_entrada" name="hora_entrada" required
                       value="<?= e($valor('hora_entrada')) ?>" data-validar="requerido">
                <small class="campo-error" data-error-de="hora_entrada"><?= e($error('hora_entrada')) ?></small>
            </div>

            <?php if (!$esCita): ?>
            <div class="<?= $claseCampo('hora_salida') ?>">
                <label for="hora_salida">
                    Hora de salida
                    <?= $esFinalizada ? '<span class="obligatorio">*</span>' : '<span class="opcional">(opcional)</span>' ?>
                </label>
                <input type="time" id="hora_salida" name="hora_salida" <?= $esFinalizada ? 'required data-validar="requerido"' : '' ?>
                       value="<?= e($valor('hora_salida')) ?>">
                <small class="campo-error" data-error-de="hora_salida"><?= e($error('hora_salida')) ?></small>
            </div>
            <?php endif; ?>
        </div>

        <fieldset class="<?= $claseCampo('departamentos') ?> grupo-departamentos" data-grupo-obligatorio="departamentos">
            <legend>Departamento(s) visitados <span class="obligatorio">*</span></legend>
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
                   value="<?= e($valor('motivo_visita')) ?>" data-validar="requerido">
            <small class="campo-error" data-error-de="motivo_visita"><?= e($error('motivo_visita')) ?></small>
        </div>

        <div class="campo">
            <label for="observaciones">Observaciones <span class="opcional">(opcional)</span></label>
            <textarea id="observaciones" name="observaciones" rows="3" maxlength="<?= Visita::MAX_OBSERVACIONES ?>"><?= e($valor('observaciones')) ?></textarea>
            <small class="campo-error" data-error-de="observaciones"><?= e($error('observaciones')) ?></small>
        </div>
    </section>

    <div class="acciones-formulario">
        <a class="boton boton-secundario" href="<?= e(url($volverA)) ?>">Cancelar</a>
        <button type="submit" class="boton boton-primario">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
            Guardar cambios
        </button>
    </div>
</form>
