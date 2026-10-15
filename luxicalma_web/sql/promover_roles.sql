-- ============================================================
-- promover_roles.sql
-- Úsalo DESPUÉS de que la persona ya se haya registrado normalmente
-- desde registro.php con su propio correo y contraseña.
--
-- Cómo usarlo en phpMyAdmin:
-- 1. Entra a la base de datos luxicalma_db → pestaña "SQL".
-- 2. Pega SOLO la línea que necesites (cambia el correo si hace falta).
-- 3. Dale "Continuar / Go".
-- ============================================================

-- Ascender a Dailyn a administradora (después de que ella se registre):
UPDATE usuarios SET rol = 'administrador' WHERE correo = 'lucupaola@gmail.com';

-- Ascender a cada empleada a visualizadora (después de que se registren):
UPDATE usuarios SET rol = 'visualizador' WHERE correo = 'karenyulimerxhan@gmail.com';
UPDATE usuarios SET rol = 'visualizador' WHERE correo = '24sofiahernandez@gmail.com';
UPDATE usuarios SET rol = 'visualizador' WHERE correo = 'palominovalentina316@gmail.com';
UPDATE usuarios SET rol = 'visualizador' WHERE correo = 'mairaquiquive@gmail.com';
