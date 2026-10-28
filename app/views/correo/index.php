<?php
// Configuración de correo (solo administrador).
// Variables desde CorreoController::index(): $config, $cifrados, $problemas, $sePuedeEscribir, $old,
// $erroresCampos, $detallePrueba, $correoPrueba, $proximas, $sinAviso, $ultimaRevision, $csrf,
// $flashOk, $flashAviso, $errores
$valor = static fn (string $campo) => $old[$campo] ?? $config[$campo] ?? '';
$error = static fn (string $campo): string => $erroresCampos[$campo] ?? '';
$claseCampo = static fn (string $campo): string => isset($erroresCampos[$campo]) ? 'campo con-error' : 'campo';
$listo = $problemas === [];
$habilitado = $old !== null ? !empty($old['habilitado']) : $config['habilitado'];
?>
<section class="encabezado-pagina">
    <div>
        <h1>Configuración de correo</h1>
        <p>Cuenta con la que el sistema envía los avisos de las citas y el recordatorio 24 horas antes.</p>
    </div>
</section>

<?php require APP_PATH . '/views/parciales/alertas.php'; ?>

<section class="estado-correo <?= $listo ? 'estado-correo-listo' : 'estado-correo-pendiente' ?>" data-estado-correo>
    <span class="estado-correo-icono" aria-hidden="true">
        <?php if ($listo): ?>
            <svg class="icono" viewBox="0 0 24 24"><path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><path d="m22 4-10 10-3-3"/></svg>
        <?php else: ?>
            <svg class="icono" viewBox="0 0 24 24"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <?php endif; ?>
    </span>
    <div>
        <?php if ($listo): ?>
            <strong>El envío de correos está activo.</strong>
            <p>Los correos salen desde <span class="texto-mono"><?= e(CORREO_REMITENTE) ?></span>
               (<?= e(CORREO_HOST) ?>:<?= (int) CORREO_PUERTO ?>, <?= e(CORREO_SEGURIDAD ? strtoupper(CORREO_SEGURIDAD) : 'sin cifrado') ?>).
               Si aún no lo hizo, envíe un correo de prueba.</p>
        <?php else: ?>
            <strong>Todavía no se envían correos a los visitantes.</strong>
            <ul>
                <?php foreach ($problemas as $p): ?><li><?= e($p) ?></li><?php endforeach; ?>
            </ul>
            <p>Complete los datos de la cuenta aquí abajo y guarde.</p>
        <?php endif; ?>
    </div>
</section>

<div class="rejilla-correo">
    <?php require APP_PATH . '/views/correo/secciones/cuenta.php'; ?>

    <div class="columna-correo">
        <form method="post" action="<?= e(url('correo/probar')) ?>" class="panel seccion-formulario formulario">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <div class="seccion-titulo">
                <span class="paso">2</span>
                <div>
                    <h2>Correo de prueba</h2>
                    <p>Envía un aviso de ejemplo para comprobar la configuración.</p>
                </div>
            </div>
            <div class="campo">
                <label for="destino">Enviar la prueba a</label>
                <input type="email" id="destino" name="destino" maxlength="120" value="<?= e($correoPrueba) ?>" required placeholder="su_correo@gmail.com">
            </div>
            <button type="submit" class="boton boton-secundario" <?= $listo ? '' : 'disabled' ?>>
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4z"/><path d="M22 2 11 13"/></svg>
                Enviar correo de prueba
            </button>
            <?php if ($detallePrueba): ?>
                <details class="detalle-smtp" open>
                    <summary>Conversación con el servidor (para encontrar el problema)</summary>
                    <pre><?= e(implode("\n", $detallePrueba)) ?></pre>
                </details>
            <?php endif; ?>
        </form>

        <?php require APP_PATH . '/views/correo/secciones/recordatorios.php'; ?>
    </div>
</div>
