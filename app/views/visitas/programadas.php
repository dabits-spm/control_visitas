<?php
// Visitas programadas: citas pendientes (programadas o reprogramadas), de la más próxima a la más lejana.
// Cuando la cita ocurre (se inicia y finaliza) o se cancela, pasa al historial.
// Variables recibidas desde VisitaController::programadas():
//   $visitas (página actual), $paginacion, $filtros ['q'], $hayVisitas, $csrf, $correoActivo,
//   $flashOk, $flashAviso, $errores
$filtrosLista  = array_filter(['q' => $filtros['q']]);
$volverFiltros = http_build_query($filtrosLista + ($paginacion['pagina'] > 1 ? ['pagina' => $paginacion['pagina']] : []));
$hoy = date('Y-m-d');
?>
<section class="encabezado-pagina">
    <div>
        <h1>Visitas Programadas</h1>
        <p>Citas pendientes, de la más próxima a la más lejana. Al iniciarla se toma la hora real de llegada;
           si la persona no llega el día acordado, la cita se finaliza sola al día siguiente.</p>
    </div>
    <a class="boton boton-primario" href="<?= e(url('visitas/crear?tipo=' . Visita::TIPO_PROGRAMADA)) ?>">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        Programar Visita
    </a>
</section>

<?php require APP_PATH . '/views/parciales/alertas.php'; ?>

<section class="panel">
    <div class="panel-cabecera">
        <h2>
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            Citas pendientes
        </h2>
        <span class="contador"><?= $paginacion['total'] ?> <?= $paginacion['total'] === 1 ? 'cita' : 'citas' ?><?= $filtros['q'] !== '' ? ' encontradas' : '' ?></span>
    </div>

    <?php if (!$hayVisitas): ?>
        <div class="estado-vacio">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M9 16h6"/></svg>
            <p><strong>No hay citas pendientes.</strong></p>
            <p>Las visitas que registre como «Programada» aparecerán aquí hasta que se atiendan.</p>
            <a class="boton boton-primario" href="<?= e(url('visitas/crear?tipo=' . Visita::TIPO_PROGRAMADA)) ?>">Programar una visita</a>
        </div>
    <?php else: ?>
        <form method="get" action="<?= e(url('visitas/programadas')) ?>" class="filtros" role="search">
            <label class="campo-busqueda">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" name="q" value="<?= e($filtros['q']) ?>" maxlength="100" data-busqueda-rapida
                       placeholder="Buscar por cédula, nombre, departamento o teléfono..." aria-label="Buscar citas">
            </label>
            <button type="submit" class="boton boton-secundario">Buscar</button>
            <?php if ($filtros['q'] !== ''): ?>
                <a class="boton boton-secundario" href="<?= e(url('visitas/programadas')) ?>">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="tabla-contenedor">
            <?php if (empty($visitas)): ?>
                <p class="sin-resultados">No hay citas que coincidan con la búsqueda.</p>
            <?php else: ?>
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Fecha y hora</th>
                        <th>Visitante</th>
                        <th>Contacto</th>
                        <th>Departamento</th>
                        <th>Motivo</th>
                        <th>Estado</th>
                        <th>Observaciones</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($visitas as $v): ?>
                    <tr class="<?= $v['fecha_visita'] === $hoy ? 'fila-hoy' : '' ?>">
                        <td data-etiqueta="Fecha y hora">
                            <span class="hora"><?= e(horaDoce($v['hora_entrada'])) ?></span>
                            <small class="texto-suave sin-salto"><?= e(fechaLarga($v['fecha_visita'])) ?></small>
                            <?php if ($v['fecha_visita'] === $hoy): ?><small class="marca-hoy">Hoy</small><?php endif; ?>
                        </td>
                        <td data-etiqueta="Visitante">
                            <a class="enlace-visitante" href="<?= e(url('visitantes/ver?id=' . (int) $v['id_visitante'])) ?>"><?= e($v['visitante']) ?></a>
                            <small class="texto-mono"><?= e($v['cedula']) ?></small>
                        </td>
                        <td data-etiqueta="Contacto">
                            <span class="sin-salto"><?= e($v['telefono']) ?></span>
                            <small class="texto-suave texto-correo"><?= e($v['correo'] ?: '—') ?></small>
                        </td>
                        <td data-etiqueta="Departamento"><?= e($v['departamentos'] ?? '—') ?></td>
                        <td data-etiqueta="Motivo" class="celda-motivo"><?= e($v['motivo_visita']) ?></td>
                        <td data-etiqueta="Estado"><?php require APP_PATH . '/views/parciales/detalle_estado.php'; ?></td>
                        <td data-etiqueta="Observaciones" class="celda-obs">
                            <?php $volver = 'visitas/programadas'; require APP_PATH . '/views/parciales/celda_observaciones.php'; ?>
                        </td>
                        <td data-etiqueta="Acción">
                            <?php require APP_PATH . '/views/parciales/selector_estado.php'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <?php $rutaListado = 'visitas/programadas'; $filtros = $filtrosLista; require APP_PATH . '/views/parciales/paginacion.php'; ?>
    <?php endif; ?>
</section>
