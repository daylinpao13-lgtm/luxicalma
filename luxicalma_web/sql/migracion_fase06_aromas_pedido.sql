-- ============================================================
-- migracion_fase06_aromas_pedido.sql
-- Ejecuta después de migracion_fase05_pedidos_velas.sql.
-- Guarda el aroma seleccionado en el detalle de cada pedido.
-- Se puede volver a ejecutar sin duplicar la columna.
-- ============================================================

USE luxicalma_db;

SET @existe_aroma_detalle = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'pedido_detalle'
    AND COLUMN_NAME = 'aroma'
);

SET @sql_aroma_detalle = IF(
  @existe_aroma_detalle = 0,
  'ALTER TABLE pedido_detalle ADD COLUMN aroma VARCHAR(100) DEFAULT NULL AFTER color',
  'ALTER TABLE pedido_detalle MODIFY COLUMN aroma VARCHAR(100) DEFAULT NULL'
);

PREPARE sentencia_aroma_detalle FROM @sql_aroma_detalle;
EXECUTE sentencia_aroma_detalle;
DEALLOCATE PREPARE sentencia_aroma_detalle;
