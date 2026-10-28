/* =====================================================================
   Control de Visitas · 06-validacion.js
   Validación de los formularios (registro, edición, usuarios) y tipo de visita (al momento / programada).
   Los scripts se cargan en orden (01, 02, ...) y comparten funciones en window.CV.
   ===================================================================== */
(function (CV) {
    'use strict';

    /* ---------- 5. Validación del formulario de registro ---------- */
    const formulario = document.querySelector('[data-validar-formulario]');

    // Reglas: cada una devuelve el mensaje de error o '' si el valor es correcto.
    // Deben coincidir con Visitante::validar() en PHP.
    const reglas = {
        requerido: function (valor, campo) {
            return valor === '' ? 'Este campo es obligatorio.' : '';
        },
        // Hora del selector de 12 horas: el valor es la hora en 24 h ("14:30") o vacío si no es válida
        hora: function (valor, campo) {
            if (valor !== '') return '';
            return campo.dataset.textoHora ? 'Escriba una hora válida, por ejemplo 2:30 PM.' : 'Este campo es obligatorio.';
        },
        'hora-opcional': function (valor, campo) {
            return valor === '' && campo.dataset.textoHora ? 'Escriba una hora válida, por ejemplo 2:30 PM.' : '';
        },
        cedula: function (valor) {
            const limpio = valor.replace(/[\s.-]/g, '').toUpperCase();
            if (limpio === '') return 'La cédula es obligatoria.';
            if (!/^[VE]?\d{6,10}$/.test(limpio)) {
                return 'Escriba V o E y de 6 a 10 dígitos (Ej: V-28123456 o E-81234567).';
            }
            return '';
        },
        nombre: function (valor, campo) {
            const etiqueta = campo.dataset.nombreCampo === 'apellido' ? 'El apellido' : 'El nombre';
            if (valor === '') return etiqueta + ' es obligatorio.';
            if (valor.length < 2 || valor.length > 60) return etiqueta + ' debe tener entre 2 y 60 caracteres.';
            if (!/^\p{L}[\p{L}\s'.-]*$/u.test(valor)) return etiqueta + ' solo puede contener letras.';
            return '';
        },
        telefono: function (valor) {
            // Venezolano: 0414-1234567 / +58 414 1234567. Extranjero: + código de país (Ej: +57 300 1234567)
            let limpio = valor.replace(/[\s().-]/g, '');
            if (limpio === '') return 'El teléfono es obligatorio.';
            if (limpio.startsWith('00')) limpio = '+' + limpio.slice(2);
            if (!/^(0\d{10}|\+58\d{10}|\+(?!58)[1-9]\d{7,14})$/.test(limpio)) {
                return 'Ingrese un teléfono válido (Ej: 0414-1234567, o +57 300 1234567 si es extranjero).';
            }
            return '';
        },
        correo: function (valor) {
            if (valor === '') return 'El correo electrónico es obligatorio.';
            if (valor.length > 100 || !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(valor)) {
                return 'Ingrese un correo electrónico válido (Ej: nombre@correo.com).';
            }
            return '';
        }
    };

    function mostrarError(contenedor, nombre, mensaje) {
        const salida = formulario.querySelector('[data-error-de="' + nombre + '"]');
        if (salida) salida.textContent = mensaje;
        contenedor.classList.toggle('con-error', mensaje !== '');
    }

    function validarCampo(campo) {
        const regla = reglas[campo.dataset.validar];
        // Los campos ocultos (p. ej. fecha y hora de una visita "al momento") no se validan
        if (!regla || campo.closest('[hidden]')) return true;
        const mensaje = regla(campo.value.trim(), campo);
        const contenedor = campo.closest('.campo');
        mostrarError(contenedor, campo.name, mensaje);
        contenedor.classList.toggle('valido', mensaje === '' && campo.value.trim() !== '');
        return mensaje === '';
    }

    function validarGrupo(grupo) {
        const marcado = grupo.querySelector('input[type="checkbox"]:checked') !== null;
        mostrarError(grupo, grupo.dataset.grupoObligatorio, marcado ? '' : 'Seleccione al menos un departamento.');
        return marcado;
    }

    if (formulario) {
        const campos = formulario.querySelectorAll('[data-validar]');
        const grupos = formulario.querySelectorAll('[data-grupo-obligatorio]');

        campos.forEach(function (campo) {
            // Al salir del campo se valida; mientras escribe, solo se limpia el error si ya lo corrigió
            campo.addEventListener('blur', function () { validarCampo(campo); });
            campo.addEventListener('input', function () {
                if (campo.closest('.campo').classList.contains('con-error')) {
                    validarCampo(campo);
                }
            });
        });

        grupos.forEach(function (grupo) {
            grupo.addEventListener('change', function () { validarGrupo(grupo); });
        });

        formulario.addEventListener('submit', function (evento) {
            let primeroConError = null;

            campos.forEach(function (campo) {
                if (!validarCampo(campo) && !primeroConError) primeroConError = campo;
            });
            grupos.forEach(function (grupo) {
                if (!validarGrupo(grupo) && !primeroConError) primeroConError = grupo.querySelector('input');
            });

            if (primeroConError) {
                evento.preventDefault();
                (primeroConError.campoVisible || primeroConError).focus({ preventScroll: true });
                primeroConError.closest('.campo').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            // Evita guardar dos veces por doble clic
            const boton = formulario.querySelector('button[type="submit"]');
            boton.disabled = true;
            boton.lastChild.textContent = ' Guardando...';
        });
    }

    /* ---------- 5b. Tipo de visita: al momento o programada ---------- */
    // La fecha y hora solo se piden para las visitas programadas
    const opcionesTipo = document.querySelector('[data-tipo-visita]');
    const camposProgramada = document.querySelector('[data-campos-programada]');

    if (opcionesTipo && camposProgramada) {
        opcionesTipo.addEventListener('change', function (evento) {
            camposProgramada.hidden = evento.target.value !== 'programada';
            mostrarError(opcionesTipo.closest('.campo'), 'tipo_visita', '');
        });
    }


    CV.validacion = { reglas: reglas, validarCampo: validarCampo, mostrarError: mostrarError };

})(window.CV = window.CV || {});
