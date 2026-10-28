    </main>

    <footer class="pie-pagina">
        Control de Visitas &middot; <?= date('Y') ?>
    </footer>
</div>

<?php /* ------------------------------------------------------------------
   Ventanas modales (misma estructura que un modal de Bootstrap:
   .modal > .modal-dialog > .modal-content > header/body/footer).
   Se abren con data-abrir-modal="id" o desde 02-modales-alertas.js.
   ------------------------------------------------------------------ */ ?>

<!-- Cerrar sesión -->
<dialog class="modal" id="modal-salir" aria-labelledby="modal-salir-titulo">
    <form method="post" action="<?= e(url('logout')) ?>" class="modal-dialog" data-cerrar-sesion>
        <input type="hidden" name="csrf" value="<?= e($csrf ?? '') ?>">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-icono modal-icono-peligro" aria-hidden="true">
                    <svg class="icono" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                </span>
                <h2 class="modal-title" id="modal-salir-titulo">Cerrar sesión</h2>
                <button type="button" class="modal-cerrar" data-cerrar-modal aria-label="Cerrar">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas cerrar la sesión actual?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="boton boton-secundario" data-cerrar-modal autofocus>Cancelar</button>
                <button type="submit" class="boton boton-primario">Sí, cerrar sesión</button>
            </div>
        </div>
    </form>
</dialog>

<!-- Confirmación genérica (cambios de estado, eliminar...) -->
<dialog class="modal" id="modal-confirmar" aria-labelledby="modal-confirmar-titulo">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-icono" aria-hidden="true">
                    <svg class="icono" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                </span>
                <h2 class="modal-title" id="modal-confirmar-titulo">Confirmar</h2>
                <button type="button" class="modal-cerrar" data-cerrar-modal aria-label="Cerrar">&times;</button>
            </div>
            <div class="modal-body"><p data-confirmar-mensaje></p></div>
            <div class="modal-footer">
                <button type="button" class="boton boton-secundario" data-cerrar-modal>Cancelar</button>
                <button type="button" class="boton boton-primario" data-confirmar-aceptar>Aceptar</button>
            </div>
        </div>
    </div>
</dialog>

<!-- Reprogramar una visita: nueva fecha y hora -->
<dialog class="modal" id="modal-reprogramar" aria-labelledby="modal-reprogramar-titulo">
    <form method="post" action="<?= e(url('visitas/estado')) ?>" class="modal-dialog formulario-modal">
        <input type="hidden" name="csrf" value="<?= e($csrf ?? '') ?>">
        <input type="hidden" name="id_estado" value="<?= Visita::ESTADO_REPROGRAMADA ?>">
        <input type="hidden" name="id_visita" value="">
        <input type="hidden" name="volver" value="">
        <input type="hidden" name="volver_filtros" value="">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-icono modal-icono-reprogramar" aria-hidden="true">
                    <svg class="icono" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M12 14v3l2 1"/></svg>
                </span>
                <h2 class="modal-title" id="modal-reprogramar-titulo">Reprogramar visita</h2>
                <button type="button" class="modal-cerrar" data-cerrar-modal aria-label="Cerrar">&times;</button>
            </div>
            <div class="modal-body">
                <p>Nueva fecha y hora para la visita de <strong data-modal-visitante></strong>.</p>
                <div class="rejilla rejilla-2">
                    <div class="campo">
                        <label for="reprogramar-fecha">Fecha <span class="obligatorio">*</span></label>
                        <input type="date" id="reprogramar-fecha" name="fecha_visita" required
                               min="<?= e(date('Y-m-d')) ?>" max="<?= e(date('Y-m-d', strtotime('+1 year'))) ?>">
                    </div>
                    <div class="campo">
                        <label for="reprogramar-hora">Hora <span class="obligatorio">*</span></label>
                        <input type="time" id="reprogramar-hora" name="hora_entrada" required>
                    </div>
                </div>
                <?php if (CorreoSmtp::estaConfigurado()): ?>
                    <p class="modal-nota">Se enviará un aviso por correo con los nuevos datos.</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="boton boton-secundario" data-cerrar-modal>Cancelar</button>
                <button type="submit" class="boton boton-primario">Reprogramar</button>
            </div>
        </div>
    </form>
</dialog>

<!-- Agregar o editar observaciones -->
<dialog class="modal" id="modal-observaciones" aria-labelledby="modal-observaciones-titulo">
    <form method="post" action="<?= e(url('visitas/observaciones')) ?>" class="modal-dialog formulario-modal">
        <input type="hidden" name="csrf" value="<?= e($csrf ?? '') ?>">
        <input type="hidden" name="id_visita" value="">
        <input type="hidden" name="volver" value="">
        <input type="hidden" name="volver_filtros" value="">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-icono" aria-hidden="true">
                    <svg class="icono" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                </span>
                <h2 class="modal-title" id="modal-observaciones-titulo">Observaciones</h2>
                <button type="button" class="modal-cerrar" data-cerrar-modal aria-label="Cerrar">&times;</button>
            </div>
            <div class="modal-body">
                <p>Visita de <strong data-modal-visitante></strong>.</p>
                <div class="campo">
                    <label for="observaciones-texto">Observaciones <span class="opcional">(déjelo vacío para «Ninguna»)</span></label>
                    <textarea id="observaciones-texto" name="observaciones" rows="5" maxlength="<?= Visita::MAX_OBSERVACIONES ?>"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="boton boton-secundario" data-cerrar-modal>Cancelar</button>
                <button type="submit" class="boton boton-primario">Guardar observaciones</button>
            </div>
        </div>
    </form>
</dialog>

<?php if (!empty($mostrarNotificacionesInicio)): ?>
    <?php if (!empty($notificacionesPendientes)): ?>
        <!-- Al iniciar sesión: citas y tareas pendientes del día -->
        <dialog class="modal modal-ancho" id="modal-notificaciones-inicio" aria-labelledby="modal-notificaciones-titulo" data-abrir-al-cargar>
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <span class="modal-icono modal-icono-notificaciones" aria-hidden="true">
                            <svg class="icono" viewBox="0 0 24 24"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/></svg>
                        </span>
                        <h2 class="modal-title" id="modal-notificaciones-titulo">Pendientes de hoy</h2>
                        <button type="button" class="modal-cerrar" data-cerrar-modal aria-label="Cerrar">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Bienvenido(a), <?= e($_SESSION['usuario']['nombre'] ?? '') ?>. Estas son las citas y tareas pendientes de hoy:</p>
                        <?php $listaNotificaciones = $notificacionesPendientes; $limiteNotificaciones = 8;
                              require APP_PATH . '/views/parciales/lista_notificaciones.php'; ?>
                        <?php if (count($notificacionesPendientes) > 8): ?>
                            <p class="modal-nota">Y <?= count($notificacionesPendientes) - 8 ?> más en Notificaciones.</p>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="boton boton-secundario" data-cerrar-modal>Cerrar</button>
                        <a class="boton boton-primario" href="<?= e(url('notificaciones')) ?>">Ver notificaciones</a>
                    </div>
                </div>
            </div>
        </dialog>
    <?php else: ?>
        <div class="aviso-flotante aviso-flotante-ok" role="status" data-aviso-temporal>
            <svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><path d="m22 4-10 10-3-3"/></svg>
            <span>No hay citas ni tareas pendientes para hoy.</span>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Avisos flotantes: recordatorios de seguimiento que aparecen sin recargar la página -->
<div class="avisos-flotantes" aria-live="polite" data-avisos-flotantes></div>

<?php foreach (scriptsDelSistema() as $script): ?>
<script src="<?= e($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
