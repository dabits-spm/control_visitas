<?php
// Parcial: navegación entre páginas de un listado.
// Variables: $paginacion (ver paginacion()), $rutaListado ('visitas' | 'visitantes'), $filtros (parámetros a conservar)
$pag = $paginacion;
if ($pag['paginas'] <= 1) {
    return;
}
$enlace = static fn (int $numero): string => urlConFiltros($rutaListado, $filtros + ['pagina' => $numero > 1 ? $numero : null]);

// Números visibles: primera, última y dos a cada lado de la actual (1 … 4 5 [6] 7 8 … 20)
$numeros = array_unique(array_filter(
    [1, $pag['pagina'] - 2, $pag['pagina'] - 1, $pag['pagina'], $pag['pagina'] + 1, $pag['pagina'] + 2, $pag['paginas']],
    static fn ($n) => $n >= 1 && $n <= $pag['paginas']
));
sort($numeros);
?>
<nav class="paginacion" aria-label="Páginas del listado">
    <p class="paginacion-resumen">Mostrando <?= $pag['desde'] ?>–<?= $pag['hasta'] ?> de <?= $pag['total'] ?></p>
    <ul>
        <li>
            <?php if ($pag['pagina'] > 1): ?>
                <a href="<?= e($enlace($pag['pagina'] - 1)) ?>" rel="prev" aria-label="Página anterior">‹ Anterior</a>
            <?php else: ?>
                <span class="desactivado" aria-hidden="true">‹ Anterior</span>
            <?php endif; ?>
        </li>
        <?php $anterior = 0; foreach ($numeros as $n): ?>
            <?php if ($n - $anterior > 1): ?><li><span class="separador">…</span></li><?php endif; ?>
            <li>
                <?php if ($n === $pag['pagina']): ?>
                    <span class="actual" aria-current="page"><?= $n ?></span>
                <?php else: ?>
                    <a href="<?= e($enlace($n)) ?>" aria-label="Página <?= $n ?>"><?= $n ?></a>
                <?php endif; ?>
            </li>
        <?php $anterior = $n; endforeach; ?>
        <li>
            <?php if ($pag['pagina'] < $pag['paginas']): ?>
                <a href="<?= e($enlace($pag['pagina'] + 1)) ?>" rel="next" aria-label="Página siguiente">Siguiente ›</a>
            <?php else: ?>
                <span class="desactivado" aria-hidden="true">Siguiente ›</span>
            <?php endif; ?>
        </li>
    </ul>
</nav>
