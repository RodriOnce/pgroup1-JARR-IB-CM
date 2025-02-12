<?php
session_start(); 

if (!isset($_SESSION['username'])) {
    header("Location: login.html");  
    exit();
}

$user = $_SESSION['username'];  

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.html");
    exit();
}
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
                    <div class="dropdown empty">
                        <p>No hay documentos recientes.</p>
                    </div>
                </div>
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
    </script>
</body>
</html>
