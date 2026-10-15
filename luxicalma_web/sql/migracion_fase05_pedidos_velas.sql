-- ============================================================
-- migracion_fase05_pedidos_velas.sql
-- Corre este archivo DESPUÉS de luxicalma_db.sql y migracion_fase04b.sql
-- (phpMyAdmin -> luxicalma_db -> pestaña SQL).
--
-- Qué arregla:
--  1) La columna vieja "categoria" era un ENUM obligatorio que NO
--     incluía velas: ahora es opcional, así se pueden crear velas.
--  2) pedidos: guarda datos de entrega (nombre, teléfono, dirección...).
--  3) pedido_detalle: guarda el precio de cada unidad al momento de
--     comprar (si el precio cambia después, el pedido no se altera).
-- ============================================================

USE luxicalma_db;

-- 1) La categoría vieja deja de ser obligatoria
ALTER TABLE productos
  MODIFY COLUMN categoria ENUM('osos','flores','ramos','religiosos','velas') DEFAULT NULL;

-- 2) Datos de entrega en el pedido
ALTER TABLE pedidos
  ADD COLUMN nombre_contacto VARCHAR(150) DEFAULT NULL,
  ADD COLUMN telefono VARCHAR(30) DEFAULT NULL,
  ADD COLUMN direccion_envio VARCHAR(255) DEFAULT NULL,
  ADD COLUMN ciudad_envio VARCHAR(100) DEFAULT NULL,
  ADD COLUMN departamento_envio VARCHAR(100) DEFAULT NULL,
  ADD COLUMN notas VARCHAR(500) DEFAULT NULL;

-- 3) Precio por unidad al momento de la compra
ALTER TABLE pedido_detalle
  ADD COLUMN precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0;
