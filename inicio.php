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

// Verificar login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $nombre = $_POST['nombre'];
    $input_pass = $_POST['password'];
    
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

// Obtener estadísticas reales
$stats = [
    'total_empleados' => $conn->query("SELECT COUNT(*) FROM empleados")->fetchColumn(),
    'activos' => $conn->query("SELECT COUNT(*) FROM empleados")->fetchColumn(), // Modificar según tu lógica
    'pendientes' => 0, // Agregar campo en la tabla si es necesario
    'inactivos' => 0  // Agregar campo en la tabla si es necesario
];

// Datos para el gráfico (ejemplo)
$chart_data = $conn->query("
    SELECT DATE(created_at) as fecha, COUNT(*) as total 
    FROM empleados 
    GROUP BY DATE(created_at)
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Employee Dashboard - <?= htmlspecialchars($_SESSION['username'] ?? '') ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Mantener todos los estilos del dashboard anterior */
        :root {
            --primary-color: #6f42c1;
            --secondary-color: #ff6b6b;
            --success-color: #4CAF50;
            --background-color: #f9f3f8;
            --text-color: #333;
            --card-background: #ffffff;
            --header-background: var(--primary-color);
            --sidebar-width: 280px;
            --transition-speed: 0.3s;
        }

        [data-theme="dark"] {
            --background-color: #121212;
            --text-color: #ffffff;
            --card-background: #1e1e1e;
            --header-background: #2d2d2d;
        }

        /* ... (Mantener todo el CSS original) ... */
    </style>
</head>
<body data-theme="light">
    <?php if(isset($_SESSION['username'])): ?>
        <div class="sidebar animated">
            <h2 style="text-align: center; margin-bottom: 2rem;">
                <i class="fas fa-users"></i> Gestión Empleados
            </h2>
            <nav>
                <ul style="list-style: none; padding: 0;">
                    <li class="smart-card" onclick="showSection('control-panel')">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </li>
                    <li class="smart-card" onclick="showSection('empleados')">
                        <i class="fas fa-list"></i> Lista de Empleados
                    </li>
                    <li class="smart-card" onclick="showSection('ayuda')">
                        <i class="fas fa-question-circle"></i> Ayuda
                    </li>
                </ul>
            </nav>
        </div>

        <div class="main-content">
            <div class="smart-header animated">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="header-actions">
                    <h1>Bienvenido, <?= htmlspecialchars($_SESSION['username']) ?></h1>
                    <button class="theme-toggle" onclick="toggleTheme()">
                        <i class="fas fa-moon"></i>
                    </button>
                    <a href="?logout=1" class="logout-button">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>

            <!-- Sección Principal -->
            <div id="control-panel" class="dashboard-section active">
                <div class="dashboard-grid">
                    <?php foreach ($stats as $key => $value): ?>
                    <div class="smart-card animated">
                        <h3><?= ucfirst(str_replace('_', ' ', $key)) ?></h3>
                        <div class="stat-value"><?= $value ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="chart-container animated">
                    <canvas id="empleadosChart"></canvas>
                </div>
            </div>

            <!-- Sección Empleados -->
            <div id="empleados" class="dashboard-section">
                <div class="dashboard-grid">
                    <?php 
                    $empleados = $conn->query("SELECT * FROM empleados")->fetchAll();
                    foreach ($empleados as $empleado): ?>
                    <div class="smart-card">
                        <h3><?= htmlspecialchars($empleado['nombre']) ?></h3>
                        <p>ID: <?= $empleado['id'] ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <script>
            // Gráfico de empleados
            new Chart(document.getElementById('empleadosChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_column($chart_data, 'fecha')) ?>,
                    datasets: [{
                        label: 'Registro de Empleados',
                        data: <?= json_encode(array_column($chart_data, 'total')) ?>,
                        borderColor: '#6f42c1',
                        tension: 0.4
                    }]
                }
            });

            // Mantener funciones JavaScript originales
            function toggleSidebar() {
                document.querySelector('.sidebar').classList.toggle('collapsed');
                document.querySelector('.main-content').classList.toggle('expanded');
            }

            function toggleTheme() {
                const body = document.body;
                const newTheme = body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                body.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
            }

            function showSection(sectionId) {
                document.querySelectorAll('.dashboard-section').forEach(section => {
                    section.style.display = 'none';
                });
                document.getElementById(sectionId).style.display = 'block';
            }
        </script>
    <?php else: ?>
        <!-- Login Form -->
        <div class="login-container">
            <div class="smart-card" style="max-width: 400px; margin: 5rem auto;">
                <h2 style="text-align: center;"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</h2>
                <?php if(isset($error)): ?>
                    <div class="error-message"><?= $error ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="text" name="nombre" placeholder="Usuario" required>
                    <input type="password" name="password" placeholder="Contraseña" required>
                    <button type="submit" name="login" class="smart-card">
                        <i class="fas fa-unlock"></i> Acceder
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
