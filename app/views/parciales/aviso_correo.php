<?php
// Parcial: avisa cuando los correos a los visitantes no se pueden enviar (falta configurar el SMTP).
if (CorreoSmtp::estaConfigurado()) {
    return;
}
$problemasCorreo = CorreoSmtp::problemas();
?>
<div class="alerta alerta-aviso aviso-correo-inactivo" role="status" data-aviso-correo>
    <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
    <span>
        <strong>Los visitantes no están recibiendo correos</strong> (avisos de citas y recordatorios 24 horas antes).
        <?= e($problemasCorreo[0] ?? '') ?>
        <?php if (puede('correo.configurar')): ?>
            <a href="<?= e(url('correo')) ?>">Configurar el correo</a>
        <?php else: ?>
            Pida al administrador que configure el correo.
        <?php endif; ?>
    </span>
</div>
