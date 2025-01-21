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
            echo "Procesando archivo: $fileName...<br>";

            // Comando para ejecutar el script Python
            $command = "python3 $pythonScript $targetFilePath 2>&1";

            // Usar proc_open para manejar la salida en tiempo real
            $descriptorspec = [
                1 => ['pipe', 'w'], // Salida estándar
                2 => ['pipe', 'w'], // Salida de errores
            ];
            $process = proc_open($command, $descriptorspec, $pipes);

            if (is_resource($process)) {
                // Leer la salida mientras se genera
                while (($line = fgets($pipes[1])) !== false) {
                    echo nl2br(htmlspecialchars($line)); // Mostrar cada línea en tiempo real
                    ob_flush(); // Forzar la salida al navegador
                    flush();    // Asegurarse de que el buffer se vacía
                }

                // Leer errores (si los hay)
                while (($error = fgets($pipes[2])) !== false) {
                    echo "<span style='color: red;'>" . nl2br(htmlspecialchars($error)) . "</span>";
                    ob_flush();
                    flush();
                }

                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
            } else {
                echo "Error al ejecutar el script Python.<br>";
            }
        } else {
            echo "Error al subir el archivo $fileName.<br>";
        }
    }
} else {
    echo "No se han seleccionado archivos.";
}
?>
