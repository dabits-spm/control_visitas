<?php
// Notificaciones e historial de alertas pendientes (administrador y recepcionista).
// Solo se muestran registros PENDIENTES: en cuanto una visita cambia de estado, su
// notificación desaparece sola (ver app/models/Notificacion.php).
// Variables recibidas desde NotificacionController::index():
//   $grupos [tipo => notificaciones], $resumen, $correoActivo, $csrf, $flashOk, $flashAviso, $errores
$iconosNotificacion = [
    'seguimiento'     => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    'cita_hoy'        => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    'aviso_pendiente' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
    'en_curso'        => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 11h-6"/>',
];
$volver = 'notificaciones';
$volverFiltros = '';
?>
<section class="encabezado-pagina">
    <div>
        <h1>Notificaciones</h1>
        <p>Citas y tareas pendientes de hoy, <?= e(mb_strtolower(fechaHoyEnTexto(), 'UTF-8')) ?>. Al atender cada una (cambiar su estado), desaparece de esta lista.</p>
    </div>
    <a class="boton boton-secundario" href="<?= e(url('notificaciones')) ?>">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
        Actualizar
    </a>
</section>

<?php require APP_PATH . '/views/parciales/alertas.php'; ?>
<?php require APP_PATH . '/views/parciales/aviso_correo.php'; ?>

<section class="tarjetas-resumen resumen-notificaciones" aria-label="Resumen de pendientes">
    <article class="tarjeta-dato tarjeta-roja">
        <div><h2>Citas sin actualizar</h2><p class="dato-numero"><?= (int) $resumen['seguimiento'] ?></p></div>
        <span class="dato-icono" aria-hidden="true"><svg class="icono" viewBox="0 0 24 24"><?= $iconosNotificacion['seguimiento'] ?></svg></span>
    </article>
    <article class="tarjeta-dato tarjeta-ambar">
        <div><h2>Alertas pendientes</h2><p class="dato-numero"><?= (int) $resumen['alertas'] ?></p></div>
        <span class="dato-icono" aria-hidden="true"><svg class="icono" viewBox="0 0 24 24"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/></svg></span>
    </article>
    <article class="tarjeta-dato tarjeta-oscura">
        <div><h2>Visitas en curso</h2><p class="dato-numero"><?= (int) $resumen['tareas'] ?></p></div>
        <span class="dato-icono" aria-hidden="true"><svg class="icono" viewBox="0 0 24 24"><?= $iconosNotificacion['en_curso'] ?></svg></span>
    </article>
</section>

<?php if (empty($grupos)): ?>
    <section class="panel">
        <div class="estado-vacio">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><path d="m22 4-10 10-3-3"/></svg>
            <p><strong>Todo al día.</strong></p>
            <p>No hay citas ni tareas pendientes en este momento.</p>
        </div>
    </section>
<?php endif; ?>

<?php foreach ($grupos as $tipo => $lista): ?>
    <section class="panel bloque-notificaciones bloque-<?= e($tipo) ?>">
        <div class="panel-cabecera">
            <h2>
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><?= $iconosNotificacion[$tipo] ?></svg>
                <?= e(Notificacion::TIPOS[$tipo]) ?>
            </h2>
            <span class="contador"><?= count($lista) ?></span>
        </div>
        <?php if ($tipo === 'seguimiento'): ?>
            <p class="nota-bloque">La hora de estas citas pasó hace <?= Notificacion::MINUTOS_SEGUIMIENTO ?> minutos o más y su estado no se ha actualizado.
               ¿Se realizaron? Inícielas, cancélelas o reprográmelas. Las marcadas en rojo llevan más de <?= Notificacion::MINUTOS_ATRASO ?> minutos.</p>
        <?php endif; ?>

        <ul class="tarjetas-notificacion">
            <?php foreach ($lista as $n): $v = $n['visita']; ?>
                <li class="tarjeta-notificacion notificacion-<?= e($tipo) ?><?= $n['urgente'] ? ' notificacion-urgente' : '' ?>" id="<?= e($n['clave']) ?>">
                    <span class="notificacion-icono" aria-hidden="true">
                        <svg class="icono" viewBox="0 0 24 24"><?= $iconosNotificacion[$tipo] ?></svg>
                    </span>
                    <div class="notificacion-cuerpo">
                        <p class="notificacion-titulo">
                            <strong><?= e($n['titulo']) ?></strong>
                            <?php if ($n['urgente']): ?><span class="marca-urgente">Atrasada</span><?php endif; ?>
                        </p>
                        <p class="notificacion-detalle"><?= e($n['detalle']) ?></p>
                        <p class="notificacion-meta">
                            <a class="enlace-visitante" href="<?= e(url('visitantes/ver?id=' . (int) $v['id_visitante'])) ?>"><?= e($v['visitante']) ?></a>
                            · <span class="texto-mono"><?= e($v['cedula']) ?></span> · <?= e($v['telefono']) ?>
                            · <?php require APP_PATH . '/views/parciales/detalle_estado.php'; ?>
                        </p>
                    </div>
                    <div class="notificacion-acciones">
                        <?php require APP_PATH . '/views/parciales/selector_estado.php'; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endforeach; ?>
