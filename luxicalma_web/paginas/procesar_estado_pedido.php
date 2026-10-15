<?php
require_once __DIR__ . '/includes/carrito_helper.php';
require_once __DIR__ . '/../config/conexion.php';

if (($_SESSION['usuario_rol'] ?? '') !== 'administrador') {
    http_response_code(403);
    exit('Solo la administración puede actualizar el seguimiento de pedidos.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !luxi_csrf_valido()) {
    http_response_code($_SERVER['REQUEST_METHOD'] === 'POST' ? 403 : 405);
    exit('La solicitud no es válida. Recarga la página e intenta de nuevo.');
}

$pedido_id = filter_input(INPUT_POST, 'pedido_id', FILTER_VALIDATE_INT);
$estado = (string) ($_POST['estado'] ?? '');
$estados_permitidos = [
    'pendiente',
    'pago_en_revision',
    'anticipo_pagado',
    'pagado',
    'en_empaque',
    'enviado',
    'completado',
    'cancelado',
];

if (!$pedido_id || !in_array($estado, $estados_permitidos, true)) {
    header('Location: panel_productos.php?error=' . urlencode('El pedido o el estado seleccionado no es válido.'));
    exit;
}

$actualizar = $conexion->prepare('UPDATE pedidos SET estado = :estado WHERE id = :id');
$actualizar->execute([':estado' => $estado, ':id' => $pedido_id]);
if ($actualizar->rowCount() === 0) {
    $existe = $conexion->prepare('SELECT 1 FROM pedidos WHERE id = :id');
    $existe->execute([':id' => $pedido_id]);
    if (!$existe->fetchColumn()) {
        header('Location: panel_productos.php?error=' . urlencode('No se encontró ese pedido.'));
        exit;
    }
}

header('Location: panel_productos.php?pedido_ok=' . urlencode('Se actualizó el estado del pedido #' . $pedido_id . '.') . '#seguimiento-pedidos');
exit;
