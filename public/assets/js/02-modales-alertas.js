/* =====================================================================
   Control de Visitas · 02-modales-alertas.js
   Ventanas modales, confirmación de acciones, cerrar alertas y mostrar/ocultar contraseña.
   Los scripts se cargan en orden (01, 02, ...) y comparten funciones en window.CV.
   ===================================================================== */
(function (CV) {
    'use strict';

    const menuUsuario = CV.menuUsuario;

    /* ---------- 1b. Ventanas modales ---------- */
    // Estructura de modal de Bootstrap (.modal > .modal-dialog > .modal-content) sobre <dialog>,
    // que el navegador ya sabe mostrar encima de todo, cerrar con Escape y mantener el foco dentro.
    function abrirModal(modal) {
        if (menuUsuario) menuUsuario.open = false;
        if (typeof modal.showModal === 'function') {
            modal.showModal();
        } else {
            modal.setAttribute('open', '');   // navegadores muy antiguos
        }
        modal.classList.add('mostrar');
    }

    function cerrarModal(modal) {
        modal.classList.remove('mostrar');
        if (typeof modal.close === 'function') modal.close(); else modal.removeAttribute('open');
    }

    document.querySelectorAll('dialog.modal').forEach(function (modal) {
        // Clic en el fondo oscuro (fuera del contenido): cierra
        modal.addEventListener('click', function (evento) {
            if (evento.target === modal) cerrarModal(modal);
        });
        modal.addEventListener('close', function () { modal.classList.remove('mostrar'); });
        modal.querySelectorAll('[data-cerrar-modal]').forEach(function (boton) {
            boton.addEventListener('click', function () { cerrarModal(modal); });
        });
    });

    document.querySelectorAll('[data-abrir-modal]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            const modal = document.getElementById(boton.dataset.abrirModal);
            if (modal) abrirModal(modal);
        });
    });

    // Cerrar sesión: evita el doble clic mientras se envía
    document.querySelectorAll('form[data-cerrar-sesion]').forEach(function (form) {
        form.addEventListener('submit', function () {
            const boton = form.querySelector('button[type="submit"]');
            if (boton) { boton.disabled = true; boton.textContent = 'Cerrando sesión...'; }
        });
    });

    // Al llegar al login después de cerrar sesión (la sesión ya no existe en el servidor),
    // se avisa a las demás pestañas abiertas para que también vuelvan al login.
    if (document.body.hasAttribute('data-pagina-login') && /[?&]salida=1/.test(window.location.search)) {
        try { localStorage.setItem('cv_sesion_cerrada', String(Date.now())); } catch (e) {}
    }

    /**
     * Pregunta con el modal de confirmación. Devuelve una promesa con true (Aceptar) o false.
     * opciones: { titulo, boton, peligro }
     */
    const modalConfirmar = document.getElementById('modal-confirmar');
    function confirmar(mensaje, opciones) {
        opciones = opciones || {};
        if (!modalConfirmar) return Promise.resolve(window.confirm(mensaje));

        return new Promise(function (resolver) {
            const aceptar = modalConfirmar.querySelector('[data-confirmar-aceptar]');
            modalConfirmar.querySelector('[data-confirmar-mensaje]').textContent = mensaje;
            modalConfirmar.querySelector('.modal-title').textContent = opciones.titulo || 'Confirmar';
            aceptar.textContent = opciones.boton || 'Aceptar';
            aceptar.classList.toggle('boton-peligro-lleno', !!opciones.peligro);
            let respuesta = false;

            function alAceptar() { respuesta = true; cerrarModal(modalConfirmar); }
            function alCerrar() {
                aceptar.removeEventListener('click', alAceptar);
                modalConfirmar.removeEventListener('close', alCerrar);
                resolver(respuesta);
            }
            aceptar.addEventListener('click', alAceptar);
            modalConfirmar.addEventListener('close', alCerrar);
            abrirModal(modalConfirmar);
            aceptar.focus();
        });
    }

    /* ---------- 2. Cerrar alertas ---------- */
    document.querySelectorAll('[data-cerrar-alerta]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            boton.closest('.alerta').remove();
        });
    });

    /* ---------- 3. Mostrar / ocultar contraseña ---------- */
    document.querySelectorAll('[data-ver-clave]').forEach(function (boton) {
        const campo = document.getElementById(boton.dataset.verClave);
        boton.addEventListener('click', function () {
            const visible = campo.type === 'text';
            campo.type = visible ? 'password' : 'text';
            campo.toggleAttribute('data-clave-visible', !visible);
            boton.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
        });
    });


    CV.abrirModal = abrirModal;
    CV.cerrarModal = cerrarModal;
    CV.confirmar = confirmar;

})(window.CV = window.CV || {});
