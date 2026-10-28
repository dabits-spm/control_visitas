<?php
// Parcial: mensajes de éxito y de error.
// Variables: $flashOk (string|null), $flashAviso (string|null, advertencia), $errores (array)
// Si la vista define $erroresAlerta, se muestran esos en lugar de $errores.
$listaErrores = $erroresAlerta ?? ($errores ?? []);
?>
<?php if (!empty($flashOk)): ?>
    <div class="alerta alerta-exito" role="status">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><path d="m22 4-10 10-3-3"/></svg>
        <span><?= e($flashOk) ?></span>
        <button type="button" class="alerta-cerrar" aria-label="Cerrar mensaje" data-cerrar-alerta>&times;</button>
    </div>
<?php endif; ?>

<?php if (!empty($flashAviso)): ?>
    <div class="alerta alerta-aviso" role="status">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <span><?= e($flashAviso) ?></span>
        <button type="button" class="alerta-cerrar" aria-label="Cerrar mensaje" data-cerrar-alerta>&times;</button>
    </div>
<?php endif; ?>

<?php if (!empty($listaErrores)): ?>
    <div class="alerta alerta-error" role="alert">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
        <div>
            <?php if (count($listaErrores) === 1): ?>
                <span><?= e(reset($listaErrores)) ?></span>
            <?php else: ?>
                <strong>Revise los siguientes datos:</strong>
                <ul>
                    <?php foreach ($listaErrores as $mensaje): ?>
                        <li><?= e($mensaje) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <button type="button" class="alerta-cerrar" aria-label="Cerrar mensaje" data-cerrar-alerta>&times;</button>
    </div>
<?php endif; ?>
