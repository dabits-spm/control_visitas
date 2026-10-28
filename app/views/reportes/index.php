<?php
// Variables recibidas desde ReporteController::index():
//   $tipo, $tipos, $rangos, $desde, $hasta, $errores, $flashOk,
//   $reporte (null si hay errores): periodos, totales, maximo, promedio, pico, departamentos
$infoTipo = $tipos[$tipo];
$usuarioReporte = $_SESSION['usuario'] ?? [];
?>
<section class="encabezado-pagina no-imprimir">
    <div>
        <h1>Reportes de Visitas</h1>
        <p>Cantidad de visitas por día, semana o mes, o un listado personalizado con filtros. Descárguelo en Excel o texto, o use «Imprimir / PDF».</p>
    </div>
    <?php if ($reporte): ?>
        <?php $rutaReporte = 'reportes'; require APP_PATH . '/views/parciales/botones_exportar.php'; ?>
    <?php endif; ?>
</section>

<?php $pestanaActiva = 'periodo'; require APP_PATH . '/views/parciales/pestanas_reportes.php'; ?>

<div class="no-imprimir">
    <?php require APP_PATH . '/views/parciales/alertas.php'; ?>
</div>

<form method="get" action="<?= e(url('reportes')) ?>" class="panel filtros-reporte no-imprimir">
    <div class="campo">
        <label for="tipo">Tipo de reporte</label>
        <select id="tipo" name="tipo" data-tipo-reporte>
            <?php foreach ($tipos as $clave => $info): ?>
                <option value="<?= e($clave) ?>" <?= $clave === $tipo ? 'selected' : '' ?>
                        data-desde="<?= e($rangos[$clave]['desde']) ?>"
                        data-hasta="<?= e($rangos[$clave]['hasta']) ?>">
                    <?= e(ucfirst($clave)) ?> (visitas por <?= e(mb_strtolower($info['periodo'], 'UTF-8')) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo">
        <label for="desde">Desde</label>
        <input type="date" id="desde" name="desde" required value="<?= e($desde) ?>">
    </div>
    <div class="campo">
        <label for="hasta">Hasta</label>
        <input type="date" id="hasta" name="hasta" required value="<?= e($hasta) ?>">
    </div>
    <button type="submit" class="boton boton-secundario">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"/><path d="M7 16v-4M12 16V8M17 16v-7"/></svg>
        Generar reporte
    </button>
</form>

<?php if ($reporte): ?>
    <!-- Encabezado que solo aparece en la hoja impresa / PDF -->
    <header class="encabezado-impresion solo-impresion">
        <p class="encabezado-impresion-marca">Control de Visitas</p>
        <h1><?= e($infoTipo['titulo']) ?></h1>
        <p>
            Periodo: <strong><?= e(fechaLarga($desde)) ?></strong> al <strong><?= e(fechaLarga($hasta)) ?></strong>
            &middot; Generado el <?= e(date('d/m/Y h:i A')) ?>
            <?php if (!empty($usuarioReporte)): ?>
                por <?= e($usuarioReporte['nombre'] . ' ' . $usuarioReporte['apellido']) ?>
            <?php endif; ?>
        </p>
    </header>

    <section class="tarjetas-resumen" aria-label="Resumen del reporte">
        <article class="tarjeta-dato tarjeta-oscura">
            <div>
                <h2>Total de visitas</h2>
                <p class="dato-numero"><?= (int) $reporte['totales']['total'] ?></p>
            </div>
            <span class="dato-icono" aria-hidden="true">
                <svg class="icono" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </span>
        </article>

        <article class="tarjeta-dato tarjeta-ambar">
            <div>
                <h2>Promedio por <?= e(mb_strtolower($infoTipo['periodo'], 'UTF-8')) ?></h2>
                <p class="dato-numero"><?= e(number_format($reporte['promedio'], 1, ',', '.')) ?></p>
            </div>
            <span class="dato-icono" aria-hidden="true">
                <svg class="icono" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m7 14 4-4 3 3 5-6"/></svg>
            </span>
        </article>

        <article class="tarjeta-dato tarjeta-roja">
            <div>
                <h2>Mayor afluencia</h2>
                <p class="dato-numero"><?= (int) $reporte['maximo'] ?></p>
                <small class="dato-detalle"><?= e($reporte['pico']['etiqueta'] ?? 'Sin visitas en el periodo') ?></small>
            </div>
            <span class="dato-icono" aria-hidden="true">
                <svg class="icono" viewBox="0 0 24 24"><path d="m3 17 6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>
            </span>
        </article>
    </section>

    <section class="panel bloque-reporte">
        <div class="panel-cabecera">
            <h2>
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"/><path d="M7 16v-4M12 16V8M17 16v-7"/></svg>
                Visitas por <?= e(mb_strtolower($infoTipo['periodo'], 'UTF-8')) ?>
            </h2>
            <span class="contador"><?= count($reporte['periodos']) ?> <?= e(count($reporte['periodos']) === 1 ? mb_strtolower($infoTipo['periodo'], 'UTF-8') : $infoTipo['plural']) ?></span>
        </div>

        <div class="tabla-contenedor">
            <table class="tabla tabla-reporte">
                <thead>
                    <tr>
                        <th><?= e($infoTipo['periodo']) ?></th>
                        <th class="numero">Programadas</th>
                        <th class="numero">Reprogramadas</th>
                        <th class="numero">En proceso</th>
                        <th class="numero">Finalizadas</th>
                        <th class="numero">Canceladas</th>
                        <th class="numero">Total</th>
                        <th class="columna-barra" aria-hidden="true"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($reporte['periodos'] as $p): ?>
                    <tr class="<?= $p['total'] === 0 ? 'fila-vacia' : '' ?>">
                        <td data-etiqueta="<?= e($infoTipo['periodo']) ?>"><?= e($p['etiqueta']) ?></td>
                        <td data-etiqueta="Programadas" class="numero"><?= (int) $p['programadas'] ?></td>
                        <td data-etiqueta="Reprogramadas" class="numero"><?= (int) $p['reprogramadas'] ?></td>
                        <td data-etiqueta="En proceso" class="numero"><?= (int) $p['en_proceso'] ?></td>
                        <td data-etiqueta="Finalizadas" class="numero"><?= (int) $p['finalizadas'] ?></td>
                        <td data-etiqueta="Canceladas" class="numero"><?= (int) $p['canceladas'] ?></td>
                        <td data-etiqueta="Total" class="numero"><strong><?= (int) $p['total'] ?></strong></td>
                        <td class="columna-barra" aria-hidden="true">
                            <span class="barra-reporte" style="width: <?= $reporte['maximo'] > 0 ? round($p['total'] / $reporte['maximo'] * 100, 1) : 0 ?>%"></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total del periodo</th>
                        <td data-etiqueta="Programadas" class="numero"><?= (int) $reporte['totales']['programadas'] ?></td>
                        <td data-etiqueta="Reprogramadas" class="numero"><?= (int) $reporte['totales']['reprogramadas'] ?></td>
                        <td data-etiqueta="En proceso" class="numero"><?= (int) $reporte['totales']['en_proceso'] ?></td>
                        <td data-etiqueta="Finalizadas" class="numero"><?= (int) $reporte['totales']['finalizadas'] ?></td>
                        <td data-etiqueta="Canceladas" class="numero"><?= (int) $reporte['totales']['canceladas'] ?></td>
                        <td data-etiqueta="Total" class="numero"><?= (int) $reporte['totales']['total'] ?></td>
                        <td class="columna-barra" aria-hidden="true"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <section class="panel bloque-reporte">
        <div class="panel-cabecera">
            <h2>
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4h2a2 2 0 0 1 2 2v10"/><path d="M2 21h20M8 7h4M8 11h4M8 15h4"/></svg>
                Visitas por departamento
            </h2>
        </div>

        <?php if (empty($reporte['departamentos'])): ?>
            <p class="sin-resultados">No hay visitas registradas en este periodo.</p>
        <?php else: ?>
            <?php $maxDepto = (int) $reporte['departamentos'][0]['total']; ?>
            <div class="tabla-contenedor">
                <table class="tabla tabla-reporte">
                    <thead>
                        <tr>
                            <th>Departamento</th>
                            <th class="numero">Visitas</th>
                            <th class="columna-barra" aria-hidden="true"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reporte['departamentos'] as $d): ?>
                        <tr>
                            <td data-etiqueta="Departamento"><?= e($d['nombre_departamento']) ?></td>
                            <td data-etiqueta="Visitas" class="numero"><strong><?= (int) $d['total'] ?></strong></td>
                            <td class="columna-barra" aria-hidden="true">
                                <span class="barra-reporte" style="width: <?= $maxDepto > 0 ? round($d['total'] / $maxDepto * 100, 1) : 0 ?>%"></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="nota-reporte">Una visita dirigida a varios departamentos se cuenta en cada uno de ellos.</p>
        <?php endif; ?>
    </section>
<?php endif; ?>
