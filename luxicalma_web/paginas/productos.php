<?php
require_once __DIR__ . '/includes/carrito_helper.php';
require_once __DIR__ . '/../config/conexion.php';

$titulo_pagina = 'Productos';
$css_pagina = 'productos.css';

$categoria_id = isset($_GET['categoria_id']) ? (int) $_GET['categoria_id'] : null;
$filtro = $_GET['filtro'] ?? null;
$busqueda = trim($_GET['q'] ?? '');
$titulo_seccion = 'Catálogo';
$nota_filtro = null;

if ($busqueda !== '') {
    // Busca coincidencias en nombre, descripción, aroma y colores disponibles.
    $sentencia = $conexion->prepare(
        "SELECT * FROM productos
         WHERE nombre LIKE :busqueda
            OR descripcion LIKE :busqueda
            OR aroma LIKE :busqueda
            OR colores_disponibles LIKE :busqueda
         ORDER BY nombre"
    );
    $sentencia->execute([':busqueda' => '%' . $busqueda . '%']);
    $titulo_seccion = 'Resultados de búsqueda';

} elseif ($categoria_id) {
    $sentencia = $conexion->prepare(
        "SELECT p.* FROM productos p
         JOIN producto_categorias pc ON pc.producto_id = p.id
         WHERE pc.categoria_id = :id
         ORDER BY p.nombre"
    );
    $sentencia->execute([':id' => $categoria_id]);

    $nombre_cat = $conexion->prepare("SELECT nombre FROM categorias WHERE id = :id");
    $nombre_cat->execute([':id' => $categoria_id]);
    $titulo_seccion = $nombre_cat->fetchColumn() ?: 'Catálogo';

} elseif ($filtro === 'ofertas') {
    // Ofertas: productos que tienen un precio anterior (están en descuento)
    $sentencia = $conexion->query(
        "SELECT * FROM productos WHERE precio_anterior IS NOT NULL ORDER BY nombre"
    );
    $titulo_seccion = 'Ofertas';

} elseif ($filtro === 'mas_vendidos') {
    // Más vendidos de la semana: se calcula sumando las cantidades
    // pedidas en los últimos 7 días desde pedido_detalle.
    $sentencia = $conexion->query(
        "SELECT p.*, COALESCE(SUM(CASE WHEN pe.id IS NOT NULL THEN pd.cantidad END), 0) AS vendidos
         FROM productos p
         LEFT JOIN pedido_detalle pd ON pd.producto_id = p.id
         LEFT JOIN pedidos pe ON pe.id = pd.pedido_id
              AND pe.fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)
              AND pe.estado <> 'cancelado'
         GROUP BY p.id
         ORDER BY vendidos DESC, p.nombre
         LIMIT 20"
    );
    $titulo_seccion = 'Más vendidos esta semana';
    $nota_filtro = 'Se actualiza automáticamente según lo vendido en los últimos 7 días.';

} elseif ($filtro === 'tematicos') {
    // Temáticos: por ahora muestra las colecciones activas (San Valentín,
    // Navidad, etc.). El administrador las activa/desactiva según la fecha.
    $sentencia = $conexion->query(
        "SELECT DISTINCT p.* FROM productos p
         JOIN coleccion_producto cp ON cp.producto_id = p.id
         JOIN colecciones c ON c.id = cp.coleccion_id
         WHERE c.activa = 1
           AND (c.fecha_inicio IS NULL OR c.fecha_inicio <= CURRENT_DATE())
           AND (c.fecha_fin IS NULL OR c.fecha_fin >= CURRENT_DATE())
         ORDER BY p.nombre"
    );
    $titulo_seccion = 'Temáticos';
    $nota_filtro = 'Productos seleccionados para las temporadas y celebraciones activas.';

} else {
    $sentencia = $conexion->query("SELECT * FROM productos ORDER BY nombre");
}

$productos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

require 'includes/header.php';
?>

<section class="contenedor seccion-catalogo">
    <h1><?php echo luxi_h($titulo_seccion); ?></h1>
    <?php if ($nota_filtro): ?>
        <p class="nota-filtro"><?php echo luxi_h($nota_filtro); ?></p>
    <?php endif; ?>
    <p class="conteo-resultados"><?php echo count($productos); ?> producto<?php echo count($productos) === 1 ? '' : 's'; ?></p>

    <div class="grid-productos">
        <?php if (count($productos) === 0): ?>
            <?php if ($busqueda !== ''): ?>
                <div class="sin-resultados">
                    <div class="icono-sin-resultados" aria-hidden="true">⌕</div>
                    <h2>No encontramos productos</h2>
                    <p>No hay productos que coincidan con <strong>"<?php echo htmlspecialchars($busqueda); ?>"</strong>.</p>
                    <a href="productos.php" class="boton-negro">Ver todo el catálogo</a>
                </div>
            <?php else: ?>
                <div class="sin-resultados">
                    <div class="icono-sin-resultados" aria-hidden="true">🕯️</div>
                    <h2>Aún no hay velas aquí</h2>
                    <p>Estamos preparando nuevas fragancias para esta sección.</p>
                    <a href="productos.php" class="boton-negro">Ver todo el catálogo</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php foreach ($productos as $producto): ?>
            <?php require __DIR__ . '/includes/tarjeta_producto.php'; ?>
        <?php endforeach; ?>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
