<?php
session_start();

// Redirige al inicio de sesión si el usuario no está autenticado
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

$user = $_SESSION['username'];
// Define el directorio base donde se subirán los archivos del usuario
$userUploadDir = "/var/www/html/archivos/$user/";
// Ruta al script de análisis de Python
$pythonScript = '/var/www/html/archivos/bueno-archivos.py';

// Inicio de la salida HTML para la página de resultados del análisis
echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análisis de Archivos</title>
    <style>
        /* Variables CSS para un tema consistente */
        :root {
            --background-color: #f9f3f8;
            --text-color: #333;
            --card-background: #ffffff;
            --button-background: linear-gradient(135deg, #6f42c1, #5a349f);
            --button-hover-background: linear-gradient(135deg, #5a349f, #4a2d82);
            --section-line-color: #6f42c1;
        }

        /* Estilo básico del cuerpo */
        body {
            font-family: "Roboto", sans-serif; /* Usando Roboto para un aspecto moderno */
            margin: 0;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--background-color);
        }

        /* Contenedor del contenido principal, estilizado como una tarjeta */
        .container {
            background: var(--card-background);
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 100%;
            max-width: 800px;
            border: 2px solid var(--section-line-color);
        }

        /* Estilo para mensajes (éxito/error) */
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

        /* Estilo para texto preformateado (salida del script de Python) */
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            overflow-x: auto; /* Permite el desplazamiento horizontal para líneas largas */
            margin: 15px 0;
        }

        /* Estilo para el botón "Volver al Panel" */
        .btn-volver {
            background: var(--button-background);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease; /* Transición suave para efectos hover */
            margin-top: 20px;
            text-decoration: none; /* Quita el subrayado del enlace */
            display: inline-block; /* Permite aplicar padding y margin */
        }

        .btn-volver:hover {
            background: var(--button-hover-background);
            transform: translateY(-2px); /* Efecto de elevación sutil al pasar el ratón */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); /* Sombra sutil al pasar el ratón */
        }
    </style>
</head>
<body>';

echo '<div class="container">';

/**
 * Función de ayuda para procesar la subida de un solo archivo.
 * Esta función maneja el movimiento del archivo y la ejecución del script de análisis de Python.
 *
 * @param string $user El nombre de usuario de la sesión actual.
 * @param string $userUploadDir El directorio base para las subidas del usuario.
 * @param string $pythonScript La ruta al script de análisis de Python.
 * @param string $fileTmpName La ruta temporal del archivo subido.
 * @param string $fileName El nombre del archivo, incluyendo cualquier ruta relativa si es de una carpeta.
 */
function process_file($user, $userUploadDir, $pythonScript, $fileTmpName, $fileName) {
    // Construye la ruta de destino completa para el archivo, incluyendo cualquier subdirectorio
    // ej., /var/www/html/archivos/usuario/mi_carpeta/documento.pdf
    $targetFilePath = $userUploadDir . $fileName;
    // Extrae la parte del directorio de la ruta del archivo de destino
    $targetFileDir = dirname($targetFilePath);

    // Crea el directorio si no existe. El argumento 'true' permite la creación recursiva.
    if (!is_dir($targetFileDir)) {
        if (!mkdir($targetFileDir, 0755, true)) {
            echo "<div class='message error'>Error: No se pudo crear el directorio para $fileName</div>";
            return; // Sale de la función si falla la creación del directorio
        }
    }

    // Intenta mover el archivo subido desde su ubicación temporal a la ruta de destino
    if (move_uploaded_file($fileTmpName, $targetFilePath)) {
        echo "<div class='message success'>Archivo subido: $fileName</div>";

        // Ejecuta el script de análisis de Python.
        // Pasamos el nombre de usuario y la ruta relativa del archivo (ej., 'mi_carpeta/documento.pdf').
        // El script de Python resolverá correctamente la ruta completa basándose en su BASE_DIR.
        $command = "python3 " . escapeshellarg($pythonScript) . " " .
                  escapeshellarg($user) . " " .
                  escapeshellarg($fileName) . " 2>&1"; // Redirige stderr a stdout para la salida completa

        $output = shell_exec($command); // Ejecuta el comando y captura su salida
        echo "<pre>" . htmlspecialchars($output) . "</pre>"; // Muestra la salida del script de Python

        // Comprueba la salida del script de Python para los resultados del análisis
        if (strpos($output, 'infected') !== false) {
            echo "<div class='message error'>$fileName - ELIMINADO (Infectado)</div>";
        } elseif (strpos($output, 'safe') !== false) {
            echo "<div class='message success'>$fileName - Seguro</div>";
        } else {
            // Alternativa para casos en los que la salida no indica claramente 'infected' o 'safe'
            echo "<div class='message error'>$fileName - Análisis no concluyente o error.</div>";
        }
    } else {
        echo "<div class='message error'>Error subiendo $fileName</div>";
    }
}

$filesProcessed = false; // Bandera para verificar si se procesó algún archivo

// Maneja las subidas de archivos individuales (desde el botón "Seleccionar Archivos")
// $_FILES['files'] contendrá un array de archivos si se seleccionan varios
if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
    $filesProcessed = true;
    foreach ($_FILES['files']['name'] as $key => $fileName) {
        // Para archivos individuales, solo necesitamos el nombre base (ej., 'documento.pdf')
        process_file($user, $userUploadDir, $pythonScript, $_FILES['files']['tmp_name'][$key], basename($fileName));
    }
}

// Maneja las subidas de carpetas (desde el botón "Seleccionar Carpeta")
// $_FILES['folders'] contendrá un array donde 'name' incluye rutas relativas
if (isset($_FILES['folders']) && !empty($_FILES['folders']['name'][0])) {
    $filesProcessed = true;
    foreach ($_FILES['folders']['name'] as $key => $fullRelativePath) {
        // Para las subidas de carpetas, $fullRelativePath será como 'mi_carpeta/subcarpeta/archivo.txt'
        // Pasamos esta ruta relativa completa para mantener la estructura de directorios en el servidor
        process_file($user, $userUploadDir, $pythonScript, $_FILES['folders']['tmp_name'][$key], $fullRelativePath);
    }
}

// Muestra un mensaje si no se seleccionaron archivos o carpetas para analizar
if (!$filesProcessed) {
    echo "<div class='message error'>No se seleccionaron archivos ni carpetas para analizar.</div>";
}

// Botón para volver al panel principal
echo '<a href="/inicio-1.php" class="btn-volver">Volver al Panel</a>';
echo '</div></body></html>';
?>
