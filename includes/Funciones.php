<?php
require_once __DIR__ . '/../config/Conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
        'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function texto_post(string $nombre): string
{
    $valor = $_POST[$nombre] ?? '';

    return is_string($valor) ? $valor : '';
}

function password_valido(string $password, string $guardada): bool
{
    $info = password_get_info($guardada);
    if ($info['algo'] !== null) {
        return password_verify($password, $guardada);
    }

    return $guardada !== '' && hash_equals($guardada, $password);
}

function password_en_texto_plano(string $guardada): bool
{
    return password_get_info($guardada)['algo'] === null;
}

function token_csrf(): string
{
    if (empty($_SESSION['token_csrf'])) {
        $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['token_csrf'];
}

function csrf_valido(): bool
{
    $token = $_POST['token_csrf'] ?? '';

    return is_string($token)
        && isset($_SESSION['token_csrf'])
        && hash_equals($_SESSION['token_csrf'], $token);
}

function es_admin(): bool
{
    return ($_SESSION['rol'] ?? '') === 'admin';
}

function requiere_admin(): void
{
    if (!es_admin()) {
        header('Location: ../Login.php');
        exit();
    }
}

function requiere_cliente(): void
{
    if (empty($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'cliente') {
        header('Location: Login.php');
        exit();
    }
}

function escapar_imagen($imagen): string
{
    $imagen = trim((string) $imagen);

    if (preg_match('/^https:\/\//i', $imagen)) {
        return $imagen;
    }

    $ruta = str_replace('\\', '/', $imagen);
    if (preg_match('#^uploads/(products|collections|users)/[a-f0-9]{32}\.(jpg|jpeg|png|webp)$#i', $ruta)) {
        return 'assets/' . $ruta;
    }

    $nombre = basename($ruta);
    if ($nombre === '' || !preg_match('/\.(jpg|jpeg|png|webp)$/i', $nombre)) {
        return 'assets/images/hero-bg.jpg';
    }

    return 'assets/images/' . rawurlencode($nombre);
}

function validar_imagen_local(string $imagen): bool
{
    $ruta = str_replace('\\', '/', trim($imagen));
    if (preg_match('#^uploads/(products|collections|users)/[a-f0-9]{32}\.(jpg|jpeg|png|webp)$#i', $ruta)) {
        return is_file(__DIR__ . '/../assets/' . $ruta);
    }

    $nombre = basename($ruta);

    if ($nombre !== $ruta || !preg_match('/\.(jpg|jpeg|png|webp)$/i', $nombre)) {
        return false;
    }

    $carpeta = realpath(__DIR__ . '/../assets/images');
    $archivo = realpath(__DIR__ . '/../assets/images/' . $nombre);

    return $carpeta !== false
        && $archivo !== false
        && strpos($archivo, $carpeta . DIRECTORY_SEPARATOR) === 0;
}

function guardar_imagenes_subidas(string $campo, string $tipo, int $maximo = 3): array
{
    if (!in_array($tipo, ['products', 'collections', 'users'], true)) {
        throw new InvalidArgumentException('El destino de imagen no es válido.');
    }

    $archivos = $_FILES[$campo] ?? null;
    if (!is_array($archivos)
        || !isset($archivos['name'], $archivos['error'])
        || !is_array($archivos['name'])
        || !is_array($archivos['error'])) {
        return [];
    }

    $destino = __DIR__ . '/../assets/uploads/' . $tipo;
    if (!is_dir($destino) && !mkdir($destino, 0755, true) && !is_dir($destino)) {
        throw new RuntimeException('No se pudo preparar la carpeta para las imágenes.');
    }

    $mimeExtensiones = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $guardadas = [];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $seleccionadas = array_filter($archivos['error'], function ($error) {
        return $error !== UPLOAD_ERR_NO_FILE;
    });
    if (count($seleccionadas) > $maximo) {
        throw new RuntimeException('Sube máximo ' . $maximo . ' imágenes.');
    }

    try {
        foreach ($archivos['name'] as $indice => $nombre) {
            $error = $archivos['error'][$indice] ?? UPLOAD_ERR_NO_FILE;
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
                throw new RuntimeException('No se pudo cargar una imagen. Inténtalo nuevamente.');
            }

            $temporal = $archivos['tmp_name'][$indice] ?? '';
            $tamano = (int) ($archivos['size'][$indice] ?? 0);
            $mime = is_string($temporal) && is_file($temporal) ? $finfo->file($temporal) : false;
            if ($tamano < 1 || $tamano > 5 * 1024 * 1024 || !isset($mimeExtensiones[$mime]) || @getimagesize($temporal) === false) {
                throw new RuntimeException('Cada imagen debe ser JPG, PNG o WEBP y pesar máximo 5 MB.');
            }

            $nombreSeguro = bin2hex(random_bytes(16)) . '.' . $mimeExtensiones[$mime];
            if (!move_uploaded_file($temporal, $destino . DIRECTORY_SEPARATOR . $nombreSeguro)) {
                throw new RuntimeException('No se pudo guardar una de las imágenes.');
            }
            $guardadas[] = 'uploads/' . $tipo . '/' . $nombreSeguro;
        }
    } catch (RuntimeException $e) {
        foreach ($guardadas as $ruta) {
            $rutaCompleta = __DIR__ . '/../assets/' . $ruta;
            if (is_file($rutaCompleta)) {
                unlink($rutaCompleta);
            }
        }
        throw $e;
    }

    return $guardadas;
}

function validar_producto(array $datos): array
{
    $errores = [];
    $nombre = is_string($datos['nombre'] ?? null) ? trim($datos['nombre']) : '';
    $descripcion = is_string($datos['descripcion'] ?? null) ? trim($datos['descripcion']) : '';
    $marca = is_string($datos['marca'] ?? null) ? trim($datos['marca']) : '';
    $categoria = is_string($datos['categoria'] ?? null) ? trim($datos['categoria']) : '';
    $precio = $datos['precio'] ?? '';
    $stock = $datos['stock'] ?? '';
    $imagen = is_string($datos['imagen'] ?? null) ? trim($datos['imagen']) : '';

    if (!is_string($datos['nombre'] ?? null) || $nombre === '' || strlen($nombre) > 150) {
        $errores[] = 'El nombre es obligatorio y debe tener máximo 150 caracteres.';
    }
    if (!is_string($datos['descripcion'] ?? null) || strlen($descripcion) > 5000) {
        $errores[] = 'La descripción no puede superar 5000 caracteres.';
    }
    if ($marca === '' || strlen($marca) > 100) {
        $errores[] = 'La marca es obligatoria y debe tener máximo 100 caracteres.';
    }
    if ($categoria === '' || strlen($categoria) > 100) {
        $errores[] = 'La categoría es obligatoria y debe tener máximo 100 caracteres.';
    }
    if (!is_numeric($precio) || (float) $precio < 0 || (float) $precio > 99999999) {
        $errores[] = 'Ingresa un precio válido, igual o mayor que cero.';
    }
    if (filter_var($stock, FILTER_VALIDATE_INT) === false || (int) $stock < 0) {
        $errores[] = 'El stock debe ser un número entero igual o mayor que cero.';
    }
    if (array_key_exists('imagen', $datos) && (!is_string($datos['imagen']) || ($imagen !== '' && !validar_imagen_local($imagen)))) {
        $errores[] = 'La imagen indicada no es válida.';
    }
    if (!in_array($datos['genero'] ?? 'unisex', ['mujer', 'hombre', 'unisex'], true)) {
        $errores[] = 'Selecciona un género válido.';
    }

    return $errores;
}
