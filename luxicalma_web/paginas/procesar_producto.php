<?php
require_once __DIR__ . '/includes/carrito_helper.php';
require_once __DIR__ . '/../config/conexion.php';

if (($_SESSION['usuario_rol'] ?? '') !== 'administrador') {
    http_response_code(403);
    exit('No tienes permiso para administrar productos.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panel_productos.php');
    exit;
}
if (!luxi_csrf_valido()) {
    header('Location: panel_productos.php?error=' . urlencode('La sesión expiró. Recarga la página e intenta de nuevo.'));
    exit;
}

$producto_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$nombre = trim((string) ($_POST['nombre'] ?? ''));
$descripcion = trim((string) ($_POST['descripcion'] ?? ''));
$categoria_ids = array_values(array_unique(array_filter(
    array_map('intval', is_array($_POST['categoria_ids'] ?? null) ? $_POST['categoria_ids'] : []),
    static function (int $id): bool { return $id > 0; }
)));
$categoria_id = $categoria_ids[0] ?? 0;
$precio_detal = filter_input(INPUT_POST, 'precio_detal', FILTER_VALIDATE_FLOAT);
$precio_mayorista = filter_input(INPUT_POST, 'precio_mayorista', FILTER_VALIDATE_FLOAT);
$precio_anterior_texto = trim((string) ($_POST['precio_anterior'] ?? ''));
$precio_anterior = $precio_anterior_texto === ''
    ? null
    : filter_var($precio_anterior_texto, FILTER_VALIDATE_FLOAT);
$stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
$colores = trim((string) ($_POST['colores_disponibles'] ?? ''));
$aromas = trim((string) ($_POST['aroma'] ?? ''));
$return_to = $producto_id > 0 ? 'panel_productos.php?editar=' . $producto_id : 'panel_productos.php?nuevo=1';

if ($nombre === '' || mb_strlen($nombre) > 150 || $categoria_id <= 0
    || $precio_detal === false || $precio_detal <= 0
    || $precio_mayorista === false || $precio_mayorista <= 0
    || $stock === false || $stock < 0
    || ($precio_anterior_texto !== '' && ($precio_anterior === false || $precio_anterior <= $precio_detal))) {
    header('Location: ' . $return_to . '&error=' . urlencode('Revisa nombre, categoría, precios y stock. El precio anterior debe ser mayor al precio actual.'));
    exit;
}

if (mb_strlen($colores) > 255 || mb_strlen($aromas) > 100) {
    header('Location: ' . $return_to . '&error=' . urlencode('La lista de colores o aromas supera el máximo permitido.'));
    exit;
}

$marcas_categoria = implode(',', array_fill(0, count($categoria_ids), '?'));
$categoria_stmt = $conexion->prepare(
    "SELECT id, grupo FROM categorias WHERE activa = 1 AND id IN ($marcas_categoria) ORDER BY orden, id"
);
$categoria_stmt->execute($categoria_ids);
$categorias_activas = $categoria_stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($categorias_activas) !== count($categoria_ids)) {
    header('Location: ' . $return_to . '&error=' . urlencode('Selecciona una categoría activa.'));
    exit;
}
$categoria_id = (int) $categorias_activas[0]['id'];
$grupo = $categorias_activas[0]['grupo'];

$foto = null;
if ($producto_id > 0) {
    $foto_stmt = $conexion->prepare('SELECT foto FROM productos WHERE id = :id');
    $foto_stmt->execute([':id' => $producto_id]);
    $foto_actual = $foto_stmt->fetchColumn();
    if ($foto_actual === false) {
        header('Location: panel_productos.php?error=' . urlencode('El producto que intentas editar ya no existe.'));
        exit;
    }
    $foto = (string) $foto_actual;
}

$archivo = $_FILES['foto'] ?? null;
$archivo_mascara = $_FILES['mascara_color'] ?? null;
$eliminar_mascara = ($_POST['eliminar_mascara_color'] ?? '') === '1';
$subir_mascara = $archivo_mascara && $archivo_mascara['error'] !== UPLOAD_ERR_NO_FILE;
if (($subir_mascara || $eliminar_mascara) && $producto_id <= 0) {
    header('Location: ' . $return_to . '&error=' . urlencode('Guarda el producto antes de añadir o quitar su máscara de color.'));
    exit;
}

if ($subir_mascara) {
    if ($archivo_mascara['error'] !== UPLOAD_ERR_OK || $archivo_mascara['size'] > 5 * 1024 * 1024) {
        header('Location: ' . $return_to . '&error=' . urlencode('La máscara no se pudo subir o supera el límite de 5 MB.'));
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $tipo_mascara = $finfo->file($archivo_mascara['tmp_name']);
    $datos_mascara = @getimagesize($archivo_mascara['tmp_name']);
    if ($tipo_mascara !== 'image/png' || $datos_mascara === false || $datos_mascara[2] !== IMAGETYPE_PNG) {
        header('Location: ' . $return_to . '&error=' . urlencode('La máscara debe ser una imagen PNG válida con el objeto recortado y el fondo transparente.'));
        exit;
    }
}

if ($archivo && $archivo['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($archivo['error'] !== UPLOAD_ERR_OK || $archivo['size'] > 5 * 1024 * 1024) {
        header('Location: ' . $return_to . '&error=' . urlencode('La imagen no se pudo subir o supera el límite de 5 MB.'));
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $tipo = $finfo->file($archivo['tmp_name']);
    $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $datos_imagen = @getimagesize($archivo['tmp_name']);
    if (!isset($extensiones[$tipo]) || $datos_imagen === false) {
        header('Location: ' . $return_to . '&error=' . urlencode('Usa una imagen JPG, PNG o WEBP válida.'));
        exit;
    }

    $nombre_archivo = bin2hex(random_bytes(16)) . '.' . $extensiones[$tipo];
    $directorio = __DIR__ . '/../assets/img/productos';
    if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
        error_log('Luxicalma: no se pudo crear assets/img/productos para imágenes de productos.');
        header('Location: ' . $return_to . '&error=' . urlencode('No se pudo preparar la carpeta de imágenes.'));
        exit;
    }
    if (!move_uploaded_file($archivo['tmp_name'], $directorio . '/' . $nombre_archivo)) {
        error_log('Luxicalma: falló el almacenamiento de una imagen de producto.');
        header('Location: ' . $return_to . '&error=' . urlencode('No se pudo guardar la imagen del producto.'));
        exit;
    }
    $foto = 'productos/' . $nombre_archivo;
}

$valores = [
    ':nombre' => $nombre,
    ':descripcion' => $descripcion !== '' ? $descripcion : null,
    ':categoria' => $grupo,
    ':categoria_id' => $categoria_id,
    ':precio_detal' => $precio_detal,
    ':precio_mayorista' => $precio_mayorista,
    ':precio_anterior' => $precio_anterior,
    ':colores_disponibles' => $colores !== '' ? $colores : null,
    ':aroma' => $aromas !== '' ? $aromas : null,
    ':foto' => $foto !== '' ? $foto : null,
    ':stock' => $stock,
];

try {
    $conexion->beginTransaction();
    if ($producto_id > 0) {
        $valores[':id'] = $producto_id;
        $sql = 'UPDATE productos
                SET nombre = :nombre, descripcion = :descripcion, categoria = :categoria,
                    categoria_id = :categoria_id, precio_detal = :precio_detal,
                    precio_mayorista = :precio_mayorista, precio_anterior = :precio_anterior,
                    colores_disponibles = :colores_disponibles, aroma = :aroma,
                    foto = :foto, stock = :stock
                WHERE id = :id';
        $conexion->prepare($sql)->execute($valores);
    } else {
        $sql = 'INSERT INTO productos
                    (nombre, descripcion, categoria, categoria_id, precio_detal, precio_mayorista,
                     precio_anterior, colores_disponibles, aroma, foto, stock)
                VALUES
                    (:nombre, :descripcion, :categoria, :categoria_id, :precio_detal, :precio_mayorista,
                     :precio_anterior, :colores_disponibles, :aroma, :foto, :stock)';
        $conexion->prepare($sql)->execute($valores);
        $producto_id = (int) $conexion->lastInsertId();
    }
    $conexion->prepare('DELETE FROM producto_categorias WHERE producto_id = :id')->execute([':id' => $producto_id]);
    $asignar_categoria = $conexion->prepare(
        'INSERT INTO producto_categorias (producto_id, categoria_id) VALUES (:producto, :categoria)'
    );
    foreach ($categorias_activas as $categoria_activa) {
        $asignar_categoria->execute([
            ':producto' => $producto_id,
            ':categoria' => (int) $categoria_activa['id'],
        ]);
    }
    $conexion->commit();
} catch (PDOException $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log('Luxicalma: no se pudo guardar el producto -> ' . $error->getMessage());
    header('Location: ' . $return_to . '&error=' . urlencode('No se pudo guardar el producto. Verifica la base de datos e intenta de nuevo.'));
    exit;
}

$ruta_archivo_mascara = __DIR__ . '/../assets/img/productos/producto-' . $producto_id . '.mask.png';
if ($subir_mascara) {
    if (!move_uploaded_file($archivo_mascara['tmp_name'], $ruta_archivo_mascara)) {
        error_log('Luxicalma: falló el almacenamiento de una máscara de color.');
        header('Location: panel_productos.php?editar=' . $producto_id . '&error=' . urlencode('El producto se guardó, pero no se pudo guardar su máscara de color.'));
        exit;
    }
} elseif ($eliminar_mascara && is_file($ruta_archivo_mascara) && !unlink($ruta_archivo_mascara)) {
    error_log('Luxicalma: no se pudo eliminar una máscara de color.');
    header('Location: panel_productos.php?editar=' . $producto_id . '&error=' . urlencode('El producto se guardó, pero no se pudo quitar su máscara de color.'));
    exit;
}

header('Location: panel_productos.php?ok=' . urlencode('Producto guardado correctamente.'));
exit;
