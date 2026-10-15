<?php
require_once __DIR__ . '/includes/carrito_helper.php';
require_once __DIR__ . '/../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: carrito.php');
    exit;
}
if (!luxi_csrf_valido()) {
    luxi_responder(false, 'Tu sesión expiró. Recarga la página.', 'carrito.php');
}

$clave = $_POST['clave'] ?? '';
if (!isset($_SESSION['carrito'][$clave])) {
    luxi_responder(false, 'Ese producto ya no está en tu carrito.', 'carrito.php');
}

$linea = $_SESSION['carrito'][$clave];
$actual = (int) $linea['cantidad'];

// Admite el botón +/- (accion) o un número escrito (cantidad)
$accion = $_POST['accion'] ?? '';
if ($accion === 'mas') {
    $nueva = $actual + 1;
} elseif ($accion === 'menos') {
    $nueva = $actual - 1;
} else {
    $nueva = (int) ($_POST['cantidad'] ?? $actual);
}

if ($nueva < 1) {
    unset($_SESSION['carrito'][$clave]);
    luxi_responder(true, 'Quitamos el producto de tu carrito.', 'carrito.php');
}

$stock = $conexion->prepare("SELECT stock FROM productos WHERE id = :id");
$stock->execute([':id' => (int) $linea['producto_id']]);
$disponible = (int) $stock->fetchColumn();
$otras_unidades = 0;
foreach ($_SESSION['carrito'] as $otra_clave => $otra_linea) {
    if ($otra_clave !== $clave
        && (int) ($otra_linea['producto_id'] ?? 0) === (int) $linea['producto_id']) {
        $otras_unidades += (int) ($otra_linea['cantidad'] ?? 0);
    }
}

if ($nueva + $otras_unidades > $disponible) {
    $_SESSION['carrito'][$clave]['cantidad'] = max(1, min($actual, $disponible));
    luxi_responder(false, "Solo hay $disponible unidad(es) disponibles.", 'carrito.php');
}

$_SESSION['carrito'][$clave]['cantidad'] = $nueva;
luxi_responder(true, 'Cantidad actualizada.', 'carrito.php');
