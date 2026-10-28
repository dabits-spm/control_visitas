<?php
// Gestión de Usuarios: listado de recepcionistas (solo administrador).
// Variables recibidas desde UsuarioController::index():
//   $usuarios (página actual), $paginacion, $filtros ['q', 'estado'], $filtroEstados, $hayUsuarios,
//   $csrf, $flashOk, $flashAviso, $errores
$filtrosLista  = array_filter(['q' => $filtros['q'], 'estado' => $filtros['estado']]);
$volverFiltros = http_build_query($filtrosLista + ($paginacion['pagina'] > 1 ? ['pagina' => $paginacion['pagina']] : []));
?>
<section class="encabezado-pagina">
    <div>
        <h1>Gestión de Usuarios</h1>
        <p>Cuentas de los recepcionistas: cree, corrija sus datos, restablezca contraseñas o dé de baja cuentas.</p>
    </div>
    <a class="boton boton-primario" href="<?= e(url('usuarios/crear')) ?>">
        <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        Nuevo recepcionista
    </a>
</section>

<?php require APP_PATH . '/views/parciales/alertas.php'; ?>

<section class="panel">
    <div class="panel-cabecera">
        <h2>
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
            Recepcionistas
        </h2>
        <span class="contador"><?= $paginacion['total'] ?> <?= $paginacion['total'] === 1 ? 'cuenta' : 'cuentas' ?></span>
    </div>

    <?php if (!$hayUsuarios): ?>
        <div class="estado-vacio">
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
            <p><strong>Todavía no hay recepcionistas registrados.</strong></p>
            <a class="boton boton-primario" href="<?= e(url('usuarios/crear')) ?>">Crear el primero</a>
        </div>
    <?php else: ?>
        <form method="get" action="<?= e(url('usuarios')) ?>" class="filtros" role="search">
            <label class="campo-busqueda">
                <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" name="q" value="<?= e($filtros['q']) ?>" maxlength="100" data-busqueda-rapida
                       placeholder="Buscar por cédula, nombre, teléfono, correo o usuario..." aria-label="Buscar recepcionistas">
            </label>
            <select name="estado" aria-label="Filtrar por estado" data-enviar-al-cambiar>
                <option value="">Todas las cuentas</option>
                <?php foreach ($filtroEstados as $clave => $nombre): ?>
                    <option value="<?= e($clave) ?>" <?= $filtros['estado'] === $clave ? 'selected' : '' ?>><?= e($nombre) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="boton boton-secundario">Buscar</button>
        </form>

        <div class="tabla-contenedor">
            <?php if (empty($usuarios)): ?>
                <p class="sin-resultados">No hay cuentas que coincidan con la búsqueda.</p>
            <?php else: ?>
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Recepcionista</th>
                        <th>Cédula</th>
                        <th>Contacto</th>
                        <th>Usuario</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <?php $nombreCompleto = $u['nombre'] . ' ' . $u['apellido']; $activa = (int) $u['estado'] === 1; ?>
                    <tr class="<?= $activa ? '' : 'fila-inactiva' ?>">
                        <td data-etiqueta="Recepcionista">
                            <div class="celda-visitante">
                                <span class="avatar" aria-hidden="true"><?= e(iniciales($nombreCompleto)) ?></span>
                                <div>
                                    <strong><?= e($nombreCompleto) ?></strong>
                                    <small class="texto-suave"><?= (int) $u['total_visitas'] ?> <?= (int) $u['total_visitas'] === 1 ? 'visita registrada' : 'visitas registradas' ?></small>
                                </div>
                            </div>
                        </td>
                        <td data-etiqueta="Cédula" class="texto-mono"><?= e($u['cedula']) ?></td>
                        <td data-etiqueta="Contacto">
                            <span class="sin-salto"><?= e($u['telefono'] ?: '—') ?></span>
                            <small class="texto-suave texto-correo"><?= e($u['correo'] ?: '—') ?></small>
                        </td>
                        <td data-etiqueta="Usuario" class="texto-mono"><?= e($u['user']) ?></td>
                        <td data-etiqueta="Estado"><?php $estadoCuenta = $u['estado']; require APP_PATH . '/views/parciales/estado_cuenta.php'; ?></td>
                        <td data-etiqueta="Acciones">
                            <div class="acciones-usuario">
                                <a class="boton-enlace" href="<?= e(url('usuarios/editar?id=' . (int) $u['id_usuario'])) ?>">
                                    <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>Editar datos
                                </a>
                                <a class="boton-enlace" href="<?= e(url('usuarios/clave?id=' . (int) $u['id_usuario'])) ?>">
                                    <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Restablecer contraseña
                                </a>
                                <form method="post" action="<?= e(url('usuarios/estado')) ?>" class="form-estado"
                                      data-confirmar="<?= e($activa
                                          ? "¿Dar de baja la cuenta de {$nombreCompleto}? No podrá iniciar sesión y, si está conectado, su sesión se cerrará. Su historial se conserva."
                                          : "¿Reactivar la cuenta de {$nombreCompleto}? Podrá iniciar sesión de nuevo con su contraseña.") ?>"
                                      data-confirmar-boton="<?= $activa ? 'Dar de baja' : 'Reactivar' ?>" <?= $activa ? 'data-confirmar-peligro' : '' ?>>
                                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="id_usuario" value="<?= (int) $u['id_usuario'] ?>">
                                    <input type="hidden" name="estado" value="<?= $activa ? '0' : '1' ?>">
                                    <input type="hidden" name="volver" value="usuarios">
                                    <input type="hidden" name="volver_filtros" value="<?= e($volverFiltros) ?>">
                                    <button type="submit" class="boton-enlace <?= $activa ? 'boton-enlace-peligro' : '' ?>">
                                        <?php if ($activa): ?>
                                            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/></svg>Dar de baja
                                        <?php else: ?>
                                            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Reactivar
                                        <?php endif; ?>
                                    </button>
                                </form>
                                <?php if ((int) $u['total_visitas'] === 0): ?>
                                    <form method="post" action="<?= e(url('usuarios/eliminar')) ?>" class="form-estado"
                                          data-confirmar="¿Eliminar definitivamente la cuenta de <?= e($nombreCompleto) ?>? Esta acción no se puede deshacer."
                                          data-confirmar-boton="Eliminar" data-confirmar-peligro>
                                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                        <input type="hidden" name="id_usuario" value="<?= (int) $u['id_usuario'] ?>">
                                        <button type="submit" class="boton-enlace boton-enlace-peligro">
                                            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>Eliminar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <p class="nota-reporte">Las cuentas que ya registraron visitas no se eliminan (el historial guarda quién registró cada visita): se dan de baja.</p>

        <?php $rutaListado = 'usuarios'; $filtros = $filtrosLista; require APP_PATH . '/views/parciales/paginacion.php'; ?>
    <?php endif; ?>
</section>
