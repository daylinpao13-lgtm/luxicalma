document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.galeria-carrusel').forEach(function (galeria) {
        var pista = galeria.querySelector('[data-galeria-pista]');
        var puntos = Array.from(galeria.querySelectorAll('[data-galeria-indice]'));
        if (!pista || puntos.length < 2) return;

        var temporizador;
        function actualizarIndicador() {
            var indice = Math.round(pista.scrollLeft / pista.clientWidth);
            puntos.forEach(function (punto, posicion) {
                var activo = posicion === indice;
                punto.classList.toggle('activo', activo);
                punto.setAttribute('aria-pressed', activo ? 'true' : 'false');
            });
        }

        function irA(indice) {
            var destino = (indice + puntos.length) % puntos.length;
            pista.scrollTo({ left: pista.clientWidth * destino, behavior: 'smooth' });
        }

        galeria.querySelectorAll('[data-galeria-paso]').forEach(function (flecha) {
            flecha.addEventListener('click', function (evento) {
                evento.stopPropagation();
                var indiceActual = puntos.findIndex(function (punto) {
                    return punto.getAttribute('aria-pressed') === 'true';
                });
                irA(indiceActual + Number(flecha.dataset.galeriaPaso));
            });
        });

        puntos.forEach(function (punto) {
            punto.addEventListener('click', function (evento) {
                evento.stopPropagation();
                irA(Number(punto.dataset.galeriaIndice));
            });
        });

        pista.addEventListener('scroll', function () {
            window.clearTimeout(temporizador);
            temporizador = window.setTimeout(actualizarIndicador, 80);
        }, { passive: true });
    });

    document.querySelectorAll('.galeria-opciones').forEach(function (galeria) {
        var principal = galeria.querySelector('[data-imagen-opcion-principal]');
        if (!principal) return;

        galeria.querySelectorAll('[data-imagen-opcion]').forEach(function (opcion) {
            opcion.addEventListener('click', function (evento) {
                evento.stopPropagation();
                principal.src = opcion.dataset.imagenOpcion;
                principal.alt = opcion.dataset.imagenAlt;
                galeria.querySelectorAll('[data-imagen-opcion]').forEach(function (otraOpcion) {
                    var activa = otraOpcion === opcion;
                    otraOpcion.classList.toggle('activa', activa);
                    otraOpcion.setAttribute('aria-pressed', activa ? 'true' : 'false');
                });
            });
        });
    });

    document.querySelectorAll('.tarjeta-producto[data-product-url]').forEach(function (tarjeta) {
        tarjeta.addEventListener('click', function (evento) {
            if (evento.target.closest('a, button')) return;
            window.location.href = tarjeta.dataset.productUrl;
        });
        tarjeta.addEventListener('keydown', function (evento) {
            if (evento.target !== tarjeta || (evento.key !== 'Enter' && evento.key !== ' ')) return;
            evento.preventDefault();
            window.location.href = tarjeta.dataset.productUrl;
        });
    });
});
