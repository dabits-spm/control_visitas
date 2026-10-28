<?php
// Parcial: botones de descarga de un reporte (Excel, texto) e impresión.
// Variables: $rutaReporte ('reportes' | 'reportes/personalizado'), $consultaExportar (parámetros del reporte)
$enlaceFormato = static fn (string $formato): string =>
    url($rutaReporte) . '?' . http_build_query($consultaExportar + ['formato' => $formato]);
?>
<div class="acciones-reporte">
    <a class="boton boton-secundario" href="<?= e($enlaceFormato('xlsx')) ?>" download>
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13l4 5M12 13l-4 5"/></svg>
        Excel
    </a>
    <a class="boton boton-secundario" href="<?= e($enlaceFormato('txt')) ?>" download>
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8M8 9h2"/></svg>
        Texto (.txt)
    </a>
    <button type="button" class="boton boton-primario" data-imprimir>
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
        Imprimir / PDF
    </button>
</div>
