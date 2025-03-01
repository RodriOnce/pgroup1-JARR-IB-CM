<?php
session_start();

// Configuración para XAMPP/MAMP (valores por defecto)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "empresa";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Consulta para obtener empleados
    $stmt = $conn->prepare("SELECT id, nombre FROM empleados");
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Error: Verifica tus credenciales de MySQL. Detalle: " . $e->getMessage());
}

// Resto del código (login, logout, etc.)
?>
