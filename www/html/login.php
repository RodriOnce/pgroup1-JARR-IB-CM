<?php
declare(strict_types=1); // PHP en modo estricto para evitar errores tontos

/*------------------------------------------------------
 |  Cabeceras básicas de seguridad
 *-----------------------------------------------------*/
header('X-Frame-Options: DENY'); // Evitamos que esta web se pueda insertar en un iframe (protege de clickjacking).
header('X-Content-Type-Options: nosniff'); // Evitamos que el navegador intente adivinar tipos de archivo, solo usa los que decimos.
header('Referrer-Policy: no-referrer'); // Nunca enviamos la página anterior al salir de aquí (protege privacidad).

/*------------------------------------------------------
 |  Sesión protegida
 *-----------------------------------------------------*/
session_set_cookie_params([
    'secure'   => false,    // ← ponemos true cuando se use HTTPS
    'httponly' => true,     // Así nadie puede robar la cookie con JS.
    'samesite' => 'Strict'  // Solo nuestra web puede usar la cookie de sesión.
]);
session_start(); // Iniciamos o continuamos la sesión del usuario.

/*------------------------------------------------------
 |  Carga de configuración
 *-----------------------------------------------------*/
require_once __DIR__ . '/../seguridad/config.php'; // Cargamos los datos de configuración DB, rutas privadas, etc.

/*------------------------------------------------------
 |  Aceptamos solo método POST
 *-----------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { // Solo permitimos que se acceda a este script mediante formulario POST. Nada de acceder por URL.
    header('Location: login.html'); // Redirigimos a login si alguien entra de otra forma.
    exit;
}

/*------------------------------------------------------
 |  Verificación del token CSRF
 *-----------------------------------------------------*/
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { // Protegemos el login comprobando que el formulario viene de nuestro propio sitio, token CSRF.
    http_response_code(403); // Si el token no coincide, mostramos error de seguridad.
    exit('CSRF inválido');
}

/*------------------------------------------------------
 |  Validación de campos de entrada
 *-----------------------------------------------------*/
$user = filter_input( // Validamos los campos del formulario: usuario y contraseña.
    INPUT_POST,
    'username',
    FILTER_VALIDATE_REGEXP,
    ['options' => ['regexp' => '/^[A-Za-z0-9_\-]{3,30}$/']]
); // Aceptamos usuarios de 3 a 30 caracteres, solo letras, números, guion o guion bajo.
$pass = $_POST['password'] ?? ''; // Cogemos la contraseña enviada.

if ($user === false || $pass === '') {
    error(); // Si los campos no están bien, llamamos a la función de error, manda de vuelta al login con error.
}

/*------------------------------------------------------
 |  Conexión BD (PDO)
 *-----------------------------------------------------*/
try { // Intentamos conectar a la base de datos usando PDO y los datos de config.php.
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log('DB: ' . $e->getMessage()); // Guardamos el error en el log.
    error(); // Y mandamos de vuelta al login.
}

/*------------------------------------------------------
 |  Busca el usuario
 *-----------------------------------------------------*/
$stmt = $pdo->prepare('SELECT id, user, pass FROM empleados WHERE user = :u LIMIT 1');
$stmt->execute(['u' => $user]);
$row = $stmt->fetch(PDO::FETCH_ASSOC); // Sacamos la info del usuario si existe.

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
