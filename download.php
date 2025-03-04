<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

$id = $_GET['id'];

// Aquí deberías obtener la ruta del archivo desde la base de datos
$archivo = obtenerArchivoPorId($id); // Esta función debería ser implementada

if ($archivo) {
    $filepath = $archivo['ruta'];

    if (file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        echo "El archivo no existe.";
    }
} else {
    echo "Archivo no encontrado.";
}
?>