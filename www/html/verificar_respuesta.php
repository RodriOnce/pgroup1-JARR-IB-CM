<?php
session_start();
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
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['respuesta_usuario'])) {
    if (!isset($_SESSION['recuperacion_id_empleado']) || !isset($_SESSION['recuperacion_pregunta'])) {
        echo "<p style='color: red;'>Error: No se pudo iniciar el proceso de recuperación. Vuelve a intentarlo.</p>";
        header("Location: recuperar_contrasena.php");
        exit();
    }

    $id_empleado = $_SESSION['recuperacion_id_empleado'];
    $respuesta_usuario = $_POST['respuesta_usuario'];
    $hashed_respuesta_usuario = hash('sha256', $respuesta_usuario); // Hash de la respuesta del usuario

    try {
        $stmt = $pdo->prepare("SELECT respuesta_seguridad FROM empleados WHERE id = ?");
        $stmt->execute([$id_empleado]);
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($empleado) {
            // Compara el hash de la respuesta del usuario con el hash guardado en la DB
            if ($hashed_respuesta_usuario === $empleado['respuesta_seguridad']) {
                // Respuesta correcta: Permite cambiar la contraseña
                $_SESSION['permitir_cambio_contrasena'] = true; // Flag para el siguiente paso
                header("Location: cambiar_contrasena.php");
                exit();
            } else {
                echo "<p style='color: red;'>Respuesta incorrecta. Inténtalo de nuevo.</p>";
                // Opcional: limpiar la sesión para obligar a reiniciar el proceso
                unset($_SESSION['recuperacion_id_empleado']);
                unset($_SESSION['recuperacion_pregunta']);
            }
        } else {
            echo "<p style='color: red;'>Error: No se encontró el usuario. Vuelve a intentarlo.</p>";
            // Limpiar la sesión
            unset($_SESSION['recuperacion_id_empleado']);
            unset($_SESSION['recuperacion_pregunta']);
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error al verificar respuesta: " . $e->getMessage() . "</p>";
    }
} else {
    header("Location: recuperar_contrasena.php");
    exit();
}
?>
