<?php
require_once __DIR__ . '/includes/carrito_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: carrito.php');
    exit;
}
if (!luxi_csrf_valido()) {
    luxi_responder(false, 'Tu sesión expiró. Recarga la página.', 'carrito.php');
}

$clave = $_POST['clave'] ?? '';
unset($_SESSION['carrito'][$clave]);

luxi_responder(true, 'Producto quitado del carrito.', 'carrito.php');
