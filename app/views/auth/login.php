<?php
// Variables recibidas desde AuthController::mostrarLogin(): $csrf, $error, $usuarioEscrito, $mensajeSalida
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión - Control de Visitas · <?= e(INSTITUCION_NOMBRE) ?></title>
    <?php foreach (hojasDeEstilo() as $hoja): ?>
    <link rel="stylesheet" href="<?= e($hoja) ?>">
    <?php endforeach; ?>
</head>
<body class="pagina-login" data-pagina-login>
    <main class="login-envoltura">
        <section class="login-presentacion" aria-label="Institución"
                 style="--foto-sede: url('<?= e(recurso(INSTITUCION_FOTO_LOGIN)) ?>')">
            <div class="login-institucion">
                <div class="login-emblema" aria-hidden="true">
                    <svg class="icono" viewBox="0 0 24 24"><path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/><path d="M22 10v6"/></svg>
                </div>
                <h2 class="login-titulo">Control de Visitas</h2>
                <div class="login-separador" aria-hidden="true"><span></span></div>
                <p class="login-nombre-institucion"><?= e(INSTITUCION_NOMBRE_COMPLETO) ?></p>
                <p class="login-sede"><?= e(INSTITUCION_SEDE) ?></p>
            </div>

            <p class="login-ubicacion">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span><?= e(INSTITUCION_UBICACION) ?></span>
            </p>
        </section>

        <section class="login-tarjeta">
            <h1>Iniciar sesión</h1>
            <p class="login-subtitulo">Ingrese sus credenciales para acceder al sistema.</p>

            <?php if (!empty($mensajeSalida) && empty($error)): ?>
                <div class="alerta alerta-exito" role="status">
                    <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><path d="m22 4-10 10-3-3"/></svg>
                    <span><?= e($mensajeSalida) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alerta alerta-error" role="alert">
                    <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('login')) ?>" autocomplete="off" class="formulario">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

                <div class="campo">
                    <label for="user">Usuario</label>
                    <div class="control-icono">
                        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
                        <input type="text" id="user" name="user" required autofocus
                               value="<?= e($usuarioEscrito ?? '') ?>" placeholder="Su nombre de usuario">
                    </div>
                </div>

                <div class="campo">
                    <label for="password">Contraseña</label>
                    <div class="control-icono">
                        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="password" name="password" required placeholder="••••••••">
                        <button type="button" class="ver-clave" aria-label="Mostrar contraseña" data-ver-clave="password">
                            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="boton boton-primario boton-bloque">Ingresar</button>
            </form>
        </section>
    </main>

    <?php foreach (scriptsDelSistema() as $script): ?>
    <script src="<?= e($script) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
