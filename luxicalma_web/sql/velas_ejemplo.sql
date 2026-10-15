-- ============================================================
-- velas_ejemplo.sql
-- Velas de prueba para ver el catálogo y probar el carrito.
-- Corre DESPUÉS de migracion_fase04b.sql y migracion_fase05_pedidos_velas.sql.
-- Bórralas desde phpMyAdmin cuando subas las velas reales.
-- ============================================================

USE luxicalma_db;

INSERT INTO productos
 (nombre, descripcion, categoria_id, precio_detal, precio_mayorista, precio_anterior,
  colores_disponibles, foto, stock, aroma, tamano, duracion_horas, tipo_cera, es_personalizable)
VALUES
('Vela Lavanda Serena', 'Vela aromática de lavanda para relajarte al final del día. Cera de soya y mecha de algodón.',
  (SELECT id FROM categorias WHERE nombre='Velas aromáticas'), 38000, 28000, NULL,
  'Lila, Blanco', '', 30, 'Lavanda', 'mediana', 40, 'Soya', 0),
('Vela Vainilla Dorada', 'Aroma cálido a vainilla y caramelo. Perfecta para acompañar una noche tranquila.',
  (SELECT id FROM categorias WHERE nombre='Velas aromáticas'), 42000, 31000, 48000,
  'Crema, Dorado', '', 25, 'Vainilla', 'grande', 55, 'Soya', 0),
('Vela Fe y Luz', 'Vela religiosa en vaso de vidrio, ideal para oración y agradecimiento.',
  (SELECT id FROM categorias WHERE nombre='Velas religiosas'), 18000, 13000, NULL,
  'Blanco, Dorado', '', 60, 'Sin aroma', 'mediana', 48, 'Parafina', 0),
('Vela Noche Buena', 'Canela, clavo y naranja: el aroma de la Navidad en casa.',
  (SELECT id FROM categorias WHERE nombre='Velas navideñas'), 36000, 27000, NULL,
  'Rojo, Verde, Dorado', 'productos/velas-navideñas', 40, 'Canela y naranja', 'mediana', 40, 'Soya', 0),
('Vela Corazón Rosa', 'Vela en forma de corazón para regalar en amor y amistad.',
  (SELECT id FROM categorias WHERE nombre='Velas amor y amistad'), 29000, 21000, 34000,
  'Rosa, Rojo, Blanco', '', 35, 'Rosas', 'pequeña', 20, 'Soya', 1),
('Vela Nube Baby', 'Detalle suave para recibir a un bebé: aroma a algodón limpio.',
  (SELECT id FROM categorias WHERE nombre='Velas baby shower'), 24000, 18000, NULL,
  'Celeste, Rosa, Blanco', '', 45, 'Algodón limpio', 'pequeña', 25, 'Soya', 1),
('Vela Pilar Artesanal', 'Vela decorativa tipo pilar, acabado liso y elegante para centros de mesa.',
  (SELECT id FROM categorias WHERE nombre='Velas decorativas'), 32000, 24000, NULL,
  'Crema, Terracota, Negro', '', 20, 'Sándalo', 'grande', 70, 'Parafina', 0),
('Vela Jazmín Luna', 'Flores blancas de jazmín con fondo de madera suave.',
  (SELECT id FROM categorias WHERE nombre='Velas aromáticas'), 39000, 29000, NULL,
  'Blanco, Crema', '', 28, 'Jazmín', 'mediana', 42, 'Soya', 0);
