/* =====================================================================
   Control de Visitas · 03-hora-formato.js
   Hora en formato de 12 horas (AM/PM): conversión, lectura de lo escrito y construcción del reloj.
   Los scripts se cargan en orden (01, 02, ...) y comparten funciones en window.CV.
   ===================================================================== */
(function (CV) {
    'use strict';

    /* ---------- 3b. Selector de hora en formato de 12 horas (AM/PM) ---------- */
    // Reemplaza cada <input type="time"> por un campo de texto con reloj desplegable:
    // se elige la hora, los minutos y AM/PM con clic, o se escribe (Ej: 2:30 PM).
    // El formulario sigue enviando la hora en 24 horas ("14:30") en un campo oculto,
    // así el servidor no cambia. Sin JavaScript queda el campo de hora del navegador.
    const ICONO_RELOJ = '<svg class="icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>';

    function dosDigitos(n) { return (n < 10 ? '0' : '') + n; }

    // Hora en 24 h ("14:30") -> {h12, minuto, periodo}
    function desde24(valor) {
        const p = /^(\d{1,2}):(\d{2})/.exec(valor || '');
        if (!p) return null;
        const h = parseInt(p[1], 10), m = parseInt(p[2], 10);
        if (h > 23 || m > 59) return null;
        return { h12: (h % 12) || 12, minuto: m, periodo: h < 12 ? 'AM' : 'PM' };
    }
    function a24(e) {
        return dosDigitos((e.h12 % 12) + (e.periodo === 'PM' ? 12 : 0)) + ':' + dosDigitos(e.minuto);
    }
    function texto12(e) {
        return dosDigitos(e.h12) + ':' + dosDigitos(e.minuto) + ' ' + e.periodo;
    }
    // Sin AM/PM, una hora de 1 a 12 se interpreta en horario de oficina: 7-11 AM, 12-6 PM
    function periodoProbable(h) { return (h >= 7 && h <= 11) ? 'AM' : 'PM'; }

    // Texto escrito -> {h12, minuto, periodo} o null si no es una hora válida.
    // Acepta "2:30 PM", "2:30pm", "230p", "2 pm", "14:30", "1430", "2:30 p. m."
    function interpretarHora(texto) {
        const t = String(texto).toLowerCase().replace(/[\s.]/g, '');
        if (t === '') return null;
        const p = /^(\d{1,2})(?::?(\d{2}))?(am|pm|a|p)?$/.exec(t);
        if (!p) return null;
        let h = parseInt(p[1], 10);
        const m = p[2] !== undefined ? parseInt(p[2], 10) : 0;
        if (m > 59) return null;
        if (p[3]) {
            if (h < 1 || h > 12) return null;
            return { h12: h, minuto: m, periodo: p[3].charAt(0) === 'p' ? 'PM' : 'AM' };
        }
        if (h > 23) return null;
        // Con cero inicial ("08:00"), 0 o más de 12: se entiende en 24 horas
        if (h === 0 || h > 12 || p[1].length === 2 && p[1].charAt(0) === '0') {
            return desde24(dosDigitos(h) + ':' + dosDigitos(m));
        }
        return { h12: h, minuto: m, periodo: periodoProbable(h) };
    }


    /**
     * Construye el selector: campo de texto, botón del reloj y panel (hora, minutos, AM/PM).
     * El <input type="time"> original pasa a ser un campo oculto con la hora en 24 horas.
     * El comportamiento lo agrega 04-selector-hora.js.
     */
    function construirSelectorHora(original) {
        const requerido = original.required;
        const etiqueta = original.id;
        const oculto = original;

        const envoltura = document.createElement('div');
        envoltura.className = 'selector-hora';
        const texto = document.createElement('input');
        texto.type = 'text';
        texto.id = etiqueta;
        texto.className = 'selector-hora-texto';
        texto.placeholder = 'hh:mm AM';
        texto.autocomplete = 'off';
        texto.maxLength = 12;
        texto.required = requerido;
        texto.setAttribute('aria-haspopup', 'dialog');
        texto.setAttribute('aria-expanded', 'false');
        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'selector-hora-boton';
        boton.setAttribute('aria-label', 'Elegir la hora');
        boton.tabIndex = -1;   // con el teclado: flecha abajo abre el reloj; Tab pasa al siguiente campo
        boton.innerHTML = ICONO_RELOJ;

        const panel = document.createElement('div');
        panel.className = 'selector-hora-panel';
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-label', 'Elegir la hora');
        panel.hidden = true;
        const columnas = document.createElement('div');
        columnas.className = 'selector-hora-columnas';
        function columna(titulo, valores, tipo, formato) {
            const col = document.createElement('div');
            col.className = 'selector-hora-columna';
            const t = document.createElement('span');
            t.className = 'selector-hora-titulo';
            t.textContent = titulo;
            const lista = document.createElement('div');
            lista.className = 'selector-hora-lista';
            lista.setAttribute('role', 'listbox');
            lista.setAttribute('aria-label', titulo);
            valores.forEach(function (v) {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'selector-hora-opcion';
                b.dataset.tipo = tipo;
                b.dataset.valor = v;
                b.setAttribute('role', 'option');
                b.textContent = formato ? formato(v) : v;
                lista.append(b);
            });
            col.append(t, lista);
            columnas.append(col);
        }
        columna('Hora', [12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'h12', dosDigitos);
        columna('Min.', [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55], 'minuto', dosDigitos);
        columna('', ['AM', 'PM'], 'periodo');
        const pie = document.createElement('div');
        pie.className = 'selector-hora-pie';
        const ahora = document.createElement('button');
        ahora.type = 'button';
        ahora.className = 'boton boton-secundario boton-chico';
        ahora.textContent = 'Ahora';
        const listo = document.createElement('button');
        listo.type = 'button';
        listo.className = 'boton boton-primario boton-chico';
        listo.textContent = 'Listo';
        pie.append(ahora, listo);
        panel.append(columnas, pie);

        oculto.parentNode.insertBefore(envoltura, oculto);
        envoltura.append(texto, boton, panel);
        oculto.type = 'hidden';
        oculto.id = etiqueta + '-valor';
        oculto.removeAttribute('required');
        // Validación de los formularios del sistema (sección 5)
        if (oculto.dataset.validar === 'requerido' || requerido) {
            oculto.dataset.validar = 'hora';
        } else if (!oculto.dataset.validar) {
            oculto.dataset.validar = 'hora-opcional';
        }
        oculto.campoVisible = texto;

        return { oculto: oculto, requerido: requerido, envoltura: envoltura, texto: texto,
                 boton: boton, panel: panel, ahora: ahora, listo: listo };
    }


    CV.hora = { dosDigitos: dosDigitos, desde24: desde24, a24: a24, texto12: texto12,
                periodoProbable: periodoProbable, interpretarHora: interpretarHora,
                construir: construirSelectorHora };

})(window.CV = window.CV || {});
