<?php
/**
 * carrito_helper.php
 * -------------------------------------------------------------
 * Funciones compartidas por todo el flujo de compra (carrito,
 * checkout y pedidos). Así la lógica vive en UN solo lugar.
 *
 * Idea clave: en la sesión el carrito solo guarda
 *   producto_id + color + cantidad.
 * El nombre, la foto, el precio y el stock se vuelven a leer de la
 * base de datos cada vez, así nunca se muestra (ni se cobra) un
 * precio viejo, aunque la persona inicie sesión como empresa
 * después de haber agregado productos.
 * -------------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regla del negocio: más de 5 unidades = 10 días de anticipación + anticipo
const LUXI_UMBRAL_ANTICIPO = 5;
const LUXI_DIAS_ANTICIPO = 10;

function luxi_es_empresa(): bool
{
    return ($_SESSION['usuario_tipo_cliente'] ?? '') === 'empresa';
}

function luxi_precio_producto(array $producto): float
{
    return (float) (luxi_es_empresa() ? $producto['precio_mayorista'] : $producto['precio_detal']);
}

function luxi_formato_precio($valor): string
{
    return '$' . number_format((float) $valor, 0, ',', '.');
}

/** Resuelve rutas de fotos guardadas como productos/nombre, con o sin extensión. */
function luxi_foto(?string $foto, ?string $nombre = null): string
{
    $directorio_imagenes = __DIR__ . '/../../assets/img/';
    $normalizar = static function (string $texto): string {
        $texto = strtolower(strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
        ]));
        return preg_replace('/[^a-z0-9]/', '', $texto);
    };
    $resolver = static function (string $ruta) use ($directorio_imagenes, $normalizar): ?string {
        $ruta = str_replace('\\', '/', trim($ruta));
        $ruta = preg_replace('~^(?:\./)+~', '', $ruta);
        if (strpos($ruta, 'assets/img/') === 0) {
            $ruta = substr($ruta, strlen('assets/img/'));
        }
        $partes = array_values(array_filter(explode('/', $ruta), static function ($parte) {
            return $parte !== '' && $parte !== '.';
        }));
        if (!$partes || in_array('..', $partes, true)) {
            return null;
        }

        $relativa = implode('/', $partes);
        $archivo = $directorio_imagenes . implode(DIRECTORY_SEPARATOR, $partes);
        if (is_file($archivo)) {
            return $relativa;
        }

        $extension = pathinfo($archivo, PATHINFO_EXTENSION);
        if ($extension === '') {
            foreach (['jpg', 'jpeg', 'png', 'webp', 'svg'] as $posible_extension) {
                if (is_file($archivo . '.' . $posible_extension)) {
                    return $relativa . '.' . $posible_extension;
                }
            }
        }

        $directorio = dirname($archivo);
        $nombre_base = pathinfo($archivo, PATHINFO_FILENAME);
        if (is_dir($directorio) && $nombre_base !== '') {
            foreach (scandir($directorio) as $archivo_candidato) {
                $ruta_candidata = $directorio . DIRECTORY_SEPARATOR . $archivo_candidato;
                if (is_file($ruta_candidata)
                    && $normalizar(pathinfo($archivo_candidato, PATHINFO_FILENAME)) === $normalizar($nombre_base)) {
                    return implode('/', array_merge(array_slice($partes, 0, -1), [$archivo_candidato]));
                }
            }
        }
        return null;
    };

    $foto = trim((string) $foto);
    $foto_resuelta = $foto !== '' ? $resolver($foto) : null;
    if ($foto_resuelta === null && $foto !== '') {
        $alias_rutas = [
            'productos/teddy-nube' => 'productos/teddy-nude.jpg',
        ];
        $foto_resuelta = $alias_rutas[strtolower(str_replace('\\', '/', $foto))] ?? null;
        if ($foto_resuelta !== null) {
            $foto_resuelta = $resolver($foto_resuelta);
        }
    }

    $nombre_normalizado = $normalizar((string) $nombre);
    $fotos_por_nombre = [
        'teddynube' => 'productos/teddy-nude.jpg',
        'teddynude' => 'productos/teddy-nude.jpg',
        'teddyclasico' => 'productos/teddy-clásico.jpg',
        'ospequeno' => 'productos/oso-pequeño.jpg',
        'ositopequeno' => 'productos/oso-pequeño.jpg',
        'dulcefe' => 'productos/dulce-fe.jpg',
        'dulceabrazo' => 'productos/dulce-abrazo.jpg',
        'nochebuena' => 'productos/velas-navideñas.jpg',
        'navidena' => 'productos/velas-navideñas.jpg',
        'navideno' => 'productos/velas-navideñas.jpg',
        'margaritaglow' => 'productos/margarita-glow.svg',
        'peoniabloom' => 'productos/peonia-bloom.svg',
        'florencanto' => 'productos/flor-encanto.svg',
    ];
    if ($foto_resuelta === null) {
        foreach ($fotos_por_nombre as $coincidencia => $foto_sugerida) {
            if (strpos($nombre_normalizado, $coincidencia) !== false) {
                $foto_resuelta = $resolver($foto_sugerida);
                if ($foto_resuelta !== null) {
                    break;
                }
            }
        }
    }

    $foto_resuelta = $foto_resuelta ?? 'producto-generico.svg';
    return '../assets/img/' . implode('/', array_map('rawurlencode', explode('/', $foto_resuelta)));
}

function luxi_h($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function luxi_opciones(): array
{
    static $opciones = null;
    if ($opciones === null) {
        $opciones = require __DIR__ . '/../../config/opciones_velas.php';
    }
    return $opciones;
}

function luxi_colores(?string $colores_disponibles = null): array
{
    $colores = array_values(array_filter(array_map('trim', explode(',', (string) $colores_disponibles))));
    return $colores ?: array_keys(luxi_opciones()['colores']);
}

function luxi_aromas(?string $aromas_disponibles = null): array
{
    $aromas = array_values(array_filter(array_map('trim', explode(',', (string) $aromas_disponibles))));
    return $aromas ?: array_values(luxi_opciones()['aromas']);
}

function luxi_color_hex(string $color): string
{
    $colores = luxi_opciones()['colores'];
    if (isset($colores[$color])) {
        return $colores[$color];
    }
    if (preg_match('/^#[0-9a-f]{6}$/i', $color)) {
        return $color;
    }

    $nombre = strtolower(strtr($color, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
    ]));
    $colores_comunes = [
        'blanco' => '#FFFFFF', 'negro' => '#292524', 'rojo' => '#D94A4A',
        'naranja' => '#F28C38', 'amarillo' => '#F5D547', 'verde' => '#54A66B',
        'azul' => '#4287D6', 'morado' => '#A58AD8', 'violeta' => '#A58AD8',
        'rosado' => '#F2A7C3', 'rosa' => '#F2A7C3', 'lila' => '#C7A7E8',
        'cafe' => '#8B5E3C', 'beige' => '#DCC7A1', 'dorado' => '#D4AF37',
        'gris' => '#9A9A9A', 'celeste' => '#8FD3E8',
    ];
    return $colores_comunes[$nombre] ?? '#DDD';
}

function luxi_variante(?string $aroma, ?string $color): string
{
    $partes = array_filter(
        [trim((string) $aroma), trim((string) $color)],
        static function ($valor) {
            return $valor !== '' && $valor !== 'Único';
        }
    );
    return implode(' · ', $partes);
}

function luxi_clave_carrito(int $producto_id, string $color, string $aroma): string
{
    return $producto_id . '_' . hash('sha256', $color . "\0" . $aroma);
}

// ---------------------------------------------------------------
// Protección CSRF: evita que otra página envíe formularios en nombre
// de quien está logueado.
// ---------------------------------------------------------------
function luxi_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function luxi_csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . luxi_h(luxi_csrf_token()) . '">';
}

function luxi_csrf_valido(): bool
{
    $enviado = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($enviado) && $enviado !== ''
        && hash_equals($_SESSION['csrf_token'] ?? '', $enviado);
}

// ---------------------------------------------------------------
// Respuestas: JSON si la petición viene del JavaScript de la tienda,
// redirección normal si viene de un formulario sin JS.
// ---------------------------------------------------------------
function luxi_es_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

function luxi_carrito_contar(): int
{
    $n = 0;
    foreach ($_SESSION['carrito'] ?? [] as $linea) {
        $n += (int) ($linea['cantidad'] ?? 0);
    }
    return $n;
}

function luxi_responder(bool $ok, string $mensaje, string $redirigir_a, array $extra = []): void
{
    if (luxi_es_ajax()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($ok ? 200 : 422);
        echo json_encode(
            array_merge(['ok' => $ok, 'mensaje' => $mensaje, 'cantidad_total' => luxi_carrito_contar()], $extra),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
    $separador = strpos($redirigir_a, '?') === false ? '?' : '&';
    header('Location: ' . $redirigir_a . $separador . ($ok ? 'ok=' : 'error=') . urlencode($mensaje));
    exit;
}

// ---------------------------------------------------------------
// Carrito "detallado": junta lo que hay en la sesión con los datos
// reales de la base de datos.
// ---------------------------------------------------------------
function luxi_carrito_detallado(PDO $conexion): array
{
    $resultado = ['items' => [], 'total' => 0.0, 'unidades' => 0, 'avisos' => [], 'requiere_anticipo' => false];
    $carrito = $_SESSION['carrito'] ?? [];
    if (!$carrito) {
        return $resultado;
    }

    $ids = array_values(array_unique(array_map(fn($l) => (int) ($l['producto_id'] ?? 0), $carrito)));
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $consulta = $conexion->prepare("SELECT * FROM productos WHERE id IN ($marcas)");
    $consulta->execute($ids);
    $productos = [];
    foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $productos[(int) $p['id']] = $p;
    }

    foreach ($carrito as $clave => $linea) {
        $p = $productos[(int) ($linea['producto_id'] ?? 0)] ?? null;

        if (!$p || (int) $p['stock'] <= 0) {
            $nombre = $p['nombre'] ?? 'Un producto';
            $resultado['avisos'][] = "$nombre ya no está disponible y se quitó de tu carrito.";
            unset($_SESSION['carrito'][$clave]);
            continue;
        }

        $cantidad = max(1, (int) $linea['cantidad']);
        if ($cantidad > (int) $p['stock']) {
            $cantidad = (int) $p['stock'];
            $_SESSION['carrito'][$clave]['cantidad'] = $cantidad;
            $resultado['avisos'][] = "Ajustamos la cantidad de {$p['nombre']} a $cantidad (es lo que tenemos disponible).";
        }

        $precio = luxi_precio_producto($p);
        $subtotal = $precio * $cantidad;
        $aromas_disponibles = luxi_aromas($p['aroma'] ?? null);
        $aroma = trim((string) ($linea['aroma'] ?? ''));
        if ($aroma === '' && $aromas_disponibles) {
            $aroma = $aromas_disponibles[0];
            $_SESSION['carrito'][$clave]['aroma'] = $aroma;
        }
        $colores_disponibles = luxi_colores($p['colores_disponibles'] ?? null);
        $color = trim((string) ($linea['color'] ?? ''));
        if ($color === '' && $colores_disponibles) {
            $color = $colores_disponibles[0];
            $_SESSION['carrito'][$clave]['color'] = $color;
        }

        $resultado['items'][$clave] = [
            'clave' => $clave,
            'producto_id' => (int) $p['id'],
            'nombre' => $p['nombre'],
            'foto' => luxi_foto($p['foto'], $p['nombre']),
            'color' => $color,
            'aroma' => $aroma !== '' ? $aroma : null,
            'precio' => $precio,
            'cantidad' => $cantidad,
            'stock' => (int) $p['stock'],
            'subtotal' => $subtotal,
        ];
        $resultado['total'] += $subtotal;
        $resultado['unidades'] += $cantidad;
    }

    $resultado['requiere_anticipo'] = $resultado['unidades'] > LUXI_UMBRAL_ANTICIPO;
    return $resultado;
}
