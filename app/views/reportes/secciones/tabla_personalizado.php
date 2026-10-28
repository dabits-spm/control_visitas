<?php
// app/views/reportes/secciones/tabla_personalizado.php
// Reporte personalizado: tabla de visitas con las columnas elegidas (usa $visitas, $columnasDisponibles y $mostrar).
?>
<section class="panel bloque-reporte">
    <div class="panel-cabecera">
        <h2>
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
            Detalle de visitas
        </h2>
        <span class="contador"><?= count($visitas) ?> <?= count($visitas) === 1 ? 'visita' : 'visitas' ?></span>
    </div>

    <?php if (empty($visitas)): ?>
        <p class="sin-resultados">No hay visitas que cumplan con los filtros seleccionados.</p>
    <?php else: ?>
        <div class="tabla-contenedor">
            <table class="tabla tabla-reporte tabla-detalle">
                <thead>
                    <tr>
                        <th class="numero">N°</th>
                        <th>Fecha</th>
                        <th>Visitante</th>
                        <?php foreach ($columnasDisponibles as $clave => $nombre): ?>
                            <?php if ($mostrar($clave)): ?><th><?= e($nombre) ?></th><?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($visitas as $i => $v): ?>
                    <tr>
                        <td data-etiqueta="N°" class="numero"><?= $i + 1 ?></td>
                        <td data-etiqueta="Fecha"><?= e(fechaLarga($v['fecha_visita'])) ?></td>
                        <td data-etiqueta="Visitante">
                            <strong><?= e($v['visitante']) ?></strong>
                            <small class="texto-mono"><?= e($v['cedula']) ?></small>
                        </td>
                        <?php if ($mostrar('horario')): ?>
                            <td data-etiqueta="Entrada / Salida" class="texto-mono"><?= e(horaCorta($v['hora_entrada'])) ?> – <?= e(horaCorta($v['hora_salida'])) ?></td>
                        <?php endif; ?>
                        <?php if ($mostrar('contacto')): ?>
                            <td data-etiqueta="Contacto">
                                <?= e($v['telefono']) ?>
                                <small class="texto-suave"><?= e($v['correo'] ?: '—') ?></small>
                            </td>
                        <?php endif; ?>
                        <?php if ($mostrar('departamentos')): ?>
                            <td data-etiqueta="Departamentos"><?= e($v['departamentos'] ?? '—') ?></td>
                        <?php endif; ?>
                        <?php if ($mostrar('motivo')): ?>
                            <td data-etiqueta="Motivo"><?= e($v['motivo_visita']) ?></td>
                        <?php endif; ?>
                        <?php if ($mostrar('observaciones')): ?>
                            <td data-etiqueta="Observaciones" class="<?= trim((string) $v['observaciones']) === '' ? 'texto-ninguna' : '' ?>"><?= e(Visita::textoObservaciones($v['observaciones'])) ?></td>
                        <?php endif; ?>
                        <?php if ($mostrar('tipo')): ?>
                            <td data-etiqueta="Tipo de visita"><?= e(Visita::TIPOS[$v['tipo_visita']] ?? '—') ?></td>
                        <?php endif; ?>
                        <?php if ($mostrar('estado')): ?>
                            <td data-etiqueta="Estado"><span class="insignia insignia-<?= e(claseEstado($v['estado'])) ?>"><?= e($v['estado']) ?></span><?php if (!empty($v['cierre_automatico'])): ?><small class="texto-suave">Cierre automático</small><?php endif; ?></td>
                        <?php endif; ?>
                        <?php if ($mostrar('registrado_por')): ?>
                            <td data-etiqueta="Registrada por"><?= e($v['registrado_por']) ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
