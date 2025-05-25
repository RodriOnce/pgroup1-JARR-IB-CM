<?php
/*------------------------------------------------------
 |  Inicia la sesión y devuelve un token CSRF en JSON
 *-----------------------------------------------------*/
session_set_cookie_params([
    'secure'   => false,   // ← pon true cuando uses HTTPS
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

/* Genera el token si no existe */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/* Devuelve el token */
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['token' => $_SESSION['csrf']]);
