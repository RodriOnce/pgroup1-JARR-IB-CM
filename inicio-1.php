<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

$user = $_SESSION['username'];
$basePath = "/var/www/html/archivos/";
$userFolder = $basePath . $user . "/";

// Verificar si la carpeta del usuario existe
if (!is_dir($userFolder)) {
    echo "<p>No se encontró la carpeta del usuario.</p>";
    exit();
}

// Obtener la lista de archivos en la carpeta del usuario
$files = scandir($userFolder);
$files = array_diff($files, array('.', '..'));  // Filtrar "." y ".."

// Eliminar un archivo si se ha solicitado
if (isset($_GET['delete'])) {
    $fileToDelete = $userFolder . basename($_GET['delete']);
    if (file_exists($fileToDelete)) {
        unlink($fileToDelete);  // Eliminar el archivo
        header("Location: inicio-1.php");  // Recargar la página
        exit();
    }
}

// Compartir un archivo si se ha solicitado
if (isset($_POST['compartir'])) {
    $archivo = $_POST['archivo'];  // Nombre del archivo a compartir
    $usuario_destino = $_POST['usuario_destino'];  // Usuario con el que se comparte

    // Ruta del archivo en la carpeta del usuario que comparte
    $file_src = $userFolder . $archivo;

    // Ruta de la carpeta del usuario destino
    $carpeta_destino = $basePath . $usuario_destino . "/";

    // Verificar si la carpeta del usuario destino existe
    if (!is_dir($carpeta_destino)) {
        mkdir($carpeta_destino, 0755, true);  // Crear la carpeta si no existe
    }

    // Copiar el archivo a la carpeta del usuario destino
    if (copy($file_src, $carpeta_destino . $archivo)) {
        // Registrar el archivo compartido en la base de datos
        $conn = new mysqli("localhost", "root", "momo", "empresa");
        if ($conn->connect_error) {
            die("Conexión fallida: " . $conn->connect_error);
        }

        $sql = "INSERT INTO shared (file_src, user_src, user_dst) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $file_src, $user, $usuario_destino);
        $stmt->execute();
        $stmt->close();
        $conn->close();

        echo "<p>Archivo compartido con éxito.</p>";
    } else {
        echo "<p>Error al compartir el archivo.</p>";
    }
}

// Obtener la lista de usuarios disponibles para compartir
$conn = new mysqli("localhost", "root", "momo", "empresa");
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$sql = "SELECT user FROM empleados WHERE user != ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();
$usuarios = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de <?php echo $user; ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');

        :root {
            --background-color: #f9f3f8;
            --text-color: #333;
            --card-background: #ffffff;
            --header-background: #6f42c1;
            --button-background: linear-gradient(135deg, #6f42c1, #5a349f);
            --button-hover-background: linear-gradient(135deg, #5a349f, #4a2d82);
            --dropdown-background: #fefefe;
            --dropdown-border: #ddd;
            --section-line-color: #6f42c1;
            --sidebar-background: #f5f5f5;
            --sidebar-border-color: #ddd;
            --sidebar-link-hover: linear-gradient(135deg, #6f42c1, #5a349f);
            --sidebar-active-link: #6f42c1;
            --sidebar-text-color: #333;
        }

        [data-theme="dark"] {
            --background-color: #121212;
            --text-color: #ffffff;
            --card-background: #1e1e1e;
            --header-background: #3e3e3e;
            --button-background: linear-gradient(135deg, #bb86fc, #985eff);
            --button-hover-background: linear-gradient(135deg, #985eff, #7c4dff);
            --dropdown-background: #2e2e2e;
            --dropdown-border: #444;
            --section-line-color: #bb86fc;
            --sidebar-background: #1e1e1e;
            --sidebar-border-color: #444;
            --sidebar-link-hover: linear-gradient(135deg, #bb86fc, #985eff);
            --sidebar-active-link: #bb86fc;
            --sidebar-text-color: #ffffff;
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            background-color: var(--background-color);
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .sidebar {
            width: 250px;
            background-color: var(--sidebar-background);
            padding: 20px;
            position: fixed;
            height: 100vh;
            border-right: 1px solid var(--sidebar-border-color);
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar h2 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--sidebar-text-color);
            text-align: center;
            font-weight: bold;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar ul li {
            margin-bottom: 15px;
        }

        .sidebar ul li a {
            text-decoration: none;
            color: var(--sidebar-text-color);
            font-size: 1rem;
            padding: 10px 15px;
            display: block;
            border-radius: 8px;
            transition: background 0.3s ease, color 0.3s ease;
            font-weight: 500;
        }

        .sidebar ul li a:hover {
            background: var(--sidebar-link-hover);
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .sidebar ul li a.active {
            background-color: var(--sidebar-active-link);
            color: white;
        }

        .main-content {
            margin-left: 270px;
            padding: 20px;
            width: calc(100% - 270px);
        }

        header {
            background-color: var(--header-background);
            color: white;
            padding: 20px;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            margin: 0;
            font-size: 2rem;
        }

        .dark-mode-toggle {
            background: var(--button-background);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .dark-mode-toggle:hover {
            background: var(--button-hover-background);
        }

        .logout-button {
            background: var(--button-background);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .logout-button:hover {
            background: var(--button-hover-background);
        }

        .dashboard-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .section {
            flex: 1 1 calc(33.333% - 20px);
            background: var(--card-background);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--text-color);
            border-bottom: 2px solid var(--section-line-color);
            padding-bottom: 5px;
        }

        .card {
            background: var(--card-background);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 10px;
        }

        .card h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .card p {
            font-size: 1rem;
            color: var(--text-color);
        }

        .dropdown.empty {
            text-align: center;
            font-size: 0.9rem;
            color: var(--text-color);
        }

        .file-list {
            list-style-type: none;
            padding: 0;
        }

        .file-list li {
            background-color: var(--card-background);
            margin: 10px 0;
            padding: 10px;
            border: 1px solid var(--dropdown-border);
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .file-list a {
            color: var(--section-line-color);
            text-decoration: none;
            margin-right: 10px;
        }

        .file-list a:hover {
            text-decoration: underline;
        }

        .file-list button {
            background-color: #ff4d4d;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .file-list button:hover {
            background-color: #cc0000;
        }
    </style>
</head>
<body data-theme="light">
    <div class="sidebar">
        <h2>Menú</h2>
        <ul>
            <li><a href="#" class="active">Editar Perfil</a></li>
            <li><a href="#">Cambiar Idioma</a></li>
            <li><a href="#">Centro de Ayuda</a></li>
        </ul>
    </div>

    <div class="main-content">
        <header>
            <button class="dark-mode-toggle" onclick="toggleDarkMode()">Modo Oscuro</button>
            <h1>Bienvenido, <?php echo $user; ?>!</h1>
            <form method="POST" style="margin: 0;">
                <button type="submit" name="logout" class="logout-button">Cerrar Sesión</button>
            </form>
        </header>

        <div class="dashboard-container">
            <div class="section">
                <h2 class="section-title">Mis Documentos</h2>
                <div class="card">
                    <h3>Documentos recientes</h3>
                    <p>Accede rápidamente a los documentos más recientes.</p>
                    <div class="dropdown">
                        <ul class="file-list">
                            <?php
                            if (empty($files)) {
                                echo "<li>No hay archivos en tu carpeta.</li>";
                            } else {
                                foreach ($files as $file) {
                                    $filePath = $userFolder . $file;
                                    $fileUrl = "archivos/" . $user . "/" . $file;  // Ruta para acceder al archivo
                                    echo "<li>
                                            <span>$file</span>
                                            <div>
                                                <a href='$fileUrl' target='_blank'>Abrir</a>
                                                <a href='$fileUrl' download>Descargar</a>
                                                <button onclick=\"window.location.href='inicio-1.php?delete=$file'\">Eliminar</button>
                                                <button onclick=\"mostrarModal('$file')\">Compartir</button>
                                            </div>
                                          </li>";
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Modal para compartir archivos -->
            <div id="modalCompartir" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); z-index: 1000;">
                <h3>Compartir archivo</h3>
                <form method="POST">
                    <input type="hidden" id="archivoCompartir" name="archivo">
                    <label for="usuario_destino">Selecciona un usuario:</label>
                    <select name="usuario_destino" id="usuario_destino" required>
                        <?php
                        foreach ($usuarios as $usuario) {
                            echo "<option value='{$usuario['user']}'>{$usuario['user']}</option>";
                        }
                        ?>
                    </select>
                    <button type="submit" name="compartir">Compartir</button>
                    <button type="button" onclick="cerrarModal()">Cancelar</button>
                </form>
            </div>

            <div class="section">
                <h2 class="section-title">Mis Descargas</h2>
                <div class="card">
                    <h3>Archivos descargados</h3>
                    <p>Revisa los archivos que has descargado.</p>
                    <div class="dropdown empty">
                        <p>No hay descargas disponibles.</p>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">Mis Análisis</h2>
                <div class="card">
                    <h3>Resultados de análisis</h3>
                    <p>Consulta los detalles de tus análisis realizados.</p>
                    <div class="dropdown empty">
                        <p>No hay análisis disponibles.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleDarkMode() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            body.setAttribute('data-theme', newTheme);
            const toggleButton = document.querySelector('.dark-mode-toggle');
            toggleButton.textContent = newTheme === 'light' ? 'Modo Oscuro' : 'Modo Claro';
        }

        function mostrarModal(archivo) {
            document.getElementById('archivoCompartir').value = archivo;
            document.getElementById('modalCompartir').style.display = 'block';
        }

        function cerrarModal() {
            document.getElementById('modalCompartir').style.display = 'none';
        }
    </script>
</body>
</html>
