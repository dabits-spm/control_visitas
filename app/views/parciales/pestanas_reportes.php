<?php
// Parcial: pestañas para elegir el tipo de reporte.
// Variable: $pestanaActiva ('periodo' | 'personalizado')
?>
<nav class="pestanas no-imprimir" aria-label="Tipos de reporte">
    <a href="<?= e(url('reportes')) ?>" class="<?= $pestanaActiva === 'periodo' ? 'activa' : '' ?>"
       <?= $pestanaActiva === 'periodo' ? 'aria-current="page"' : '' ?>>
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"/><path d="M7 16v-4M12 16V8M17 16v-7"/></svg>
        Por día, semana o mes
    </a>
    <a href="<?= e(url('reportes/personalizado')) ?>" class="<?= $pestanaActiva === 'personalizado' ? 'activa' : '' ?>"
       <?= $pestanaActiva === 'personalizado' ? 'aria-current="page"' : '' ?>>
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 3H2l8 9.5V19l4 2v-8.5z"/></svg>
        Personalizado
    </a>
</nav>
