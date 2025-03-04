<?php
session_start();

// Configuración de la base de datos
$servername = "localhost";
$username = "root";  // Usuario root
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

    try {
        // Obtener el nombre del archivo y el usuario que lo subió
        $stmt = $conn->prepare("SELECT filename, scan_user FROM archivos WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $archivo = $stmt->fetch();

        if ($archivo && $archivo['scan_user'] === $_SESSION['username']) {
            // Eliminar el archivo del sistema de archivos
            $filePath = "/var/www/html/escanear/" . basename($archivo['filename']);
            if (file_exists($filePath)) {
                if (!unlink($filePath)) {
                    throw new Exception("No se pudo eliminar el archivo del sistema de archivos.");
                }
            }

            // Eliminar el archivo de la base de datos
            $stmt = $conn->prepare("DELETE FROM archivos WHERE id = :id");
            $stmt->bindParam(':id', $id);
            if (!$stmt->execute()) {
                throw new Exception("No se pudo eliminar el archivo de la base de datos.");
            }

            $_SESSION['success'] = "Archivo eliminado correctamente.";
        } else {
            $_SESSION['error'] = "No tienes permiso para eliminar este archivo.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

header("Location: inicio.php");
exit();
?>
