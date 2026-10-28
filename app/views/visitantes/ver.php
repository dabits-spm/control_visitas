<?php
// Ficha del visitante: datos personales e historial de todas sus visitas (de la más reciente a la más antigua).
// Variables recibidas desde VisitanteController::ver():
//   $visitante, $visitas, $csrf, $flashOk, $flashAviso, $errores
$nombreCompleto = $visitante['nombre'] . ' ' . $visitante['apellido'];
$volver         = 'visitantes/ver';
$volverFiltros  = http_build_query(['id' => (int) $visitante['id_visitante']]);
$totalVisitas   = count($visitas);
$correoActivo   = CorreoSmtp::estaConfigurado();
// Última visita que ya ocurrió (las citas pendientes no cuentan) y próxima cita, si hay
$ocurridas      = array_values(array_filter($visitas, static fn ($v) => !Visita::esCita((int) $v['id_estado'])));
$pendientes     = array_values(array_filter($visitas, static fn ($v) => Visita::esCita((int) $v['id_estado'])));
$proximaCita    = $pendientes ? end($pendientes) : null;   // la lista va de la más reciente a la más antigua
?>
<section class="encabezado-pagina">
    <div>
        <h1>Ficha del visitante</h1>
        <p>Datos personales e historial de visitas.</p>
    </div>
    <a class="boton boton-secundario" href="<?= e(url('visitantes')) ?>">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Volver a visitantes
    </a>
</section>

<?php require APP_PATH . '/views/parciales/alertas.php'; ?>

<section class="panel ficha-visitante">
    <div class="ficha-cabecera">
        <span class="avatar avatar-grande" aria-hidden="true"><?= e(iniciales($nombreCompleto)) ?></span>
        <div>
            <h2><?= e($nombreCompleto) ?></h2>
            <p class="texto-mono"><?= e($visitante['cedula']) ?>
                · <?= str_starts_with($visitante['cedula'], 'E-') ? 'Extranjero' : 'Venezolano' ?></p>
        </div>
        <div class="acciones-fila ficha-acciones">
            <a class="boton boton-primario" href="<?= e(url('visitas/crear?cedula=' . rawurlencode($visitante['cedula']))) ?>">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Registrar visita
            </a>
            <?php if (puede('visitantes.editar')): ?>
                <a class="boton boton-secundario" href="<?= e(url('visitantes/editar?id=' . (int) $visitante['id_visitante'])) ?>">
                    <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                    Editar datos
                </a>
            <?php endif; ?>
            <?php if (puede('visitantes.eliminar') && $totalVisitas === 0): ?>
                <form method="post" action="<?= e(url('visitantes/eliminar')) ?>" class="form-estado"
                      data-confirmar="¿Eliminar a <?= e($nombreCompleto) ?> del sistema? Esta acción no se puede deshacer."
                      data-confirmar-boton="Eliminar" data-confirmar-peligro>
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="id_visitante" value="<?= (int) $visitante['id_visitante'] ?>">
                    <button type="submit" class="boton boton-peligro">Eliminar visitante</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <dl class="ficha-datos">
        <div><dt>Teléfono</dt><dd><?= e($visitante['telefono']) ?></dd></div>
        <div><dt>Correo</dt><dd><?= e($visitante['correo'] ?: '—') ?></dd></div>
        <div><dt>Procedencia</dt><dd><?= e($visitante['procedencia'] ?: '—') ?></dd></div>
        <div><dt>Visitas registradas</dt><dd><?= $totalVisitas ?></dd></div>
        <div><dt>Última visita</dt><dd><?= e($ocurridas ? fechaLarga($ocurridas[0]['fecha_visita']) : '—') ?></dd></div>
        <div><dt>Próxima cita</dt><dd><?= e($proximaCita ? fechaLarga($proximaCita['fecha_visita']) . ', ' . horaDoce($proximaCita['hora_entrada']) : '—') ?></dd></div>
    </dl>
</section>

<section class="panel">
    <div class="panel-cabecera">
        <h2>
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l4 2"/></svg>
            Historial de visitas
        </h2>
        <span class="contador"><?= $totalVisitas ?> <?= $totalVisitas === 1 ? 'visita' : 'visitas' ?></span>
    </div>

    <?php if (!$visitas): ?>
        <p class="sin-resultados">Esta persona todavía no tiene visitas registradas.</p>
    <?php else: ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Fecha y horario</th>
                        <th>Departamento</th>
                        <th>Motivo</th>
                        <th>Estado</th>
                        <th>Registrado por</th>
                        <th>Observaciones</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($visitas as $v): ?>
                    <tr>
                        <td data-etiqueta="Fecha y horario">
                            <span class="fecha-celda"><?= e(fechaLarga($v['fecha_visita'])) ?></span>
                            <small class="horario"><span>Entrada: <?= e(horaDoce($v['hora_entrada'])) ?></span>
                                <span>Salida: <?= e(horaDoce($v['hora_salida'])) ?></span></small>
                        </td>
                        <td data-etiqueta="Departamento"><?= e($v['departamentos'] ?? '—') ?></td>
                        <td data-etiqueta="Motivo" class="celda-motivo"><?= e($v['motivo_visita']) ?></td>
                        <td data-etiqueta="Estado"><?php require APP_PATH . '/views/parciales/detalle_estado.php'; ?></td>
                        <td data-etiqueta="Registrado por"><?= e($v['registrado_por']) ?></td>
                        <td data-etiqueta="Observaciones" class="celda-obs">
                            <?php require APP_PATH . '/views/parciales/celda_observaciones.php'; ?>
                        </td>
                        <td data-etiqueta="Acción">
                            <?php require APP_PATH . '/views/parciales/selector_estado.php'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
