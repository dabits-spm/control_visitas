/* =====================================================================
   Control de Visitas · 08-notificaciones.js
   Notificaciones internas: resumen al iniciar sesión, campana, recordatorios flotantes y envío de recordatorios por correo.
   Los scripts se cargan en orden (01, 02, ...) y comparten funciones en window.CV.
   ===================================================================== */
(function (CV) {
    'use strict';

    const paginaProtegida = CV.paginaProtegida, abrirModal = CV.abrirModal;

    /* ---------- 10. Notificaciones internas y recordatorios por correo ---------- */
    // Al iniciar sesión se abre el resumen de pendientes del día
    document.querySelectorAll('dialog[data-abrir-al-cargar]').forEach(function (modal) { abrirModal(modal); });
    // Avisos que se cierran solos
    document.querySelectorAll('[data-aviso-temporal]').forEach(function (aviso) {
        setTimeout(function () { aviso.classList.add('saliendo'); setTimeout(function () { aviso.remove(); }, 400); }, 6000);
    });

    const menuNotificaciones = document.querySelector('[data-menu-notificaciones]');
    document.addEventListener('click', function (evento) {
        if (menuNotificaciones && menuNotificaciones.open && !menuNotificaciones.contains(evento.target)) {
            menuNotificaciones.open = false;
        }
    });

    if (paginaProtegida && document.body.dataset.urlEstadoNotificaciones) {
        const datosPagina = document.body.dataset;
        const ICONOS = {
            seguimiento: '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
            cita_hoy: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            aviso_pendiente: '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
            en_curso: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 11h-6"/>'
        };
        const tituloOriginal = document.title.replace(/^\(\d+\) /, '');

        // Claves ya conocidas: las que venían en la página y las que ya se avisaron en esta pestaña
        let avisadas = [];
        try { avisadas = JSON.parse(sessionStorage.getItem('cv_notificaciones_avisadas') || '[]'); } catch (e) {}
        const conocidas = new Set((datosPagina.clavesNotificaciones || '').split(',').filter(Boolean).concat(avisadas));

        function mostrarAvisoFlotante(n) {
            const contenedor = document.querySelector('[data-avisos-flotantes]');
            if (!contenedor) return;
            const aviso = document.createElement('div');
            aviso.className = 'aviso-flotante' + (n.urgente ? ' aviso-flotante-urgente' : '');
            aviso.setAttribute('role', 'alert');
            const texto = document.createElement('div');
            const titulo = document.createElement('strong');
            titulo.textContent = 'Recordatorio: ' + n.titulo;
            const detalle = document.createElement('small');
            detalle.textContent = n.detalle;
            const enlace = document.createElement('a');
            enlace.href = datosPagina.urlNotificaciones + '#' + n.clave;
            enlace.textContent = 'Actualizar estado';
            texto.append(titulo, detalle, enlace);
            const cerrar = document.createElement('button');
            cerrar.type = 'button';
            cerrar.className = 'alerta-cerrar';
            cerrar.setAttribute('aria-label', 'Cerrar recordatorio');
            cerrar.textContent = '×';
            cerrar.addEventListener('click', function () { aviso.remove(); });
            aviso.append(texto, cerrar);
            contenedor.append(aviso);
        }

        function actualizarCampana(datos) {
            const r = datos.resumen;
            document.querySelectorAll('[data-contador-notificaciones]').forEach(function (contador) {
                contador.textContent = r.alertas;
                contador.hidden = r.alertas === 0;
                contador.classList.toggle('contador-urgente', r.urgentes > 0);
            });
            document.title = (r.alertas > 0 ? '(' + r.alertas + ') ' : '') + tituloOriginal;

            // Lista del menú de la campana (máximo 6), armada con textContent (sin HTML de los datos)
            const lista = menuNotificaciones && menuNotificaciones.querySelector('[data-lista-notificaciones]');
            if (lista) {
                lista.textContent = '';
                datos.items.slice(0, 6).forEach(function (n) {
                    const li = document.createElement('li');
                    li.className = 'notificacion notificacion-' + n.tipo + (n.urgente ? ' notificacion-urgente' : '');
                    const a = document.createElement('a');
                    a.href = datosPagina.urlNotificaciones + '#' + n.clave;
                    const icono = document.createElement('span');
                    icono.className = 'notificacion-icono';
                    icono.innerHTML = '<svg class="icono" viewBox="0 0 24 24" aria-hidden="true">' + (ICONOS[n.tipo] || '') + '</svg>';
                    const texto = document.createElement('span');
                    texto.className = 'notificacion-texto';
                    const t = document.createElement('strong'); t.textContent = n.titulo;
                    const d = document.createElement('small'); d.textContent = n.detalle;
                    texto.append(t, d);
                    a.append(icono, texto);
                    li.append(a);
                    lista.append(li);
                });
                const vacio = menuNotificaciones.querySelector('[data-notificaciones-vacias]');
                if (vacio) vacio.hidden = datos.items.length > 0;
            }

            // Recordatorio flotante cuando una cita pasa 30 minutos de su hora sin cambio de estado
            datos.items.forEach(function (n) {
                if (n.tipo === 'seguimiento' && !conocidas.has(n.clave)) {
                    conocidas.add(n.clave);
                    avisadas.push(n.clave);
                    mostrarAvisoFlotante(n);
                }
            });
            try { sessionStorage.setItem('cv_notificaciones_avisadas', JSON.stringify(avisadas.slice(-200))); } catch (e) {}
        }

        function consultarNotificaciones() {
            if (document.hidden) return;
            fetch(datosPagina.urlEstadoNotificaciones, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin', cache: 'no-store' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (datos) { if (datos && datos.resumen) actualizarCampana(datos); })
                .catch(function () {});
        }

        // Recordatorios por correo de las citas de las próximas 24 horas: se piden en segundo plano
        function revisarRecordatorios() {
            fetch(datosPagina.urlRecordatorios, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-Token': datosPagina.csrf },
                credentials: 'same-origin',
                keepalive: true
            }).catch(function () {});
        }

        // Enlaces a una notificación que llegó después de cargar la bandeja: la tarjeta
        // todavía no está en la página, así que se recarga para mostrarla
        document.addEventListener('click', function (evento) {
            const enlace = evento.target.closest('a[href*="#"]');
            if (!enlace) return;
            const destino = new URL(enlace.href, location.href);
            const clave = decodeURIComponent(destino.hash.slice(1));
            if (destino.pathname === location.pathname && clave && !document.getElementById(clave)) {
                evento.preventDefault();
                history.replaceState(null, '', destino.pathname + destino.search + destino.hash);
                location.reload();
            }
        });

        setInterval(consultarNotificaciones, 60 * 1000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) consultarNotificaciones(); });
        setTimeout(revisarRecordatorios, 4000);
        setInterval(revisarRecordatorios, 5 * 60 * 1000);
    }

})(window.CV = window.CV || {});
