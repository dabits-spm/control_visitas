<?php
// Historial de visitas: las que ya ocurrieron (en proceso, finalizadas o canceladas),
// de la más reciente a la más antigua. Las citas pendientes están en «Visitas Programadas».
// Variables recibidas desde VisitaController::index():
//   $visitas (página actual), $paginacion, $filtros ['q', 'estado', 'tipo'], $hayVisitas,
//   $csrf, $correoActivo, $flashOk, $flashAviso, $errores
$filtrosLista  = array_filter(['q' => $filtros['q'], 'estado' => $filtros['estado'], 'tipo' => $filtros['tipo']]);
$volverFiltros = http_build_query($filtrosLista + ($paginacion['pagina'] > 1 ? ['pagina' => $paginacion['pagina']] : []));
$conFiltros    = $filtrosLista !== [];
?>
<section class="encabezado-pagina">
    <div>
        <h1>Historial de Visitas</h1>
        <p>Visitas atendidas, en curso y canceladas, de la más reciente a la más antigua. Las citas pendientes están en
           <a href="<?= e(url('visitas/programadas')) ?>">Visitas Programadas</a>.</p>
    </div>
    <a class="boton boton-primario" href="<?= e(url('visitas/crear')) ?>">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        Registrar Nueva Visita
    </a>
</section>

<?php require APP_PATH . '/views/parciales/alertas.php'; ?>

<section class="panel">
    <div class="panel-cabecera">
        <h2>
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l4 2"/></svg>
            Visitas registradas
        </h2>
        <span class="contador"><?= $paginacion['total'] ?> <?= $paginacion['total'] === 1 ? 'visita' : 'visitas' ?><?= $conFiltros ? ' encontradas' : '' ?></span>
    </div>

    <?php if (!$hayVisitas): ?>
        <div class="estado-vacio">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M9 16h6"/></svg>
            <p><strong>Todavía no hay visitas en el historial.</strong></p>
            <a class="boton boton-primario" href="<?= e(url('visitas/crear')) ?>">Registrar la primera visita</a>
        </div>
    <?php else: ?>
        <form method="get" action="<?= e(url('visitas')) ?>" class="filtros" role="search">
            <label class="campo-busqueda">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" name="q" value="<?= e($filtros['q']) ?>" maxlength="100" data-busqueda-rapida
                       placeholder="Buscar por cédula, nombre, departamento o teléfono..." aria-label="Buscar visitas">
            </label>
            <select name="estado" aria-label="Filtrar por estado" data-enviar-al-cambiar>
                <option value="">Todos los estados</option>
                <?php foreach (['finalizada' => 'Finalizada', 'cancelada' => 'Cancelada', 'en-proceso' => 'En proceso'] as $clave => $nombre): ?>
                    <option value="<?= e($clave) ?>" <?= $filtros['estado'] === $clave ? 'selected' : '' ?>><?= e($nombre) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="tipo" aria-label="Filtrar por tipo de visita" data-enviar-al-cambiar>
                <option value="">Todas (al momento y citas)</option>
                <option value="<?= e(Visita::TIPO_AL_MOMENTO) ?>" <?= $filtros['tipo'] === Visita::TIPO_AL_MOMENTO ? 'selected' : '' ?>>Solo al momento</option>
                <option value="<?= e(Visita::TIPO_PROGRAMADA) ?>" <?= $filtros['tipo'] === Visita::TIPO_PROGRAMADA ? 'selected' : '' ?>>Solo citas programadas</option>
            </select>
            <button type="submit" class="boton boton-secundario">Buscar</button>
            <?php if ($conFiltros): ?>
                <a class="boton boton-secundario" href="<?= e(url('visitas')) ?>">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="tabla-contenedor">
            <?php if (empty($visitas)): ?>
                <p class="sin-resultados">No hay visitas que coincidan con la búsqueda.</p>
            <?php else: ?>
            <table class="tabla tabla-historial">
                <thead>
                    <tr>
                        <th>Fecha y horario</th>
                        <th>Visitante</th>
                        <th>Contacto</th>
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
                        <td data-etiqueta="Registrado por"><?= e($v['registrado_por']) ?></td>
                        <td data-etiqueta="Observaciones" class="celda-obs">
                            <?php $volver = 'visitas'; require APP_PATH . '/views/parciales/celda_observaciones.php'; ?>
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

        <?php $rutaListado = 'visitas'; $filtros = $filtrosLista; require APP_PATH . '/views/parciales/paginacion.php'; ?>
    <?php endif; ?>
</section>
