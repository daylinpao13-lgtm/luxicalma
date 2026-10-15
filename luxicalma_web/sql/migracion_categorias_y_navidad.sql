-- Habilita productos en varias categorías y asigna las colecciones solicitadas.
-- Ejecutar una sola vez o volver a ejecutar con seguridad en luxicalma_db.
USE luxicalma_db;

CREATE TABLE IF NOT EXISTS producto_categorias (
  producto_id INT NOT NULL,
  categoria_id INT NOT NULL,
  PRIMARY KEY (producto_id, categoria_id),
  CONSTRAINT fk_producto_categorias_producto
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
  CONSTRAINT fk_producto_categorias_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
);

-- Conserva las categorías que ya estaban guardadas como categoría principal.
INSERT IGNORE INTO producto_categorias (producto_id, categoria_id)
SELECT id, categoria_id FROM productos WHERE categoria_id IS NOT NULL;

-- Las dos fotografías navideñas corresponden a productos distintos con el
-- mismo nombre comercial.
-- Se dejan con stock 0 hasta que la administración confirme las existencias.
UPDATE productos
SET nombre = 'Velas navideñas'
WHERE nombre IN ('Vela navideña - Diseño 1', 'Vela navideña - Diseño 2');

INSERT INTO productos
  (nombre, categoria, categoria_id, precio_detal, precio_mayorista, foto, stock)
SELECT
  'Velas navideñas', 'velas', c.id, 20000, 20000,
  'productos/velas-navideñas.svg', 0
FROM categorias c
WHERE c.nombre = 'Velas navideñas'
  AND NOT EXISTS (SELECT 1 FROM productos WHERE foto = 'productos/velas-navideñas.svg');

INSERT INTO productos
  (nombre, categoria, categoria_id, precio_detal, precio_mayorista, foto, stock)
SELECT
  'Velas navideñas', 'velas', c.id, 20000, 20000,
  'productos/velas-navidenas-2.svg', 0
FROM categorias c
WHERE c.nombre = 'Velas navideñas'
  AND NOT EXISTS (SELECT 1 FROM productos WHERE foto = 'productos/velas-navidenas-2.svg');

INSERT IGNORE INTO producto_categorias (producto_id, categoria_id)
SELECT p.id, c.id
FROM productos p
JOIN categorias c ON c.nombre = 'Velas navideñas'
WHERE p.foto IN ('productos/velas-navideñas.svg', 'productos/velas-navidenas-2.svg');

-- Amor y amistad: siete productos expresamente indicados.
INSERT IGNORE INTO producto_categorias (producto_id, categoria_id)
SELECT p.id, c.id
FROM productos p
JOIN categorias c ON c.nombre = 'Velas amor y amistad'
WHERE p.nombre IN (
  'Teddy Clásico', 'Teddy Nube', 'Dulce Abrazo', 'Dulce Primavera',
  'Luz de Amor', 'Sueño Celestial', 'Oso pequeño'
);

-- Baby shower: Dulce Abrazo.
INSERT IGNORE INTO producto_categorias (producto_id, categoria_id)
SELECT p.id, c.id
FROM productos p
JOIN categorias c ON c.nombre = 'Velas baby shower'
WHERE p.nombre = 'Dulce Abrazo';

-- Aromáticas: todos los productos disponibles, incluidos los que también
-- pertenecen a categorías temáticas o de diseño.
INSERT IGNORE INTO producto_categorias (producto_id, categoria_id)
SELECT p.id, c.id
FROM productos p
JOIN categorias c ON c.nombre = 'Velas aromáticas';

-- Flores: todos los productos ya asignados a la categoría Flores.
INSERT IGNORE INTO producto_categorias (producto_id, categoria_id)
SELECT p.id, c.id
FROM productos p
JOIN categorias c ON c.nombre = 'Flores'
WHERE p.categoria_id = c.id;

-- Incluye un producto de ramo y uno de flores en Velas decorativas.
INSERT IGNORE INTO producto_categorias (producto_id, categoria_id)
SELECT p.id, c.id
FROM productos p
JOIN categorias c ON c.nombre = 'Velas decorativas'
WHERE p.nombre IN ('Dulce Primavera', 'Margarita Glow');
