<?php
// Parcial: insignia de estado de una visita y sus datos breves.
//   - Etiqueta gris «Cita programada» si la visita viene de una cita.
//   - «Cierre automático» si el sistema la finalizó por quedar abierta de un día anterior.
//   - Estado del aviso por correo de las citas pendientes.
// Variables: $v (fila de la visita)
?>
<span class="insignia insignia-<?= e(claseEstado($v['estado'])) ?>"><?= e($v['estado']) ?></span>
<?php if ($v['tipo_visita'] === Visita::TIPO_PROGRAMADA): ?>
    <span class="etiqueta-cita" title="Esta visita se registró como una cita programada">Cita programada</span>
<?php endif; ?>
<?php if (!empty($v['cierre_automatico'])): ?>
    <small class="nota-estado" title="Se finalizó sola porque quedó abierta de un día anterior">Cierre automático</small>
<?php elseif (Visita::esCita((int) $v['id_estado'])): ?>
    <?php if (!empty($v['aviso_enviado'])): ?>
        <small class="nota-estado nota-ok" title="Aviso por correo enviado el <?= e(date('d/m/Y h:i A', strtotime($v['aviso_enviado']))) ?>">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Aviso enviado
        </small>
    <?php else: ?>
        <small class="nota-estado nota-pendiente">Sin aviso</small>
    <?php endif; ?>
<?php endif; ?>
