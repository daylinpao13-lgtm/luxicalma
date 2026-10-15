<?php
require_once __DIR__ . '/includes/carrito_helper.php';
require_once __DIR__ . '/../config/conexion.php';

if (($_SESSION['usuario_rol'] ?? '') !== 'administrador') {
    header('Location: login.php?error=' . urlencode('Esta sección es solo para la administración.'));
    exit;
}

$categorias = $conexion->query(
    'SELECT id, nombre, grupo FROM categorias WHERE activa = 1 ORDER BY orden, nombre'
)->fetchAll(PDO::FETCH_ASSOC);
$editar_id = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT) ?: 0;
$producto = null;
if ($editar_id > 0) {
    $stmt = $conexion->prepare('SELECT * FROM productos WHERE id = :id');
    $stmt->execute([':id' => $editar_id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$producto) {
        header('Location: panel_productos.php?error=' . urlencode('No se encontró ese producto.'));
        exit;
    }
    $categorias_producto_stmt = $conexion->prepare(
        'SELECT categoria_id FROM producto_categorias WHERE producto_id = :id'
    );
    $categorias_producto_stmt->execute([':id' => $editar_id]);
    $categorias_producto_ids = array_map('intval', $categorias_producto_stmt->fetchAll(PDO::FETCH_COLUMN));
    if (!$categorias_producto_ids && !empty($producto['categoria_id'])) {
        $categorias_producto_ids[] = (int) $producto['categoria_id'];
    }
} else {
    $categorias_producto_ids = [];
}

$editar_coleccion_id = filter_input(INPUT_GET, 'editar_coleccion', FILTER_VALIDATE_INT) ?: 0;
$coleccion_editar = null;
if ($editar_coleccion_id > 0) {
    $coleccion_stmt = $conexion->prepare('SELECT * FROM colecciones WHERE id = :id');
    $coleccion_stmt->execute([':id' => $editar_coleccion_id]);
    $coleccion_editar = $coleccion_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$coleccion_editar) {
        header('Location: panel_productos.php?error=' . urlencode('No se encontró esa temporada.'));
        exit;
    }
    $coleccion_productos_stmt = $conexion->prepare(
        'SELECT producto_id FROM coleccion_producto WHERE coleccion_id = :id'
    );
    $coleccion_productos_stmt->execute([':id' => $editar_coleccion_id]);
    $coleccion_producto_ids = array_map('intval', $coleccion_productos_stmt->fetchAll(PDO::FETCH_COLUMN));
} else {
    $coleccion_producto_ids = [];
}

$productos = $conexion->query(
    'SELECT p.id, p.nombre, p.precio_detal, p.precio_mayorista, p.precio_anterior, p.stock, p.foto,
            GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.orden, c.nombre SEPARATOR ", ") AS categorias_nombre
     FROM productos p
     LEFT JOIN producto_categorias pc ON pc.producto_id = p.id
     LEFT JOIN categorias c ON c.id = pc.categoria_id
     GROUP BY p.id
     ORDER BY p.nombre'
)->fetchAll(PDO::FETCH_ASSOC);
$colecciones_panel = $conexion->query(
    'SELECT c.id, c.nombre, c.descripcion, c.fecha_inicio, c.fecha_fin, c.activa,
            COUNT(cp.producto_id) AS total_productos
     FROM colecciones c
     LEFT JOIN coleccion_producto cp ON cp.coleccion_id = c.id
     GROUP BY c.id
     ORDER BY c.fecha_inicio DESC, c.nombre'
)->fetchAll(PDO::FETCH_ASSOC);
$resenas_panel = $conexion->query(
    'SELECT r.id, r.producto_id, r.comentario, r.calificacion, r.fecha,
            u.nombres AS cliente_nombre, p.nombre AS producto_nombre,
            rr.respuesta
     FROM resenas r
     JOIN usuarios u ON u.id = r.usuario_id
     JOIN productos p ON p.id = r.producto_id
     LEFT JOIN resena_respuestas rr ON rr.resena_id = r.id
     ORDER BY r.fecha DESC'
)->fetchAll(PDO::FETCH_ASSOC);
$pedidos_panel = $conexion->query(
    'SELECT id, usuario_id, fecha, estado, total, metodo_pago, nombre_contacto, telefono, ciudad_envio
     FROM pedidos
     ORDER BY fecha DESC
     LIMIT 50'
)->fetchAll(PDO::FETCH_ASSOC);
$estados_pedido = [
    'pendiente' => 'Pedido confirmado',
    'pago_en_revision' => 'Pago en revisión',
    'anticipo_pagado' => 'Anticipo pagado',
    'pagado' => 'Pagado',
    'en_empaque' => 'En proceso de empaque',
    'enviado' => 'En camino',
    'completado' => 'Entregado',
    'cancelado' => 'Cancelado',
];
$mascara_producto = $producto
    ? __DIR__ . '/../assets/img/productos/producto-' . (int) $producto['id'] . '.mask.png'
    : null;

$titulo_pagina = 'Administrar productos';
$css_pagina = 'admin_productos.css';
require 'includes/header.php';
?>

<section class="contenedor admin-productos">
    <div class="admin-productos-cabecera">
        <div>
            <p class="categoria-producto">Administración</p>
            <h1>Productos, precios y ofertas</h1>
            <p>Los cambios se reflejan en el catálogo de la tienda.</p>
        </div>
        <nav class="admin-accesos" aria-label="Secciones de administración">
            <a class="boton-claro" href="#temporadas">Temporadas y productos temáticos</a>
        </nav>
        <a class="boton-claro" href="panel_productos.php?nuevo=1">Añadir producto</a>
    </div>

    <?php if (isset($_GET['temporada_ok'])): ?><p class="mensaje-exito"><?php echo luxi_h($_GET['temporada_ok']); ?></p><?php endif; ?>
    <?php if (isset($_GET['ok'])): ?><p class="mensaje-exito"><?php echo luxi_h($_GET['ok']); ?></p><?php endif; ?>
    <?php if (isset($_GET['error'])): ?><p class="mensaje-error"><?php echo luxi_h($_GET['error']); ?></p><?php endif; ?>
    <?php if (count($categorias) === 0): ?>
        <p class="mensaje-error">No hay categorías activas. Activa una categoría antes de añadir productos.</p>
    <?php endif; ?>

    <?php if (isset($_GET['nuevo']) || $producto): ?>
        <form class="admin-form-producto" action="procesar_producto.php" method="POST" enctype="multipart/form-data">
            <?php echo luxi_csrf_campo(); ?>
            <input type="hidden" name="id" value="<?php echo (int) ($producto['id'] ?? 0); ?>">
            <h2><?php echo $producto ? 'Editar producto' : 'Nuevo producto'; ?></h2>

            <div class="admin-form-grid">
                <label>Nombre
                    <input name="nombre" maxlength="150" required value="<?php echo luxi_h($producto['nombre'] ?? ''); ?>">
                </label>
                <fieldset class="admin-categorias-producto admin-campo-ancho">
                    <legend>Categorías <small>Selecciona una o varias categorías para este producto.</small></legend>
                    <div class="admin-categorias-opciones">
                        <?php foreach ($categorias as $categoria): ?>
                            <label>
                                <input type="checkbox" name="categoria_ids[]" value="<?php echo (int) $categoria['id']; ?>"
                                    <?php echo in_array((int) $categoria['id'], $categorias_producto_ids, true) ? 'checked' : ''; ?>>
                                <?php echo luxi_h($categoria['nombre']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <label>Precio actual / de venta
                    <input type="number" name="precio_detal" min="1" step="1" required value="<?php echo luxi_h($producto['precio_detal'] ?? ''); ?>">
                </label>
                <label>Precio mayorista
                    <input type="number" name="precio_mayorista" min="1" step="1" required value="<?php echo luxi_h($producto['precio_mayorista'] ?? ''); ?>">
                </label>
                <label>Precio anterior (opcional, para mostrar oferta)
                    <input type="number" name="precio_anterior" min="1" step="1" value="<?php echo luxi_h($producto['precio_anterior'] ?? ''); ?>">
                    <small>Déjalo vacío para quitar la oferta. Debe superar el precio actual.</small>
                </label>
                <label>Unidades disponibles
                    <input type="number" name="stock" min="0" step="1" required value="<?php echo luxi_h($producto['stock'] ?? '0'); ?>">
                </label>
                <label>Colores disponibles
                    <input name="colores_disponibles" maxlength="255" value="<?php echo luxi_h($producto['colores_disponibles'] ?? ''); ?>" placeholder="Rosado, Amarillo, Azul, Morado">
                    <small>Separa las opciones con comas. Si queda vacío, se usan los colores por defecto.</small>
                </label>
                <label>Aromas disponibles
                    <input name="aroma" maxlength="100" value="<?php echo luxi_h($producto['aroma'] ?? ''); ?>" placeholder="Mango, Canela, Lavanda">
                    <small>Separa las opciones con comas. Si queda vacío, se usan los aromas por defecto.</small>
                </label>
                <label class="admin-campo-ancho">Descripción
                    <textarea name="descripcion" rows="4"><?php echo luxi_h($producto['descripcion'] ?? ''); ?></textarea>
                </label>
                <label class="admin-campo-ancho">Imagen del producto (JPG, PNG o WEBP; máximo 5 MB)
                    <input type="file" name="foto" accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($producto['foto'])): ?>
                        <small>Imagen actual: <?php echo luxi_h($producto['foto']); ?>. Déjalo sin seleccionar para conservarla.</small>
                    <?php endif; ?>
                </label>
                <?php if ($producto): ?>
                    <label class="admin-campo-ancho">Máscara de color del objeto (PNG con transparencia; máximo 5 MB)
                        <input type="file" name="mascara_color" accept="image/png">
                        <small>Sube un PNG con el objeto que se debe teñir visible y el resto transparente. Usa las mismas proporciones que la imagen del producto. Si cambias la foto, actualiza también esta máscara.</small>
                        <?php if (is_file($mascara_producto)): ?>
                            <small>Ya hay una máscara para este producto.</small>
                            <span><input type="checkbox" name="eliminar_mascara_color" value="1"> Quitar la máscara actual</span>
                        <?php endif; ?>
                    </label>
                <?php else: ?>
                    <p class="admin-campo-ancho">Para añadir una máscara de color, guarda primero el producto y luego edítalo.</p>
                <?php endif; ?>
            </div>

            <div class="admin-form-acciones">
                <button type="submit" class="boton-negro">Guardar producto</button>
                <a href="panel_productos.php" class="boton-claro">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>

    <div class="admin-tabla-productos">
        <h2>Catálogo actual <span><?php echo count($productos); ?> productos</span></h2>
        <div class="tabla-contenedor">
            <table class="tabla-panel tabla-catalogo-admin">
                <thead><tr><th>Producto</th><th>Categoría</th><th>Precio actual</th><th>Mayorista</th><th>Stock</th><th>Oferta</th><th></th></tr></thead>
                <tbody>
                    <?php if (!$productos): ?><tr><td colspan="7" class="tabla-vacia">Todavía no hay productos.</td></tr><?php endif; ?>
                    <?php foreach ($productos as $fila): ?>
                        <tr>
                            <td class="admin-producto-nombre">
                                <?php if (!empty($fila['foto'])): ?><img src="<?php echo luxi_foto($fila['foto'], $fila['nombre']); ?>" alt=""><?php endif; ?>
                                <span><?php echo luxi_h($fila['nombre']); ?></span>
                            </td>
                            <td><?php echo luxi_h($fila['categorias_nombre'] ?? 'Sin categoría'); ?></td>
                            <td><?php echo luxi_formato_precio($fila['precio_detal']); ?></td>
                            <td><?php echo luxi_formato_precio($fila['precio_mayorista']); ?></td>
                            <td><?php echo (int) $fila['stock']; ?></td>
                            <td><?php echo !empty($fila['precio_anterior']) ? luxi_formato_precio($fila['precio_anterior']) : '—'; ?></td>
                            <td><a class="boton-claro boton-chico boton-editar-producto" href="panel_productos.php?editar=<?php echo (int) $fila['id']; ?>">Editar</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <section class="admin-tabla-productos admin-temporadas" id="temporadas">
        <div class="admin-temporadas-cabecera">
            <div>
                <h2>Temporadas y productos temáticos</h2>
                <p>Elige los productos y las fechas en que aparecerán en «Temáticos».</p>
            </div>
        </div>
        <form class="admin-temporada-form" action="procesar_coleccion.php" method="POST">
            <?php echo luxi_csrf_campo(); ?>
            <input type="hidden" name="coleccion_id" value="<?php echo (int) ($coleccion_editar['id'] ?? 0); ?>">
            <div class="admin-temporada-campos">
                <label>Nombre de la temporada
                    <input name="nombre" required maxlength="150" value="<?php echo luxi_h($coleccion_editar['nombre'] ?? ''); ?>" placeholder="Ej. Navidad, Día de la Madre">
                </label>
                <label>Fecha de inicio <span>(opcional)</span>
                    <input type="date" name="fecha_inicio" value="<?php echo luxi_h($coleccion_editar['fecha_inicio'] ?? ''); ?>">
                </label>
                <label>Fecha de fin <span>(opcional)</span>
                    <input type="date" name="fecha_fin" value="<?php echo luxi_h($coleccion_editar['fecha_fin'] ?? ''); ?>">
                </label>
                <label class="admin-temporada-descripcion">Descripción <span>(opcional)</span>
                    <textarea name="descripcion" rows="2" maxlength="2000" placeholder="Mensaje de temporada para tus clientes"><?php echo luxi_h($coleccion_editar['descripcion'] ?? ''); ?></textarea>
                </label>
            </div>
            <fieldset class="admin-temporada-productos">
                <legend>Productos de la temporada <small>Selecciona todos los que quieras mostrar.</small></legend>
                <div class="admin-temporada-lista">
                    <?php foreach ($productos as $producto_temporada): ?>
                        <label>
                            <input type="checkbox" name="producto_ids[]" value="<?php echo (int) $producto_temporada['id']; ?>"
                                <?php echo in_array((int) $producto_temporada['id'], $coleccion_producto_ids, true) ? 'checked' : ''; ?>>
                            <span><?php echo luxi_h($producto_temporada['nombre']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
            <label class="admin-temporada-activa">
                <input type="checkbox" name="activa" value="1" <?php echo !$coleccion_editar || !empty($coleccion_editar['activa']) ? 'checked' : ''; ?>>
                Temporada activa
            </label>
            <div class="admin-form-acciones">
                <button type="submit" class="boton-negro"><?php echo $coleccion_editar ? 'Guardar temporada' : 'Crear temporada'; ?></button>
                <?php if ($coleccion_editar): ?><a class="boton-claro" href="panel_productos.php#temporadas">Cancelar edición</a><?php endif; ?>
            </div>
        </form>

        <div class="tabla-contenedor">
            <table class="tabla-panel">
                <thead><tr><th>Temporada</th><th>Vigencia</th><th>Productos</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    <?php if (!$colecciones_panel): ?><tr><td colspan="5" class="tabla-vacia">Aún no hay temporadas. Crea una para seleccionar sus productos.</td></tr><?php endif; ?>
                    <?php foreach ($colecciones_panel as $coleccion): ?>
                        <?php
                        $vigencia = $coleccion['fecha_inicio'] ? date('d/m/Y', strtotime($coleccion['fecha_inicio'])) : 'Sin inicio';
                        $vigencia .= ' – ' . ($coleccion['fecha_fin'] ? date('d/m/Y', strtotime($coleccion['fecha_fin'])) : 'Sin vencimiento');
                        $en_vigencia = (!$coleccion['fecha_inicio'] || $coleccion['fecha_inicio'] <= date('Y-m-d'))
                            && (!$coleccion['fecha_fin'] || $coleccion['fecha_fin'] >= date('Y-m-d'));
                        ?>
                        <tr>
                            <td><strong><?php echo luxi_h($coleccion['nombre']); ?></strong>
                                <?php if (!empty($coleccion['descripcion'])): ?><br><small><?php echo luxi_h($coleccion['descripcion']); ?></small><?php endif; ?>
                            </td>
                            <td><?php echo luxi_h($vigencia); ?></td>
                            <td><?php echo (int) $coleccion['total_productos']; ?></td>
                            <td><?php echo !empty($coleccion['activa']) && $en_vigencia ? 'Activa' : (!empty($coleccion['activa']) ? 'Fuera de fecha' : 'Pausada'); ?></td>
                            <td><a class="boton-claro boton-chico" href="panel_productos.php?editar_coleccion=<?php echo (int) $coleccion['id']; ?>#temporadas">Editar</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-resenas">
        <h2>Reseñas y respuestas oficiales <span><?php echo count($resenas_panel); ?> reseñas</span></h2>
        <?php if (!$resenas_panel): ?>
            <p class="tabla-vacia">Todavía no hay reseñas de clientes.</p>
        <?php else: ?>
            <?php foreach ($resenas_panel as $resena): ?>
                <article class="admin-resena">
                    <div class="admin-resena-cabecera">
                        <a href="detalle_producto.php?id=<?php echo (int) $resena['producto_id']; ?>">
                            <?php echo luxi_h($resena['producto_nombre']); ?>
                        </a>
                        <span><?php echo luxi_h($resena['cliente_nombre']); ?> ·
                            <?php echo date('d/m/Y', strtotime($resena['fecha'])); ?> ·
                            <?php echo (int) $resena['calificacion']; ?>/5 ★</span>
                    </div>
                    <blockquote><?php echo nl2br(luxi_h($resena['comentario'])); ?></blockquote>
                    <form class="formulario-respuesta" action="procesar_resena.php" method="POST">
                        <?php echo luxi_csrf_campo(); ?>
                        <input type="hidden" name="accion" value="responder">
                        <input type="hidden" name="resena_id" value="<?php echo (int) $resena['id']; ?>">
                        <label for="respuesta-panel-<?php echo (int) $resena['id']; ?>">Respuesta oficial de Luxicalma</label>
                        <textarea id="respuesta-panel-<?php echo (int) $resena['id']; ?>" name="respuesta"
                                  rows="3" minlength="2" maxlength="2000" required><?php echo luxi_h($resena['respuesta'] ?? ''); ?></textarea>
                        <button type="submit" class="boton-negro">Guardar respuesta oficial</button>
                    </form>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section class="admin-tabla-productos admin-seguimiento-pedidos" id="seguimiento-pedidos">
        <h2>Seguimiento de pedidos <span><?php echo count($pedidos_panel); ?> recientes</span></h2>
        <?php if (isset($_GET['pedido_ok'])): ?><p class="mensaje-exito"><?php echo luxi_h($_GET['pedido_ok']); ?></p><?php endif; ?>
        <div class="tabla-contenedor">
            <table class="tabla-panel">
                <thead><tr><th>Pedido</th><th>Cliente</th><th>Fecha</th><th>Teléfono</th><th>Total</th><th>Pago</th><th>Estado</th><th>Actualizar</th></tr></thead>
                <tbody>
                    <?php if (!$pedidos_panel): ?><tr><td colspan="8" class="tabla-vacia">Todavía no hay pedidos.</td></tr><?php endif; ?>
                    <?php foreach ($pedidos_panel as $pedido_panel): ?>
                        <tr>
                            <td>#<?php echo (int) $pedido_panel['id']; ?></td>
                            <td><?php echo luxi_h($pedido_panel['nombre_contacto'] ?: 'Cliente #' . $pedido_panel['usuario_id']); ?>
                                <?php if (!empty($pedido_panel['ciudad_envio'])): ?><br><small><?php echo luxi_h($pedido_panel['ciudad_envio']); ?></small><?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($pedido_panel['fecha'])); ?></td>
                            <td><?php echo luxi_h($pedido_panel['telefono'] ?? ''); ?></td>
                            <td><?php echo luxi_formato_precio($pedido_panel['total']); ?></td>
                            <td>
                                <?php
                                $metodos_pago_pedido = [
                                    'nequi' => 'Nequi',
                                    'whatsapp' => 'WhatsApp',
                                ];
                                echo luxi_h($metodos_pago_pedido[$pedido_panel['metodo_pago'] ?? ''] ?? 'Por coordinar');
                                ?>
                            </td>
                            <td><?php echo luxi_h($estados_pedido[$pedido_panel['estado']] ?? $pedido_panel['estado']); ?></td>
                            <td>
                                <form class="admin-estado-pedido" action="procesar_estado_pedido.php" method="POST">
                                    <?php echo luxi_csrf_campo(); ?>
                                    <input type="hidden" name="pedido_id" value="<?php echo (int) $pedido_panel['id']; ?>">
                                    <select name="estado" aria-label="Estado del pedido <?php echo (int) $pedido_panel['id']; ?>">
                                        <?php foreach ($estados_pedido as $valor_estado => $etiqueta_estado): ?>
                                            <option value="<?php echo luxi_h($valor_estado); ?>" <?php echo $pedido_panel['estado'] === $valor_estado ? 'selected' : ''; ?>>
                                                <?php echo luxi_h($etiqueta_estado); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="boton-claro boton-chico" type="submit">Guardar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>

<?php require 'includes/footer.php'; ?>
