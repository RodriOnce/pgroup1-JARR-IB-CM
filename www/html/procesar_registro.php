<?php

    // Conexión a la base de datos
    $servername = "localhost";
    $username = "root";  // Ajusta este usuario si es necesario
    $password = "momo";  // Ajusta la contraseña si es necesario
    $dbname = "empresa";

    try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // Si hay un error de conexión a la base de datos, lo mostramos y terminamos.
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $user = $_POST['user'];
    $pass = $_POST['password']; 
    $dpt = $_POST['dpt'];
    $mail = $_POST['mail'];
    $pregunta_seguridad = $_POST['pregunta'];
    $respuesta_seguridad = $_POST['respuesta'];

    $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
    $hashed_respuesta_seguridad = hash('sha256', $respuesta_seguridad);

    // --- LÍNEAS DE DEPURACIÓN CRÍTICAS (Temporalmente) ---
    // Mantenlas por ahora. Te mostrarán si los valores son correctos
    // ANTES de la inserción.
    // echo "<pre>";
    // echo "<h1>Depuración de Registro - Valores Recibidos y Hasheados</h1>";
    // echo "<h3>Contenido completo de \$_POST:</h3>";
    // print_r($_POST);
    // echo "<br>----------------------------------------<br>";
    // echo "<h3>Valores de seguridad procesados:</h3>";
    // echo "Valor de \$pregunta_seguridad (recibido): '" . htmlspecialchars($pregunta_seguridad) . "'<br>";
    // echo "Valor de \$respuesta_seguridad (recibido): '" . htmlspecialchars($respuesta_seguridad) . "'<br>";
    // echo "Valor hasheado de \$hashed_respuesta_seguridad: '" . htmlspecialchars($hashed_respuesta_seguridad) . "'<br>";
    // echo "</pre>";
    // die("Script detenido para depuración. Revisa los valores de arriba.");
    // --- FIN LÍNEAS DE DEPURACIÓN ---


    try {
        $stmt = $pdo->prepare("INSERT INTO empleados (name, user, pass, dpt, mail, status, pregunta_seguridad, respuesta_seguridad) VALUES (?, ?, ?, ?, ?, 'pendiente', ?, ?)");
        $stmt->execute([$name, $user, $hashed_pass, $dpt, $mail, $pregunta_seguridad, $hashed_respuesta_seguridad]);

        echo "¡Registro exitoso!";
        // Redirige al usuario a una página de éxito o al login
        header("Location: login.php?registro=exito");
        exit();

    } catch (PDOException $e) {
        // Manejo de errores (por ejemplo, usuario o email duplicado)
        if ($e->getCode() == '23000') { // Código de error para entrada duplicada
            echo "Error: El usuario o correo electrónico ya existen.";
        } else {
            echo "Error al registrar: " . $e->getMessage();
        }
    }
} else {
    // Si alguien intenta acceder directamente a procesar_registro.php sin enviar el formulario
    header("Location: registro.php");
    exit();
}
?>
