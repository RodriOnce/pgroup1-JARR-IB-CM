<?php
session_start();  // Inicia la sesión

// Verificar si el usuario está logueado
if (!isset($_SESSION['username'])) {
    header("Location: login.html");  // Si no está logueado, redirigir al login
    exit();
}

$user = $_SESSION['username'];  // Obtener el nombre del usuario desde la sesión
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido, <?php echo $user; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        header {
            background-color: #333;
            color: white;
            padding: 15px;
            text-align: center;
        }
        .content {
            text-align: center;
            padding: 50px;
        }
        .button-container {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        .button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            text-decoration: none;
        }
        .button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

<header>
    <h1>Bienvenido, <?php echo $user; ?>!</h1>
</header>

<div class="content">
    <h2>Opciones disponibles:</h2>
    <div class="button-container">
        <a href="mis_documentos.php" class="button">Mis Documentos</a>
        <a href="mis_descargas.php" class="button">Mis Descargas</a>
        <a href="mis_analisis.php" class="button">Mis Análisis</a>
    </div>
</div>

</body>
</html>