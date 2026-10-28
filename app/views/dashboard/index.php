<?php
// Variables recibidas desde DashboardController::index():
//   $visitasHoy, $visitasEnProceso, $totalVisitantes, $visitasDelDia, $porDepartamento, $porEstado,
//   $nombresEstado, $csrf, $correoActivo, $flashOk, $flashAviso, $errores
?>
<section class="encabezado-pagina">
    <div>
        <h1>Panel de Control</h1>
        <p>Gestión de accesos y visitas registradas para el día de hoy &middot; <span class="fecha-hoy"><?= e(fechaHoyEnTexto()) ?></span></p>
    </div>
    <a class="boton boton-primario" href="<?= e(url('visitas/crear')) ?>">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        Registrar Nueva Visita
    </a>
</section>

<?php require APP_PATH . '/views/parciales/alertas.php'; ?>
<?php require APP_PATH . '/views/parciales/aviso_correo.php'; ?>

<section class="tarjetas-resumen" aria-label="Resumen del día">
    <article class="tarjeta-dato tarjeta-oscura">
        <div>
            <h2>Visitas de Hoy</h2>
            <p class="dato-numero"><?= (int) $visitasHoy ?></p>
        </div>
        <span class="dato-icono" aria-hidden="true">
            <svg class="icono" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        </span>
    </article>

    <article class="tarjeta-dato tarjeta-ambar">
        <div>
            <h2>En Proceso</h2>
            <p class="dato-numero"><?= (int) $visitasEnProceso ?></p>
        </div>
        <span class="dato-icono" aria-hidden="true">
            <svg class="icono" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        </span>
    </article>

    <article class="tarjeta-dato tarjeta-roja">
        <div>
            <h2>Total de Visitantes</h2>
            <p class="dato-numero"><?= (int) $totalVisitantes ?></p>
        </div>
        <span class="dato-icono" aria-hidden="true">
            <svg class="icono" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/></svg>
        </span>
    </article>
</section>

<?php require APP_PATH . '/views/parciales/graficos_dia.php'; ?>

<section class="panel">
    <div class="panel-cabecera">
        <h2>
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2M9 12h6M9 16h6"/></svg>
            Listado de Visitas del Día
        </h2>
        <span class="contador"><?= count($visitasDelDia) ?> <?= count($visitasDelDia) === 1 ? 'visita' : 'visitas' ?></span>
    </div>

    <?php if (empty($visitasDelDia)): ?>
        <div class="estado-vacio">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M9 16h6"/></svg>
            <p><strong>No hay visitas registradas para hoy.</strong></p>
            <p>Cuando registre una visita aparecerá en este listado.</p>
            <a class="boton boton-primario" href="<?= e(url('visitas/crear')) ?>">Registrar la primera visita</a>
        </div>
    <?php else: ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Visitante</th>
                        <th>Departamento</th>
                        <th>Hora</th>
                        <th>Estado Actual</th>
                        <th>Observaciones</th>
                        <th>Acción / Cambiar Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($visitasDelDia as $v): ?>
                    <tr>
                        <td data-etiqueta="Visitante">
                            <div class="celda-visitante">
                                <span class="avatar" aria-hidden="true"><?= e(iniciales($v['visitante'])) ?></span>
                                <div>
                                    <a class="enlace-visitante" href="<?= e(url('visitantes/ver?id=' . (int) $v['id_visitante'])) ?>"><?= e($v['visitante']) ?></a>
                                    <small class="texto-mono"><?= e($v['cedula']) ?></small>
                                    <small><?= e($v['telefono']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td data-etiqueta="Departamento"><?= e($v['departamentos'] ?? '—') ?></td>
                        <td data-etiqueta="Hora">
                            <span class="hora"><?= e(horaDoce($v['hora_entrada'])) ?></span>
                            <small class="texto-suave">Salida: <?= e(horaDoce($v['hora_salida'])) ?></small>
                        </td>
                        <td data-etiqueta="Estado Actual">
                            <?php require APP_PATH . '/views/parciales/detalle_estado.php'; ?>
                        </td>
                        <td data-etiqueta="Observaciones" class="celda-obs">
                            <?php $volver = 'dashboard'; $volverFiltros = ''; require APP_PATH . '/views/parciales/celda_observaciones.php'; ?>
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
