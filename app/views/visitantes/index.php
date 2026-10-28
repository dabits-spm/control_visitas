<?php
// Variables recibidas desde VisitanteController::index():
//   $visitantes (página actual), $paginacion, $filtros ['q'], $hayVisitantes, $flashOk, $errores
$filtrosLista = array_filter(['q' => $filtros['q']]);
?>
<section class="encabezado-pagina">
    <div>
        <h1>Visitantes</h1>
        <p>Personas registradas en el sistema. Haga clic en un nombre para ver su ficha y el historial de sus visitas.</p>
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
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/></svg>
            Listado de visitantes
        </h2>
        <span class="contador">
            <?= $paginacion['total'] ?> <?= $paginacion['total'] === 1 ? 'visitante' : 'visitantes' ?><?= $filtros['q'] !== '' ? ' encontrados' : '' ?>
        </span>
    </div>

    <?php if (!$hayVisitantes): ?>
        <div class="estado-vacio">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
            <p><strong>Todavía no hay visitantes registrados.</strong></p>
            <p>Los visitantes se registran automáticamente al crear una visita.</p>
        </div>
    <?php else: ?>
        <form method="get" action="<?= e(url('visitantes')) ?>" class="filtros" role="search">
            <label class="campo-busqueda">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" name="q" value="<?= e($filtros['q']) ?>" maxlength="100" data-busqueda-rapida
                       placeholder="Buscar por cédula, nombre, teléfono, correo o procedencia..." aria-label="Buscar visitantes">
            </label>
            <button type="submit" class="boton boton-secundario">Buscar</button>
            <?php if ($filtros['q'] !== ''): ?>
                <a class="boton boton-secundario" href="<?= e(url('visitantes')) ?>">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="tabla-contenedor">
            <?php if (empty($visitantes)): ?>
                <p class="sin-resultados">No hay visitantes que coincidan con la búsqueda.</p>
            <?php else: ?>
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Cédula</th>
                        <th>Nombre y Apellido</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Procedencia</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($visitantes as $vi): ?>
                    <?php $nombreCompleto = $vi['nombre'] . ' ' . $vi['apellido']; $enlaceFicha = url('visitantes/ver?id=' . (int) $vi['id_visitante']); ?>
                    <tr>
                        <td data-etiqueta="Cédula" class="texto-mono"><?= e($vi['cedula']) ?></td>
                        <td data-etiqueta="Nombre y Apellido">
                            <div class="celda-visitante">
                                <span class="avatar" aria-hidden="true"><?= e(iniciales($nombreCompleto)) ?></span>
                                <div>
                                    <a class="enlace-visitante" href="<?= e($enlaceFicha) ?>"><?= e($nombreCompleto) ?></a>
                                    <small class="texto-suave"><?= (int) $vi['total_visitas'] ?> <?= (int) $vi['total_visitas'] === 1 ? 'visita' : 'visitas' ?></small>
                                </div>
                            </div>
                        </td>
                        <td data-etiqueta="Teléfono" class="sin-salto"><?= e($vi['telefono']) ?></td>
                        <td data-etiqueta="Correo"><?= e($vi['correo'] ?: '—') ?></td>
                        <td data-etiqueta="Procedencia"><?= e($vi['procedencia'] ?: '—') ?></td>
                        <td data-etiqueta="Acción">
                            <div class="acciones-fila">
                                <a class="boton boton-secundario boton-chico" href="<?= e($enlaceFicha) ?>">Ver ficha</a>
                                <?php if (puede('visitantes.editar')): ?>
                                    <a class="boton boton-secundario boton-chico" href="<?= e(url('visitantes/editar?id=' . (int) $vi['id_visitante'])) ?>">
                                        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                        Editar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <?php $rutaListado = 'visitantes'; $filtros = $filtrosLista; require APP_PATH . '/views/parciales/paginacion.php'; ?>
    <?php endif; ?>
</section>
