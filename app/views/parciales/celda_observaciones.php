<?php
// Parcial: observaciones de una visita ("Ninguna" si no tiene) y botón para editarlas.
//   - Recepcionista: puede editarlas solo mientras la visita está activa.
//   - Administrador: en cualquier momento.
// Variables: $v (fila de la visita), $volver, $volverFiltros
$textoObs  = trim((string) $v['observaciones']);
$puedeObs  = Visita::esActiva((int) $v['id_estado']) || puede('visitas.observaciones_cerradas');
?>
<div class="celda-observaciones">
    <span class="<?= $textoObs === '' ? 'texto-ninguna' : 'texto-observaciones' ?>"><?= nl2br(e(Visita::textoObservaciones($textoObs))) ?></span>
    <?php if ($puedeObs): ?>
        <button type="button" class="boton-enlace" data-editar-observaciones
                data-id="<?= (int) $v['id_visita'] ?>"
                data-visitante="<?= e($v['visitante'] . ' (' . fechaLarga($v['fecha_visita']) . ')') ?>"
                data-texto="<?= e($textoObs) ?>"
                data-volver="<?= e($volver) ?>" data-volver-filtros="<?= e($volverFiltros ?? '') ?>">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
            <?= $textoObs === '' ? 'Agregar' : 'Editar' ?>
        </button>
    <?php endif; ?>
</div>
