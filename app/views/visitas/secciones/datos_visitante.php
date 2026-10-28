<?php
// app/views/visitas/secciones/datos_visitante.php
// Sección 1 del registro de visitas: datos personales del visitante (usa $valor, $error y $claseCampo de crear.php).
?>
<section class="panel seccion-formulario">
    <div class="seccion-titulo">
        <span class="paso">1</span>
        <div>
            <h2>Datos personales del visitante</h2>
            <p>Escriba la cédula primero: si el visitante ya está registrado, sus datos se cargan solos.
               Use <strong>V</strong> para venezolanos y <strong>E</strong> para extranjeros; sin letra se toma como V.</p>
        </div>
    </div>

    <div class="aviso-visitante" data-aviso-visitante hidden>
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><path d="m22 4-10 10-3-3"/></svg>
        <span data-aviso-texto
              data-texto-editable="Visitante ya registrado: se cargaron sus datos. Puede corregirlos si cambiaron."
              data-texto-contacto="Visitante ya registrado: se cargaron sus datos. Puede actualizar teléfono, correo y procedencia.">
            Visitante ya registrado: se cargaron sus datos.
        </span>
    </div>

    <div class="rejilla rejilla-2">
        <div class="<?= $claseCampo('cedula') ?>">
            <label for="cedula">Cédula de identidad <span class="obligatorio">*</span></label>
            <div class="control-icono">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="8" cy="12" r="2"/><path d="M14 10h4M14 14h4"/></svg>
                <input type="text" id="cedula" name="cedula" required maxlength="14"
                       autocomplete="off" placeholder="Ej: V-28123456 o E-81234567"
                       value="<?= e($valor('cedula')) ?>"
                       data-validar="cedula" data-buscar-visitante="<?= e(url('visitantes/buscar')) ?>">
                <span class="indicador-busqueda" data-indicador-busqueda aria-hidden="true"></span>
            </div>
            <small class="campo-error" data-error-de="cedula"><?= e($error('cedula')) ?></small>
        </div>

        <div class="<?= $claseCampo('telefono') ?>">
            <label for="telefono">Teléfono <span class="obligatorio">*</span></label>
            <div class="control-icono">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg>
                <input type="tel" id="telefono" name="telefono" required maxlength="20"
                       autocomplete="tel" placeholder="Ej: 0414-1234567 o +57 300 1234567"
                       value="<?= e($valor('telefono')) ?>" data-validar="telefono">
            </div>
            <small class="campo-error" data-error-de="telefono"><?= e($error('telefono')) ?></small>
        </div>

        <div class="<?= $claseCampo('nombre') ?>">
            <label for="nombre">Nombre <span class="obligatorio">*</span></label>
            <div class="control-icono">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
                <input type="text" id="nombre" name="nombre" required maxlength="60"
                       autocomplete="given-name" placeholder="Nombre del visitante"
                       value="<?= e($valor('nombre')) ?>" data-validar="nombre" data-nombre-campo="nombre">
            </div>
            <small class="campo-error" data-error-de="nombre"><?= e($error('nombre')) ?></small>
        </div>

        <div class="<?= $claseCampo('apellido') ?>">
            <label for="apellido">Apellido <span class="obligatorio">*</span></label>
            <div class="control-icono">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
                <input type="text" id="apellido" name="apellido" required maxlength="60"
                       autocomplete="family-name" placeholder="Apellido del visitante"
                       value="<?= e($valor('apellido')) ?>" data-validar="nombre" data-nombre-campo="apellido">
            </div>
            <small class="campo-error" data-error-de="apellido"><?= e($error('apellido')) ?></small>
        </div>

        <div class="<?= $claseCampo('correo') ?>">
            <label for="correo">Correo electrónico <span class="obligatorio">*</span></label>
            <div class="control-icono">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                <input type="email" id="correo" name="correo" required maxlength="100"
                       autocomplete="email" placeholder="nombre@correo.com"
                       value="<?= e($valor('correo')) ?>" data-validar="correo">
            </div>
            <small class="campo-error" data-error-de="correo"><?= e($error('correo')) ?></small>
        </div>

        <div class="<?= $claseCampo('procedencia') ?>">
            <label for="procedencia">Procedencia <span class="opcional">(opcional)</span></label>
            <div class="control-icono">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16M2 21h20M8 7h4M8 11h4M8 15h4"/></svg>
                <input type="text" id="procedencia" name="procedencia" maxlength="100"
                       placeholder="Empresa, institución o ciudad"
                       value="<?= e($valor('procedencia')) ?>">
            </div>
            <small class="campo-error" data-error-de="procedencia"><?= e($error('procedencia')) ?></small>
        </div>
    </div>
</section>
