<?php
session_start();  // Inicia la sesión

// Conexión a la base de datos
$servername = "localhost";
$username = "root";  // Ajusta este usuario si es necesario
$password = "";      // Ajusta la contraseña si es necesario
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

    // Consultar si el usuario y la contraseña existen
    $sql = "SELECT * FROM empleados WHERE nombre = ? AND password = SHA2(?, 256)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $user, $pass);  // Vincula los parámetros
    $stmt->execute();
    $result = $stmt->get_result();

    // Verificar si el usuario existe
    if ($result->num_rows > 0) {
        // El usuario existe, almacenar en la sesión
        $_SESSION['username'] = $user;  // Almacenar el nombre de usuario en la sesión

        // Redirigir a la página de inicio personalizada
        header("Location: inicio.php");
    } else {
        // El usuario no existe
        echo "<p>Usuario o contraseña incorrectos. <a href='login.html'>Intentar de nuevo</a></p>";
    }

    // Cerrar conexión
    $stmt->close();
    $conn->close();
}
?>