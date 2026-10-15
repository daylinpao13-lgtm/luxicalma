<?php
require_once __DIR__ . '/includes/carrito_helper.php';
require_once __DIR__ . '/../config/conexion.php';

if (($_SESSION['usuario_rol'] ?? '') !== 'administrador') {
    http_response_code(403);
    exit('Solo la administración puede gestionar las temporadas.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}
if (!luxi_csrf_valido()) {
    header('Location: panel_productos.php?error=' . urlencode('La sesión expiró. Recarga la página e intenta de nuevo.') . '#temporadas');
    exit;
}

$coleccion_id = filter_input(INPUT_POST, 'coleccion_id', FILTER_VALIDATE_INT) ?: 0;
$nombre = trim((string) ($_POST['nombre'] ?? ''));
$descripcion = trim((string) ($_POST['descripcion'] ?? ''));
$fecha_inicio = trim((string) ($_POST['fecha_inicio'] ?? ''));
$fecha_fin = trim((string) ($_POST['fecha_fin'] ?? ''));
$activa = isset($_POST['activa']) ? 1 : 0;
$producto_ids = array_values(array_unique(array_filter(
    array_map('intval', is_array($_POST['producto_ids'] ?? null) ? $_POST['producto_ids'] : []),
    static function (int $id): bool { return $id > 0; }
)));

$fecha_valida = static function (string $fecha): bool {
    $valor = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    return $valor !== false && $valor->format('Y-m-d') === $fecha;
};

if ($nombre === '' || mb_strlen($nombre) > 150 || mb_strlen($descripcion) > 2000
    || ($fecha_inicio !== '' && !$fecha_valida($fecha_inicio))
    || ($fecha_fin !== '' && !$fecha_valida($fecha_fin))
    || ($fecha_inicio !== '' && $fecha_fin !== '' && $fecha_inicio > $fecha_fin)) {
    header('Location: panel_productos.php?' . ($coleccion_id ? 'editar_coleccion=' . $coleccion_id . '&' : '')
        . 'error=' . urlencode('Revisa el nombre, la descripción y las fechas de la temporada.') . '#temporadas');
    exit;
}

if ($producto_ids) {
    $marcas = implode(',', array_fill(0, count($producto_ids), '?'));
    $productos_stmt = $conexion->prepare("SELECT id FROM productos WHERE id IN ($marcas)");
    $productos_stmt->execute($producto_ids);
    if (count($productos_stmt->fetchAll(PDO::FETCH_COLUMN)) !== count($producto_ids)) {
        header('Location: panel_productos.php?' . ($coleccion_id ? 'editar_coleccion=' . $coleccion_id . '&' : '')
            . 'error=' . urlencode('La selección incluye un producto que ya no existe.') . '#temporadas');
        exit;
    }
}

try {
    $conexion->beginTransaction();

    if ($coleccion_id > 0) {
        $actualizar = $conexion->prepare(
            'UPDATE colecciones
             SET nombre = :nombre, descripcion = :descripcion,
                 fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin, activa = :activa
             WHERE id = :id'
        );
        $actualizar->execute([
            ':nombre' => $nombre,
            ':descripcion' => $descripcion !== '' ? $descripcion : null,
            ':fecha_inicio' => $fecha_inicio !== '' ? $fecha_inicio : null,
            ':fecha_fin' => $fecha_fin !== '' ? $fecha_fin : null,
            ':activa' => $activa,
            ':id' => $coleccion_id,
        ]);
        if ($actualizar->rowCount() === 0) {
            $existe = $conexion->prepare('SELECT 1 FROM colecciones WHERE id = :id');
            $existe->execute([':id' => $coleccion_id]);
            if (!$existe->fetchColumn()) {
                throw new RuntimeException('No se encontró la temporada que intentas editar.');
            }
        }
    } else {
        $crear = $conexion->prepare(
            'INSERT INTO colecciones (nombre, descripcion, fecha_inicio, fecha_fin, activa)
             VALUES (:nombre, :descripcion, :fecha_inicio, :fecha_fin, :activa)'
        );
        $crear->execute([
            ':nombre' => $nombre,
            ':descripcion' => $descripcion !== '' ? $descripcion : null,
            ':fecha_inicio' => $fecha_inicio !== '' ? $fecha_inicio : null,
            ':fecha_fin' => $fecha_fin !== '' ? $fecha_fin : null,
            ':activa' => $activa,
        ]);
        $coleccion_id = (int) $conexion->lastInsertId();
    }

    $conexion->prepare('DELETE FROM coleccion_producto WHERE coleccion_id = :id')
        ->execute([':id' => $coleccion_id]);
    $asignar = $conexion->prepare(
        'INSERT INTO coleccion_producto (coleccion_id, producto_id) VALUES (:coleccion, :producto)'
    );
    foreach ($producto_ids as $producto_id) {
        $asignar->execute([':coleccion' => $coleccion_id, ':producto' => $producto_id]);
    }

    $conexion->commit();
} catch (Throwable $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log('Luxicalma: no se pudo guardar la temporada -> ' . $error->getMessage());
    $parametros = $coleccion_id > 0 ? 'editar_coleccion=' . $coleccion_id . '&' : '';
    header('Location: panel_productos.php?' . $parametros
        . 'error=' . urlencode($error instanceof RuntimeException ? $error->getMessage() : 'No se pudo guardar la temporada.')
        . '#temporadas');
    exit;
}

header('Location: panel_productos.php?temporada_ok=' . urlencode('Temporada guardada correctamente.') . '#temporadas');
exit;
