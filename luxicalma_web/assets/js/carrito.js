// carrito.js
// -------------------------------------------------------------
// Hace que el carrito se sienta ágil:
//  - Los botones "Agregar" suman la vela sin recargar la página.
//  - El ícono del carrito abre un panel lateral (mini-carrito).
//  - Aparecen avisos breves ("Se agregó a tu carrito").
// Si JavaScript no carga, todo sigue funcionando con formularios
// normales (los mismos archivos PHP responden en ambos casos).
// -------------------------------------------------------------
(function () {
    'use strict';

    var meta = document.querySelector('meta[name="csrf-token"]');
    var csrf = meta ? meta.content : '';

    var fondo = document.getElementById('mini-carrito-fondo');
    var panel = document.getElementById('mini-carrito');
    var cuerpo = document.getElementById('mini-carrito-cuerpo');
    var pie = document.getElementById('mini-carrito-pie');
    var contador = document.getElementById('contador-carrito');
    var toasts = document.getElementById('toast-contenedor');

    function esc(texto) {
        var d = document.createElement('div');
        d.textContent = texto == null ? '' : String(texto);
        return d.innerHTML;
    }

    // ---------- Avisos ----------
    function aviso(mensaje, tipo) {
        if (!toasts) return;
        var t = document.createElement('div');
        t.className = 'toast toast-' + (tipo || 'ok');
        t.textContent = mensaje;
        toasts.appendChild(t);
        requestAnimationFrame(function () { t.classList.add('visible'); });
        setTimeout(function () {
            t.classList.remove('visible');
            setTimeout(function () { t.remove(); }, 300);
        }, 3200);
    }

    function actualizarContador(n) {
        if (!contador) return;
        contador.textContent = n;
        contador.hidden = !(n > 0);
        contador.classList.remove('rebote');
        void contador.offsetWidth; // reinicia la animación
        contador.classList.add('rebote');
    }

    // ---------- Llamadas al servidor ----------
    function post(url, datos) {
        var cuerpoForm = new FormData();
        Object.keys(datos).forEach(function (k) { cuerpoForm.append(k, datos[k]); });
        cuerpoForm.append('csrf', csrf);
        return fetch(url, {
            method: 'POST',
            body: cuerpoForm,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'fetch', 'X-CSRF-Token': csrf }
        }).then(function (r) {
            return r.json().catch(function () {
                return { ok: false, mensaje: 'Ocurrió un problema. Intenta de nuevo.' };
            });
        });
    }

    // ---------- Mini-carrito ----------
    function abrirPanel() {
        if (!panel) return;
        panel.classList.add('abierto');
        panel.setAttribute('aria-hidden', 'false');
        fondo.hidden = false;
        requestAnimationFrame(function () { fondo.classList.add('visible'); });
        document.body.classList.add('sin-scroll');
        cargarPanel();
    }

    function cerrarPanel() {
        if (!panel) return;
        panel.classList.remove('abierto');
        panel.setAttribute('aria-hidden', 'true');
        fondo.classList.remove('visible');
        document.body.classList.remove('sin-scroll');
        setTimeout(function () { fondo.hidden = true; }, 250);
    }

    function cargarPanel() {
        cuerpo.innerHTML = '<p class="mini-vacio">Cargando…</p>';
        fetch('carrito_mini.php', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(pintarPanel)
            .catch(function () {
                cuerpo.innerHTML = '<p class="mini-vacio">No pudimos cargar tu carrito.</p>';
            });
    }

    function pintarPanel(datos) {
        actualizarContador(datos.unidades);

        if (!datos.items.length) {
            cuerpo.innerHTML =
                '<div class="mini-vacio"><p class="mini-vela" aria-hidden="true">🕯️</p>' +
                '<p>Tu carrito está vacío.</p>' +
                '<a href="productos.php" class="boton-negro">Ver velas</a></div>';
            pie.innerHTML = '';
            return;
        }

        var html = '';
        if (datos.avisos && datos.avisos.length) {
            html += datos.avisos.map(function (a) { return '<p class="mini-aviso">' + esc(a) + '</p>'; }).join('');
        }
        html += datos.items.map(function (i) {
            return '<div class="mini-item">' +
                '<img src="' + esc(i.foto) + '" alt="">' +
                '<div class="mini-datos">' +
                '<a href="' + esc(i.url) + '" class="mini-nombre">' + esc(i.nombre) + '</a>' +
                '<span class="mini-color">' + esc(i.variante) + '</span>' +
                '<div class="mini-cantidad">' +
                '<button type="button" data-accion="menos" data-clave="' + esc(i.clave) + '" aria-label="Quitar una unidad">−</button>' +
                '<span>' + i.cantidad + '</span>' +
                '<button type="button" data-accion="mas" data-clave="' + esc(i.clave) + '" aria-label="Agregar una unidad">+</button>' +
                '</div></div>' +
                '<div class="mini-precio">' + esc(i.subtotal) +
                '<button type="button" class="mini-quitar" data-quitar="' + esc(i.clave) + '">Quitar</button></div>' +
                '</div>';
        }).join('');
        cuerpo.innerHTML = html;

        pie.innerHTML =
            (datos.requiere_anticipo
                ? '<p class="mini-nota">Más de 5 unidades: se agenda la entrega con 10 días de anticipación y un pago inicial.</p>'
                : '') +
            '<div class="mini-total"><span>Total</span><strong>' + esc(datos.total) + '</strong></div>' +
            '<a href="checkout.php" class="boton-negro boton-ancho">Finalizar pedido</a>' +
            '<a href="carrito.php" class="boton-claro boton-ancho">Ver carrito completo</a>';
    }

    if (panel) {
        var abrir = document.getElementById('abrir-mini-carrito');
        if (abrir) {
            abrir.addEventListener('click', function (e) {
                // La página del carrito y la de checkout ya muestran todo: ahí se navega normal
                if (/carrito\.php|checkout\.php/.test(location.pathname)) return;
                e.preventDefault();
                abrirPanel();
            });
        }
        document.getElementById('cerrar-mini-carrito').addEventListener('click', cerrarPanel);
        fondo.addEventListener('click', cerrarPanel);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') cerrarPanel();
        });

        // Botones +, − y "Quitar" dentro del panel
        panel.addEventListener('click', function (e) {
            var btn = e.target.closest('button');
            if (!btn) return;

            if (btn.dataset.accion) {
                post('carrito_actualizar.php', { clave: btn.dataset.clave, accion: btn.dataset.accion })
                    .then(function (r) {
                        if (!r.ok) aviso(r.mensaje, 'error');
                        cargarPanel();
                    });
            } else if (btn.dataset.quitar) {
                post('carrito_eliminar.php', { clave: btn.dataset.quitar })
                    .then(function () { cargarPanel(); });
            }
        });
    }

    // ---------- "Agregar al carrito" sin recargar ----------
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.matches || !form.matches('form[data-agregar]')) return;
        e.preventDefault();

        var boton = form.querySelector('button[type="submit"]');
        var textoOriginal = boton ? boton.textContent : '';
        if (boton) { boton.disabled = true; boton.textContent = 'Agregando…'; }

        var datos = {};
        new FormData(form).forEach(function (valor, clave) { datos[clave] = valor; });

        post('carrito_agregar.php', datos).then(function (r) {
            aviso(r.mensaje, r.ok ? 'ok' : 'error');
            if (r.ok) {
                actualizarContador(r.cantidad_total);
                if (boton) {
                    boton.textContent = '✓ Agregado';
                    setTimeout(function () { boton.textContent = textoOriginal; boton.disabled = false; }, 1400);
                    return;
                }
            }
            if (boton) { boton.textContent = textoOriginal; boton.disabled = false; }
        }).catch(function () {
            aviso('No pudimos agregar el producto. Intenta de nuevo.', 'error');
            if (boton) { boton.textContent = textoOriginal; boton.disabled = false; }
        });
    });

    // ---------- Selector de cantidad (+ / −) en la ficha del producto ----------
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-paso]');
        if (!btn) return;
        var campo = document.getElementById(btn.dataset.objetivo);
        if (!campo) return;
        var min = parseInt(campo.min || '1', 10);
        var max = parseInt(campo.max || '999', 10);
        var valor = (parseInt(campo.value, 10) || min) + parseInt(btn.dataset.paso, 10);
        campo.value = Math.min(max, Math.max(min, valor));
    });
})();
