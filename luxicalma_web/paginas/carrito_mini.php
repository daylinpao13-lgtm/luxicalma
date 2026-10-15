<?php
/**
 * carrito_mini.php
 * Devuelve en JSON el contenido del carrito para el panel lateral
 * (mini-carrito) que se abre desde el ícono del encabezado.
 */
require_once __DIR__ . '/includes/carrito_helper.php';
require_once __DIR__ . '/../config/conexion.php';

$c = luxi_carrito_detallado($conexion);

$items = [];
foreach ($c['items'] as $item) {
    $items[] = [
        'clave' => $item['clave'],
        'nombre' => $item['nombre'],
        'foto' => $item['foto'],
        'color' => $item['color'],
        'aroma' => $item['aroma'],
        'variante' => luxi_variante($item['aroma'], $item['color']),
        'cantidad' => $item['cantidad'],
        'precio' => luxi_formato_precio($item['precio']),
        'subtotal' => luxi_formato_precio($item['subtotal']),
        'url' => 'detalle_producto.php?id=' . $item['producto_id'],
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'items' => $items,
    'unidades' => $c['unidades'],
    'total' => luxi_formato_precio($c['total']),
    'avisos' => $c['avisos'],
    'requiere_anticipo' => $c['requiere_anticipo'],
], JSON_UNESCAPED_UNICODE);
