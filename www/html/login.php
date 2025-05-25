<?php
declare(strict_types=1);

/*------------------------------------------------------
 |  Cabeceras básicas de seguridad
 *-----------------------------------------------------*/
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

/*------------------------------------------------------
 |  Sesión protegida
 *-----------------------------------------------------*/
session_set_cookie_params([
    'secure'   => false,    // ← pon true cuando uses HTTPS
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

/*------------------------------------------------------
 |  Carga de configuración
 *-----------------------------------------------------*/
require_once __DIR__ . '/../seguridad/config.php';

/*------------------------------------------------------
 |  Aceptamos solo método POST
 *-----------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

/*------------------------------------------------------
 |  Verificación del token CSRF
 *-----------------------------------------------------*/
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    http_response_code(403);
    exit('CSRF inválido');
}

/*------------------------------------------------------
 |  Validación de campos de entrada
 *-----------------------------------------------------*/
$user = filter_input(
    INPUT_POST,
    'username',
    FILTER_VALIDATE_REGEXP,
    ['options' => ['regexp' => '/^[A-Za-z0-9_\-]{3,30}$/']]
);
$pass = $_POST['password'] ?? '';

if ($user === false || $pass === '') {
    error();
}

/*------------------------------------------------------
 |  Conexión BD (PDO)
 *-----------------------------------------------------*/
try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log('DB: ' . $e->getMessage());
    error();
}

/*------------------------------------------------------
 |  Busca el usuario
 *-----------------------------------------------------*/
$stmt = $pdo->prepare('SELECT id, user, pass FROM empleados WHERE user = :u LIMIT 1');
$stmt->execute(['u' => $user]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

/*------------------------------------------------------
 |  Verifica contraseña (y migra hashes SHA-256 antiguos)
 *-----------------------------------------------------*/
$ok = $row && password_verify($pass, $row['pass']);

if (!$ok && $row && hash_equals($row['pass'], hash('sha256', $pass))) {
    /* Migración automática a password_hash() */
    $ok = true;
    $newHash = password_hash($pass, PASSWORD_DEFAULT);
    $pdo->prepare('UPDATE empleados SET pass = :p WHERE id = :id')
        ->execute(['p' => $newHash, 'id' => $row['id']]);
}

/*------------------------------------------------------
 |  Resultado
 *-----------------------------------------------------*/
if ($ok) {
    /* Regenera el ID de sesión para evitar fijación */
    session_regenerate_id(true);

    $_SESSION['uid']      = $row['id'];
    $_SESSION['username'] = $row['user'];

    /* Crea la carpeta personal si no existe */
    $dir = $privatePath . $row['id'] . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    /* Redirige según rol */
    $dest = ($row['user'] === 'admin') ? 'inicio.php' : 'inicio-1.php';
    header('Location: ' . $dest);
    exit;
}

/* Falla ⇒ mensaje genérico */
sleep(1);
error();

/*------------------------------------------------------
 |  Función de respuesta de error
 *-----------------------------------------------------*/
function error(): void
{
    header('Location: login.html?err=1');
    exit;
}
