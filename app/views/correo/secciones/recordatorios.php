<?php
// app/views/correo/secciones/recordatorios.php
// Configuración de correo: recordatorios 24 horas antes y avisos pendientes.
?>
<section class="panel seccion-formulario">
    <div class="seccion-titulo">
        <span class="paso">3</span>
        <div>
            <h2>Recordatorios 24 horas antes</h2>
            <p>Cada cita programada o reprogramada recibe un recordatorio con la fecha, la hora, el departamento,
               el motivo y la ubicación.</p>
        </div>
    </div>
    <p class="nota-correo">Se envían solos cada 5 minutos mientras alguien tenga el sistema abierto.
        Última revisión: <strong><?= $ultimaRevision ? e(fechaLarga(substr($ultimaRevision, 0, 10)) . ' ' . horaDoce(substr($ultimaRevision, 11))) : 'todavía ninguna' ?></strong>.
        Para que salgan también de noche, configure el Programador de tareas de Windows (ver README).</p>

    <?php if (!$proximas): ?>
        <p class="sin-resultados">No hay citas en las próximas 24 horas.</p>
    <?php else: ?>
        <ul class="lista-recordatorios" data-lista-recordatorios>
            <?php foreach ($proximas as $v): ?>
                <li>
                    <div>
                        <strong><?= e($v['visitante']) ?></strong>
                        <small><?= e(fechaLarga($v['fecha_visita'])) ?> · <?= e(horaDoce($v['hora_entrada'])) ?> · <?= e($v['correo'] ?: 'sin correo') ?></small>
                    </div>
                    <?php if (empty($v['correo'])): ?>
                        <span class="marca-recordatorio marca-sin-correo">Sin correo</span>
                    <?php elseif ($v['recordatorio_enviado']): ?>
                        <span class="marca-recordatorio marca-enviado" title="<?= e($v['recordatorio_enviado']) ?>">Enviado</span>
                    <?php else: ?>
                        <span class="marca-recordatorio marca-pendiente">Pendiente</span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="<?= e(url('correo/recordatorios')) ?>">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <button type="submit" class="boton boton-primario" <?= $listo ? '' : 'disabled' ?>>
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/></svg>
            Enviar ahora los recordatorios pendientes
        </button>
    </form>

    <?php if ($sinAviso): ?>
        <div class="avisos-pendientes-correo">
            <p><strong><?= count($sinAviso) ?> <?= count($sinAviso) === 1 ? 'cita próxima no recibió' : 'citas próximas no recibieron' ?> el aviso</strong>
               al registrarse (por ejemplo, porque el correo aún no estaba configurado).</p>
            <form method="post" action="<?= e(url('correo/avisos')) ?>">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <button type="submit" class="boton boton-secundario" <?= $listo ? '' : 'disabled' ?>>Enviar los avisos pendientes</button>
            </form>
        </div>
    <?php endif; ?>
</section>
