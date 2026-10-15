-- ============================================================
-- migracion_fase04b.sql
-- Amplía la base de datos para que Luxicalma venda velas junto
-- con osos, flores y ramos, con categorías reales (administrables),
-- colecciones, kits, favoritos, cupones y banners del inicio.
-- Cárgalo en phpMyAdmin → luxicalma_db → pestaña SQL (pega todo
-- el contenido) DESPUÉS de tener ya importado luxicalma_db.sql.
-- ============================================================

USE luxicalma_db;

-- ------------------------------------------------------------
-- Categorías (antes era un ENUM fijo; ahora las administra Dailyn)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  grupo ENUM('velas', 'osos', 'flores', 'ramos', 'religiosos') NOT NULL,
  imagen_icono VARCHAR(255), -- ícono circular que aparece en el inicio
  orden INT NOT NULL DEFAULT 0,
  activa BOOLEAN NOT NULL DEFAULT TRUE
);

INSERT INTO categorias (nombre, grupo, orden) VALUES
('Velas religiosas', 'velas', 1),
('Velas navideñas', 'velas', 2),
('Velas amor y amistad', 'velas', 3),
('Velas baby shower', 'velas', 4),
('Velas aromáticas', 'velas', 5),
('Velas decorativas', 'velas', 6),
('Ramos', 'ramos', 7),
('Teddys', 'osos', 8),
('Flores', 'flores', 9);

-- ------------------------------------------------------------
-- Productos: se agregan los atributos propios de una vela
-- ------------------------------------------------------------
ALTER TABLE productos
  ADD COLUMN categoria_id INT DEFAULT NULL AFTER categoria,
  ADD COLUMN aroma VARCHAR(100) DEFAULT NULL,
  ADD COLUMN tamano ENUM('pequeña', 'mediana', 'grande') DEFAULT NULL,
  ADD COLUMN duracion_horas INT DEFAULT NULL,
  ADD COLUMN tipo_cera VARCHAR(100) DEFAULT NULL,
  ADD COLUMN precio_anterior DECIMAL(10,2) DEFAULT NULL, -- para mostrar tachado si está en oferta
  ADD COLUMN es_personalizable BOOLEAN NOT NULL DEFAULT FALSE,
  ADD COLUMN es_nuevo BOOLEAN NOT NULL DEFAULT TRUE,
  ADD CONSTRAINT fk_producto_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id);

-- La columna vieja "categoria" (ENUM) se deja por ahora sin usar,
-- para no perder los productos de ejemplo que ya tengas cargados;
-- de aquí en adelante se usa "categoria_id".

-- ------------------------------------------------------------
-- Colecciones temáticas (San Valentín, Navidad, Amor y amistad...)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS colecciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  descripcion TEXT,
  imagen_banner VARCHAR(255),
  fecha_inicio DATE,
  fecha_fin DATE,
  activa BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS coleccion_producto (
  coleccion_id INT NOT NULL,
  producto_id INT NOT NULL,
  PRIMARY KEY (coleccion_id, producto_id),
  FOREIGN KEY (coleccion_id) REFERENCES colecciones(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Kits y combos (varios productos empaquetados con un precio especial)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS kits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  descripcion TEXT,
  precio_kit DECIMAL(10,2) NOT NULL,
  foto VARCHAR(255),
  activo BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS kit_producto (
  kit_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL DEFAULT 1,
  PRIMARY KEY (kit_id, producto_id),
  FOREIGN KEY (kit_id) REFERENCES kits(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Favoritos (corazón de "guardar para después")
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS favoritos (
  usuario_id INT NOT NULL,
  producto_id INT NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id, producto_id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Cupones de descuento
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cupones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(50) NOT NULL UNIQUE,
  tipo_descuento ENUM('porcentaje', 'monto_fijo') NOT NULL,
  valor DECIMAL(10,2) NOT NULL,
  fecha_inicio DATE,
  fecha_fin DATE,
  usos_maximos INT DEFAULT NULL,
  usos_actuales INT NOT NULL DEFAULT 0,
  activo BOOLEAN NOT NULL DEFAULT TRUE
);

-- ------------------------------------------------------------
-- Banners del carrusel de inicio (promociones, lanzamientos, temáticas)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS banners (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(150) NOT NULL,
  imagen VARCHAR(255) NOT NULL,
  enlace VARCHAR(255),
  orden INT NOT NULL DEFAULT 0,
  activo BOOLEAN NOT NULL DEFAULT TRUE
);

-- ------------------------------------------------------------
-- Direcciones guardadas del cliente (para el checkout futuro)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS direcciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  direccion VARCHAR(255) NOT NULL,
  ciudad VARCHAR(100),
  departamento VARCHAR(100),
  referencia VARCHAR(255),
  predeterminada BOOLEAN NOT NULL DEFAULT FALSE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
