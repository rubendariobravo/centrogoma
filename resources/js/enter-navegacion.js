/**
 * Enter en lugar de Tab para cargar formularios (como frango-saas y el Delphi).
 *
 *   Enter        → siguiente campo (input o select) del mismo formulario o modal.
 *   Shift+Enter  → campo anterior.
 *   Último campo → foco en el botón de guardar (`data-enter-guardar`); otro Enter lo aprieta.
 *                  En un <form> sin ese botón, lo envía.
 *
 * Las casillas y los textarea (observaciones) no son destino: se saltean, así el Enter no queda atrapado
 * escribiendo saltos de línea; se entra a ellos con el mouse o con Tab.
 *
 * Quedan afuera, con su Enter de siempre: los textarea (salto de línea), los botones, y todo campo que
 * maneje su propio Enter (`x-on:keydown.enter`, `@keydown.enter`, `wire:keydown.enter`, o que haga
 * preventDefault): buscadores de artículo y de cliente, renglones de Ventas, etc. Un contenedor con
 * `data-enter-nativo` desactiva todo esto adentro; `data-enter-saltar` hace que un campo se saltee.
 */

const CAMPOS = [
    'input:not([type=hidden]):not([type=button]):not([type=submit]):not([type=reset])'
        + ':not([type=file]):not([type=image]):not([type=checkbox]):not([type=radio]):not([type=range]):not([type=color])',
    'select',
].join(',');

function visible(el) {
    if (! (el.offsetWidth || el.offsetHeight || el.getClientRects().length)) return false;
    return getComputedStyle(el).visibility !== 'hidden';
}

function disponible(el) {
    return ! el.disabled
        && ! el.readOnly
        && el.tabIndex !== -1
        && ! el.closest('[inert], fieldset[disabled], [data-enter-saltar]')
        && visible(el);
}

/** Dónde se busca el siguiente campo: el modal o formulario que lo contiene, o la página. */
function ambito(el) {
    return el.closest('[data-enter-scope]')
        || el.closest('[role="dialog"], [aria-modal="true"]')
        || el.closest('.fixed')            // los modales de la app son overlays `fixed inset-0`
        || el.closest('form')
        || document.querySelector('main')
        || document.body;
}

function enfocar(el) {
    el.focus();
    try {
        if (el.select && el.tagName === 'INPUT') el.select();
    } catch (_) { /* date, number en algunos navegadores */ }
}

function botonGuardar(scope) {
    return [...scope.querySelectorAll('[data-enter-guardar]')].find(b => visible(b) && ! b.disabled) || null;
}

/** Lleva el foco al botón de guardar del ámbito (o envía el <form>). Devuelve si pudo. */
export function irAGuardar(el) {
    const scope = ambito(el);
    const boton = botonGuardar(scope);
    if (boton) {
        boton.focus();
        return true;
    }
    if (el.form && scope === el.form) {
        el.form.requestSubmit();
        return true;
    }
    return false;
}

/** Foco al campo siguiente (o anterior). Al pasar el último, al botón de guardar. */
export function moverFoco(el, paso = 1) {
    const scope  = ambito(el);
    // El propio campo entra aunque no sea destino (una casilla): da la posición de partida.
    const campos = [...scope.querySelectorAll(CAMPOS + ', input[type=checkbox], input[type=radio]')]
        .filter(c => c === el || (c.matches(CAMPOS) && disponible(c)));
    const i      = campos.indexOf(el);
    const destino = campos[i + paso];

    if (destino) {
        enfocar(destino);
        return true;
    }

    return paso > 0 ? irAGuardar(el) : false;
}

function manejaSuEnter(el) {
    return [...el.attributes].some(a => a.name.includes('keydown.enter'));
}

document.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter' || e.defaultPrevented || e.isComposing || e.altKey || e.ctrlKey || e.metaKey) return;

    const el = e.target;
    if (! (el instanceof HTMLInputElement || el instanceof HTMLSelectElement)) return;
    if (el.type === 'checkbox' || el.type === 'radio') {
        // En una casilla, Enter sigue de largo (la marca se cambia con la barra espaciadora).
    } else if (! el.matches(CAMPOS)) {
        return;
    }
    if (el.closest('[data-enter-nativo]') || manejaSuEnter(el)) return;

    e.preventDefault();
    moverFoco(el, e.shiftKey ? -1 : 1);
});

// Para los handlers de Alpine que deciden su propio Enter y después quieren seguir de largo.
window.enterSiguiente = (el) => moverFoco(el, 1);
window.enterGuardar   = (el) => irAGuardar(el);
