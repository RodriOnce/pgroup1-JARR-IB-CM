<?php
session_start();

// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "empresa";

// Conexión a la base de datos
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
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['username'] = $usuario['nombre'];
        // Verificar si es admin (usar nombre de usuario como criterio temporal)
        $_SESSION['es_admin'] = ($usuario['nombre'] === 'admin');
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

// Obtener datos para admin
$stats = $detalles = [];
if (isset($_SESSION['es_admin']) && $_SESSION['es_admin']) {
    // Estadísticas
    $stats = [
        'uploads' => $conn->query("SELECT COUNT(*) FROM archivos WHERE tipo = 'subido'")->fetchColumn(),
        'downloads' => $conn->query("SELECT COUNT(*) FROM archivos WHERE tipo = 'descargado'")->fetchColumn(),
        'deleted' => $conn->query("SELECT COUNT(*) FROM archivos WHERE eliminado = 1")->fetchColumn(),
        'empleados' => $conn->query("SELECT COUNT(*) FROM empleados")->fetchColumn()
    ];

    // Detalles
    $detalles = [
        'archivos' => $conn->query("SELECT * FROM archivos")->fetchAll(),
        'empleados' => $conn->query("SELECT * FROM empleados")->fetchAll()
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Empresa</title>
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
            transition: all 0.3s;
        }

        .dashboard {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background: var(--card-bg);
            padding: 1rem;
            border-right: 1px solid #ddd;
        }

        .main-content {
            padding: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .detail-panel {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 8px;
            margin-top: 2rem;
            display: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 1rem;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        .login-box {
            max-width: 400px;
            margin: 5rem auto;
            padding: 2rem;
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body data-theme="light">
    <?php if(isset($_SESSION['username'])): ?>
        <div class="dashboard">
            <!-- Sidebar -->
            <div class="sidebar">
                <h2>Menú Principal</h2>
                <div class="stat-card" onclick="showDetail('inicio')">
                    <i class="fas fa-home"></i> Inicio
                </div>
                <?php if($_SESSION['es_admin']): ?>
                    <div class="stat-card" onclick="showDetail('archivos')">
                        <i class="fas fa-folder"></i> Gestión de Archivos
                    </div>
                    <div class="stat-card" onclick="showDetail('empleados')">
                        <i class="fas fa-users"></i> Gestión de Empleados
                    </div>
                <?php endif; ?>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding: 1rem; background: var(--header-bg); color: white; border-radius: 8px;">
                    <button onclick="toggleTheme()" style="background: none; border: none; color: inherit; cursor: pointer;">
                        <i class="fas fa-moon"></i> Tema
                    </button>
                    <h1>Bienvenido, <?= htmlspecialchars($_SESSION['username']) ?></h1>
                    <a href="?logout=1" style="color: white; text-decoration: none;">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>

                <!-- Contenido Dinámico -->
                <?php if($_SESSION['es_admin']): ?>
                    <!-- Panel Admin -->
                    <div class="stats-grid">
                        <div class="stat-card" onclick="showDetail('subidos')">
                            <h3><i class="fas fa-upload"></i> Subidos</h3>
                            <p><?= $stats['uploads'] ?? 0 ?></p>
                        </div>
                        
                        <div class="stat-card" onclick="showDetail('descargas')">
                            <h3><i class="fas fa-download"></i> Descargas</h3>
                            <p><?= $stats['downloads'] ?? 0 ?></p>
                        </div>
                        
                        <div class="stat-card" onclick="showDetail('empleados')">
                            <h3><i class="fas fa-users"></i> Empleados</h3>
                            <p><?= $stats['empleados'] ?? 0 ?></p>
                        </div>
                    </div>

                    <!-- Detalles Archivos -->
                    <div class="detail-panel" id="subidos-detail">
                        <h3>Archivos Subidos</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalles['archivos'] as $archivo): ?>
                                    <?php if($archivo['tipo'] === 'subido'): ?>
                                    <tr>
                                        <td><?= $archivo['id'] ?></td>
                                        <td><?= htmlspecialchars($archivo['nombre_archivo']) ?></td>
                                        <td><?= $archivo['fecha'] ?></td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Detalles Empleados -->
                    <div class="detail-panel" id="empleados-detail">
                        <h3>Listado de Empleados</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalles['empleados'] as $emp): ?>
                                <tr>
                                    <td><?= $emp['id'] ?></td>
                                    <td><?= htmlspecialchars($emp['nombre']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php else: ?>
                    <!-- Panel Usuario Normal -->
                    <div class="stat-card">
                        <h3><i class="fas fa-user"></i> Panel de Usuario</h3>
                        <p>Bienvenido al sistema de gestión interno</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Formulario Login -->
        <div class="login-box">
            <h2 style="text-align: center;"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</h2>
            <?php if(isset($error)): ?>
                <div style="color: red; margin: 1rem 0;"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="nombre" placeholder="Usuario" required
                    style="width: 100%; padding: 0.8rem; margin: 0.5rem 0; border: 1px solid #ddd; border-radius: 5px;">
                <input type="password" name="password" placeholder="Contraseña" required
                    style="width: 100%; padding: 0.8rem; margin: 0.5rem 0; border: 1px solid #ddd; border-radius: 5px;">
                <button type="submit"
                    style="width: 100%; padding: 1rem; background: var(--primary); color: white; border: none; border-radius: 5px; cursor: pointer;">
                    <i class="fas fa-unlock"></i> Ingresar
                </button>
            </form>
        </div>
    <?php endif; ?>

    <script>
        // Funcionalidades JavaScript
        function showDetail(tipo) {
            document.querySelectorAll('.detail-panel').forEach(panel => {
                panel.style.display = 'none';
            });
            const panel = document.getElementById(`${tipo}-detail`);
            if(panel) panel.style.display = 'block';
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
