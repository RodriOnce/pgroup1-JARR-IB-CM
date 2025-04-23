<?php
session_start();  // Inicia la sesión

// Conexión a la base de datos
$servername = "localhost";
$username = "root";  // Ajusta este usuario si es necesario
$password = "momo";  // Ajusta la contraseña si es necesario
$dbname = "empresa";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger los datos del formulario
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Consultar si el usuario existe
    $sql = "SELECT * FROM empleados WHERE user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user);  // Vincula el nombre de usuario
    $stmt->execute();
    $result = $stmt->get_result();

    // Verificar si el usuario existe
    if ($result->num_rows > 0) {
        // Obtener la fila del usuario
        $row = $result->fetch_assoc();

        // Verificar la contraseña usando hash('sha256')
        $hashed_password = hash('sha256', $pass);  // Generar el hash de la contraseña ingresada
        if ($hashed_password === $row['pass']) {  // Comparar con el hash almacenado
            // El usuario y la contraseña son correctos
            $_SESSION['username'] = $user;  // Almacenar el nombre de usuario en la sesión

            // Ruta base donde se almacenarán las carpetas de los usuarios
            $basePath = "/var/www/html/archivos/";

            // Ruta de la carpeta del usuario actual
            $userFolder = $basePath . $user . "/";

            // Crear la carpeta del usuario si no existe
            if (!is_dir($userFolder)) {
                mkdir($userFolder, 0755, true);  // Crear la carpeta con permisos 0755
            }

            // Redirigir a la página de inicio personalizada
            if ($user === "admin") {
                header("Location: inicio.php");
            } else {
                header("Location: inicio-1.php");
            }
            exit();  // Asegúrate de salir del script después de la redirección
        } else {
            // La contraseña es incorrecta
            echo "<p>Contraseña incorrecta. <a href='login.html'>Intentar de nuevo</a></p>";
        }
    } else {
        // El usuario no existe
        echo "<p>Usuario no encontrado. <a href='login.html'>Intentar de nuevo</a></p>";
    }

    // Cerrar conexión
    $stmt->close();
    $conn->close();
}
?>
