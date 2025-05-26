<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

$user = $_SESSION['username'];
$targetDir = "/var/www/html/archivos/$user/";
$pythonScript = '/var/www/html/archivos/bueno-archivos.py';

echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análisis de Archivos</title>
    <style>
        :root {
            --background-color: #f9f3f8;
            --text-color: #333;
            --card-background: #ffffff;
            --button-background: linear-gradient(135deg, #6f42c1, #5a349f);
            --button-hover-background: linear-gradient(135deg, #5a349f, #4a2d82);
            --section-line-color: #6f42c1;
        }

        body {
            font-family: "Roboto", sans-serif;
            margin: 0;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--background-color);
        }

        .container {
            background: var(--card-background);
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 100%;
            max-width: 800px;
            border: 2px solid var(--section-line-color);
        }

        .message {
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            font-size: 0.95rem;
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
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            overflow-x: auto;
            margin: 15px 0;
        }

        .btn-volver {
            background: var(--button-background);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 20px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-volver:hover {
            background: var(--button-hover-background);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>';

echo '<div class="container">';

// Crear directorio si no existe
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['name'] as $key => $fileName) {
        $targetFilePath = $targetDir . basename($fileName);

        if (move_uploaded_file($_FILES['files']['tmp_name'][$key], $targetFilePath)) {
            echo "<div class='message success'>Archivo subido: $fileName</div>";

            // Ejecutar análisis
            $command = "python3 " . escapeshellarg($pythonScript) . " " .
                      escapeshellarg($user) . " " .
                      escapeshellarg($fileName) . " 2>&1";

            $output = shell_exec($command);
            echo "<pre>" . htmlspecialchars($output) . "</pre>";

            // Verificar resultados
            if (strpos($output, 'infected') !== false) {
                echo "<div class='message error'>$fileName - ELIMINADO (Infectado)</div>";
            } elseif (strpos($output, 'safe') !== false) {
                echo "<div class='message success'>$fileName - Seguro</div>";
            }
        } else {
            echo "<div class='message error'>Error subiendo $fileName</div>";
        }
    }
} else {
    echo "<div class='message error'>No se seleccionaron archivos</div>";
}

echo '<a href="/inicio-1.php" class="btn-volver">Volver al Panel</a>';
echo '</div></body></html>';
?>
