# Luxicalma – cambios de esta versión

## Instalación (XAMPP / phpMyAdmin)
Importa en este orden (pestaña SQL de `luxicalma_db`):
1. `sql/luxicalma_db.sql` (solo si instalas desde cero)
2. `sql/migracion_fase04b.sql`
3. **`sql/migracion_fase05_pedidos_velas.sql`**  ← NUEVO y obligatorio
4. `sql/migracion_fase06_aromas_pedido.sql` (guarda el aroma elegido en cada pedido)
5. `sql/migracion_resenas_respuestas.sql` (reseñas únicas por cliente/producto y respuestas oficiales)
6. `sql/migracion_categorias_y_navidad.sql` (varias categorías por producto y velas navideñas)
7. `sql/migracion_seguimiento_pedidos.sql` (estado de empaque para seguimiento)
8. `sql/migracion_metodo_pago_whatsapp.sql` (guarda el medio de pago elegido)
9. `sql/velas_ejemplo.sql` (opcional: productos de ejemplo)

> Sin el paso 3, confirmar un pedido mostrará "falta actualizar la base de datos".

## Imágenes de productos
- La columna `productos.foto` puede guardar una ruta relativa como `productos/dulce-ilusion`, con o sin extensión.
- Las rutas se resuelven ignorando diferencias entre acentos y guiones; los archivos deben estar dentro de `assets/img`.
- Los productos de ejemplo ya incluyen la ruta de sus imágenes disponibles.
- Las fotos repetidas del catálogo se agrupan por ID en `config/galerias_productos.php`: Teddy Clásico, Teddy Nube, Oso pequeño y Dulce Fe muestran sus opciones simultáneamente.
- La migración `migracion_categorias_y_navidad.sql` crea dos productos llamados «Velas navideñas», cada uno con su foto y precio de $20.000; quedan con stock 0 hasta que la administración confirme las existencias.
- Un producto puede pertenecer a varias categorías mediante `producto_categorias`. La migración asigna los siete productos solicitados a Amor y amistad, Dulce Abrazo a Baby shower, todos los productos actuales a Aromáticas y las cuatro flores existentes a Flores.
- Velas decorativas incluye Dulce Primavera (ramo) y Margarita Glow (flores); se pueden cambiar estas asignaciones desde el panel.
- Para añadir las futuras fotos de flores, crea o actualiza el producto desde administración y asígnalo a Flores; las imágenes adicionales aún no están en la carpeta del proyecto para poder agruparlas.
- Si la ruta guardada no coincide exactamente con el archivo, se intenta resolver por el nombre del producto; si no existe una imagen correspondiente, se muestra la imagen genérica.

## Selección de color y aroma
- Todos los productos ofrecen selección de color y aroma. Los valores por defecto son aromas Mango, Canela, Coconut, Lavanda, Maracuyá, Chocolate y Baby; colores Rosado, Amarillo, Azul y Morado.
- Un producto puede personalizar su lista escribiendo los valores separados por comas en `colores_disponibles` y `aroma` (por ejemplo: `Rosa, Blanco` y `Lavanda, Vainilla`).
- La ficha muestra una simulación teñida del producto y se actualiza al elegir color. La selección se conserva en el carrito, checkout, confirmación e historial.
- Para teñir únicamente el objeto, cada producto debe tener una máscara PNG transparente cargada desde el panel; sin ella solo se muestra el color elegido, no se tiñe la fotografía.
- Ejecuta `sql/migracion_fase06_aromas_pedido.sql` una vez para habilitar el guardado del aroma en pedidos existentes.

## Administración de productos
- Al iniciar sesión con una cuenta de rol `administrador`, se abre `paginas/panel_productos.php`.
- Desde allí se pueden crear productos, cambiar precios detallistas y mayoristas, activar/quitar ofertas con el precio anterior, modificar stock, imágenes, varias categorías, colores y aromas.
- En «Temporadas y productos temáticos» la administración puede crear y editar campañas, elegir sus fechas de vigencia y asignar productos. La sección «Temáticos» muestra solo productos de campañas activas y vigentes.
- La administración puede actualizar cada pedido a empaque, en camino o entregado; el cliente ve el avance en «Mis pedidos».
- Las imágenes deben ser JPG, PNG o WEBP y pesar hasta 5 MB. Los archivos subidos se guardan en `assets/img/productos`.
- En «Seguimiento de pedidos» se puede cambiar el estado; en «Mis pedidos» el cliente ve el avance hasta la entrega.
- Deja la lista de colores o aromas vacía para usar las opciones predeterminadas; escribe los valores separados por comas para personalizar cada producto.
- En móvil, abre Categorías con un toque; en computadora, con clic o al pasar el puntero.

## Reseñas y redes sociales
- Los clientes autenticados pueden publicar una reseña auténtica de 1 a 5 estrellas por producto; el índice único y la transacción evitan reseñas duplicadas. No se publican opiniones inventadas.
- La cuenta administradora puede responder desde el panel de productos o desde la ficha. La respuesta se guarda separada y se identifica como oficial de Luxicalma.
- Ejecuta `sql/migracion_resenas_respuestas.sql` para habilitar el índice único y la tabla persistente de respuestas.
- Los enlaces del pie llevan a WhatsApp `+57 322 725 5894`, Instagram y TikTok oficiales.

## Pedidos y carrito
- Elegir variantes y agregar al carrito desde la ficha del producto; las tarjetas del catálogo muestran sus galerías y llevan al detalle de compra.
- Mini‑carrito lateral al tocar el ícono 🛍️ del encabezado (+, −, quitar, total).
- Carrito con botones +/−, control de stock y resumen.
- Nueva ventana **Finalizar pedido** (`checkout.php`): datos de entrega.
- Pedido confirmado con resumen y entrega; nueva página **Mis pedidos**.
- La confirmación incluye un botón para que el cliente abra WhatsApp con un mensaje listo para enviar a Luxicalma. El sitio no envía mensajes automáticos a teléfonos de clientes.
- El enlace general de WhatsApp del pie abre el chat sin texto prellenado; solo el botón de confirmación incluye el resumen de esa orden.
- El checkout permite transferencias manuales a Nequi `321 532 4610` o coordinar el pago por WhatsApp; los comprobantes se verifican manualmente. PSE, Visa y Mastercard solo aparecen como marcas, sin simular un cobro ni solicitar datos de tarjeta. Para habilitar pagos automáticos se debe conectar una pasarela autorizada.
- Si no hay sesión, pide iniciar sesión y devuelve al checkout con el carrito intacto.

## Errores corregidos
- La tabla `productos` tenía `categoria` ENUM obligatorio sin "velas": no se podían crear velas.
- Imágenes rotas (`producto-generico.png` no existía): ahora hay una vela genérica SVG.
- Precios guardados en la sesión: ahora se recalculan desde la BD (mayorista/detal al día).
- El carrito no validaba stock acumulado ni al actualizar cantidades.
- Al confirmar, no se descontaba stock ni se guardaba el precio pagado (`precio_unitario`).
- "Más vendidos" contaba ventas de todos los tiempos, no solo los últimos 7 días.
- Textos de desarrollo visibles al cliente en el inicio ("Fase 04b", "la administradora…").
- Protección CSRF en acciones del carrito y pedido; `session_regenerate_id` al iniciar sesión.

## Diseño
- Paleta cálida de velas (crema, dorado, tinta) – se cambia en `assets/css/variables.css`.
- Tipografía Cormorant Garamond + Poppins (Google Fonts).
- Hero con llama animada, tarjetas con insignias (Oferta/Agotado/Personalizable), precio tachado,
  aroma y duración, ficha con características de vela y "También te pueden gustar".
- Pie de página con columnas, responsive en móvil.

## Pendiente (no incluido)
- `panel_admin.php` y `panel_visualizador.php` no existen: el login de esos roles da error 404.
- Cupones y costo de envío (la tabla `cupones` existe, falta la lógica).
