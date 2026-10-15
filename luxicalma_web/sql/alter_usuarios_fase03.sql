-- ============================================================
-- SOLO USAR ESTE ARCHIVO SI YA HABÍAS IMPORTADO luxicalma_db.sql
-- ANTES DE LA FASE 03. Actualiza la tabla usuarios a los nuevos
-- campos del formulario de registro (nombres, apellidos y fecha
-- de nacimiento en vez de nombre y edad).
--
-- Si todavía NO habías importado nada, no uses este archivo:
-- simplemente importa el luxicalma_db.sql actualizado y ya.
-- ============================================================

USE luxicalma_db;

ALTER TABLE usuarios
  CHANGE COLUMN nombre nombres VARCHAR(100) NOT NULL,
  ADD COLUMN apellidos VARCHAR(100) NOT NULL AFTER nombres,
  ADD COLUMN fecha_nacimiento DATE DEFAULT NULL AFTER apellidos,
  DROP COLUMN edad;
