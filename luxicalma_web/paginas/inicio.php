<?php
require_once __DIR__ . '/includes/carrito_helper.php';
require_once __DIR__ . '/../config/conexion.php';

$titulo_pagina = 'Inicio';
$css_pagina = 'inicio.css';

// Banners del carrusel (promociones, lanzamientos, temáticas)
$banners = [];
try {
    $banners = $conexion->query(
        "SELECT * FROM banners WHERE activo = 1 ORDER BY orden"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $banners = [];
}

// Productos nuevos
$consulta = $conexion->query(
    "SELECT * FROM productos
     ORDER BY fecha_creacion DESC
     LIMIT 8"
);
$productos_nuevos = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Últimas reseñas de toda la tienda, para mostrar en el inicio
$resenas_stmt = $conexion->query(
    "SELECT r.comentario, r.calificacion, u.nombres, p.nombre AS producto
     FROM resenas r
     JOIN usuarios u ON u.id = r.usuario_id
     JOIN productos p ON p.id = r.producto_id
     ORDER BY r.fecha DESC
     LIMIT 5"
);
$resenas_inicio = $resenas_stmt->fetchAll(PDO::FETCH_ASSOC);

// Colecciones/temáticas activas (San Valentín, Amor y amistad, Navidad...)
// para el carrusel de temporada. Rota automáticamente entre las que
// la administradora tenga activas y con imagen subida.
$tematicas = [];
try {
    $tematicas = $conexion->query(
        "SELECT nombre, descripcion, imagen_banner
         FROM colecciones
         WHERE activa = 1
           AND (fecha_inicio IS NULL OR fecha_inicio <= CURRENT_DATE())
           AND (fecha_fin IS NULL OR fecha_fin >= CURRENT_DATE())
           AND imagen_banner IS NOT NULL AND imagen_banner <> ''
         ORDER BY fecha_inicio DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $tematicas = [];
}

require 'includes/header.php';
// (la variable $categorias_menu ya quedó cargada desde el header)
?>

<!-- Carrusel de promociones, lanzamientos y temáticas -->
<section class="carrusel" id="carrusel">
    <?php if (count($banners) === 0): ?>
        <div class="carrusel-slide activo">
            <div class="hero-velas">
                <div class="hero-llama" aria-hidden="true"></div>
                <p class="hero-sobretitulo">Velas aromáticas artesanales</p>
                <h1>Enciende la calma de tu hogar</h1>
                <p class="hero-texto">Aromas que abrazan, luz cálida y detalles para cada ocasión especial.</p>
                <div class="hero-botones">
                    <a href="productos.php" class="boton-negro">Ver catálogo</a>
                    <a href="productos.php?filtro=ofertas" class="boton-claro">Ofertas</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($banners as $indice => $banner): ?>
            <div class="carrusel-slide <?php echo $indice === 0 ? 'activo' : ''; ?>">
                <a href="<?php echo htmlspecialchars($banner['enlace'] ?: '#'); ?>">
                    <img src="../assets/img/<?php echo htmlspecialchars($banner['imagen']); ?>"
                         alt="<?php echo htmlspecialchars($banner['titulo']); ?>">
                </a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<!-- Íconos circulares de categorías -->
<?php if (count($categorias_menu) > 0): ?>
<section class="contenedor seccion-categorias-circulares">
    <div class="fila-categorias-circulares">
        <?php foreach ($categorias_menu as $cat): ?>
            <a href="productos.php?categoria_id=<?php echo (int) $cat['id']; ?>" class="categoria-circular">
                <div class="circulo-icono">
                    <?php if (!empty($cat['imagen_icono'])): ?>
                        <img src="../assets/img/<?php echo htmlspecialchars($cat['imagen_icono']); ?>" alt="">
                    <?php else: ?>
                        🕯️
                    <?php endif; ?>
                </div>
                <span><?php echo htmlspecialchars($cat['nombre']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Carrusel de la temática de temporada (solo si hay una activa con imagen) -->
<?php if (count($tematicas) > 0): ?>
<section class="carrusel carrusel-tematico" id="carrusel-tematico">
    <?php foreach ($tematicas as $indice => $tematica): ?>
        <div class="carrusel-slide <?php echo $indice === 0 ? 'activo' : ''; ?>">
            <div class="imagen-tematica">
                <img src="../assets/img/<?php echo htmlspecialchars($tematica['imagen_banner']); ?>"
                     alt="<?php echo htmlspecialchars($tematica['nombre']); ?>">
                <div class="texto-tematico">
                    <h2><?php echo htmlspecialchars($tematica['nombre']); ?></h2>
                    <?php if (!empty($tematica['descripcion'])): ?>
                        <p><?php echo htmlspecialchars($tematica['descripcion']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<!-- Beneficios -->
<section class="contenedor franja-beneficios">
    <div><strong>Cera de calidad</strong><span>Mechas de algodón y fragancias cuidadosamente elegidas</span></div>
    <div><strong>Para cada ocasión</strong><span>Religiosas, navideñas, baby shower, amor y amistad</span></div>
    <div><strong>Envíos nacionales</strong><span>Empacadas con cuidado para que lleguen perfectas</span></div>
</section>

<section class="contenedor seccion-productos">
    <h2>Nuevas fragancias</h2>
    <div class="grid-productos">
        <?php if (count($productos_nuevos) === 0): ?>
            <p>Muy pronto tendremos nuevas velas para ti.</p>
        <?php else: ?>
            <?php foreach ($productos_nuevos as $producto): ?>
                <?php require __DIR__ . '/includes/tarjeta_producto.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Reseñas de clientes, antes del pie de página -->
<section class="contenedor seccion-resenas-inicio">
    <h2>Lo que dicen nuestras clientas</h2>
    <?php if (count($resenas_inicio) === 0): ?>
        <p class="nota-filtro">Todavía no hay reseñas. ¡Sé la primera en dejar una!</p>
    <?php else: ?>
        <div class="fila-resenas">
            <?php foreach ($resenas_inicio as $resena): ?>
                <div class="tarjeta-resena">
                    <div class="estrellas">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="<?php echo $i <= $resena['calificacion'] ? 'estrella-llena' : 'estrella-vacia'; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <p class="comentario">"<?php echo htmlspecialchars($resena['comentario']); ?>"</p>
                    <p class="autor">— <?php echo htmlspecialchars($resena['nombres']); ?>, sobre <?php echo htmlspecialchars($resena['producto']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<script src="../assets/js/carrusel.js"></script>
<?php require 'includes/footer.php'; ?>
