<?php
$titulo_pagina = 'Registro';
$css_pagina = 'registro.css';
require 'includes/header.php';
?>

<section class="contenedor caja-formulario">
    <h1>Crear cuenta</h1>

    <?php if (isset($_GET['error'])): ?>
        <p class="mensaje-error"><?php echo htmlspecialchars($_GET['error']); ?></p>
    <?php endif; ?>

    <form action="procesar_registro.php" method="POST" class="formulario" id="form-registro">
        <label for="nombres">Nombres</label>
        <input type="text" id="nombres" name="nombres" required
               pattern="[A-Za-zÀ-ÿ\s]+"
               title="Solo letras y espacios, sin números ni símbolos">
        <small class="texto-ayuda" id="ayuda-nombres">Solo letras (sin números ni símbolos).</small>

        <label for="apellidos">Apellidos</label>
        <input type="text" id="apellidos" name="apellidos" required
               pattern="[A-Za-zÀ-ÿ\s]+"
               title="Solo letras y espacios, sin números ni símbolos">
        <small class="texto-ayuda" id="ayuda-apellidos">Solo letras (sin números ni símbolos).</small>

        <label for="fecha_nacimiento">Fecha de nacimiento</label>
        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required>

        <label for="correo">Correo electrónico</label>
        <input type="email" id="correo" name="correo" required>

        <label for="contrasena">Crear contraseña</label>
        <div class="campo-contrasena">
            <input type="password" id="contrasena" name="contrasena"
                   minlength="8" maxlength="8" required
                   title="Exactamente 8 caracteres, combinando letras, números y símbolos">
            <button type="button" class="boton-ver-clave" data-objetivo="contrasena" aria-label="Mostrar contraseña"></button>
        </div>

        <label for="confirmar_contrasena">Confirmar contraseña</label>
        <div class="campo-contrasena">
            <input type="password" id="confirmar_contrasena" name="confirmar_contrasena"
                   minlength="8" maxlength="8" required>
            <button type="button" class="boton-ver-clave" data-objetivo="confirmar_contrasena" aria-label="Mostrar contraseña"></button>
        </div>

        <small id="ayuda-contrasena" class="texto-ayuda">
            Debe tener exactamente 8 caracteres, con letras, números y símbolos (ej: Lux2026*!).
        </small>

        <label for="tipo_cliente">Me registro como</label>
        <select id="tipo_cliente" name="tipo_cliente" required>
            <option value="">Selecciona una opción</option>
            <option value="particular">Cliente</option>
            <option value="empresa">Empresa</option>
        </select>

        <button type="submit" class="boton-negro">Registrarme</button>
    </form>

    <p class="enlace-secundario">
        ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
    </p>
</section>

<script src="../assets/js/validaciones.js"></script>
<?php require 'includes/footer.php'; ?>