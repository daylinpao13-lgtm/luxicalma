<?php
require_once __DIR__ . '/includes/carrito_helper.php';
require_once __DIR__ . '/../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}
if (!luxi_csrf_valido()) {
    http_response_code(403);
    exit('La sesión expiró. Recarga la página e intenta de nuevo.');
}

$accion = (string) ($_POST['accion'] ?? '');
$rol = (string) ($_SESSION['usuario_rol'] ?? '');
$usuario_id = (int) ($_SESSION['usuario_id'] ?? 0);

if ($usuario_id <= 0) {
    header('Location: login.php?error=' . urlencode('Inicia sesión para participar en las reseñas.'));
    exit;
}

if ($accion === 'crear') {
    if ($rol !== 'cliente') {
        http_response_code(403);
        exit('Solo las cuentas de cliente pueden publicar reseñas.');
    }

    $producto_id = filter_input(INPUT_POST, 'producto_id', FILTER_VALIDATE_INT);
    $calificacion = filter_input(INPUT_POST, 'calificacion', FILTER_VALIDATE_INT);
    $comentario = trim((string) ($_POST['comentario'] ?? ''));
    $destino = $producto_id ? 'detalle_producto.php?id=' . $producto_id : 'productos.php';
    if (!$producto_id || !$calificacion || $calificacion < 1 || $calificacion > 5
        || mb_strlen($comentario) < 10 || mb_strlen($comentario) > 2000) {
        if ($producto_id) {
            header('Location: ' . $destino . '&resena_error=' . urlencode('La calificación debe ser de 1 a 5 estrellas y la opinión de 10 a 2000 caracteres.'));
        } else {
            header('Location: productos.php?error=' . urlencode('La calificación debe ser de 1 a 5 estrellas y la opinión de 10 a 2000 caracteres.'));
        }
        exit;
    }

    try {
        $conexion->beginTransaction();
        $producto_stmt = $conexion->prepare('SELECT id FROM productos WHERE id = :id FOR UPDATE');
        $producto_stmt->execute([':id' => $producto_id]);
        if (!$producto_stmt->fetchColumn()) {
            $conexion->rollBack();
            http_response_code(404);
            exit('No se encontró el producto de esta reseña.');
        }

        $existente_stmt = $conexion->prepare(
            'SELECT id FROM resenas WHERE producto_id = :producto AND usuario_id = :usuario LIMIT 1'
        );
        $existente_stmt->execute([':producto' => $producto_id, ':usuario' => $usuario_id]);
        if ($existente_stmt->fetchColumn()) {
            $conexion->rollBack();
            header('Location: ' . $destino . '&resena_error=' . urlencode('Ya publicaste una reseña para este producto.'));
            exit;
        }

        $insertar = $conexion->prepare(
            'INSERT INTO resenas (producto_id, usuario_id, comentario, calificacion)
             VALUES (:producto, :usuario, :comentario, :calificacion)'
        );
        $insertar->execute([
            ':producto' => $producto_id,
            ':usuario' => $usuario_id,
            ':comentario' => $comentario,
            ':calificacion' => $calificacion,
        ]);
        $conexion->commit();
    } catch (PDOException $error) {
        if ($conexion->inTransaction()) $conexion->rollBack();
        if ($error->getCode() === '23000') {
            header('Location: ' . $destino . '&resena_error=' . urlencode('Ya publicaste una reseña para este producto.') . '#resenas');
            exit;
        }
        error_log('Luxicalma: no se pudo guardar una reseña -> ' . $error->getMessage());
        header('Location: ' . $destino . '&resena_error=' . urlencode('No se pudo guardar la reseña. Intenta de nuevo.'));
        exit;
    }

    header('Location: ' . $destino . '&resena=ok#resenas');
    exit;
}

if ($accion === 'responder') {
    if ($rol !== 'administrador') {
        http_response_code(403);
        exit('Solo la cuenta administradora puede publicar respuestas oficiales.');
    }

    $resena_id = filter_input(INPUT_POST, 'resena_id', FILTER_VALIDATE_INT);
    $respuesta = trim((string) ($_POST['respuesta'] ?? ''));
    if (!$resena_id || mb_strlen($respuesta) < 2 || mb_strlen($respuesta) > 2000) {
        http_response_code(400);
        exit('La respuesta debe tener entre 2 y 2000 caracteres.');
    }

    $producto_stmt = $conexion->prepare('SELECT producto_id FROM resenas WHERE id = :id');
    $producto_stmt->execute([':id' => $resena_id]);
    $producto_id = (int) $producto_stmt->fetchColumn();
    if ($producto_id <= 0) {
        http_response_code(404);
        exit('No se encontró la reseña que intentas responder.');
    }

    try {
        $guardar = $conexion->prepare(
            'INSERT INTO resena_respuestas (resena_id, administrador_id, respuesta)
             VALUES (:resena, :administrador, :respuesta)
             ON DUPLICATE KEY UPDATE administrador_id = VALUES(administrador_id),
                                     respuesta = VALUES(respuesta),
                                     fecha = CURRENT_TIMESTAMP'
        );
        $guardar->execute([
            ':resena' => $resena_id,
            ':administrador' => $usuario_id,
            ':respuesta' => $respuesta,
        ]);
    } catch (PDOException $error) {
        error_log('Luxicalma: no se pudo guardar una respuesta oficial -> ' . $error->getMessage());
        header('Location: detalle_producto.php?id=' . $producto_id . '&resena_error=' . urlencode('No se pudo guardar la respuesta. Intenta de nuevo.') . '#resenas');
        exit;
    }

    header('Location: detalle_producto.php?id=' . $producto_id . '&resena=respuesta_ok#resenas');
    exit;
}

http_response_code(400);
exit('Acción de reseñas no válida.');
