-- ============================================================
-- asignar_categorias_ejemplo.sql
-- Opcional: si ya tenías cargados los productos de ejemplo
-- (sql/productos_ejemplo.sql), esto les asigna una categoría real
-- de la tabla nueva "categorias", para que aparezcan al filtrar.
-- Corre esto DESPUÉS de sql/migracion_fase04b.sql
-- ============================================================

USE luxicalma_db;

UPDATE productos SET categoria_id = (SELECT id FROM categorias WHERE nombre = 'Teddys') WHERE categoria = 'osos';
UPDATE productos SET categoria_id = (SELECT id FROM categorias WHERE nombre = 'Flores') WHERE categoria = 'flores';
UPDATE productos SET categoria_id = (SELECT id FROM categorias WHERE nombre = 'Ramos') WHERE categoria = 'ramos';
UPDATE productos SET categoria_id = (SELECT id FROM categorias WHERE nombre = 'Velas religiosas') WHERE categoria = 'religiosos';
