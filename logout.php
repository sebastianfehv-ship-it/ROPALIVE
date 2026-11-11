<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
        'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token_csrf'] ?? '';
    if (!is_string($token) || empty($_SESSION['token_csrf']) || !hash_equals($_SESSION['token_csrf'], $token)) {
        http_response_code(400);
        exit('La solicitud no es válida. Vuelve a la tienda e inténtalo otra vez.');
    }

    $_SESSION = [];
    session_destroy();
}

header('Location: index.php');
exit();