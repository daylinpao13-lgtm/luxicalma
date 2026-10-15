<?php
$titulo_pagina = 'Iniciar sesión';
$css_pagina = 'login.css';
require 'includes/header.php';
?>

<section class="contenedor caja-formulario">
    <h1>Iniciar sesión</h1>

    <?php if (isset($_GET['error'])): ?>
        <p class="mensaje-error"><?php echo htmlspecialchars($_GET['error']); ?></p>
    <?php endif; ?>

    <form action="procesar_login.php" method="POST" class="formulario">
        <label for="correo">Correo electrónico</label>
        <input type="email" id="correo" name="correo" required>

        <label for="contrasena">Contraseña</label>
        <div class="campo-contrasena">
            <input type="password" id="contrasena" name="contrasena" required>
            <button type="button" class="boton-ver-clave" data-objetivo="contrasena" aria-label="Mostrar contraseña"></button>
        </div>

        <button type="submit" class="boton-negro">Entrar</button>
    </form>

    <p class="enlace-secundario">
        ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
    </p>
</section>

<script src="../assets/js/validaciones.js"></script>
<?php require 'includes/footer.php'; ?>
