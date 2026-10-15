<?php
/**
 * procesar_pedido.php
 * -------------------------------------------------------------
 * Convierte el carrito en un pedido real:
 *  1) valida sesión, token y datos de entrega,
 *  2) vuelve a leer precios y stock desde la base de datos,
 *  3) descuenta el stock y guarda el pedido en UNA sola transacción
 *     (o se guarda todo, o no se guarda nada).
 * -------------------------------------------------------------
 */
require_once __DIR__ . '/includes/carrito_helper.php';
require_once __DIR__ . '/../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: carrito.php');
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['volver_a'] = 'checkout.php';
    header('Location: login.php?error=' . urlencode('Inicia sesión para confirmar tu pedido.'));
    exit;
}

if (!luxi_csrf_valido()) {
    header('Location: checkout.php?error=' . urlencode('Tu sesión expiró. Intenta de nuevo.'));
    exit;
}

$metodo_pago = (string) ($_POST['metodo_pago'] ?? '');
if (!in_array($metodo_pago, ['nequi', 'whatsapp'], true)) {
    header('Location: checkout.php?error=' . urlencode('Selecciona Nequi o coordina el pago por WhatsApp.'));
    exit;
}

// ---------- Datos de entrega ----------
$campos = ['nombre_contacto', 'telefono', 'direccion_envio', 'ciudad_envio', 'departamento_envio', 'notas'];
$datos = [];
foreach ($campos as $campo) {
    $datos[$campo] = trim((string) ($_POST[$campo] ?? ''));
}
$_SESSION['checkout_datos'] = $datos;   // para no perder lo escrito si hay error

foreach (['nombre_contacto', 'telefono', 'direccion_envio', 'ciudad_envio', 'departamento_envio'] as $obligatorio) {
    if ($datos[$obligatorio] === '') {
        header('Location: checkout.php?error=' . urlencode('Completa todos los datos de entrega.'));
        exit;
    }
}
if (!preg_match('/^[0-9+\s\-]{7,20}$/', $datos['telefono'])) {
    header('Location: checkout.php?error=' . urlencode('El teléfono solo puede tener números (mínimo 7).'));
    exit;
}

// ---------- Carrito con precios y stock reales ----------
$c = luxi_carrito_detallado($conexion);
if (count($c['items']) === 0) {
    header('Location: carrito.php?error=' . urlencode('Tu carrito está vacío.'));
    exit;
}

// Regla del negocio: más de 5 unidades = 10 días de anticipación + anticipo
$fecha_entrega = $c['requiere_anticipo']
    ? date('Y-m-d', strtotime('+' . LUXI_DIAS_ANTICIPO . ' days'))
    : null;

try {
    $conexion->beginTransaction();

    $insertar_pedido = $conexion->prepare(
        "INSERT INTO pedidos
            (usuario_id, estado, total, anticipo_pagado, fecha_entrega_estimada,
             nombre_contacto, telefono, direccion_envio, ciudad_envio, departamento_envio, notas, metodo_pago)
         VALUES
            (:usuario_id, 'pendiente', :total, 0, :fecha_entrega,
             :nombre, :telefono, :direccion, :ciudad, :departamento, :notas, :metodo_pago)"
    );
    $insertar_pedido->execute([
        ':usuario_id'   => $_SESSION['usuario_id'],
        ':total'        => $c['total'],
        ':fecha_entrega' => $fecha_entrega,
        ':nombre'       => $datos['nombre_contacto'],
        ':telefono'     => $datos['telefono'],
        ':direccion'    => $datos['direccion_envio'],
        ':ciudad'       => $datos['ciudad_envio'],
        ':departamento' => $datos['departamento_envio'],
        ':notas'        => $datos['notas'] !== '' ? $datos['notas'] : null,
        ':metodo_pago'  => $metodo_pago,
    ]);
    $pedido_id = (int) $conexion->lastInsertId();

    $insertar_detalle = $conexion->prepare(
        "INSERT INTO pedido_detalle (pedido_id, producto_id, cantidad, color, aroma, precio_unitario)
         VALUES (:pedido_id, :producto_id, :cantidad, :color, :aroma, :precio)"
    );
    // Descuenta stock solo si todavía alcanza (evita vender de más
    // si dos personas compran la última vela al mismo tiempo)
    $descontar_stock = $conexion->prepare(
        "UPDATE productos SET stock = stock - :cantidad
         WHERE id = :id AND stock >= :cantidad"
    );

    foreach ($c['items'] as $item) {
        $descontar_stock->execute([':cantidad' => $item['cantidad'], ':id' => $item['producto_id']]);
        if ($descontar_stock->rowCount() === 0) {
            throw new RuntimeException('SIN_STOCK:' . $item['nombre']);
        }
        $insertar_detalle->execute([
            ':pedido_id'   => $pedido_id,
            ':producto_id' => $item['producto_id'],
            ':cantidad'    => $item['cantidad'],
            ':color'       => $item['color'],
            ':aroma'       => $item['aroma'],
            ':precio'      => $item['precio'],
        ]);
    }

    $conexion->commit();

    // Pedido guardado: se vacía el carrito y los datos temporales
    unset($_SESSION['carrito'], $_SESSION['checkout_datos']);
    header('Location: pedido_confirmado.php?id=' . $pedido_id);
    exit;

} catch (Throwable $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    if ($error instanceof RuntimeException && strpos($error->getMessage(), 'SIN_STOCK:') === 0) {
        $nombre = substr($error->getMessage(), strlen('SIN_STOCK:'));
        $mensaje = "Lo sentimos, alguien acaba de llevarse las últimas unidades de $nombre. Revisa tu carrito.";
        header('Location: carrito.php?error=' . urlencode($mensaje));
        exit;
    }

    // 42S22 = columna inexistente: falta correr la migración de la fase 05
    if ($error instanceof PDOException && $error->getCode() === '42S22') {
        error_log('Luxicalma: falta ejecutar sql/migracion_fase05_pedidos_velas.sql -> ' . $error->getMessage());
        header('Location: checkout.php?error=' . urlencode('La tienda aún no está lista para recibir pedidos (falta actualizar la base de datos).'));
        exit;
    }

    error_log('Luxicalma: error al guardar pedido -> ' . $error->getMessage());
    header('Location: checkout.php?error=' . urlencode('No se pudo guardar el pedido, intenta de nuevo.'));
    exit;
}
