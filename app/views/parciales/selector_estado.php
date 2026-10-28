<?php
// Parcial: acciones de una visita en las tablas.
//   - Selector de estado: disponible para visitas programadas, reprogramadas o en proceso.
//     Si la visita ya está finalizada o cancelada, queda bloqueado (salvo para el administrador).
//   - "Reprogramar…" abre un modal para elegir la nueva fecha y hora.
//   - Cita pendiente: botón para enviar o reenviar el aviso por correo.
//   - Administrador: enlaces Editar y Eliminar para cualquier visita.
// Variables: $v (fila de la visita), $csrf, $volver ('dashboard' | 'visitas' | 'visitas/programadas' | 'visitantes/ver'),
//            $volverFiltros (filtros del listado, ej. "q=ana&pagina=2"), $correoActivo (bool)
$volverFiltros = $volverFiltros ?? '';
$idEstado      = (int) $v['id_estado'];
$esAdmin       = puede('visitas.estado_cerradas');
$acciones      = Visita::transicionesDesde($idEstado, $esAdmin);
$etiquetaVisita = $v['visitante'] . ' (' . fechaLarga($v['fecha_visita']) . ')';
?>
<div class="acciones-visita">
    <?php if (!empty($acciones)): ?>
        <form method="post" action="<?= e(url('visitas/estado')) ?>" class="form-estado">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="id_visita" value="<?= (int) $v['id_visita'] ?>">
            <input type="hidden" name="volver" value="<?= e($volver) ?>">
            <input type="hidden" name="volver_filtros" value="<?= e($volverFiltros) ?>">
            <select name="id_estado" class="selector-estado" required
                    aria-label="Cambiar estado de la visita de <?= e($v['visitante']) ?>"
                    data-cambiar-estado data-visitante="<?= e($etiquetaVisita) ?>">
                <option value="">Cambiar estado...</option>
                <?php foreach ($acciones as $idNuevo => $texto): ?>
                    <option value="<?= (int) $idNuevo ?>" <?= $idNuevo === Visita::ESTADO_REPROGRAMADA ? 'data-reprogramar' : '' ?>><?= e($texto) ?></option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit" class="boton boton-chico">Aplicar</button></noscript>
        </form>
    <?php else: ?>
        <select class="selector-estado" disabled aria-label="Estado bloqueado"
                title="Una visita finalizada o cancelada solo la puede cambiar un administrador">
            <option>Estado bloqueado</option>
        </select>
    <?php endif; ?>

    <?php if (Visita::esCita($idEstado) && !empty($correoActivo)): ?>
        <form method="post" action="<?= e(url('visitas/aviso')) ?>" class="form-estado form-aviso"
              data-confirmar="¿Enviar el aviso de la visita a <?= e($v['correo']) ?>?" data-confirmar-boton="Enviar aviso">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="id_visita" value="<?= (int) $v['id_visita'] ?>">
            <input type="hidden" name="volver" value="<?= e($volver) ?>">
            <input type="hidden" name="volver_filtros" value="<?= e($volverFiltros) ?>">
            <button type="submit" class="boton-enlace" data-enviando="Enviando...">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                <?= empty($v['aviso_enviado']) ? 'Enviar aviso' : 'Reenviar aviso' ?>
            </button>
        </form>
    <?php endif; ?>

    <?php if (puede('visitas.editar') || puede('visitas.eliminar')): ?>
        <div class="acciones-admin">
            <?php if (puede('visitas.editar')): ?>
                <a class="boton-enlace" href="<?= e(url('visitas/editar?id=' . (int) $v['id_visita'])) ?>"
                   aria-label="Editar la visita de <?= e($v['visitante']) ?>">
                    <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>Editar
                </a>
            <?php endif; ?>
            <?php if (puede('visitas.eliminar')): ?>
                <form method="post" action="<?= e(url('visitas/eliminar')) ?>" class="form-estado"
                      data-confirmar="¿Eliminar definitivamente la visita de <?= e($etiquetaVisita) ?>? Esta acción no se puede deshacer."
                      data-confirmar-boton="Eliminar" data-confirmar-peligro>
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="id_visita" value="<?= (int) $v['id_visita'] ?>">
                    <input type="hidden" name="volver" value="<?= e($volver) ?>">
                    <input type="hidden" name="volver_filtros" value="<?= e($volverFiltros) ?>">
                    <button type="submit" class="boton-enlace boton-enlace-peligro" aria-label="Eliminar la visita de <?= e($v['visitante']) ?>">
                        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>Eliminar
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
