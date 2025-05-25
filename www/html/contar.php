<?php
// Activar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "momo";
$dbname = "viruses";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$sql = "SELECT filename, download_count FROM archivos ORDER BY download_count DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Archivos y Descargas</title>
    <style>
        table {
            width: 70%;
            margin: 30px auto;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
        }
        th, td {
            border: 1px solid #aaa;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #eee;
        }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Lista de Archivos y Descargas</h2>
    <table>
        <tr>
            <th>Nombre del Archivo</th>
            <th>Veces Descargado</th>
        </tr>
        <?php
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row["filename"]) . "</td>";
                echo "<td>" . intval($row["download_count"]) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='2'>No hay datos disponibles</td></tr>";
        }

        $conn->close();
        ?>
    </table>
</body>
</html>
