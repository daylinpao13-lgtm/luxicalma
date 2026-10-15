-- ============================================================
-- Base de datos: luxicalma_db
-- Proyecto: Luxicalma - tienda web
--
-- ORDEN DE INSTALACIÓN: 1) este archivo, 2) migracion_fase04b.sql,
-- 3) migracion_fase05_pedidos_velas.sql, 4) velas_ejemplo.sql (opcional).
--
-- NOTA: a partir de la Fase 04b existe además el archivo
-- sql/migracion_fase04b.sql, que AGREGA categorías reales,
-- atributos de velas, colecciones, kits, favoritos, cupones y
-- banners. Si vas a instalar el proyecto desde cero, importa
-- primero este archivo y después el de la migración.
-- ============================================================

DROP DATABASE IF EXISTS luxicalma_db;

CREATE DATABASE luxicalma_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE luxicalma_db;

-- ------------------------------------------------------------
-- Usuarios: administrador, cliente, visualizador
-- ------------------------------------------------------------
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombres VARCHAR(100) NOT NULL,
  apellidos VARCHAR(100) NOT NULL,
  fecha_nacimiento DATE DEFAULT NULL,
  correo VARCHAR(150) NOT NULL UNIQUE,
  contrasena VARCHAR(255) NOT NULL, -- se guarda siempre encriptada
  rol ENUM('administrador', 'cliente', 'visualizador') NOT NULL DEFAULT 'cliente',
  tipo_cliente ENUM('particular', 'empresa') DEFAULT NULL,
  pais VARCHAR(100) DEFAULT NULL,
  departamento VARCHAR(100) DEFAULT NULL,
  ciudad VARCHAR(100) DEFAULT NULL,
  codigo_acceso VARCHAR(50) DEFAULT NULL, -- solo para visualizadores
  puntos_fidelidad INT NOT NULL DEFAULT 0,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Productos
-- ------------------------------------------------------------
CREATE TABLE productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  descripcion TEXT,
  categoria ENUM('osos', 'flores', 'ramos', 'religiosos') NOT NULL,
  precio_detal DECIMAL(10,2) NOT NULL,
  precio_mayorista DECIMAL(10,2) NOT NULL,
  colores_disponibles VARCHAR(255),
  foto VARCHAR(255), -- ruta de la imagen en /assets/img
  stock INT NOT NULL DEFAULT 0,
  destacado_semana BOOLEAN NOT NULL DEFAULT FALSE,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Reseñas de productos
-- ------------------------------------------------------------
CREATE TABLE resenas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  producto_id INT NOT NULL,
  usuario_id INT NOT NULL,
  comentario TEXT,
  calificacion TINYINT NOT NULL CHECK (calificacion BETWEEN 1 AND 5),
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_resena_producto_usuario (producto_id, usuario_id),
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE resena_respuestas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  resena_id INT NOT NULL UNIQUE,
  administrador_id INT NOT NULL,
  respuesta TEXT NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (resena_id) REFERENCES resenas(id) ON DELETE CASCADE,
  FOREIGN KEY (administrador_id) REFERENCES usuarios(id)
);

-- ------------------------------------------------------------
-- Pedidos
-- ------------------------------------------------------------
CREATE TABLE pedidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado ENUM('pendiente', 'pago_en_revision', 'anticipo_pagado', 'pagado', 'en_empaque', 'enviado', 'completado', 'cancelado') NOT NULL DEFAULT 'pendiente',
  metodo_pago VARCHAR(30) DEFAULT NULL,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  anticipo_pagado DECIMAL(10,2) NOT NULL DEFAULT 0,
  fecha_entrega_estimada DATE DEFAULT NULL,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Detalle de cada pedido (qué productos y cantidades)
CREATE TABLE pedido_detalle (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL DEFAULT 1,
  color VARCHAR(100),
  aroma VARCHAR(100) DEFAULT NULL,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- ------------------------------------------------------------
-- Cotizaciones de empresas (venta al por mayor)
-- ------------------------------------------------------------
CREATE TABLE cotizaciones_empresa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  nombre_empresa VARCHAR(150) NOT NULL,
  ciudad VARCHAR(100),
  cantidad INT NOT NULL,
  tematica VARCHAR(150),
  ocasion VARCHAR(150),
  estado ENUM('nueva', 'en_proceso', 'respondida', 'cerrada') NOT NULL DEFAULT 'nueva',
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Asesorías (chat/consultas con visualizadores en horario 8am-6pm)
-- ------------------------------------------------------------
CREATE TABLE asesorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  visualizador_id INT DEFAULT NULL,
  mensaje TEXT NOT NULL,
  respuesta TEXT,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (visualizador_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- Registro de sesiones (para estadísticas de ingreso)
-- ------------------------------------------------------------
CREATE TABLE sesiones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  fecha_ingreso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Promociones (fechas especiales del año)
-- ------------------------------------------------------------
CREATE TABLE promociones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  descuento DECIMAL(5,2) NOT NULL,
  descripcion TEXT
);

-- ------------------------------------------------------------
-- Cuentas de Dailyn (administradora) y las empleadas (visualizadoras)
-- ------------------------------------------------------------
-- NO se crean aquí con una contraseña de relleno. Cada una debe
-- registrarse normalmente desde registro.php, escogiendo ella misma
-- su propia contraseña (así queda guardada correctamente encriptada).
--
-- Después de que cada una se registre, hay que entrar UNA sola vez
-- a phpMyAdmin y cambiarle el campo "rol" a 'administrador' (Dailyn)
-- o a 'visualizador' (las 4 empleadas). El detalle de cómo hacerlo
-- está explicado en fase_03_conexion_backend.md.
