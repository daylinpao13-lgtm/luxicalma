// carrusel.js
// Hace que las imágenes de CUALQUIER carrusel de la página (puede
// haber más de uno, por ejemplo el de promociones y el de temporada)
// cambien solas cada 4 segundos, cada uno de forma independiente.
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.carrusel').forEach(function (carrusel) {
        const slides = carrusel.querySelectorAll('.carrusel-slide');
        if (slides.length <= 1) return; // con una sola imagen no hay nada que rotar

        let actual = 0;
        setInterval(function () {
            slides[actual].classList.remove('activo');
            actual = (actual + 1) % slides.length;
            slides[actual].classList.add('activo');
        }, 4000);
    });
});
