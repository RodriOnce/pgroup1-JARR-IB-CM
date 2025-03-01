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

// Datos de ejemplo (simulados)
$stats = [
    'uploads' => 42,
    'downloads' => 28,
    'deleted' => 15,
    'active_users' => $conn->query("SELECT COUNT(*) FROM empleados")->fetchColumn(),
    'pending_users' => 5,
    'inactive_users' => 2
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
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .theme-toggle {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 1.2rem;
        }
    </style>
</head>
<body data-theme="light">
    <?php if(isset($_SESSION['username'])): ?>
        <div class="dashboard-container">
            <div class="sidebar">
                <h2>Menú</h2>
                <nav>
                    <div class="stat-card" style="margin: 1rem 0;">
                        <i class="fas fa-tachometer-alt"></i> Panel de Control
                    </div>
                    <div class="stat-card" style="margin: 1rem 0;">
                        <i class="fas fa-life-ring"></i> Centro de Ayuda
                    </div>
                </nav>
            </div>

            <div class="main-content">
                <div class="header">
                    <div>
                        <button class="theme-toggle" onclick="toggleTheme()">
                            <i class="fas fa-moon"></i>
                        </button>
                    </div>
                    <h1>Bienvenido, <?= htmlspecialchars($_SESSION['username']) ?></h1>
                    <a href="?logout=1" style="color: white; text-decoration: none;">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>

                <div class="stats-grid">
                    <!-- Primera fila -->
                    <div class="stat-card">
                        <h3><i class="fas fa-upload"></i> Archivos Subidos</h3>
                        <p><?= $stats['uploads'] ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><i class="fas fa-download"></i> Descargados</h3>
                        <p><?= $stats['downloads'] ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><i class="fas fa-trash"></i> Eliminados</h3>
                        <p><?= $stats['deleted'] ?></p>
                    </div>

                    <!-- Segunda fila -->
                    <div class="stat-card">
                        <h3><i class="fas fa-user-check"></i> Usuarios Activos</h3>
                        <p><?= $stats['active_users'] ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><i class="fas fa-user-clock"></i> Pendientes</h3>
                        <p><?= $stats['pending_users'] ?></p>
                    </div>
                    <div class="stat-card">
                        <h3><i class="fas fa-user-slash"></i> Inactivos</h3>
                        <p><?= $stats['inactive_users'] ?></p>
                    </div>
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

        // Cargar tema guardado
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.body.setAttribute('data-theme', savedTheme);
    </script>
</body>
</html>
