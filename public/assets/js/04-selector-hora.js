/* =====================================================================
   Control de Visitas · 04-selector-hora.js
   Selector de hora: abrir/cerrar el reloj, elegir con clic o escribir, teclado y validación.
   Los scripts se cargan en orden (01, 02, ...) y comparten funciones en window.CV.
   ===================================================================== */
(function (CV) {
    'use strict';

    const H = CV.hora;
    const dosDigitos = H.dosDigitos, desde24 = H.desde24, a24 = H.a24, texto12 = H.texto12;
    const periodoProbable = H.periodoProbable, interpretarHora = H.interpretarHora;

    /** Da vida al selector construido en 03-hora-formato.js: clics, teclado, validación y sincronización. */
    function activarSelectorHora(p) {
        const oculto = p.oculto, requerido = p.requerido, envoltura = p.envoltura, texto = p.texto;
        const boton = p.boton, panel = p.panel, ahora = p.ahora, listo = p.listo;

        let estado = desde24(oculto.value);
        if (estado) texto.value = texto12(estado);

        function marcarOpciones() {
            panel.querySelectorAll('.selector-hora-opcion').forEach(function (b) {
                const v = b.dataset.tipo === 'periodo' ? b.dataset.valor : parseInt(b.dataset.valor, 10);
                const elegido = estado !== null && estado[b.dataset.tipo] === v;
                b.classList.toggle('elegida', elegido);
                b.setAttribute('aria-selected', elegido ? 'true' : 'false');
            });
        }

        // Guarda la hora en el campo oculto y avisa a la validación
        function guardar(nuevo, reescribir, silencioso) {
            estado = nuevo;
            const anterior = oculto.value;
            oculto.value = estado ? a24(estado) : '';
            oculto.dataset.textoHora = texto.value.trim();
            if (reescribir && estado) {
                texto.value = texto12(estado);
                oculto.dataset.textoHora = texto.value;
            }
            texto.setCustomValidity(
                texto.value.trim() !== '' && !estado ? 'Escriba una hora válida, por ejemplo 2:30 PM.'
                    : (requerido && !estado ? 'Indique la hora.' : ''));
            marcarOpciones();
            if (silencioso) return;   // al cargar la página no se tocan los errores que mostró el servidor
            if (anterior !== oculto.value) oculto.dispatchEvent(new Event('change', { bubbles: true }));
            oculto.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function centrarElegidas() {
            panel.querySelectorAll('.selector-hora-lista').forEach(function (lista) {
                const elegida = lista.querySelector('.elegida');
                if (elegida) lista.scrollTop = elegida.offsetTop - lista.clientHeight / 2 + elegida.offsetHeight / 2;
            });
        }
        function abrir() {
            if (!panel.hidden) return;
            document.querySelectorAll('.selector-hora-panel:not([hidden])').forEach(function (otro) {
                if (otro !== panel) otro.dispatchEvent(new Event('cerrar'));
            });
            panel.hidden = false;
            envoltura.classList.add('abierto');
            texto.setAttribute('aria-expanded', 'true');
            // Si no cabe debajo: en la página se desplaza para mostrarlo completo;
            // dentro de una ventana modal (que no se desplaza) se abre hacia arriba
            panel.classList.remove('hacia-arriba');
            let caja = panel.getBoundingClientRect();
            if (caja.bottom > window.innerHeight - 8) {
                if (!envoltura.closest('dialog')) {
                    window.scrollBy({ top: caja.bottom - window.innerHeight + 16, behavior: 'instant' });
                    caja = panel.getBoundingClientRect();
                }
                if (caja.bottom > window.innerHeight - 8 && envoltura.getBoundingClientRect().top > caja.height + 8) {
                    panel.classList.add('hacia-arriba');
                }
            }
            marcarOpciones();
            centrarElegidas();
        }
        function cerrar(validar) {
            if (panel.hidden) return;
            panel.hidden = true;
            envoltura.classList.remove('abierto');
            texto.setAttribute('aria-expanded', 'false');
            if (validar) oculto.dispatchEvent(new Event('blur'));
        }
        panel.addEventListener('cerrar', function () { cerrar(true); });

        boton.addEventListener('click', function () {
            if (panel.hidden) { abrir(); } else { cerrar(true); }
            texto.focus();
        });
        texto.addEventListener('click', abrir);
        texto.addEventListener('input', function () { guardar(interpretarHora(texto.value), false); });
        texto.addEventListener('blur', function () {
            // Al salir del campo se muestra la hora completa (Ej: "2p" -> "02:00 PM")
            guardar(interpretarHora(texto.value), true);
        });
        // Cuando el foco sale del selector (texto, reloj y panel), se cierra y se valida
        envoltura.addEventListener('focusout', function (evento) {
            if (evento.relatedTarget && envoltura.contains(evento.relatedTarget)) return;
            setTimeout(function () {
                if (envoltura.contains(document.activeElement)) return;
                cerrar(false);
                oculto.dispatchEvent(new Event('blur'));
            }, 0);
        });
        texto.addEventListener('keydown', function (evento) {
            if (evento.key === 'ArrowDown' && panel.hidden) { evento.preventDefault(); abrir(); }
            if (evento.key === 'Escape' && !panel.hidden) { evento.preventDefault(); evento.stopPropagation(); cerrar(true); }
            if (evento.key === 'Enter' && !panel.hidden) { evento.preventDefault(); guardar(interpretarHora(texto.value), true); cerrar(true); }
        });

        panel.addEventListener('mousedown', function (evento) { evento.preventDefault(); });   // conserva el foco en el texto
        panel.addEventListener('click', function (evento) {
            const opcion = evento.target.closest('.selector-hora-opcion');
            if (opcion) {
                const nuevo = estado ? Object.assign({}, estado) : null;
                const tipo = opcion.dataset.tipo;
                const valor = tipo === 'periodo' ? opcion.dataset.valor : parseInt(opcion.dataset.valor, 10);
                let base = nuevo;
                if (!base) {
                    // Primera elección: completa lo que falta con valores razonables
                    base = { h12: 8, minuto: 0, periodo: 'AM' };
                    if (tipo === 'h12') base.periodo = periodoProbable(valor);
                }
                base[tipo] = valor;
                texto.value = texto12(base);
                guardar(base, true);
                return;
            }
            if (evento.target === ahora) {
                const d = new Date();
                texto.value = texto12(desde24(dosDigitos(d.getHours()) + ':' + dosDigitos(d.getMinutes())));
                guardar(interpretarHora(texto.value), true);
                centrarElegidas();
            } else if (evento.target === listo) {
                cerrar(true);
                texto.focus();
            }
        });
        panel.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape') { evento.preventDefault(); evento.stopPropagation(); cerrar(true); texto.focus(); }
        });
        document.addEventListener('mousedown', function (evento) {
            if (!panel.hidden && !envoltura.contains(evento.target)) cerrar(true);
        });

        // Si otro código cambia el valor (p. ej. al abrir el modal de reprogramar), se sincroniza
        oculto.establecerHora = function (valor24) {
            const nuevo = desde24(valor24);
            texto.value = nuevo ? texto12(nuevo) : '';
            guardar(nuevo, true);
        };
        // Al limpiar el formulario (reset) se vuelve a la hora inicial
        const valorInicial = oculto.value;
        if (oculto.form) {
            oculto.form.addEventListener('reset', function () {
                setTimeout(function () { oculto.establecerHora(valorInicial); }, 0);
            });
        }
        guardar(estado, true, true);
    }

    document.querySelectorAll('input[type="time"]').forEach(function (original) {
        activarSelectorHora(H.construir(original));
    });

})(window.CV = window.CV || {});
