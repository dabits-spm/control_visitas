/* =====================================================================
   Control de Visitas · 01-sesion-barra.js
   Seguridad de la sesión (Atrás/Adelante, otras pestañas), barra lateral y menú de usuario.
   Los scripts se cargan en orden (01, 02, ...) y comparten funciones en window.CV.
   ===================================================================== */
(function (CV) {
    'use strict';

    /* ---------- 0. Seguridad de sesión: botones "Atrás" y "Adelante" ---------- */
    // Las páginas internas no se guardan en caché (ver public/index.php), pero algunos
    // navegadores conservan la página en memoria al usar "Atrás"/"Adelante" (bfcache).
    // Si la página se vuelve a mostrar así, se oculta y se consulta al servidor si la
    // sesión sigue activa; si no, se envía al login sin dejar la página en el historial.
    const paginaProtegida = document.body.hasAttribute('data-requiere-sesion');

    function verificarSesion() {
        const raiz = document.documentElement;
        raiz.classList.add('verificando-sesion');
        fetch(document.body.dataset.urlSesion, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store'
        })
            .then(function (respuesta) { return respuesta.json(); })
            .then(function (datos) {
                if (datos.activa) {
                    raiz.classList.remove('verificando-sesion');
                } else {
                    window.location.replace(document.body.dataset.urlLogin);
                }
            })
            .catch(function () { window.location.replace(document.body.dataset.urlLogin); });
    }

    if (paginaProtegida) {
        window.addEventListener('pageshow', function (evento) {
            const navegacion = (performance.getEntriesByType && performance.getEntriesByType('navigation')[0]) || {};
            if (evento.persisted || navegacion.type === 'back_forward') {
                verificarSesion();
            }
        });
        // Si se cierra la sesión en otra pestaña, esta también se protege
        window.addEventListener('storage', function (evento) {
            if (evento.key === 'cv_sesion_cerrada') verificarSesion();
        });
    }

    /* ---------- 1. Barra lateral y menú de usuario ---------- */
    const raiz = document.documentElement;
    const esPantallaChica = function () { return window.matchMedia('(max-width: 960px)').matches; };
    const botonesBarra = document.querySelectorAll('[data-alternar-barra-lateral]');
    const botonBarra = document.querySelector('.boton-barra-lateral');

    function barraAbierta() {
        return esPantallaChica()
            ? raiz.classList.contains('barra-lateral-abierta')
            : !raiz.classList.contains('barra-lateral-cerrada');
    }

    function actualizarBotonBarra() {
        if (!botonBarra) return;
        const abierta = barraAbierta();
        botonBarra.setAttribute('aria-expanded', abierta ? 'true' : 'false');
        botonBarra.setAttribute('aria-label', abierta ? 'Ocultar el menú lateral' : 'Mostrar el menú lateral');
    }

    botonesBarra.forEach(function (boton) {
        boton.addEventListener('click', function () {
            if (esPantallaChica()) {
                // Pantalla chica: el menú se abre encima del contenido
                raiz.classList.toggle('barra-lateral-abierta');
            } else {
                // Pantalla grande: se contrae para dejar la pantalla limpia (y se recuerda)
                const cerrada = raiz.classList.toggle('barra-lateral-cerrada');
                try { localStorage.setItem('cv_barra_lateral', cerrada ? 'cerrada' : 'abierta'); } catch (e) {}
            }
            actualizarBotonBarra();
        });
    });
    window.addEventListener('resize', actualizarBotonBarra);
    actualizarBotonBarra();

    const menuUsuario = document.querySelector('.menu-usuario');
    document.addEventListener('click', function (evento) {
        if (menuUsuario && menuUsuario.open && !menuUsuario.contains(evento.target)) {
            menuUsuario.open = false;
        }
    });
    document.addEventListener('keydown', function (evento) {
        if (evento.key !== 'Escape') return;
        if (menuUsuario) menuUsuario.open = false;
        if (esPantallaChica() && raiz.classList.contains('barra-lateral-abierta')) {
            raiz.classList.remove('barra-lateral-abierta');
            actualizarBotonBarra();
        }
    });


    CV.paginaProtegida = paginaProtegida;
    CV.menuUsuario = menuUsuario;

})(window.CV = window.CV || {});
