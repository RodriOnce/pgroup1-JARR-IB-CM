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
$empleados = $conn->query("SELECT * FROM empleados")->fetchAll(PDO::FETCH_ASSOC);

// Datos simulados (reemplazar con consultas reales cuando existan las tablas)
$archivos = [
    'subidos' => [],
    'descargados' => [],
    'eliminados' => []
];

$usuarios_estado = [
    'activos' => $empleados,
    'pendientes' => [],
    'inactivos' => []
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
            --background: #f8f9fa;
            --text: #212529;
            --card-bg: #fff;
            --header-bg: #6f42c1;
        }

        [data-theme="dark"] {
            --primary: #bb86fc;
            --background: #121212;
            --text: #fff;
            --card-bg: #1e1e1e;
            --header-bg: #2d2d2d;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background: var(--background);
            color: var(--text);
            transition: all 0.3s ease;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background: var(--card-bg);
            padding: 1rem;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .main-content {
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: var(--header-bg);
            color: white;
            margin-bottom: 2rem;
            border-radius: 8px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .detalles-panel {
            display: none;
            margin-top: 2rem;
            padding: 2rem;
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .tabla-detalles {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .tabla-detalles th, .tabla-detalles td {
            padding: 1rem;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
    </style>
</head>
<body data-theme="light">
    <?php if(isset($_SESSION['username'])): ?>
        <div class="dashboard-container">
            <div class="sidebar">
                <h2>Menú</h2>
                <nav>
                    <div class="stat-card" onclick="mostrarSeccion('panel-control')">
                        <i class="fas fa-tachometer-alt"></i> Panel de Control
                    </div>
                    <div class="stat-card" onclick="mostrarSeccion('centro-ayuda')">
                        <i class="fas fa-life-ring"></i> Centro de Ayuda
                    </div>
                </nav>
            </div>

            <div class="main-content">
                <div class="header">
                    <button class="theme-toggle" onclick="toggleTheme()">
                        <i class="fas fa-moon"></i> Modo Oscuro
                    </button>
                    <h1>Bienvenido, <?= htmlspecialchars($_SESSION['username']) ?></h1>
                    <a href="?logout=1" style="color: white; text-decoration: none;">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>

                <div id="panel-control">
                    <div class="stats-grid">
                        <!-- Primera fila -->
                        <div class="stat-card" onclick="mostrarDetalles('archivos-subidos')">
                            <h3><i class="fas fa-upload"></i> Archivos Subidos</h3>
                            <p><?= count($archivos['subidos']) ?></p>
                        </div>
                        <div class="stat-card" onclick="mostrarDetalles('archivos-descargados')">
                            <h3><i class="fas fa-download"></i> Descargados</h3>
                            <p><?= count($archivos['descargados']) ?></p>
                        </div>
                        <div class="stat-card" onclick="mostrarDetalles('archivos-eliminados')">
                            <h3><i class="fas fa-trash"></i> Eliminados</h3>
                            <p><?= count($archivos['eliminados']) ?></p>
                        </div>

                        <!-- Segunda fila -->
                        <div class="stat-card" onclick="mostrarDetalles('usuarios-activos')">
                            <h3><i class="fas fa-user-check"></i> Activos</h3>
                            <p><?= count($usuarios_estado['activos']) ?></p>
                        </div>
                        <div class="stat-card" onclick="mostrarDetalles('usuarios-pendientes')">
                            <h3><i class="fas fa-user-clock"></i> Pendientes</h3>
                            <p><?= count($usuarios_estado['pendientes']) ?></p>
                        </div>
                        <div class="stat-card" onclick="mostrarDetalles('usuarios-inactivos')">
                            <h3><i class="fas fa-user-slash"></i> Inactivos</h3>
                            <p><?= count($usuarios_estado['inactivos']) ?></p>
                        </div>
                    </div>

                    <!-- Paneles de detalles -->
                    <div id="detalles-container"></div>
                </div>

                <div id="centro-ayuda" style="display: none;">
                    <!-- Contenido del centro de ayuda -->
                </div>
            </div>
        </div>
    <?php else: ?>
        <div style="max-width: 400px; margin: 5rem auto; padding: 2rem;">
            <div class="stat-card">
                <h2>Iniciar Sesión</h2>
                <?php if(isset($error)): ?>
                    <div style="color: red;"><?= $error ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="text" name="nombre" placeholder="Usuario" required>
                    <input type="password" name="password" placeholder="Contraseña" required>
                    <button type="submit">Ingresar</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function toggleTheme() {
            const body = document.body;
            const newTheme = body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        }

        function mostrarSeccion(seccion) {
            document.querySelectorAll('.main-content > div').forEach(div => {
                div.style.display = 'none';
            });
            document.getElementById(seccion).style.display = 'block';
        }

        function mostrarDetalles(tipo) {
            const contenedor = document.getElementById('detalles-container');
            let html = `
                <div class="detalles-panel">
                    <h3>Detalles de ${tipo.replace('-', ' ').toUpperCase()}</h3>
                    <table class="tabla-detalles">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Departamento</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${generarFilas(tipo)}
                        </tbody>
                    </table>
                </div>
            `;
            contenedor.innerHTML = html;
        }

        function generarFilas(tipo) {
            // Simular datos - reemplazar con datos reales de la base de datos
            const datos = {
                'usuarios-activos': <?= json_encode($usuarios_estado['activos']) ?>,
                'usuarios-pendientes': <?= json_encode($usuarios_estado['pendientes']) ?>,
                'usuarios-inactivos': <?= json_encode($usuarios_estado['inactivos']) ?>
            }[tipo] || [];

            return datos.map(item => `
                <tr>
                    <td>${item.id || ''}</td>
                    <td>${item.nombre || ''}</td>
                    <td>${item.user || ''}</td>
                    <td>${item.mail || ''}</td>
                    <td>${item.dpt || ''}</td>
                </tr>
            `).join('');
        }

        // Cargar tema guardado
        document.body.setAttribute('data-theme', localStorage.getItem('theme') || 'light');
    </script>
</body>
</html>
