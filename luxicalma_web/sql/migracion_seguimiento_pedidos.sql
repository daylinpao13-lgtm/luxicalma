-- Añade el estado de empaque al seguimiento de pedidos.
-- Los estados enviados/completados existentes se conservan y se muestran
-- como "En camino" y "Entregado" en la tienda.
USE luxicalma_db;

ALTER TABLE pedidos
  MODIFY COLUMN estado ENUM(
    'pendiente',
    'pago_en_revision',
    'anticipo_pagado',
    'pagado',
    'en_empaque',
    'enviado',
    'completado',
    'cancelado'
  ) NOT NULL DEFAULT 'pendiente';
