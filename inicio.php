<?php
session_start();  // Inicia la sesión

// Verificar si el usuario está logueado
if (!isset($_SESSION['username'])) {
    header("Location: login.html");  // Si no está logueado, redirigir al login
    exit();
}

$user = $_SESSION['username'];  // Obtener el nombre del usuario desde la sesión

// Función para cerrar sesión
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
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
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
            padding: 40px 20px;
            max-width: 1200px;
            margin: auto;
        }

        .section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.8rem;
            color: var(--text-color);
            margin-bottom: 20px;
            border-bottom: 2px solid var(--section-line-color);
            padding-bottom: 5px;
        }

        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .card {
            background: var(--card-background);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15);
        }

        .card h3 {
            font-size: 1.4rem;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .card p {
            font-size: 1rem;
            color: var(--text-color);
        }

        .dropdown {
            display: none;
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--dropdown-background);
            border: 1px solid var(--dropdown-border);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
            border-radius: 10px;
            padding: 15px;
            z-index: 100;
            width: 90%;
            max-width: 300px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .card:hover .dropdown {
            display: block;
            opacity: 1;
            visibility: visible;
        }

        .dropdown a {
            display: block;
            text-decoration: none;
            color: var(--text-color);
            margin-bottom: 10px;
            font-size: 1rem;
            padding: 10px;
            border-radius: 8px;
            background: var(--dropdown-background);
            transition: background 0.3s ease;
        }

        .dropdown a:hover {
            background: var(--button-hover-background);
            color: white;
        }

        .dropdown.empty p {
            color: var(--text-color);
            font-size: 0.9rem;
            text-align: center;
            margin: 0;
        }

        .dropdown.empty {
            padding: 20px;
        }
    </style>
</head>
<body data-theme="light">
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
            <div class="card-container">
                <div class="card">
                    <h3>Documentos recientes</h3>
                    <p>Accede rápidamente a los documentos más recientes.</p>
                    <div class="dropdown empty">
                        <p>No hay documentos recientes.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Mis Descargas</h2>
            <div class="card-container">
                <div class="card">
                    <h3>Archivos descargados</h3>
                    <p>Revisa los archivos que has descargado.</p>
                    <div class="dropdown empty">
                        <p>No hay descargas disponibles.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Mis Análisis</h2>
            <div class="card-container">
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
