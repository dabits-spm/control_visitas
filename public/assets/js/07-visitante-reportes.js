/* =====================================================================
   Control de Visitas · 07-visitante-reportes.js
   Autocompletar visitante por cédula, filtros de los listados, reportes, confirmar eliminaciones e imprimir.
   Los scripts se cargan en orden (01, 02, ...) y comparten funciones en window.CV.
   ===================================================================== */
(function (CV) {
    'use strict';

    const reglas = CV.validacion.reglas, validarCampo = CV.validacion.validarCampo;
    const confirmar = CV.confirmar;

    /* ---------- 6. Autocompletar visitante por cédula ---------- */
    const campoCedula = document.querySelector('[data-buscar-visitante]');

    if (campoCedula) {
        const aviso = document.querySelector('[data-aviso-visitante]');
        const avisoTexto = document.querySelector('[data-aviso-texto]');
        const indicador = document.querySelector('[data-indicador-busqueda]');
        const camposVisitante = ['nombre', 'apellido', 'telefono', 'correo', 'procedencia'];
        const camposNombre = ['nombre', 'apellido'];
        let ultimaCedula = '';
        let espera = null;

        // Si el usuario no puede editar visitantes, el nombre registrado queda de solo lectura
        function bloquearNombre(bloquear) {
            camposNombre.forEach(function (nombre) {
                const campo = document.getElementById(nombre);
                if (!campo) return;
                campo.readOnly = bloquear;
                campo.closest('.campo').classList.toggle('solo-lectura', bloquear);
            });
        }

        function buscarVisitante() {
            const cedula = campoCedula.value.replace(/[\s.]/g, '').toUpperCase();
            if (cedula === ultimaCedula || reglas.cedula(cedula) !== '') {
                if (reglas.cedula(cedula) !== '') {
                    aviso.hidden = true;
                    bloquearNombre(false);
                }
                return;
            }
            ultimaCedula = cedula;
            indicador.classList.add('buscando');

            fetch(campoCedula.dataset.buscarVisitante + '?cedula=' + encodeURIComponent(cedula), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (respuesta) { return respuesta.ok ? respuesta.json() : { encontrado: false }; })
                .then(function (datos) {
                    aviso.hidden = !datos.encontrado;
                    bloquearNombre(datos.encontrado && !datos.puedeEditarNombre);
                    if (!datos.encontrado) return;

                    avisoTexto.textContent = datos.puedeEditarNombre
                        ? avisoTexto.dataset.textoEditable
                        : avisoTexto.dataset.textoContacto;

                    camposVisitante.forEach(function (nombre) {
                        const campo = document.getElementById(nombre);
                        if (campo && datos.visitante[nombre] !== undefined) {
                            campo.value = datos.visitante[nombre] || '';
                            if (campo.dataset.validar) validarCampo(campo);
                        }
                    });
                })
                .catch(function () { aviso.hidden = true; bloquearNombre(false); })
                .finally(function () { indicador.classList.remove('buscando'); });
        }

        campoCedula.addEventListener('input', function () {
            clearTimeout(espera);
            espera = setTimeout(buscarVisitante, 500);
        });
        campoCedula.addEventListener('blur', buscarVisitante);

        // Si se llegó con la cédula ya escrita (desde la ficha del visitante), se cargan sus datos
        if (campoCedula.value.trim() !== '' && !document.getElementById('nombre').value) {
            buscarVisitante();
        }
    }

    /* ---------- 7. Filtros de los listados ---------- */
    // La búsqueda se hace en el servidor (con paginación); al cambiar el estado se envía sola
    document.querySelectorAll('[data-enviar-al-cambiar]').forEach(function (selector) {
        selector.addEventListener('change', function () {
            selector.form.submit();
        });
    });

    // Búsqueda rápida: busca sola cuando se deja de escribir y vuelve a poner el cursor en el buscador
    document.querySelectorAll('[data-busqueda-rapida]').forEach(function (campo) {
        let espera = null;
        const valorInicial = campo.value;
        campo.addEventListener('input', function () {
            clearTimeout(espera);
            espera = setTimeout(function () {
                if (campo.value.trim() !== valorInicial.trim()) {
                    try { sessionStorage.setItem('cv_busqueda_foco', campo.form.action); } catch (e) {}
                    campo.form.submit();
                }
            }, 600);
        });
        try {
            if (sessionStorage.getItem('cv_busqueda_foco') === campo.form.action) {
                sessionStorage.removeItem('cv_busqueda_foco');
                campo.focus();
                campo.setSelectionRange(campo.value.length, campo.value.length);
            }
        } catch (e) {}
    });

    /* ---------- 8. Reportes ---------- */
    // Al cambiar el tipo de reporte se proponen las fechas sugeridas para ese tipo
    const tipoReporte = document.querySelector('[data-tipo-reporte]');
    if (tipoReporte) {
        tipoReporte.addEventListener('change', function () {
            const opcion = tipoReporte.options[tipoReporte.selectedIndex];
            tipoReporte.form.elements.desde.value = opcion.dataset.desde;
            tipoReporte.form.elements.hasta.value = opcion.dataset.hasta;
        });
    }

    /* ---------- 9. Confirmar acciones irreversibles (eliminar) ---------- */
    document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
        form.addEventListener('submit', function (evento) {
            if (form.dataset.confirmado === '1') return;   // ya se confirmó: se envía
            evento.preventDefault();
            confirmar(form.dataset.confirmar, {
                titulo: form.hasAttribute('data-confirmar-peligro') ? 'Acción irreversible' : 'Confirmar',
                boton: form.dataset.confirmarBoton || 'Aceptar',
                peligro: form.hasAttribute('data-confirmar-peligro')
            }).then(function (si) {
                if (!si) return;
                // Mientras se envía (p. ej. el correo puede tardar unos segundos) se evita el doble clic
                const boton = form.querySelector('[data-enviando]');
                if (boton) {
                    boton.disabled = true;
                    boton.lastChild.textContent = ' ' + boton.dataset.enviando;
                }
                form.dataset.confirmado = '1';
                form.submit();
            });
        });
    });


    // Abre el diálogo de impresión; desde ahí se elige "Guardar como PDF"
    document.querySelectorAll('[data-imprimir]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            window.print();
        });
    });

})(window.CV = window.CV || {});
