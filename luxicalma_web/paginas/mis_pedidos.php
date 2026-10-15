<?php
require_once __DIR__ . '/includes/carrito_helper.php';
require_once __DIR__ . '/../config/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['volver_a'] = 'mis_pedidos.php';
    header('Location: login.php?error=' . urlencode('Inicia sesión para ver tus pedidos.'));
    exit;
}

$pedidos_stmt = $conexion->prepare("SELECT * FROM pedidos WHERE usuario_id = :id ORDER BY fecha DESC");
$pedidos_stmt->execute([':id' => $_SESSION['usuario_id']]);
$pedidos = $pedidos_stmt->fetchAll(PDO::FETCH_ASSOC);

// Todas las líneas de todos los pedidos en una sola consulta
$lineas = [];
if (count($pedidos) > 0) {
    $ids = array_column($pedidos, 'id');
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $det = $conexion->prepare(
        "SELECT pd.pedido_id, pd.cantidad, pd.color, pd.aroma, pd.precio_unitario, p.nombre, p.foto
         FROM pedido_detalle pd JOIN productos p ON p.id = pd.producto_id
         WHERE pd.pedido_id IN ($marcas)"
    );
    $det->execute($ids);
    foreach ($det->fetchAll(PDO::FETCH_ASSOC) as $l) {
        $lineas[$l['pedido_id']][] = $l;
    }
}

$estados = [
    'pendiente'       => ['Pedido confirmado', 'estado-pendiente'],
    'pago_en_revision' => ['Pago en revisión', 'estado-pendiente'],
    'anticipo_pagado' => ['Anticipo pagado', 'estado-anticipo'],
    'pagado'          => ['Pagado', 'estado-anticipo'],
    'en_empaque'      => ['En proceso de empaque', 'estado-anticipo'],
    'enviado'         => ['En camino', 'estado-anticipo'],
    'completado'      => ['Entregado', 'estado-completado'],
    'cancelado'       => ['Cancelado', 'estado-cancelado'],
];

$titulo_pagina = 'Mis pedidos';
$css_pagina = 'carrito.css';
require 'includes/header.php';
?>

<section class="contenedor pagina-carrito">
    <h1>Mis pedidos</h1>

    <?php if (count($pedidos) === 0): ?>
        <div class="carrito-vacio">
            <p class="carrito-vacio-icono" aria-hidden="true">🕯️</p>
            <h2>Aún no tienes pedidos</h2>
            <p>Cuando hagas tu primera compra, la verás aquí.</p>
            <a href="productos.php" class="boton-negro">Ver catálogo</a>
        </div>
    <?php else: ?>
        <div class="lista-pedidos">
            <?php foreach ($pedidos as $pedido): ?>
                <?php [$texto_estado, $clase_estado] = $estados[$pedido['estado']] ?? [$pedido['estado'], 'estado-pendiente']; ?>
                <?php
                $pasos_seguimiento = ['Pedido confirmado', 'En proceso de empaque', 'En camino', 'Entregado'];
                $paso_actual = [
                    'en_empaque' => 2,
                    'enviado' => 3,
                    'completado' => 4,
                ][$pedido['estado']] ?? 1;
                ?>
                <details class="tarjeta-pedido">
                    <summary>
                        <span class="pedido-numero">Pedido #<?php echo (int) $pedido['id']; ?></span>
                        <span class="pedido-fecha"><?php echo date('d/m/Y', strtotime($pedido['fecha'])); ?></span>
                        <span class="estado <?php echo $clase_estado; ?>"><?php echo luxi_h($texto_estado); ?></span>
                        <strong class="pedido-total"><?php echo luxi_formato_precio($pedido['total']); ?></strong>
                    </summary>

                    <?php if ($pedido['estado'] === 'cancelado'): ?>
                        <p class="seguimiento-cancelado">Este pedido fue cancelado.</p>
                    <?php else: ?>
                        <ol class="seguimiento-pedido" aria-label="Estado actual: <?php echo luxi_h($texto_estado); ?>">
                            <?php foreach ($pasos_seguimiento as $indice_paso => $etiqueta_paso): ?>
                                <?php $numero_paso = $indice_paso + 1; ?>
                                <li class="<?php echo $numero_paso < $paso_actual ? 'completado' : ($numero_paso === $paso_actual ? 'actual' : ''); ?>">
                                    <span><?php echo $numero_paso; ?></span>
                                    <strong><?php echo luxi_h($etiqueta_paso); ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>

                    <ul class="resumen-productos">
                        <?php foreach ($lineas[$pedido['id']] ?? [] as $l): ?>
                            <li>
                                <img src="<?php echo luxi_foto($l['foto'], $l['nombre']); ?>" alt="">
                                <div>
                                    <strong><?php echo luxi_h($l['nombre']); ?></strong>
                                    <span><?php echo luxi_h($l['color']); ?><?php echo !empty($l['aroma']) ? ' · Aroma: ' . luxi_h($l['aroma']) : ''; ?> · x<?php echo (int) $l['cantidad']; ?></span>
                                </div>
                                <?php if ((float) $l['precio_unitario'] > 0): ?>
                                    <span><?php echo luxi_formato_precio($l['precio_unitario'] * $l['cantidad']); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if (!empty($pedido['direccion_envio'])): ?>
                        <p class="dato-entrega">Entrega: <?php echo luxi_h($pedido['direccion_envio']); ?>, <?php echo luxi_h($pedido['ciudad_envio']); ?></p>
                    <?php endif; ?>
                    <?php if ($pedido['fecha_entrega_estimada']): ?>
                        <p class="dato-entrega">Entrega estimada: <?php echo date('d/m/Y', strtotime($pedido['fecha_entrega_estimada'])); ?></p>
                    <?php endif; ?>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require 'includes/footer.php'; ?>
