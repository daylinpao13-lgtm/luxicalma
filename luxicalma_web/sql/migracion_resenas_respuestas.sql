-- Ejecutar una sola vez sobre luxicalma_db.
-- Conserva las reseñas existentes, limita a una reseña por cliente/producto
-- y almacena la respuesta oficial por separado.
USE luxicalma_db;

SET @existen_duplicados_resena = (
  SELECT COUNT(*)
  FROM (
    SELECT producto_id, usuario_id
    FROM resenas
    GROUP BY producto_id, usuario_id
    HAVING COUNT(*) > 1
  ) AS pares_duplicados
);

SET @existe_indice_resena = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'resenas'
    AND INDEX_NAME = 'uq_resena_producto_usuario'
);

SET @sql_indice_resena = IF(
  @existe_indice_resena > 0 OR @existen_duplicados_resena > 0,
  'SELECT 1',
  'ALTER TABLE resenas ADD UNIQUE KEY uq_resena_producto_usuario (producto_id, usuario_id)'
);

SELECT @existen_duplicados_resena AS pares_duplicados_conservados;

PREPARE sentencia_indice_resena FROM @sql_indice_resena;
EXECUTE sentencia_indice_resena;
DEALLOCATE PREPARE sentencia_indice_resena;

CREATE TABLE IF NOT EXISTS resena_respuestas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  resena_id INT NOT NULL UNIQUE,
  administrador_id INT NOT NULL,
  respuesta TEXT NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_resena_respuesta_resena
    FOREIGN KEY (resena_id) REFERENCES resenas(id) ON DELETE CASCADE,
  CONSTRAINT fk_resena_respuesta_administrador
    FOREIGN KEY (administrador_id) REFERENCES usuarios(id)
);
