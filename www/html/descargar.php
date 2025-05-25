<?php
// mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Conexión
$servername = "localhost";
$username = "root";
$password = "momo";
$dbname = "viruses";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener parámetros
if (isset($_GET['file']) && isset($_GET['user'])) {
    $filename = basename($_GET['file']);
    $user = basename($_GET['user']);
    $filePath = "archivos/$user/$filename";

    if (file_exists($filePath)) {

        // Calcular el hash SHA-256 del archivo
        $hash = hash_file('sha256', $filePath);

        // Buscar el ID más antiguo con ese hash
        $stmt = $conn->prepare("SELECT id FROM archivos WHERE hash = ? ORDER BY id ASC LIMIT 1");
        $stmt->bind_param("s", $hash);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $original_id = $row['id'];

            // Sumar +1 solo al registro más antiguo
            $updateStmt = $conn->prepare("UPDATE archivos SET download_count = download_count + 1 WHERE id = ?");
            $updateStmt->bind_param("i", $original_id);
            $updateStmt->execute();
            $updateStmt->close();
        }

        $stmt->close();
        $conn->close();

        // Forzar descarga
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=\"" . basename($filePath) . "\"");
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;

    } else {
        echo "Archivo no encontrado.";
    }
} else {
    echo "Parámetros inválidos.";
}
?>
