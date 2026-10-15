<footer class="pie-pagina">
    <div class="pie-columnas">
        <div>
            <p class="pie-marca">LUXICALMA</p>
            <p class="pie-texto">Velas aromáticas hechas para iluminar tus momentos. Luz, aroma y calma en cada detalle.</p>
        </div>
        <div>
            <p class="pie-titulo">Tienda</p>
            <ul>
                <li><a href="productos.php">Catálogo</a></li>
                <li><a href="productos.php?filtro=ofertas">Ofertas</a></li>
                <li><a href="productos.php?filtro=mas_vendidos">Más vendidos</a></li>
                <li><a href="productos.php?filtro=tematicos">Temáticos</a></li>
            </ul>
        </div>
        <div>
            <p class="pie-titulo">Tu cuenta</p>
            <ul>
                <li><a href="carrito.php">Mi carrito</a></li>
                <li><a href="mis_pedidos.php">Mis pedidos</a></li>
                <li><a href="login.php">Iniciar sesión</a></li>
            </ul>
        </div>
        <div>
            <p class="pie-titulo">Síguenos</p>
            <ul>
                <li><a href="https://www.instagram.com/luxicalma_ofi/" target="_blank" rel="noopener noreferrer">Instagram</a></li>
                <li><a href="https://www.tiktok.com/@luxicalma_ofi?_r=1&amp;_t=ZS-9AKpA1TBZTg" target="_blank" rel="noopener noreferrer">TikTok</a></li>
                <li><a href="https://wa.me/573227255894" target="_blank" rel="noopener noreferrer">WhatsApp</a></li>
            </ul>
        </div>
    </div>
    <p class="pie-derechos">&copy; <?php echo date('Y'); ?> Luxicalma — Luz y aroma</p>
</footer>

<!-- Panel lateral del mini-carrito (se abre al tocar el ícono del carrito) -->
<div class="mini-carrito-fondo" id="mini-carrito-fondo" hidden></div>
<aside class="mini-carrito" id="mini-carrito" aria-label="Tu carrito" aria-hidden="true">
    <div class="mini-carrito-cabecera">
        <h2>Tu carrito</h2>
        <button type="button" class="mini-carrito-cerrar" id="cerrar-mini-carrito" aria-label="Cerrar carrito">×</button>
    </div>
    <div class="mini-carrito-cuerpo" id="mini-carrito-cuerpo"></div>
    <div class="mini-carrito-pie" id="mini-carrito-pie"></div>
</aside>

<!-- Aviso breve ("Se agregó a tu carrito") -->
<div class="toast-contenedor" id="toast-contenedor" aria-live="polite"></div>

<script src="../assets/js/carrito.js"></script>
<script src="../assets/js/galeria_productos.js?v=20261010"></script>
</body>
</html>
