<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

$user = $_SESSION['username']; // Nombre del usuario logueado
$targetDir = "/var/www/html/archivos/$user/"; // Carpeta específica del usuario
$pythonScript = '/var/www/html/archivos/bueno-archivos.py';

// Asegurar que el directorio del usuario existe
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['name'] as $key => $fileName) {
        $targetFilePath = $targetDir . basename($fileName);

        if (move_uploaded_file($_FILES['files']['tmp_name'][$key], $targetFilePath)) {
            echo "<div class='message success'>El archivo $fileName se ha subido correctamente.</div>";

            if (file_exists($targetFilePath)) {
                echo "<div class='message success'>El archivo se encuentra en: $targetFilePath</div>";

                // Construir el comando correctamente
                $command = "python3 " . escapeshellarg($pythonScript) . " " . escapeshellarg($user) . " " . escapeshellarg($targetFilePath) . " 2>&1";
                echo "<pre>Ejecutando: $command</pre>"; // Para depuración

                $output = shell_exec($command);
                echo "<pre>$output</pre>";

                if (strpos($output, '/infectado') !== false) {
                    echo "<div class='message error'>El archivo <strong>$fileName</strong> está infectado.</div>";
                } elseif (strpos($output, '/sano') !== false) {
                    echo "<div class='message success'>El archivo <strong>$fileName</strong> está sano.</div>";
                }
            } else {
                echo "<div class='message error'>Error: El archivo no se encuentra en la ruta esperada.</div>";
            }
        } else {
            echo "<div class='message error'>Error al subir el archivo $fileName.</div>";
        }
    }
}
?>
