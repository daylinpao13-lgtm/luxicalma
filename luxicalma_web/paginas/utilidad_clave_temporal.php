<?php
/**
 * utilidad_clave_temporal.php
 * -------------------------------------------------------------
 * SOLO PARA USO LOCAL Y TEMPORAL.
 * Las cuentas de Dailyn y las empleadas se crearon desde el script
 * SQL con una contraseña de relleno en texto plano, que el sistema
 * de login no puede leer (el login necesita una contraseña
 * encriptada). Esta página te deja ponerle una contraseña real y
 * encriptada a cualquier cuenta, escribiendo su correo.
 *
 * IMPORTANTE: esta página no le pide nada a nadie para poder
 * usarla (no tiene "login" de por medio), así que cuando ya hayas
 * puesto las contraseñas de todo tu equipo, BÓRRALA de la carpeta
 * paginas/ para que nadie más la pueda usar.
 * -------------------------------------------------------------
 */
require_once '../config/conexion.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $nueva_contrasena = $_POST['nueva_contrasena'] ?? '';

    if ($correo === '' || $nueva_contrasena === '') {
        $mensaje = '<p class="mensaje-error">Completa correo y nueva contraseña.</p>';
    } else {
        $verificar = $conexion->prepare("SELECT id FROM usuarios WHERE correo = :correo");
        $verificar->execute([':correo' => $correo]);
        $usuario = $verificar->fetch();

        if (!$usuario) {
            $mensaje = '<p class="mensaje-error">No existe ninguna cuenta con ese correo.</p>';
        } else {
            $hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
            $actualizar = $conexion->prepare("UPDATE usuarios SET contrasena = :hash WHERE correo = :correo");
            $actualizar->execute([':hash' => $hash, ':correo' => $correo]);
            $mensaje = '<p class="mensaje-exito">Listo, la contraseña de ' . htmlspecialchars($correo) . ' ya quedó actualizada. Ya puedes iniciar sesión con ella.</p>';
        }
    }
}

$titulo_pagina = 'Utilidad temporal';
$css_pagina = 'login.css';
require 'includes/header.php';
?>

<section class="contenedor caja-formulario">
    <h1>Asignar contraseña</h1>
    <p style="margin-bottom:16px; color:var(--cafe-suave);">
        Úsala solo para las cuentas precargadas (Dailyn y las empleadas).
        Después de usarla, borra este archivo por seguridad.
    </p>

    <?php echo $mensaje; ?>

    <form method="POST" class="formulario">
        <label for="correo">Correo de la cuenta</label>
        <input type="email" id="correo" name="correo" required>

        <label for="nueva_contrasena">Nueva contraseña</label>
        <div class="campo-contrasena">
            <input type="text" id="nueva_contrasena" name="nueva_contrasena" required minlength="8">
        </div>

        <button type="submit" class="boton-negro">Actualizar contraseña</button>
    </form>
</section>

<?php require 'includes/footer.php'; ?>
