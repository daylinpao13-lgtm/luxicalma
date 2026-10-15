<?php
require_once __DIR__ . '/includes/carrito_helper.php';
require_once __DIR__ . '/../config/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

$pedido_stmt = $conexion->prepare("SELECT * FROM pedidos WHERE id = :id AND usuario_id = :usuario_id");
$pedido_stmt->execute([':id' => $id, ':usuario_id' => $_SESSION['usuario_id']]);
$pedido = $pedido_stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    header('Location: inicio.php');
    exit;
}

$detalle_stmt = $conexion->prepare(
    "SELECT pd.cantidad, pd.color, pd.aroma, pd.precio_unitario, p.nombre, p.foto
     FROM pedido_detalle pd JOIN productos p ON p.id = pd.producto_id
     WHERE pd.pedido_id = :id"
);
$detalle_stmt->execute([':id' => $id]);
$detalle = $detalle_stmt->fetchAll(PDO::FETCH_ASSOC);
$descripcion_pedido = [];
foreach ($detalle as $linea) {
    $descripcion_linea = 'x' . (int) $linea['cantidad'] . ' ' . $linea['nombre'];
    if (!empty($linea['color'])) {
        $descripcion_linea .= ', color ' . $linea['color'];
    }
    if (!empty($linea['aroma'])) {
        $descripcion_linea .= ', aroma ' . $linea['aroma'];
    }
    $descripcion_pedido[] = $descripcion_linea;
}
$mensaje_whatsapp = 'Hola, realicé mi pedido #' . (int) $pedido['id'] . ': '
    . implode('; ', $descripcion_pedido)
    . '. Medio de pago: ' . (($pedido['metodo_pago'] ?? '') === 'nequi' ? 'Nequi' : 'por coordinar por WhatsApp')
    . '. ¿Me confirman si ya tomaron la orden y si ya está en camino?';
$url_whatsapp = 'https://wa.me/573227255894?text=' . rawurlencode($mensaje_whatsapp);

$titulo_pagina = 'Pedido confirmado';
$css_pagina = 'carrito.css';
require 'includes/header.php';
?>

<section class="contenedor pagina-carrito confirmacion">
    <div class="confirmacion-cabecera">
        <p class="confirmacion-icono" aria-hidden="true">🕯️</p>
        <h1>¡Gracias por tu pedido!</h1>
        <p>Tu pedido <strong>#<?php echo (int) $pedido['id']; ?></strong> quedó registrado.
           Puedes consultar su estado en «Mis pedidos» o escribirnos por WhatsApp.</p>
    </div>

    <div class="layout-carrito">
        <div class="caja-resumen">
            <h2>Resumen del pedido</h2>
            <ul class="resumen-productos">
                <?php foreach ($detalle as $linea): ?>
                    <li>
                        <img src="<?php echo luxi_foto($linea['foto'], $linea['nombre']); ?>" alt="">
                        <div>
                            <strong><?php echo luxi_h($linea['nombre']); ?></strong>
                            <span><?php echo luxi_h($linea['color']); ?><?php echo !empty($linea['aroma']) ? ' · Aroma: ' . luxi_h($linea['aroma']) : ''; ?> · x<?php echo (int) $linea['cantidad']; ?></span>
                        </div>
                        <?php if ((float) $linea['precio_unitario'] > 0): ?>
                            <span><?php echo luxi_formato_precio($linea['precio_unitario'] * $linea['cantidad']); ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="linea-resumen linea-total"><span>Total</span><strong><?php echo luxi_formato_precio($pedido['total']); ?></strong></div>

            <?php if ($pedido['fecha_entrega_estimada']): ?>
                <p class="nota-anticipo">
                    Como el pedido supera las <?php echo LUXI_UMBRAL_ANTICIPO; ?> unidades, se necesita un pago inicial
                    para agendar la entrega. Fecha estimada de entrega:
                    <strong><?php echo date('d/m/Y', strtotime($pedido['fecha_entrega_estimada'])); ?></strong>.
                </p>
            <?php endif; ?>
        </div>

        <aside class="resumen-carrito">
            <h2>Entrega</h2>
            <?php if (($pedido['metodo_pago'] ?? '') === 'whatsapp'): ?>
                <p class="dato-entrega"><strong>Medio de pago:</strong> WhatsApp (temporal)</p>
            <?php elseif (($pedido['metodo_pago'] ?? '') === 'nequi'): ?>
                <div class="instrucciones-nequi">
                    <h3>Pago por Nequi</h3>
                    <p>Transfiere <strong><?php echo luxi_formato_precio($pedido['total']); ?></strong> al Nequi del negocio:</p>
                    <p class="numero-nequi">321 532 4610</p>
                    <p>Después de transferir, envíanos el comprobante por WhatsApp para verificar el pago.</p>
                </div>
            <?php endif; ?>
            <?php if (!empty($pedido['direccion_envio'])): ?>
                <p class="dato-entrega"><strong><?php echo luxi_h($pedido['nombre_contacto']); ?></strong></p>
                <p class="dato-entrega"><?php echo luxi_h($pedido['direccion_envio']); ?></p>
                <p class="dato-entrega"><?php echo luxi_h($pedido['ciudad_envio']); ?>, <?php echo luxi_h($pedido['departamento_envio']); ?></p>
                <p class="dato-entrega">Tel: <?php echo luxi_h($pedido['telefono']); ?></p>
                <?php if (!empty($pedido['notas'])): ?>
                    <p class="dato-entrega"><em>“<?php echo luxi_h($pedido['notas']); ?>”</em></p>
                <?php endif; ?>
            <?php endif; ?>
            <a href="mis_pedidos.php" class="boton-negro boton-ancho">Ver mis pedidos</a>
            <a href="<?php echo luxi_h($url_whatsapp); ?>" class="boton-claro boton-ancho" target="_blank" rel="noopener noreferrer">
                <?php echo ($pedido['metodo_pago'] ?? '') === 'nequi' ? 'Enviar comprobante por WhatsApp' : 'Consultar por WhatsApp'; ?>
            </a>
            <a href="productos.php" class="boton-claro boton-ancho">Seguir comprando</a>
        </aside>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
