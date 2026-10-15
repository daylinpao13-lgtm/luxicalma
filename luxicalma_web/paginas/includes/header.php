<?php
// Este header inicia la sesión para saber si hay alguien logueado
// y en qué páginas nos encontramos, para poder armar los links relativos.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/carrito_helper.php';

$hay_sesion = isset($_SESSION['usuario_id']);
$es_administrador = ($_SESSION['usuario_rol'] ?? '') === 'administrador';
$nombre_usuario = $_SESSION['usuario_nombres'] ?? '';
$cantidad_carrito = luxi_carrito_contar();

// Categorías reales para el menú desplegable. Si todavía no has
// corrido sql/migracion_fase04b.sql, esta tabla no existe aún, así
// que lo dejamos vacío en vez de romper la página.
$categorias_menu = [];
try {
    $categorias_menu = $conexion->query(
        "SELECT id, nombre FROM categorias WHERE activa = 1 ORDER BY orden"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categorias_menu = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titulo_pagina) ? luxi_h($titulo_pagina) . ' - Luxicalma' : 'Luxicalma'; ?></title>
    <meta name="csrf-token" content="<?php echo luxi_h(luxi_csrf_token()); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/variables.css?v=20261010-nequi">
    <link rel="stylesheet" href="../assets/css/header_footer.css?v=20261010">
    <?php if (isset($css_pagina)): ?>
        <link rel="stylesheet" href="../assets/css/<?php echo $css_pagina; ?>?v=20261010-nequi">
    <?php endif; ?>
</head>
<body>

<header class="encabezado">
    <div class="barra-anuncio">
        Velas aromáticas para toda ocasión · Envíos a nivel nacional
    </div>

    <div class="barra-superior">
        <div class="columna-izquierda">
            <form action="productos.php" method="GET" class="formulario-busqueda" id="formulario-busqueda">
                <button type="button" class="boton-buscar" id="boton-buscar" aria-label="Abrir buscador" aria-expanded="false" aria-controls="buscador-header">
                    <span aria-hidden="true">⌕</span>
                </button>
                <div class="contenedor-input-busqueda" id="contenedor-input-busqueda">
                    <input
                        type="search"
                        name="q"
                        id="buscador-header"
                        class="buscador"
                        placeholder="¿Qué estás buscando?"
                        autocomplete="off"
                        value="<?php echo htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <button type="button" class="limpiar-busqueda" id="limpiar-busqueda" aria-label="Limpiar búsqueda">×</button>
                </div>
            </form>
        </div>

        <a href="inicio.php" class="logo-luxicalma">
            <img src="../assets/img/logo-luxicalma.png" alt="Logo Luxicalma">
            <span>LUXICALMA</span>
        </a>

        <div class="iconos-usuario">
            <a href="carrito.php" class="enlace-carrito" id="abrir-mini-carrito" aria-label="Abrir carrito">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 7h12l-1 13H7L6 7z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
                <span class="contador-carrito" id="contador-carrito" <?php echo $cantidad_carrito > 0 ? '' : 'hidden'; ?>><?php echo (int) $cantidad_carrito; ?></span>
            </a>
            <?php if ($hay_sesion): ?>
                <span class="saludo-usuario">Hola, <?php echo luxi_h($nombre_usuario); ?></span>
                <?php if ($es_administrador): ?>
                    <a href="panel_productos.php">Administrar productos</a>
                <?php endif; ?>
                <a href="mis_pedidos.php">Mis pedidos</a>
                <a href="logout.php">Cerrar sesión</a>
            <?php else: ?>
                <a href="login.php">Iniciar sesión</a>
                <a href="registro.php">Registrarme</a>
            <?php endif; ?>
        </div>
    </div>

    <nav class="menu-principal">
        <ul>
            <li><a href="inicio.php">Inicio</a></li>
            <li class="tiene-submenu">
                <button type="button" class="categoria-menu-toggle" aria-expanded="false" aria-controls="menu-categorias">
                    Categorías
                    <svg class="flecha" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <path d="m3.5 6 4.5 4 4.5-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <ul class="submenu" id="menu-categorias">
                    <li><a href="productos.php">Ver todas</a></li>
                    <?php if (count($categorias_menu) === 0): ?>
                        <li><span class="categoria-vacia">No hay categorías disponibles</span></li>
                    <?php else: ?>
                        <?php foreach ($categorias_menu as $cat): ?>
                            <li><a href="productos.php?categoria_id=<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></a></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </li>
            <li><a href="productos.php?filtro=ofertas" class="enlace-dorado">Ofertas</a></li>
            <li><a href="productos.php?filtro=mas_vendidos" class="enlace-dorado">Más vendidos</a></li>
            <li><a href="productos.php?filtro=tematicos" class="enlace-azul">Temáticos</a></li>
        </ul>
    </nav>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const botonCategorias = document.querySelector('.categoria-menu-toggle');
    const menuCategorias = document.getElementById('menu-categorias');
    if (botonCategorias && menuCategorias) {
        function cerrarCategorias() {
            menuCategorias.classList.remove('abierto');
            botonCategorias.setAttribute('aria-expanded', 'false');
        }

        botonCategorias.addEventListener('click', function () {
            const abrir = !menuCategorias.classList.contains('abierto');
            menuCategorias.classList.toggle('abierto', abrir);
            botonCategorias.setAttribute('aria-expanded', abrir ? 'true' : 'false');
        });
        menuCategorias.addEventListener('click', function (evento) {
            if (evento.target.closest('a')) cerrarCategorias();
        });
        document.addEventListener('click', function (evento) {
            if (!evento.target.closest('.tiene-submenu')) {
                cerrarCategorias();
                botonCategorias.blur();
            }
        });
        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape') {
                cerrarCategorias();
                botonCategorias.focus();
            }
        });
    }

    const botonBuscar = document.getElementById('boton-buscar');
    const contenedorBusqueda = document.getElementById('contenedor-input-busqueda');
    const buscador = document.getElementById('buscador-header');
    const limpiarBusqueda = document.getElementById('limpiar-busqueda');

    if (!botonBuscar || !contenedorBusqueda || !buscador) return;

    function abrirBuscador() {
        contenedorBusqueda.classList.add('visible');
        botonBuscar.setAttribute('aria-expanded', 'true');
        botonBuscar.setAttribute('aria-label', 'Cerrar buscador');
        setTimeout(function () { buscador.focus(); }, 120);
    }

    function cerrarBuscador() {
        if (buscador.value.trim() !== '') return;
        contenedorBusqueda.classList.remove('visible');
        botonBuscar.setAttribute('aria-expanded', 'false');
        botonBuscar.setAttribute('aria-label', 'Abrir buscador');
    }

    botonBuscar.addEventListener('click', function () {
        if (contenedorBusqueda.classList.contains('visible')) {
            cerrarBuscador();
        } else {
            abrirBuscador();
        }
    });

    if (limpiarBusqueda) {
        limpiarBusqueda.addEventListener('click', function () {
            buscador.value = '';
            buscador.focus();
        });
    }

    buscador.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            buscador.value = '';
            cerrarBuscador();
        }
    });
});
</script>

</header>
