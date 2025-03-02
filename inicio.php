<?php
session_start();

// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "empresa";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Proceso de login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $nombre = trim($_POST['nombre']);
    $input_pass = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM empleados WHERE nombre = :nombre");
    $stmt->bindParam(':nombre', $nombre);
    $stmt->execute();
    $usuario = $stmt->fetch();

    if ($usuario && hash('sha256', $input_pass) === $usuario['password']) {
        $_SESSION['username'] = $usuario['nombre'];
        header("Location: inici.php");
        exit();
    } else {
        $error = "Credenciales incorrectas";
    }
}

// Cerrar sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: inici.php");
    exit();
}

// Obtener datos
$usuarios_activos = $conn->query("SELECT * FROM empleados")->fetchAll(PDO::FETCH_ASSOC);
$usuarios_pendientes = [];
$usuarios_inactivos = [];

// Datos simulados
$archivos = [
    'subidos' => [],
    'descargados' => [],
    'eliminados' => []
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6f42c1;
            --secondary: #4a148c;
            --background: #f8f9fa;
            --text: #212529;
            --card-bg: #ffffff;
            --header-bg: #6f42c1;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
        }

        [data-theme="dark"] {
            --primary: #bb86fc;
            --secondary: #3700b3;
            --background: #121212;
            --text: #ffffff;
            --card-bg: #1e1e1e;
            --header-bg: #2d2d2d;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background: var(--background);
            color: var(--text);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background: var(--card-bg);
            padding: 2rem;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
        }

        .main-content {
            padding: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
            transition: height 0.3s ease;
        }

        .stat-card:hover::after {
            height: 100%;
            opacity: 0.1;
        }

        .detalles-panel {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            background: var(--card-bg);
            margin-top: 1rem;
            border-radius: 0 0 15px 15px;
        }

        .detalles-panel.abierto {
            max-height: 500px;
            padding: 1.5rem;
            border: 1px solid rgba(0,0,0,0.1);
        }

        .tabla-detalles {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-detalles th {
            background: var(--primary);
            color: white;
            padding: 1rem;
        }

        .tabla-detalles td {
            padding: 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
    </style>
</head>
<body data-theme="light">
    <?php if(isset($_SESSION['username'])): ?>
        <div class="dashboard-container">
            <!-- Sidebar -->
            <div class="sidebar">
                <h2 style="color: var(--primary); text-align: center; margin-bottom: 2rem;">
                    <i class="fas fa-rocket"></i> Menú
                </h2>
                <div class="stat-card" onclick="mostrarSeccion('panel-control')">
                    <i class="fas fa-tachometer-alt"></i> Panel de Control
                </div>
                <div class="stat-card" onclick="mostrarSeccion('ayuda')">
                    <i class="fas fa-question-circle"></i> Centro de Ayuda
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding: 1.5rem; background: var(--header-bg); color: white; border-radius: 15px;">
                    <div>
                        <button onclick="toggleTheme()" style="background: none; border: none; color: white; cursor: pointer; padding: 0.8rem 1.2rem; border-radius: 8px;">
                            <i class="fas fa-moon"></i> Tema
                        </button>
                    </div>
                    <h1 style="margin: 0;">Bienvenido, <?= htmlspecialchars($_SESSION['username']) ?></h1>
                    <a href="?logout=1" style="color: white; text-decoration: none; padding: 0.8rem 1.2rem; border-radius: 8px; background: var(--danger);">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>

                <!-- Panel de Control -->
                <div id="panel-control">
                    <div class="stats-grid">
                        <!-- Primera Fila -->
                        <div class="stat-card" onclick="toggleDetalles('subidos')">
                            <h3><i class="fas fa-upload"></i> Archivos Subidos</h3>
                            <p style="font-size: 2.5rem; margin: 1rem 0;"><?= count($archivos['subidos']) ?></p>
                            <div class="detalles-panel" id="subidos">
                                <table class="tabla-detalles">
                                    <thead>
                                        <tr><th>ID</th><th>Nombre</th><th>Fecha</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($archivos['subidos'] as $archivo): ?>
                                        <tr><td colspan="3">No hay archivos subidos</td></tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="stat-card" onclick="toggleDetalles('descargados')">
                            <h3><i class="fas fa-download"></i> Descargados</h3>
                            <p style="font-size: 2.5rem; margin: 1rem 0;"><?= count($archivos['descargados']) ?></p>
                            <div class="detalles-panel" id="descargados">
                                <!-- Contenido similar -->
                            </div>
                        </div>

                        <div class="stat-card" onclick="toggleDetalles('eliminados')">
                            <h3><i class="fas fa-trash"></i> Eliminados</h3>
                            <p style="font-size: 2.5rem; margin: 1rem 0;"><?= count($archivos['eliminados']) ?></p>
                            <div class="detalles-panel" id="eliminados">
                                <!-- Contenido similar -->
                            </div>
                        </div>

                        <!-- Segunda Fila -->
                        <div class="stat-card" onclick="toggleDetalles('activos')">
                            <h3><i class="fas fa-user-check"></i> Activos</h3>
                            <p style="font-size: 2.5rem; margin: 1rem 0;"><?= count($usuarios_activos) ?></p>
                            <div class="detalles-panel" id="activos">
                                <table class="tabla-detalles">
                                    <thead>
                                        <tr><th>ID</th><th>Nombre</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($usuarios_activos as $usuario): ?>
                                        <tr>
                                            <td><?= $usuario['id'] ?></td>
                                            <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="stat-card" onclick="toggleDetalles('pendientes')">
                            <h3><i class="fas fa-user-clock"></i> Pendientes</h3>
                            <p style="font-size: 2.5rem; margin: 1rem 0;"><?= count($usuarios_pendientes) ?></p>
                            <div class="detalles-panel" id="pendientes">
                                <!-- Contenido similar -->
                            </div>
                        </div>

                        <div class="stat-card" onclick="toggleDetalles('inactivos')">
                            <h3><i class="fas fa-user-slash"></i> Inactivos</h3>
                            <p style="font-size: 2.5rem; margin: 1rem 0;"><?= count($usuarios_inactivos) ?></p>
                            <div class="detalles-panel" id="inactivos">
                                <!-- Contenido similar -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Login Form -->
        <div style="max-width: 400px; margin: 5rem auto; padding: 2rem;">
            <div class="stat-card" style="padding: 2rem; text-align: center;">
                <h2 style="color: var(--primary); margin-bottom: 1.5rem;">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </h2>
                <?php if(isset($error)): ?>
                    <div style="color: var(--danger); margin-bottom: 1rem;"><?= $error ?></div>
                <?php endif; ?>
                <form method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
                    <input type="text" name="nombre" placeholder="Usuario" required 
                           style="padding: 0.8rem; border-radius: 8px; border: 1px solid #ddd;">
                    <input type="password" name="password" placeholder="Contraseña" required 
                           style="padding: 0.8rem; border-radius: 8px; border: 1px solid #ddd;">
                    <button type="submit" 
                            style="padding: 1rem; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer;">
                        <i class="fas fa-unlock"></i> Acceder
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function toggleDetalles(id) {
            const panel = document.getElementById(id);
            panel.classList.toggle('abierto');
        }

        function toggleTheme() {
            const body = document.body;
            const newTheme = body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        }

        // Cargar tema guardado
        document.body.setAttribute('data-theme', localStorage.getItem('theme') || 'light');
    </script>
</body>
</html>
