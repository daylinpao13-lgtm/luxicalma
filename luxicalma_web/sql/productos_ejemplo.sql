-- ============================================================
-- productos_ejemplo.sql
-- Productos de prueba para poder navegar el catálogo y probar
-- el carrito de compras. Bórralos después desde phpMyAdmin
-- cuando subas los productos reales de Luxicalma.
-- Cárgalo igual que los demás: phpMyAdmin → luxicalma_db → Importar.
-- ============================================================

USE luxicalma_db;

INSERT INTO productos (nombre, descripcion, categoria, precio_detal, precio_mayorista, colores_disponibles, foto, stock) VALUES
('Teddy Nube', 'Oso tipo vela suave, ideal para regalar en cualquier ocasión especial.', 'osos', 55000, 42000, 'Blanco, Rosa, Azul cielo', 'productos/teddy-nude', 20),
('Teddy Clásico', 'El oso original de Luxicalma, tierno y perfecto para cualquier momento.', 'osos', 48000, 38000, 'Café, Beige, Blanco', 'productos/teddy-clásico', 25),
('Margarita Glow', 'Arreglo floral luminoso con margaritas frescas.', 'flores', 65000, 50000, 'Amarillo, Blanco', 'productos/margarita-glow', 15),
('Peonía Bloom', 'Ramo de peonías en tonos pastel, ideal para sorprender.', 'flores', 72000, 55000, 'Rosa, Lavanda', 'productos/peonia-bloom', 12),
('Dulce Fe', 'Detalle religioso con vela aromática y flores pequeñas.', 'religiosos', 40000, 32000, 'Blanco, Dorado', 'productos/dulce-fe', 18),
('Flor Encanto', 'Ramo mixto de flores frescas de temporada.', 'ramos', 58000, 45000, 'Rosa, Blanco, Amarillo', 'productos/flor-encanto', 22);
