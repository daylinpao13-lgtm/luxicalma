<?php
require_once __DIR__ . '/includes/carrito_helper.php';
require_once __DIR__ . '/../config/conexion.php';

$c = luxi_carrito_detallado($conexion);

$titulo_pagina = 'Carrito';
$css_pagina = 'carrito.css';
require 'includes/header.php';
?>

<section class="contenedor pagina-carrito">
    <h1>Tu carrito</h1>

    <?php if (isset($_GET['error'])): ?>
        <p class="mensaje-error"><?php echo luxi_h($_GET['error']); ?></p>
    <?php endif; ?>
    <?php if (isset($_GET['ok'])): ?>
        <p class="mensaje-exito"><?php echo luxi_h($_GET['ok']); ?></p>
    <?php endif; ?>
    <?php foreach ($c['avisos'] as $aviso): ?>
        <p class="mensaje-aviso"><?php echo luxi_h($aviso); ?></p>
    <?php endforeach; ?>

    <?php if (count($c['items']) === 0): ?>
        <div class="carrito-vacio">
            <p class="carrito-vacio-icono" aria-hidden="true">🕯️</p>
            <h2>Tu carrito está vacío</h2>
            <p>Encuentra la vela perfecta para tu hogar o para regalar.</p>
            <a href="productos.php" class="boton-negro">Ver catálogo</a>
        </div>
    <?php else: ?>
        <div class="layout-carrito">
            <div class="lista-carrito">
                <?php foreach ($c['items'] as $clave => $item): ?>
                    <div class="fila-carrito">
                        <a href="detalle_producto.php?id=<?php echo $item['producto_id']; ?>">
                            <img src="<?php echo $item['foto']; ?>" alt="<?php echo luxi_h($item['nombre']); ?>">
                        </a>

                        <div class="datos-item">
                            <h3><a href="detalle_producto.php?id=<?php echo $item['producto_id']; ?>"><?php echo luxi_h($item['nombre']); ?></a></h3>
                            <p><?php echo luxi_h(luxi_variante($item['aroma'], $item['color'])); ?></p>
                            <p>Precio unidad: <?php echo luxi_formato_precio($item['precio']); ?></p>
                        </div>

                        <form action="carrito_actualizar.php" method="POST" class="form-cantidad">
                            <?php echo luxi_csrf_campo(); ?>
                            <input type="hidden" name="clave" value="<?php echo luxi_h($clave); ?>">
                            <div class="selector-cantidad">
                                <button type="submit" name="accion" value="menos" aria-label="Quitar una unidad">−</button>
                                <span class="cantidad-actual"><?php echo $item['cantidad']; ?></span>
                                <button type="submit" name="accion" value="mas" aria-label="Agregar una unidad"
                                        <?php echo $item['cantidad'] >= $item['stock'] ? 'disabled' : ''; ?>>+</button>
                            </div>
                        </form>

                        <p class="subtotal"><?php echo luxi_formato_precio($item['subtotal']); ?></p>

                        <form action="carrito_eliminar.php" method="POST">
                            <?php echo luxi_csrf_campo(); ?>
                            <input type="hidden" name="clave" value="<?php echo luxi_h($clave); ?>">
                            <button type="submit" class="boton-eliminar" aria-label="Quitar <?php echo luxi_h($item['nombre']); ?>">Quitar</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <aside class="resumen-carrito">
                <h2>Resumen</h2>
                <div class="linea-resumen"><span>Productos</span><span><?php echo $c['unidades']; ?></span></div>
                <div class="linea-resumen"><span>Subtotal</span><span><?php echo luxi_formato_precio($c['total']); ?></span></div>
                <div class="linea-resumen linea-total"><span>Total</span><strong><?php echo luxi_formato_precio($c['total']); ?></strong></div>

                <?php if ($c['requiere_anticipo']): ?>
                    <p class="nota-anticipo">
                        Tu pedido supera las <?php echo LUXI_UMBRAL_ANTICIPO; ?> unidades: la entrega se agenda con
                        <?php echo LUXI_DIAS_ANTICIPO; ?> días de anticipación y un pago inicial.
                    </p>
                <?php endif; ?>

                <a href="checkout.php" class="boton-negro boton-ancho">Finalizar pedido</a>
                <a href="productos.php" class="boton-claro boton-ancho">Seguir comprando</a>
                <?php if (!isset($_SESSION['usuario_id'])): ?>
                    <p class="texto-ayuda-carrito">Te pediremos iniciar sesión para confirmar tu pedido.</p>
                <?php endif; ?>
            </aside>
        </div>
    <?php endif; ?>
</section>

<?php require 'includes/footer.php'; ?>
