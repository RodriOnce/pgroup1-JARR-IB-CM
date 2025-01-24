<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subida de Archivos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #444;
        }

        .message {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        pre {
            background: #f4f4f4;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow-x: auto;
        }

        .no-files {
            text-align: center;
            background-color: #ffeeba;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 15px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Subida de Archivos</h1>
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$targetDir = "/var/www/html/escanear/";
$pythonScript = '/var/www/html/bueno.py';

// Comprobar si se subieron archivos
if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['name'] as $key => $fileName) {

        $targetFilePath = $targetDir . basename($fileName);

        // Mover archivo al directorio objetivo
        if (move_uploaded_file($_FILES['files']['tmp_name'][$key], $targetFilePath)) {

            echo "<div class='message success'>El archivo $fileName se ha subido correctamente.</div>";

            $command = escapeshellcmd("python3 $pythonScript $targetFilePath 2>&1");
            $output = shell_exec($command);

            echo "<pre>$output</pre>";

        } else {
                echo "<div class='message error'>Error al subir el archivo $fileName.</div>";
        }
    }
}
// Comprobar si se subieron carpetas
elseif (!empty($_FILES['folders']['name'][0])) {
    foreach ($_FILES['folders']['name'] as $key => $folderFileName) {
        $targetFilePath = $targetDir . basename($folderFileName);

        if (move_uploaded_file($_FILES['folders']['tmp_name'][$key], $targetFilePath)) {

            echo "<div class='message success'>El archivo $folderFileName se ha subido correctamente.</div>";

            $command = escapeshellcmd("python3 $pythonScript $targetFilePath 2>&1");
            $output = shell_exec($command);

            echo "<pre>$output</pre>";
        } else {
            echo "<div class='message error'>Error al subir el archivo $folderFileName.</div>";
        }
    }
} else {
    echo "<div class='no-files'>No se ha seleccionado ninguna carpeta.</div>";
}
?>

</div>
</body>
</html>