<?php
/**
 * tarjeta_producto.php
 * Tarjeta de producto reutilizable (inicio, catálogo, búsquedas).
 * Antes de incluirla hay que tener la variable $producto con la fila
 * completa de la tabla productos.
 */
$tp_precio = luxi_precio_producto($producto);
$tp_en_oferta = !luxi_es_empresa()
    && !empty($producto['precio_anterior'])
    && (float) $producto['precio_anterior'] > $tp_precio;
$tp_agotado = (int) $producto['stock'] <= 0;
$tp_aromas = luxi_aromas($producto['aroma'] ?? null);
$tp_url = 'detalle_producto.php?id=' . (int) $producto['id'];
$tp_galerias = require __DIR__ . '/../../config/galerias_productos.php';
$tp_foto_base = strtolower(pathinfo(str_replace('\\', '/', (string) ($producto['foto'] ?? '')), PATHINFO_FILENAME));
$tp_galeria = $tp_galerias[(int) $producto['id']]
    ?? $tp_galerias['por_foto'][$tp_foto_base]
    ?? ['tipo' => 'carrusel', 'imagenes' => [$producto['foto'] ?? '']];
$tp_galeria = $tp_galeria['imagenes'] ? $tp_galeria : [
    'tipo' => 'carrusel',
    'imagenes' => [$producto['foto'] ?? ''],
];
$tp_cantidad_imagenes = count($tp_galeria['imagenes']);
$tp_clase_galeria = $tp_galeria['tipo'] === 'opciones' ? 'galeria-opciones' : 'galeria-carrusel';
?>
<article class="tarjeta-producto" data-product-url="<?php echo luxi_h($tp_url); ?>"
         role="link" tabindex="0" aria-label="Ver <?php echo luxi_h($producto['nombre']); ?>">
    <div class="tarjeta-galeria <?php echo $tp_clase_galeria; ?>"
         data-galeria-tipo="<?php echo luxi_h($tp_galeria['tipo']); ?>">
        <div class="tarjeta-imagen">
            <?php if ($tp_galeria['tipo'] === 'carrusel'): ?>
                <div class="galeria-pista" data-galeria-pista>
                    <?php foreach ($tp_galeria['imagenes'] as $indice_imagen => $ruta_imagen): ?>
                        <div class="galeria-diapositiva">
                            <img src="<?php echo luxi_foto($ruta_imagen, $producto['nombre']); ?>"
                                 alt="<?php echo luxi_h($producto['nombre']); ?>, vista <?php echo $indice_imagen + 1; ?>"
                                 loading="lazy">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <img data-imagen-opcion-principal
                     src="<?php echo luxi_foto($tp_galeria['imagenes'][0], $producto['nombre']); ?>"
                     alt="<?php echo luxi_h($producto['nombre']); ?>" loading="lazy">
            <?php endif; ?>
            <?php if ($tp_agotado): ?>
                <span class="insignia insignia-agotado">Agotado</span>
            <?php elseif ($tp_en_oferta): ?>
                <span class="insignia insignia-oferta">Oferta</span>
            <?php elseif (!empty($producto['es_personalizable'])): ?>
                <span class="insignia insignia-personal">Personalizable</span>
            <?php endif; ?>
        </div>
        <?php if ($tp_galeria['tipo'] === 'carrusel' && $tp_cantidad_imagenes > 1): ?>
            <button type="button" class="galeria-flecha galeria-anterior" data-galeria-paso="-1"
                    aria-label="Foto anterior de <?php echo luxi_h($producto['nombre']); ?>">‹</button>
            <button type="button" class="galeria-flecha galeria-siguiente" data-galeria-paso="1"
                    aria-label="Foto siguiente de <?php echo luxi_h($producto['nombre']); ?>">›</button>
            <div class="galeria-indicadores" role="group" aria-label="Fotos de <?php echo luxi_h($producto['nombre']); ?>">
                <?php foreach ($tp_galeria['imagenes'] as $indice_imagen => $_ruta_imagen): ?>
                    <button type="button" class="galeria-punto<?php echo $indice_imagen === 0 ? ' activo' : ''; ?>"
                            data-galeria-indice="<?php echo $indice_imagen; ?>"
                            aria-label="Ver foto <?php echo $indice_imagen + 1; ?> de <?php echo $tp_cantidad_imagenes; ?>"
                            aria-pressed="<?php echo $indice_imagen === 0 ? 'true' : 'false'; ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php elseif ($tp_galeria['tipo'] === 'opciones' && $tp_cantidad_imagenes > 1): ?>
            <div class="galeria-miniaturas" role="group" aria-label="Opciones de <?php echo luxi_h($producto['nombre']); ?>">
                <?php foreach ($tp_galeria['imagenes'] as $indice_imagen => $ruta_imagen): ?>
                    <button type="button" class="galeria-miniatura<?php echo $indice_imagen === 0 ? ' activa' : ''; ?>"
                            data-imagen-opcion="<?php echo luxi_foto($ruta_imagen, $producto['nombre']); ?>"
                            data-imagen-alt="<?php echo luxi_h($producto['nombre']); ?>, opción <?php echo $indice_imagen + 1; ?>"
                            aria-label="Ver opción <?php echo $indice_imagen + 1; ?> de <?php echo luxi_h($producto['nombre']); ?>"
                            aria-pressed="<?php echo $indice_imagen === 0 ? 'true' : 'false'; ?>">
                        <img src="<?php echo luxi_foto($ruta_imagen, $producto['nombre']); ?>"
                             alt="" loading="lazy">
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="tarjeta-cuerpo">
        <h3><?php echo luxi_h($producto['nombre']); ?></h3>
        <?php if (!empty($producto['descripcion'])): ?>
            <p class="tarjeta-descripcion"><?php echo luxi_h($producto['descripcion']); ?></p>
        <?php endif; ?>
        <?php if (count($tp_aromas) > 0): ?>
            <p class="tarjeta-detalles"><span>Aromas: <?php echo luxi_h(implode(', ', $tp_aromas)); ?></span></p>
        <?php endif; ?>
        <p class="precio">
            <?php if ($tp_en_oferta): ?>
                <span class="precio-anterior"><?php echo luxi_formato_precio($producto['precio_anterior']); ?></span>
            <?php endif; ?>
            <?php echo luxi_formato_precio($tp_precio); ?>
            <?php if (luxi_es_empresa()): ?><span class="etiqueta">precio mayorista</span><?php endif; ?>
        </p>
        <a href="<?php echo luxi_h($tp_url); ?>" class="boton-claro boton-ancho tarjeta-ver-producto">
            <?php echo $tp_agotado ? 'Ver detalle' : 'Ver producto y comprar'; ?>
        </a>
    </div>
</article>
