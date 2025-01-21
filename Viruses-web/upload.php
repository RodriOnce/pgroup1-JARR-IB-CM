<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$targetDir = "/var/www/html/escanear/";
$pythonScript = '/var/www/html/bueno.py';

if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['name'] as $key => $fileName) {

        $targetFilePath = $targetDir . basename($fileName);

        if (move_uploaded_file($_FILES['files']['tmp_name'][$key], $targetFilePath)) {
            echo "El archivo $fileName se ha subido correctamente.<br>";

            // Ejecutar el script y capturar la salida estándar y los errores
            $command = escapeshellcmd("python3 $pythonScript $targetFilePath 2>&1");
            $output = shell_exec($command);

            echo "<pre>$output</pre>";
        } else {
            echo "Error al subir el archivo $fileName.<br>";
        }
    }
} else {
    echo "No se han seleccionado archivos.";
}
?>