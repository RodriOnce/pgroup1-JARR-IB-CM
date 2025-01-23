<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$targetDir = "/var/www/html/escanear/";
$pythonScript = '/var/www/html/bueno.py';

if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['name'] as $key => $fileName) {
        $filePath = $targetDir . $fileName;
        $directory = dirname($filePath);
        
        // Crear directorios si no existen
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        if (move_uploaded_file($_FILES['files']['tmp_name'][$key], $filePath)) {
            echo "El archivo $fileName se ha subido correctamente.<br>";
            
            // Ejecutar script Python
            $cleanPath = escapeshellarg($filePath);
            $command = "python3 $pythonScript $cleanPath 2>&1";
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
