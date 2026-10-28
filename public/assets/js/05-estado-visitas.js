/* =====================================================================
   Control de Visitas · 05-estado-visitas.js
   Configuración de correo (puerto según cifrado), cambio de estado de una visita y observaciones.
   Los scripts se cargan en orden (01, 02, ...) y comparten funciones en window.CV.
   ===================================================================== */
(function (CV) {
    'use strict';

    const abrirModal = CV.abrirModal, confirmar = CV.confirmar;

    /* ---------- 3c. Configuración de correo: el puerto sigue al cifrado ---------- */
    const cifradoCorreo = document.querySelector('[data-cifrado-correo]');
    const puertoCorreo = document.querySelector('[data-puerto-correo]');
    if (cifradoCorreo && puertoCorreo) {
        cifradoCorreo.addEventListener('change', function () {
            // Solo se cambia si el puerto es uno de los habituales
            if (['', '25', '465', '587'].indexOf(puertoCorreo.value.trim()) !== -1) {
                puertoCorreo.value = cifradoCorreo.value === 'ssl' ? '465' : (cifradoCorreo.value === 'tls' ? '587' : '25');
            }
        });
    }

    /* ---------- 4. Cambiar estado de una visita ---------- */
    const modalReprogramar = document.getElementById('modal-reprogramar');

    // Copia los datos de la visita (id y página de retorno) al formulario de un modal
    function prepararModal(modal, origen, visitante) {
        ['id_visita', 'volver', 'volver_filtros'].forEach(function (campo) {
            const destino = modal.querySelector('input[name="' + campo + '"]');
            if (destino) destino.value = origen[campo] || '';
        });
        modal.querySelectorAll('[data-modal-visitante]').forEach(function (el) { el.textContent = visitante; });
    }

    document.querySelectorAll('[data-cambiar-estado]').forEach(function (selector) {
        selector.addEventListener('change', function () {
            if (!selector.value) return;
            const opcion = selector.options[selector.selectedIndex];
            const visitante = selector.dataset.visitante || 'este visitante';
            const form = selector.form;

            // Reprogramar: se elige la nueva fecha y hora en un modal
            if (opcion.hasAttribute('data-reprogramar') && modalReprogramar) {
                prepararModal(modalReprogramar, {
                    id_visita: form.elements.id_visita.value,
                    volver: form.elements.volver.value,
                    volver_filtros: form.elements.volver_filtros.value
                }, visitante);
                selector.value = '';
                abrirModal(modalReprogramar);
                modalReprogramar.querySelector('input[type="date"]').focus();
                return;
            }

            const accion = opcion.text;
            confirmar('¿Confirma la acción "' + accion + '" para la visita de ' + visitante + '?', {
                titulo: 'Cambiar estado',
                boton: accion.replace(/…$/, ''),
                peligro: /cancel/i.test(accion)
            }).then(function (si) {
                if (si) {
                    selector.disabled = false;
                    form.submit();
                } else {
                    selector.value = '';
                }
            });
        });
    });

    /* ---------- 4b. Observaciones ---------- */
    const modalObservaciones = document.getElementById('modal-observaciones');
    document.querySelectorAll('[data-editar-observaciones]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            if (!modalObservaciones) return;
            prepararModal(modalObservaciones, {
                id_visita: boton.dataset.id,
                volver: boton.dataset.volver,
                volver_filtros: boton.dataset.volverFiltros
            }, boton.dataset.visitante);
            const texto = modalObservaciones.querySelector('textarea');
            texto.value = boton.dataset.texto || '';
            abrirModal(modalObservaciones);
            texto.focus();
            texto.setSelectionRange(texto.value.length, texto.value.length);
        });
    });

})(window.CV = window.CV || {});
