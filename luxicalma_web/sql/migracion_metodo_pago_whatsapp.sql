-- Guarda el método temporal elegido para los pedidos.
USE luxicalma_db;

ALTER TABLE pedidos
  ADD COLUMN IF NOT EXISTS metodo_pago VARCHAR(30) DEFAULT NULL;
