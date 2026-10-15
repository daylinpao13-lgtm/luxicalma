<?php
/**
 * procesar_registro.php
 * -------------------------------------------------------------
 * Recibe el formulario de registro.php, valida todo de nuevo en
 * el servidor (nunca hay que confiar solo en la validación del
 * navegador) y guarda al nuevo usuario en la tabla "usuarios".
 * -------------------------------------------------------------
 */
require_once '../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registro.php');
    exit;
}

$nombres = trim($_POST['nombres'] ?? '');
$apellidos = trim($_POST['apellidos'] ?? '');
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
$correo = trim($_POST['correo'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';
$confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';
$tipo_cliente = $_POST['tipo_cliente'] ?? '';

// 1. Que ningún campo obligatorio venga vacío
if (!$nombres || !$apellidos || !$fecha_nacimiento || !$correo || !$contrasena || !$tipo_cliente) {
    header('Location: registro.php?error=' . urlencode('Completa todos los campos.'));
    exit;
}

// 2. Nombres y apellidos: solo letras y espacios (nada de números ni símbolos)
$patron_solo_letras = '/^[A-Za-zÀ-ÿ\s]+$/u';
if (!preg_match($patron_solo_letras, $nombres)) {
    header('Location: registro.php?error=' . urlencode(
        'Los nombres solo pueden tener letras (nada de números ni símbolos).'
    ));
    exit;
}
if (!preg_match($patron_solo_letras, $apellidos)) {
    header('Location: registro.php?error=' . urlencode(
        'Los apellidos solo pueden tener letras (nada de números ni símbolos).'
    ));
    exit;
}

// 3. Correo con formato válido
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header('Location: registro.php?error=' . urlencode('El correo electrónico no es válido.'));
    exit;
}

// 4. Las dos contraseñas deben coincidir
if ($contrasena !== $confirmar_contrasena) {
    header('Location: registro.php?error=' . urlencode('Las contraseñas no coinciden.'));
    exit;
}

// 5. La contraseña debe tener EXACTAMENTE 8 caracteres, con letras, números y símbolos
$patron_contrasena = '/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8}$/';
if (!preg_match($patron_contrasena, $contrasena)) {
    header('Location: registro.php?error=' . urlencode(
        'La contraseña debe tener exactamente 8 caracteres, con letras, números y símbolos (ej: Lux2026*!).'
    ));
    exit;
}

// 6. Tipo de cuenta válido
if (!in_array($tipo_cliente, ['particular', 'empresa'], true)) {
    header('Location: registro.php?error=' . urlencode('Selecciona si eres cliente o empresa.'));
    exit;
}

// 7. Que el correo no esté ya registrado
$verificar = $conexion->prepare("SELECT id FROM usuarios WHERE correo = :correo");
$verificar->execute([':correo' => $correo]);
if ($verificar->fetch()) {
    header('Location: registro.php?error=' . urlencode('Ese correo ya está registrado.'));
    exit;
}

// La contraseña NUNCA se guarda en texto plano, se guarda encriptada
$contrasena_encriptada = password_hash($contrasena, PASSWORD_DEFAULT);

// Guardamos el nuevo usuario con una consulta preparada (evita inyección SQL)
$insertar = $conexion->prepare(
    "INSERT INTO usuarios (nombres, apellidos, fecha_nacimiento, correo, contrasena, rol, tipo_cliente)
     VALUES (:nombres, :apellidos, :fecha_nacimiento, :correo, :contrasena, 'cliente', :tipo_cliente)"
);

$insertar->execute([
    ':nombres' => $nombres,
    ':apellidos' => $apellidos,
    ':fecha_nacimiento' => $fecha_nacimiento,
    ':correo' => $correo,
    ':contrasena' => $contrasena_encriptada,
    ':tipo_cliente' => $tipo_cliente,
]);

// Ya que se creó bien la cuenta, iniciamos sesión automáticamente
// (no tiene sentido pedirle que vuelva a escribir todo en el login)
session_start();
$nuevo_id = $conexion->lastInsertId();

$_SESSION['usuario_id'] = $nuevo_id;
$_SESSION['usuario_nombres'] = $nombres;
$_SESSION['usuario_rol'] = 'cliente';
$_SESSION['usuario_tipo_cliente'] = $tipo_cliente;

// También registramos este primer ingreso en la tabla "sesiones"
$registrar = $conexion->prepare("INSERT INTO sesiones (usuario_id) VALUES (:id)");
$registrar->execute([':id' => $nuevo_id]);

header('Location: inicio.php');
exit;?>
