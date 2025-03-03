<?php
session_start();

// Configuración de la base de datos
$servername = "localhost";
$username = "root";  // Usuario Root
$password = "";      // Contraseña vacía (ajusta según tu configuración)
$dbname = "empresa";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Obtener el nombre del archivo
    $stmt = $conn->prepare("SELECT filename FROM archivos WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $archivo = $stmt->fetch();

    if ($archivo) {
        // Eliminar el archivo del sistema de archivos
        $filePath = "/var/www/html/escanear/" . $archivo['filename'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Eliminar el archivo de la base de datos
        $stmt = $conn->prepare("DELETE FROM archivos WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }
}

header("Location: inicio.php");
exit();
?>