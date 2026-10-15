<?php
/**
 * procesar_login.php
 * -------------------------------------------------------------
 * Recibe el formulario de login.php, busca al usuario POR CORREO
 * (esa es la condición de ingreso) y valida la contraseña.
 * -------------------------------------------------------------
 */
session_start();
require_once '../config/conexion.php';

// Solo procesamos si el formulario se envió con POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$correo = trim($_POST['correo'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';

if ($correo === '' || $contrasena === '') {
    header('Location: login.php?error=' . urlencode('Completa correo y contraseña.'));
    exit;
}

// -------- CONDICIONAL: se busca al usuario por su correo electrónico --------
$sentencia = $conexion->prepare("SELECT * FROM usuarios WHERE correo = :correo LIMIT 1");
$sentencia->execute([':correo' => $correo]);
$usuario = $sentencia->fetch(PDO::FETCH_ASSOC);

if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {

    // Credenciales correctas: se guarda la sesión
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nombres'] = $usuario['nombres'];
    $_SESSION['usuario_rol'] = $usuario['rol'];
    $_SESSION['usuario_tipo_cliente'] = $usuario['tipo_cliente'];

    // Por seguridad se cambia el ID de sesión al iniciar sesión (el carrito se conserva)
    session_regenerate_id(true);

    // Se guarda el registro del ingreso en la tabla "sesiones"
    $registrar = $conexion->prepare("INSERT INTO sesiones (usuario_id) VALUES (:id)");
    $registrar->execute([':id' => $usuario['id']]);

    // Cada rol va a una página distinta
    switch ($usuario['rol']) {
        case 'administrador':
            header('Location: panel_productos.php');
            break;
        case 'visualizador':
            header('Location: panel_visualizador.php');
            break;
        default:
            // Si venía del carrito/checkout, vuelve ahí
            $destinos_validos = ['checkout.php', 'mis_pedidos.php'];
            $destino = $_SESSION['volver_a'] ?? '';
            unset($_SESSION['volver_a']);
            header('Location: ' . (in_array($destino, $destinos_validos, true) ? $destino : 'inicio.php'));
    }
    exit;

} else {
    header('Location: login.php?error=' . urlencode('Correo o contraseña incorrectos.'));
    exit;
}
