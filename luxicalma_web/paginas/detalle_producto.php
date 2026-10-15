<?php
require_once __DIR__ . '/includes/carrito_helper.php';
require_once __DIR__ . '/../config/conexion.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: productos.php');
    exit;
}

$sentencia = $conexion->prepare("SELECT * FROM productos WHERE id = :id");
$sentencia->execute([':id' => $id]);
$producto = $sentencia->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    header('Location: productos.php');
    exit;
}

$precio = luxi_precio_producto($producto);
$en_oferta = !luxi_es_empresa()
    && !empty($producto['precio_anterior'])
    && (float) $producto['precio_anterior'] > $precio;
$stock = (int) $producto['stock'];
$colores = luxi_colores($producto['colores_disponibles'] ?? '');
$aromas = luxi_aromas($producto['aroma'] ?? null);
$foto_producto = luxi_foto($producto['foto'], $producto['nombre']);
$nombre_mascara = 'producto-' . (int) $producto['id'] . '.mask.png';
$ruta_mascara = '../assets/img/productos/' . $nombre_mascara;
$mascara_color_disponible = is_file(__DIR__ . '/../assets/img/productos/' . $nombre_mascara);

$categorias_producto_stmt = $conexion->prepare(
    'SELECT c.id, c.nombre
     FROM producto_categorias pc
     JOIN categorias c ON c.id = pc.categoria_id
     WHERE pc.producto_id = :id
     ORDER BY c.orden, c.nombre'
);
$categorias_producto_stmt->execute([':id' => $id]);
$categorias_producto = $categorias_producto_stmt->fetchAll(PDO::FETCH_ASSOC);
$nombre_categoria = $categorias_producto[0]['nombre'] ?? null;

// Características propias de una vela
$caracteristicas = [];
if (count($aromas) > 1)                  $caracteristicas['Aromas disponibles'] = implode(', ', $aromas);
elseif (count($aromas) === 1)             $caracteristicas['Aroma'] = $aromas[0];
if (!empty($producto['tamano']))         $caracteristicas['Tamaño'] = ucfirst($producto['tamano']);
if (!empty($producto['duracion_horas'])) $caracteristicas['Duración'] = (int) $producto['duracion_horas'] . ' horas';
if (!empty($producto['tipo_cera']))      $caracteristicas['Cera'] = $producto['tipo_cera'];
if (!empty($producto['es_personalizable'])) $caracteristicas['Personalizable'] = 'Sí, consúltanos';

// Reseñas del producto
$resenas_stmt = $conexion->prepare(
    "SELECT r.id, r.producto_id, r.comentario, r.calificacion, r.fecha, u.nombres,
            rr.respuesta AS respuesta_empresa, rr.fecha AS fecha_respuesta
     FROM resenas r
     JOIN usuarios u ON u.id = r.usuario_id
     LEFT JOIN resena_respuestas rr ON rr.resena_id = r.id
     WHERE r.producto_id = :id
     ORDER BY r.fecha DESC"
);
$resenas_stmt->execute([':id' => $id]);
$resenas = $resenas_stmt->fetchAll(PDO::FETCH_ASSOC);
$promedio = count($resenas) ? array_sum(array_column($resenas, 'calificacion')) / count($resenas) : 0;
$usuario_ya_reseno = false;
if (($_SESSION['usuario_rol'] ?? '') === 'cliente') {
    $usuario_resena_stmt = $conexion->prepare(
        'SELECT 1 FROM resenas WHERE producto_id = :producto AND usuario_id = :usuario LIMIT 1'
    );
    $usuario_resena_stmt->execute([':producto' => $id, ':usuario' => (int) ($_SESSION['usuario_id'] ?? 0)]);
    $usuario_ya_reseno = (bool) $usuario_resena_stmt->fetchColumn();
}

// Otras velas para seguir comprando
$relacionados = [];
$rel = $conexion->prepare(
    "SELECT * FROM productos WHERE id <> :id AND stock > 0
     ORDER BY (categoria_id <=> :cat) DESC, fecha_creacion DESC LIMIT 4"
);
$rel->execute([':id' => $id, ':cat' => $producto['categoria_id'] ?? null]);
$relacionados = $rel->fetchAll(PDO::FETCH_ASSOC);

$titulo_pagina = $producto['nombre'];
$css_pagina = 'detalle_producto.css';
require 'includes/header.php';
?>

<section class="contenedor detalle-producto">

    <nav class="migas" aria-label="Ruta de navegación">
        <a href="inicio.php">Inicio</a> <span>›</span>
        <a href="productos.php">Catálogo</a>
        <?php if ($nombre_categoria): ?>
            <span>›</span> <a href="productos.php?categoria_id=<?php echo (int) $producto['categoria_id']; ?>"><?php echo luxi_h($nombre_categoria); ?></a>
        <?php endif; ?>
    </nav>

    <?php if (isset($_GET['ok'])): ?>
        <p class="mensaje-exito"><?php echo luxi_h($_GET['ok']); ?> <a href="carrito.php">Ver carrito</a></p>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <p class="mensaje-error"><?php echo luxi_h($_GET['error']); ?></p>
    <?php endif; ?>

    <div class="ficha-producto">
        <div class="ficha-imagen">
            <img src="<?php echo $foto_producto; ?>" alt="<?php echo luxi_h($producto['nombre']); ?>">
            <?php if (count($colores) > 0): ?>
                <div class="vista-previa-color" id="vista-previa-color" aria-live="polite" hidden>
                    <span class="muestra-previa-color" id="muestra-previa-color"></span>
                    <p><span id="texto-previa-color"></span> <strong id="nombre-color-previa"></strong></p>
                </div>
                <?php if ($mascara_color_disponible): ?>
                    <span class="capa-color-producto" id="capa-color-producto"
                          style="-webkit-mask-image: url('<?php echo luxi_h($ruta_mascara); ?>'); mask-image: url('<?php echo luxi_h($ruta_mascara); ?>');"></span>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($en_oferta): ?><span class="insignia insignia-oferta">Oferta</span><?php endif; ?>
        </div>

        <div class="info-producto">
            <?php if ($categorias_producto): ?>
                <p class="categoria-producto">
                    <?php foreach ($categorias_producto as $indice_categoria => $categoria_producto): ?>
                        <?php if ($indice_categoria > 0): ?> · <?php endif; ?>
                        <a href="productos.php?categoria_id=<?php echo (int) $categoria_producto['id']; ?>">
                            <?php echo luxi_h($categoria_producto['nombre']); ?>
                        </a>
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>
            <h1><?php echo luxi_h($producto['nombre']); ?></h1>

            <p class="resumen-estrellas">
                <span class="estrella-llena" aria-hidden="true">★</span>
                <?php if (count($resenas) > 0): ?>
                    <?php echo number_format($promedio, 1, ',', ''); ?> · <?php echo count($resenas); ?> reseña<?php echo count($resenas) === 1 ? '' : 's'; ?>
                <?php else: ?>
                    Sin reseñas todavía
                <?php endif; ?>
            </p>

            <p class="precio">
                <?php if ($en_oferta): ?>
                    <span class="precio-anterior"><?php echo luxi_formato_precio($producto['precio_anterior']); ?></span>
                <?php endif; ?>
                <?php echo luxi_formato_precio($precio); ?>
                <?php if (luxi_es_empresa()): ?><span class="etiqueta">precio mayorista</span><?php endif; ?>
            </p>

            <p class="descripcion"><?php echo nl2br(luxi_h($producto['descripcion'])); ?></p>

            <?php if (count($caracteristicas) > 0): ?>
                <dl class="caracteristicas">
                    <?php foreach ($caracteristicas as $etiqueta => $valor): ?>
                        <div><dt><?php echo luxi_h($etiqueta); ?></dt><dd><?php echo luxi_h($valor); ?></dd></div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>

            <p class="stock <?php echo $stock > 0 ? ($stock <= 5 ? 'stock-bajo' : 'stock-ok') : 'stock-agotado'; ?>">
                <?php
                if ($stock <= 0)      echo 'Agotado por el momento';
                elseif ($stock <= 5)  echo '¡Quedan solo ' . $stock . ' unidades!';
                else                  echo 'Disponible';
                ?>
            </p>

            <?php if ($stock > 0): ?>
                <form action="carrito_agregar.php" method="POST" class="formulario-agregar" data-agregar>
                    <?php echo luxi_csrf_campo(); ?>
                    <input type="hidden" name="producto_id" value="<?php echo (int) $producto['id']; ?>">

                    <?php if (count($colores) > 0): ?>
                        <fieldset class="opcion-grupo">
                            <legend>Color</legend>
                            <div class="opciones-color">
                            <?php foreach ($colores as $indice_color => $color): ?>
                                <label class="opcion-color">
                                    <input type="radio" name="color" value="<?php echo luxi_h($color); ?>"
                                           data-color="<?php echo luxi_h(luxi_color_hex($color)); ?>"
                                           required>
                                    <span class="muestra" style="background: <?php echo luxi_h(luxi_color_hex($color)); ?>"></span>
                                    <span class="nombre-color"><?php echo luxi_h($color); ?></span>
                                </label>
                            <?php endforeach; ?>
                            </div>
                        </fieldset>
                    <?php else: ?>
                        <input type="hidden" name="color" value="Único">
                    <?php endif; ?>

                    <?php if (count($aromas) > 1): ?>
                        <fieldset class="opcion-grupo">
                            <legend>Aroma</legend>
                            <div class="opciones-aroma">
                            <?php foreach ($aromas as $indice_aroma => $aroma): ?>
                                <label class="opcion-aroma">
                                    <input type="radio" name="aroma" value="<?php echo luxi_h($aroma); ?>"
                                           <?php echo $indice_aroma === 0 ? 'checked' : ''; ?> required>
                                    <span><?php echo luxi_h($aroma); ?></span>
                                </label>
                            <?php endforeach; ?>
                            </div>
                        </fieldset>
                    <?php elseif (count($aromas) === 1): ?>
                        <input type="hidden" name="aroma" value="<?php echo luxi_h($aromas[0]); ?>">
                    <?php endif; ?>

                    <label for="cantidad">Cantidad</label>
                    <div class="selector-cantidad">
                        <button type="button" data-paso="-1" data-objetivo="cantidad" aria-label="Menos">−</button>
                        <input type="number" name="cantidad" id="cantidad" value="1" min="1" max="<?php echo $stock; ?>" required>
                        <button type="button" data-paso="1" data-objetivo="cantidad" aria-label="Más">+</button>
                    </div>

                    <p class="nota-anticipo">
                        Pedidos de más de <?php echo LUXI_UMBRAL_ANTICIPO; ?> unidades requieren <?php echo LUXI_DIAS_ANTICIPO; ?> días de anticipación
                        y un pago inicial para agendar la entrega.
                    </p>

                    <button type="submit" class="boton-negro boton-ancho">Agregar al carrito</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <section class="seccion-resenas">
        <div class="resenas-cabecera" id="resenas">
            <div>
                <h2>Opiniones sobre <?php echo luxi_h($producto['nombre']); ?></h2>
                <p class="resenas-promedio">
                    <span class="estrella-llena" aria-hidden="true">★</span>
                    <?php echo number_format($promedio, 1, ',', ''); ?> de 5 ·
                    <?php echo count($resenas); ?> opinión<?php echo count($resenas) === 1 ? '' : 'es'; ?>
                </p>
            </div>
        </div>
        <?php if (($_GET['resena'] ?? '') === 'ok'): ?>
            <p class="mensaje-exito">Gracias por compartir tu opinión.</p>
        <?php elseif (($_GET['resena'] ?? '') === 'respuesta_ok'): ?>
            <p class="mensaje-exito">La respuesta oficial se guardó correctamente.</p>
        <?php elseif (($_GET['resena_error'] ?? '') !== ''): ?>
            <p class="mensaje-error"><?php echo luxi_h($_GET['resena_error']); ?></p>
        <?php endif; ?>

        <?php if (($_SESSION['usuario_rol'] ?? '') === 'cliente' && !$usuario_ya_reseno): ?>
            <form class="formulario-resena" action="procesar_resena.php" method="POST">
                <?php echo luxi_csrf_campo(); ?>
                <input type="hidden" name="accion" value="crear">
                <input type="hidden" name="producto_id" value="<?php echo (int) $producto['id']; ?>">
                <h3>Cuéntanos tu experiencia</h3>
                <p class="ayuda-resena">Tu opinión real ayuda a otras personas a elegir y a Luxicalma a mejorar.</p>
                <fieldset class="calificacion-resena">
                    <legend>Tu calificación</legend>
                    <div class="estrellas-seleccion">
                        <?php for ($estrella = 5; $estrella >= 1; $estrella--): ?>
                            <input type="radio" id="calificacion-<?php echo $estrella; ?>"
                                   name="calificacion" value="<?php echo $estrella; ?>" required>
                            <label for="calificacion-<?php echo $estrella; ?>"
                                   aria-label="<?php echo $estrella; ?> de 5 estrellas">★</label>
                        <?php endfor; ?>
                    </div>
                </fieldset>
                <label for="comentario-resena">Tu opinión</label>
                <textarea id="comentario-resena" name="comentario" rows="4" minlength="10"
                          maxlength="2000" required
                          placeholder="Comparte tu experiencia con este producto (10 a 2000 caracteres)."></textarea>
                <button type="submit" class="boton-negro">Publicar opinión</button>
            </form>
        <?php elseif (($_SESSION['usuario_rol'] ?? '') === 'cliente'): ?>
            <p class="nota-filtro">Ya compartiste tu opinión sobre este producto. Gracias por ayudar a otras personas con una reseña auténtica.</p>
        <?php elseif (empty($_SESSION['usuario_id'])): ?>
            <p class="nota-filtro">Inicia sesión como cliente para calificar y opinar sobre este producto.
                <a href="login.php">Iniciar sesión</a>
            </p>
        <?php endif; ?>

        <?php if (count($resenas) === 0): ?>
            <p class="nota-filtro">Este producto todavía no tiene opiniones. ¡Sé la primera persona en compartir tu experiencia!</p>
        <?php else: ?>
            <?php foreach ($resenas as $resena): ?>
                <div class="resena">
                    <p class="resena-producto">Opinión sobre <?php echo luxi_h($producto['nombre']); ?></p>
                    <div class="estrellas">
                        <?php for ($e = 1; $e <= 5; $e++): ?>
                            <span class="<?php echo $e <= (int) $resena['calificacion'] ? 'estrella-llena' : 'estrella-vacia'; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <p><?php echo luxi_h($resena['comentario']); ?></p>
                    <small>— <?php echo luxi_h($resena['nombres']); ?>, <?php echo date('d/m/Y', strtotime($resena['fecha'])); ?></small>
                    <?php if (!empty($resena['respuesta_empresa'])): ?>
                        <div class="respuesta-empresa">
                            <strong>Respuesta oficial de Luxicalma</strong>
                            <p><?php echo nl2br(luxi_h($resena['respuesta_empresa'])); ?></p>
                            <small><?php echo date('d/m/Y', strtotime($resena['fecha_respuesta'])); ?></small>
                        </div>
                    <?php endif; ?>
                    <?php if (($_SESSION['usuario_rol'] ?? '') === 'administrador'): ?>
                        <form class="formulario-respuesta" action="procesar_resena.php" method="POST">
                            <?php echo luxi_csrf_campo(); ?>
                            <input type="hidden" name="accion" value="responder">
                            <input type="hidden" name="resena_id" value="<?php echo (int) $resena['id']; ?>">
                            <label for="respuesta-<?php echo (int) $resena['id']; ?>">Respuesta oficial</label>
                            <textarea id="respuesta-<?php echo (int) $resena['id']; ?>" name="respuesta"
                                      rows="3" minlength="2" maxlength="2000" required><?php echo luxi_h($resena['respuesta_empresa'] ?? ''); ?></textarea>
                            <button type="submit" class="boton-claro">Guardar respuesta oficial</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <?php if (count($relacionados) > 0): ?>
        <section class="seccion-relacionados">
            <h2>También te pueden gustar</h2>
            <div class="grid-productos">
                <?php foreach ($relacionados as $producto): ?>
                    <?php require __DIR__ . '/includes/tarjeta_producto.php'; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</section>

<?php if (count($colores) > 0): ?>
<script>
var fichaImagen = document.querySelector('.ficha-imagen');
document.querySelectorAll('.opcion-color input[name="color"]').forEach(function (opcion) {
    opcion.addEventListener('change', function () {
        if (!opcion.checked) return;

        var vistaPrevia = document.getElementById('vista-previa-color');
        var muestra = document.getElementById('muestra-previa-color');
        var nombreColor = document.getElementById('nombre-color-previa');
        var textoPrevia = document.getElementById('texto-previa-color');
        var capaColor = document.getElementById('capa-color-producto');

        muestra.style.backgroundColor = opcion.dataset.color;
        nombreColor.textContent = opcion.value;
        textoPrevia.textContent = capaColor ? 'Vista previa del color:' : 'Color seleccionado:';
        vistaPrevia.hidden = false;
        fichaImagen.classList.add('color-preview-active');
        if (capaColor) capaColor.style.backgroundColor = opcion.dataset.color;
    });
});
</script>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
