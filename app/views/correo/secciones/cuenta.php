<?php
// app/views/correo/secciones/cuenta.php
// Configuración de correo: formulario de la cuenta SMTP (usa $valor, $error y $claseCampo de index.php).
?>
<form method="post" action="<?= e(url('correo/guardar')) ?>" class="panel seccion-formulario formulario" novalidate autocomplete="off">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <div class="seccion-titulo">
        <span class="paso">1</span>
        <div>
            <h2>Cuenta que envía los correos</h2>
            <p>Se recomienda una cuenta de Gmail de la institución (por ejemplo, de la recepción).</p>
        </div>
    </div>

    <details class="ayuda-gmail" <?= $config['tiene_clave'] ? '' : 'open' ?>>
        <summary>¿Cómo obtener la contraseña de aplicación de Gmail?</summary>
        <ol>
            <li>Inicie sesión en la cuenta de Gmail que enviará los correos.</li>
            <li>Active la <strong>verificación en dos pasos</strong> (Cuenta de Google → Seguridad).</li>
            <li>Entre a <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener noreferrer">myaccount.google.com/apppasswords</a>,
                escriba un nombre (por ejemplo «Control de Visitas») y pulse <strong>Crear</strong>.</li>
            <li>Copie la clave de 16 letras que aparece y péguela aquí en «Contraseña de aplicación».</li>
        </ol>
        <p>No use la contraseña normal de la cuenta: Gmail la rechaza.</p>
    </details>

    <?php if (!$sePuedeEscribir): ?>
        <div class="alerta alerta-error" role="alert">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            <span>El sistema no puede escribir app/config/correo.php (archivo o carpeta de solo lectura). Quite el atributo «Solo lectura» o edite el archivo a mano.</span>
        </div>
    <?php endif; ?>

    <div class="rejilla rejilla-2">
        <div class="<?= $claseCampo('username') ?>">
            <label for="username">Cuenta de correo <span class="obligatorio">*</span></label>
            <input type="email" id="username" name="username" maxlength="120" value="<?= e($valor('username')) ?>"
                   placeholder="recepcion.inces@gmail.com" autocomplete="off" required>
            <small class="campo-error" data-error-de="username"><?= e($error('username')) ?></small>
        </div>
        <div class="<?= $claseCampo('password') ?>">
            <label for="password">Contraseña de aplicación <?= $config['tiene_clave'] ? '<span class="opcional">(guardada)</span>' : '<span class="obligatorio">*</span>' ?></label>
            <div class="control-icono">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input type="password" id="password" name="password" maxlength="120" autocomplete="new-password"
                       placeholder="<?= $config['tiene_clave'] ? 'Déjela vacía para conservar la guardada' : 'abcd efgh ijkl mnop' ?>">
                <button type="button" class="ver-clave" aria-label="Mostrar contraseña" data-ver-clave="password">
                    <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            <small class="campo-error" data-error-de="password"><?= e($error('password')) ?></small>
        </div>
        <div class="<?= $claseCampo('from_name') ?>">
            <label for="from_name">Nombre del remitente <span class="obligatorio">*</span></label>
            <input type="text" id="from_name" name="from_name" maxlength="80" value="<?= e($valor('from_name')) ?>" required>
            <small class="campo-error" data-error-de="from_name"><?= e($error('from_name')) ?></small>
        </div>
        <div class="<?= $claseCampo('from_address') ?>">
            <label for="from_address">Correo del remitente <span class="opcional">(opcional)</span></label>
            <input type="email" id="from_address" name="from_address" maxlength="120" value="<?= e($valor('from_address')) ?>"
                   placeholder="El mismo de la cuenta">
            <small class="campo-error" data-error-de="from_address"><?= e($error('from_address')) ?></small>
        </div>
    </div>

    <details class="opciones-avanzadas" <?= ($erroresCampos['host'] ?? $erroresCampos['port'] ?? null) ? 'open' : '' ?>>
        <summary>Servidor (avanzado: ya viene listo para Gmail)</summary>
        <div class="rejilla rejilla-3">
            <div class="<?= $claseCampo('host') ?>">
                <label for="host">Servidor (host)</label>
                <input type="text" id="host" name="host" maxlength="120" value="<?= e($valor('host')) ?>">
                <small class="campo-error" data-error-de="host"><?= e($error('host')) ?></small>
            </div>
            <div class="<?= $claseCampo('port') ?>">
                <label for="port">Puerto</label>
                <input type="text" inputmode="numeric" id="port" name="port" maxlength="5" value="<?= e((string) $valor('port')) ?>" data-puerto-correo>
                <small class="campo-error" data-error-de="port"><?= e($error('port')) ?></small>
            </div>
            <div class="campo">
                <label for="encryption">Cifrado</label>
                <select id="encryption" name="encryption" data-cifrado-correo>
                    <?php foreach ($cifrados as $clave => $nombre): ?>
                        <option value="<?= e($clave) ?>" <?= (string) $valor('encryption') === (string) $clave ? 'selected' : '' ?>><?= e($nombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </details>

    <label class="casilla">
        <input type="checkbox" name="habilitado" value="1" <?= $habilitado ? 'checked' : '' ?>>
        <span>Enviar correos (desmarque para desactivar todos los envíos sin borrar los datos)</span>
    </label>

    <div class="acciones-formulario">
        <button type="submit" class="boton boton-primario" <?= $sePuedeEscribir ? '' : 'disabled' ?>>
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
            Guardar configuración
        </button>
    </div>
</form>
