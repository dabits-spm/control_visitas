<?php
// Parcial: lista compacta de notificaciones (menú de la campana y aviso al iniciar sesión).
// Variables: $listaNotificaciones (Notificacion::pendientes), $limiteNotificaciones (int|null)
$iconosNotificacion = [
    'seguimiento'     => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    'cita_hoy'        => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    'aviso_pendiente' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
    'en_curso'        => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 11h-6"/>',
];
$mostradas = $limiteNotificaciones ? array_slice($listaNotificaciones, 0, $limiteNotificaciones) : $listaNotificaciones;
?>
<ul class="lista-notificaciones" data-lista-notificaciones>
    <?php foreach ($mostradas as $n): ?>
        <li class="notificacion notificacion-<?= e($n['tipo']) ?><?= $n['urgente'] ? ' notificacion-urgente' : '' ?>">
            <a href="<?= e(url('notificaciones')) ?>#<?= e($n['clave']) ?>">
                <span class="notificacion-icono" aria-hidden="true">
                    <svg class="icono" viewBox="0 0 24 24"><?= $iconosNotificacion[$n['tipo']] ?></svg>
                </span>
                <span class="notificacion-texto">
                    <strong><?= e($n['titulo']) ?></strong>
                    <small><?= e($n['detalle']) ?></small>
                </span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
<?php if (empty($mostradas)): ?>
    <p class="notificaciones-vacias" data-notificaciones-vacias>No hay citas ni tareas pendientes.</p>
<?php endif; ?>
