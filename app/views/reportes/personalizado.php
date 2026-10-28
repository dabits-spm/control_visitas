<?php
// Variables recibidas desde ReporteController::personalizado():
//   $desde, $hasta, $filtros, $estados, $departamentos, $usuarios,
//   $columnasDisponibles, $columnas, $errores, $flashOk,
//   $visitas (null si hay errores), $porEstado, $totalPersonas, $filtrosAplicados
$usuarioReporte = $_SESSION['usuario'] ?? [];
$mostrar = static fn (string $columna): bool => in_array($columna, $columnas, true);
?>
<?php if ($visitas !== null): ?>
    <!-- El listado detallado se imprime en horizontal para que quepan las columnas -->
    <style media="print">@page { size: A4 landscape; }</style>
<?php endif; ?>

<section class="encabezado-pagina no-imprimir">
    <div>
        <h1>Reportes de Visitas</h1>
        <p>Combine filtros y elija las columnas del listado. Descárguelo en Excel o texto, o use «Imprimir / PDF».</p>
    </div>
    <?php if ($visitas !== null): ?>
        <?php $rutaReporte = 'reportes/personalizado'; require APP_PATH . '/views/parciales/botones_exportar.php'; ?>
    <?php endif; ?>
</section>

<?php $pestanaActiva = 'personalizado'; require APP_PATH . '/views/parciales/pestanas_reportes.php'; ?>

<div class="no-imprimir">
    <?php require APP_PATH . '/views/parciales/alertas.php'; ?>
</div>

<form method="get" action="<?= e(url('reportes/personalizado')) ?>" class="panel filtros-personalizado no-imprimir">
    <input type="hidden" name="generar" value="1">

    <div class="rejilla rejilla-3">
        <div class="campo">
            <label for="desde">Desde</label>
            <input type="date" id="desde" name="desde" required value="<?= e($desde) ?>">
        </div>
        <div class="campo">
            <label for="hasta">Hasta</label>
            <input type="date" id="hasta" name="hasta" required value="<?= e($hasta) ?>">
        </div>
        <div class="campo">
            <label for="visitante">Visitante <span class="opcional">(cédula o nombre)</span></label>
            <input type="search" id="visitante" name="visitante" maxlength="100"
                   placeholder="Ej: V-12345678 o María" value="<?= e($filtros['visitante']) ?>">
        </div>
        <div class="campo">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="">Todos los estados</option>
                <?php foreach ($estados as $id => $nombre): ?>
                    <option value="<?= (int) $id ?>" <?= $filtros['id_estado'] === (int) $id ? 'selected' : '' ?>><?= e($nombre) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="tipo">Tipo de visita</label>
            <select id="tipo" name="tipo">
                <option value="">Al momento y programadas</option>
                <?php foreach (Visita::TIPOS as $clave => $nombre): ?>
                    <option value="<?= e($clave) ?>" <?= $filtros['tipo_visita'] === $clave ? 'selected' : '' ?>><?= e($nombre) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="departamento">Departamento</label>
            <select id="departamento" name="departamento">
                <option value="">Todos los departamentos</option>
                <?php foreach ($departamentos as $id => $nombre): ?>
                    <option value="<?= (int) $id ?>" <?= $filtros['id_departamento'] === (int) $id ? 'selected' : '' ?>><?= e($nombre) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="usuario">Registradas por</label>
            <select id="usuario" name="usuario">
                <option value="">Todos los usuarios</option>
                <?php foreach ($usuarios as $id => $nombre): ?>
                    <option value="<?= (int) $id ?>" <?= $filtros['id_usuario'] === (int) $id ? 'selected' : '' ?>><?= e($nombre) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <fieldset class="campo">
        <legend>Columnas del reporte <span class="opcional">(la fecha y el visitante siempre se incluyen)</span></legend>
        <div class="chips">
            <?php foreach ($columnasDisponibles as $clave => $nombre): ?>
                <label class="chip">
                    <input type="checkbox" name="columnas[]" value="<?= e($clave) ?>" <?= $mostrar($clave) ? 'checked' : '' ?>>
                    <span>
                        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        <?= e($nombre) ?>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <div class="acciones-filtros">
        <a class="boton boton-secundario" href="<?= e(url('reportes/personalizado')) ?>">Limpiar filtros</a>
        <button type="submit" class="boton boton-primario">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 3H2l8 9.5V19l4 2v-8.5z"/></svg>
            Generar reporte
        </button>
    </div>
</form>

<?php if ($visitas !== null): ?>
    <!-- Encabezado que solo aparece en la hoja impresa / PDF -->
    <header class="encabezado-impresion solo-impresion">
        <p class="encabezado-impresion-marca">Control de Visitas</p>
        <h1>Reporte personalizado de visitas</h1>
        <p>
            Periodo: <strong><?= e(fechaLarga($desde)) ?></strong> al <strong><?= e(fechaLarga($hasta)) ?></strong>
            &middot; Generado el <?= e(date('d/m/Y h:i A')) ?>
            <?php if (!empty($usuarioReporte)): ?>
                por <?= e($usuarioReporte['nombre'] . ' ' . $usuarioReporte['apellido']) ?>
            <?php endif; ?>
        </p>
        <p>Filtros: <?= e($filtrosAplicados ? implode(' · ', $filtrosAplicados) : 'ninguno (todas las visitas del periodo)') ?></p>
    </header>

    <section class="resumen-estados" aria-label="Resumen del reporte">
        <div class="resumen-estado">
            <span class="resumen-numero"><?= count($visitas) ?></span>
            <span>Visitas</span>
        </div>
        <div class="resumen-estado">
            <span class="resumen-numero"><?= (int) $totalPersonas ?></span>
            <span>Visitantes distintos</span>
        </div>
        <?php foreach ($estados as $id => $nombre): ?>
            <div class="resumen-estado">
                <span class="resumen-numero"><?= (int) ($porEstado[$id] ?? 0) ?></span>
                <span class="insignia insignia-<?= e(claseEstado($nombre)) ?>"><?= e($nombre) ?></span>
            </div>
        <?php endforeach; ?>
    </section>

    <?php require APP_PATH . '/views/reportes/secciones/tabla_personalizado.php'; ?>
<?php endif; ?>
