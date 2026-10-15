<?php
/**
 * checkout.php
 * Ventana para finalizar el pedido: datos de entrega + resumen.
 */
require_once __DIR__ . '/includes/carrito_helper.php';
require_once __DIR__ . '/../config/conexion.php';

$c = luxi_carrito_detallado($conexion);

if (count($c['items']) === 0) {
    header('Location: carrito.php?error=' . urlencode('Tu carrito está vacío.'));
    exit;
}

// Hay que iniciar sesión para pedir; al volver, regresa aquí
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['volver_a'] = 'checkout.php';
    header('Location: login.php?error=' . urlencode('Inicia sesión o crea tu cuenta para finalizar tu pedido. Tu carrito se conserva.'));
    exit;
}

// Datos del usuario para prellenar el formulario
$u = $conexion->prepare("SELECT nombres, apellidos, ciudad, departamento FROM usuarios WHERE id = :id");
$u->execute([':id' => $_SESSION['usuario_id']]);
$usuario = $u->fetch(PDO::FETCH_ASSOC) ?: [];

// Si hubo un error, se conservan los datos que la persona ya escribió
$previo = $_SESSION['checkout_datos'] ?? [];
unset($_SESSION['checkout_datos']);
$val = function (string $campo, string $defecto = '') use ($previo) {
    return luxi_h($previo[$campo] ?? $defecto);
};

$titulo_pagina = 'Finalizar pedido';
$css_pagina = 'carrito.css';
require 'includes/header.php';
?>

<section class="contenedor pagina-carrito">
    <h1>Finalizar pedido</h1>

    <?php if (isset($_GET['error'])): ?>
        <p class="mensaje-error"><?php echo luxi_h($_GET['error']); ?></p>
    <?php endif; ?>
    <?php foreach ($c['avisos'] as $aviso): ?>
        <p class="mensaje-aviso"><?php echo luxi_h($aviso); ?></p>
    <?php endforeach; ?>

    <div class="layout-carrito">
        <form action="procesar_pedido.php" method="POST" class="formulario-checkout">
            <?php echo luxi_csrf_campo(); ?>
            <h2>Datos de entrega</h2>

            <label for="nombre_contacto">Nombre de quien recibe</label>
            <input type="text" id="nombre_contacto" name="nombre_contacto" required maxlength="150"
                   value="<?php echo $val('nombre_contacto', trim(($usuario['nombres'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''))); ?>">

            <label for="telefono">Teléfono / WhatsApp</label>
            <input type="tel" id="telefono" name="telefono" required maxlength="20"
                   pattern="[0-9+\s\-]{7,20}" title="Solo números, entre 7 y 20 dígitos"
                   value="<?php echo $val('telefono'); ?>" placeholder="Ej: 3001234567">

            <label for="direccion_envio">Dirección</label>
            <input type="text" id="direccion_envio" name="direccion_envio" required maxlength="255"
                   value="<?php echo $val('direccion_envio'); ?>" placeholder="Calle, número, barrio, apartamento…">

            <div class="fila-doble">
                <div>
                    <label for="ciudad_envio">Ciudad</label>
                    <input type="text" id="ciudad_envio" name="ciudad_envio" required maxlength="100"
                           value="<?php echo $val('ciudad_envio', $usuario['ciudad'] ?? ''); ?>">
                </div>
                <div>
                    <label for="departamento_envio">Departamento</label>
                    <input type="text" id="departamento_envio" name="departamento_envio" required maxlength="100"
                           value="<?php echo $val('departamento_envio', $usuario['departamento'] ?? ''); ?>">
                </div>
            </div>

            <label for="notas">Notas del pedido <span class="opcional">(opcional)</span></label>
            <textarea id="notas" name="notas" rows="3" maxlength="500"
                      placeholder="Ej: dedicatoria, hora de entrega, personalización…"><?php echo $val('notas'); ?></textarea>

            <section class="medios-pago" aria-labelledby="titulo-medios-pago">
                <h2 id="titulo-medios-pago">Medios de pago</h2>
                <div class="logos-medios-pago" aria-label="Medios de pago disponibles y próximos">
                    <span class="logo-pse" aria-label="PSE">PSE</span>
                    <span class="logo-visa" aria-label="Visa">VISA</span>
                    <span class="logo-mastercard" aria-label="Mastercard"><i></i><i></i><b>mastercard</b></span>
                    <span class="logo-nequi" aria-label="Nequi">Nequi</span>
                </div>
                <label class="opcion-pago-nequi">
                    <input type="radio" name="metodo_pago" value="nequi" required>
                    <span><strong>Transferencia por Nequi</strong><small>Envía el pago al número del negocio y comparte el comprobante para verificarlo.</small></span>
                </label>
                <p class="nequi-destino"><span>Nequi del negocio</span><strong>321 532 4610</strong></p>
                <label class="opcion-pago-whatsapp">
                    <input type="radio" name="metodo_pago" value="whatsapp">
                    <span class="icono-pago-whatsapp" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2a9.77 9.77 0 0 0-8.35 14.84L2.4 22l5.3-1.24A9.96 9.96 0 0 0 12.04 22a10 10 0 0 0 0-20Zm0 18.2a8.2 8.2 0 0 1-4.18-1.14l-.3-.18-3.15.74.76-3.06-.2-.32a8.2 8.2 0 1 1 7.07 3.96Zm4.5-6.14c-.25-.13-1.47-.73-1.7-.82-.23-.08-.4-.13-.57.13-.16.25-.65.82-.8.99-.15.17-.3.19-.55.06-.25-.13-1.05-.39-2-.1-.74-.67-1.23-1.5-1.38-1.75-.15-.25-.02-.39.11-.52.12-.11.25-.3.37-.44.13-.15.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.57-1.37-.78-1.88-.2-.48-.4-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.23.9 2.43 1.03 2.6.12.16 1.77 2.7 4.29 3.78.6.26 1.07.41 1.43.52.6.19 1.15.16 1.58.1.48-.08 1.47-.6 1.68-1.2.21-.59.21-1.1.15-1.2-.06-.1-.23-.16-.48-.29Z"/></svg>
                    </span>
                    <span><strong>Pagar por WhatsApp</strong><small>Opción temporal: coordinaremos el pago contigo por chat.</small></span>
                </label>
            </section>

            <?php if ($c['requiere_anticipo']): ?>
                <p class="nota-anticipo">
                    Como tu pedido supera las <?php echo LUXI_UMBRAL_ANTICIPO; ?> unidades, la entrega se agenda
                    para dentro de <?php echo LUXI_DIAS_ANTICIPO; ?> días y una asesora te contactará para
                    coordinar el pago inicial.
                </p>
            <?php else: ?>
                <p class="nota-anticipo">
                    Al confirmar, una asesora te contactará para coordinar el pago y el envío.
                </p>
            <?php endif; ?>

            <button type="submit" class="boton-negro boton-ancho">Confirmar pedido</button>
            <a href="carrito.php" class="enlace-volver">← Volver al carrito</a>
        </form>

        <aside class="resumen-carrito">
            <h2>Tu pedido</h2>
            <ul class="resumen-productos">
                <?php foreach ($c['items'] as $item): ?>
                    <li>
                        <img src="<?php echo $item['foto']; ?>" alt="">
                        <div>
                            <strong><?php echo luxi_h($item['nombre']); ?></strong>
                            <span><?php echo luxi_h(luxi_variante($item['aroma'], $item['color'])); ?> · x<?php echo $item['cantidad']; ?></span>
                        </div>
                        <span><?php echo luxi_formato_precio($item['subtotal']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="linea-resumen linea-total"><span>Total</span><strong><?php echo luxi_formato_precio($c['total']); ?></strong></div>
        </aside>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
