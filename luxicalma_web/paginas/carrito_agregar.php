<?php
/**
 * carrito_agregar.php
 * -------------------------------------------------------------
 * Agrega una vela (o cualquier producto) al carrito de la sesión.
 * Funciona de dos formas:
 *   - Con JavaScript (botón "Agregar" de las tarjetas): responde JSON
 *     y la página no se recarga.
 *   - Sin JavaScript (formulario normal): redirige de vuelta.
 * -------------------------------------------------------------
 */
require_once __DIR__ . '/includes/carrito_helper.php';
require_once __DIR__ . '/../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: productos.php');
    exit;
}

$producto_id = (int) ($_POST['producto_id'] ?? 0);
$volver = 'detalle_producto.php?id=' . $producto_id;

if (!luxi_csrf_valido()) {
    luxi_responder(false, 'Tu sesión expiró. Recarga la página e intenta de nuevo.', $volver);
}

// Siempre se consulta el producto en la base de datos (nunca se confía en
// un precio o nombre que llegue desde el formulario)
$sentencia = $conexion->prepare("SELECT * FROM productos WHERE id = :id");
$sentencia->execute([':id' => $producto_id]);
$producto = $sentencia->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    luxi_responder(false, 'Ese producto ya no existe.', 'productos.php');
}

// El color debe ser uno de los que realmente tiene el producto
$colores = luxi_colores($producto['colores_disponibles']);
$color = trim($_POST['color'] ?? '');
if (count($colores) === 0) {
    $color = 'Único';
} elseif ($color === '') {
    $color = $colores[0];   // desde la tarjeta rápida: primer color disponible
} elseif (!in_array($color, $colores, true)) {
    luxi_responder(false, 'Ese color no está disponible para este producto.', $volver);
}

$aromas = luxi_aromas($producto['aroma'] ?? null);
$aroma = trim((string) ($_POST['aroma'] ?? ''));
if (count($aromas) === 0) {
    $aroma = '';
} elseif ($aroma === '' && count($aromas) === 1) {
    $aroma = $aromas[0];
} elseif ($aroma === '' || !in_array($aroma, $aromas, true)) {
    luxi_responder(false, 'Selecciona un aroma disponible para este producto.', $volver);
}

$cantidad = max(1, (int) ($_POST['cantidad'] ?? 1));
$clave = luxi_clave_carrito($producto_id, $color, $aroma);
$ya_en_carrito = (int) ($_SESSION['carrito'][$clave]['cantidad'] ?? 0);
$unidades_producto_en_carrito = 0;
$clave_anterior = $producto_id . '_' . $color;
$linea_anterior = $_SESSION['carrito'][$clave_anterior] ?? null;
$migrar_linea_anterior = false;
foreach ($_SESSION['carrito'] ?? [] as $linea_carrito) {
    if ((int) ($linea_carrito['producto_id'] ?? 0) === $producto_id) {
        $unidades_producto_en_carrito += (int) ($linea_carrito['cantidad'] ?? 0);
    }
}
if ($linea_anterior !== null) {
    $aroma_anterior = trim((string) ($linea_anterior['aroma'] ?? ''));
    if ($aroma_anterior === '' && count($aromas) > 0) {
        $aroma_anterior = $aromas[0];
    }
    if ($aroma_anterior === $aroma) {
        if ($clave_anterior !== $clave) {
            $ya_en_carrito += (int) ($linea_anterior['cantidad'] ?? 0);
        }
        $migrar_linea_anterior = true;
    }
}

// El stock se compara contra lo que YA hay en el carrito + lo nuevo
if ($unidades_producto_en_carrito + $cantidad > (int) $producto['stock']) {
    $disponibles = max(0, (int) $producto['stock'] - $unidades_producto_en_carrito);
    $mensaje = $disponibles === 0
        ? 'Ya tienes en tu carrito todas las unidades disponibles de esta vela.'
        : "Solo puedes agregar $disponibles unidad(es) más: no hay más stock.";
    luxi_responder(false, $mensaje, $volver);
}

$_SESSION['carrito'][$clave] = [
    'producto_id' => $producto_id,
    'color' => $color,
    'aroma' => $aroma,
    'cantidad' => $ya_en_carrito + $cantidad,
];
if ($migrar_linea_anterior) {
    unset($_SESSION['carrito'][$clave_anterior]);
}

luxi_responder(true, $producto['nombre'] . ' se agregó a tu carrito.', $volver, [
    'nombre' => $producto['nombre'],
]);
