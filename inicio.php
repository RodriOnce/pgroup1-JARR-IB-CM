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

    try {
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
    } catch(PDOException $e) {
        $error = "Error al verificar credenciales";
    }
}

// Cerrar sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: inici.php");
    exit();
}

// Obtener estadísticas básicas
$stats = [
    'total_empleados' => $conn->query("SELECT COUNT(*) FROM empleados")->fetchColumn(),
    'activos' => $conn->query("SELECT COUNT(*) FROM empleados")->fetchColumn(), // Mismo valor de ejemplo
    'ultimo_registro' => $conn->query("SELECT MAX(id) FROM empleados")->fetchColumn()
];

// Obtener lista de empleados
$empleados = $conn->query("SELECT id, nombre FROM empleados")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Employee Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6f42c1;
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

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            padding: 0;
            background: var(--background-color);
            color: var(--text-color);
            transition: all var(--transition-speed) ease;
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            background: var(--card-background);
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
            z-index: 1000;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }

        .smart-card {
            background: var(--card-background);
            padding: 1.5rem;
            margin: 1rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
    </style>
</head>
<body data-theme="light">
    <?php if(isset($_SESSION['username'])): ?>
        <div class="sidebar">
            <h2 style="text-align: center; margin-bottom: 2rem;">
                <i class="fas fa-users"></i> Gestión
            </h2>
            <nav>
                <div class="smart-card">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </div>
                <div class="smart-card">
                    <i class="fas fa-list"></i> Empleados (<?= $stats['total_empleados'] ?>)
                </div>
                <div class="smart-card">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </div>
            </nav>
        </div>

        <div class="main-content">
            <div class="smart-card" style="margin-bottom: 2rem;">
                <h1>Bienvenido, <?= htmlspecialchars($_SESSION['username']) ?></h1>
                <a href="?logout=1" style="color: var(--primary-color);">Cerrar Sesión</a>
            </div>

            <div class="dashboard-grid">
                <div class="smart-card">
                    <h3><i class="fas fa-users"></i> Total Empleados</h3>
                    <p style="font-size: 2.5rem;"><?= $stats['total_empleados'] ?></p>
                </div>
                
                <div class="smart-card">
                    <h3><i class="fas fa-id-badge"></i> Último ID Registrado</h3>
                    <p style="font-size: 2.5rem;"><?= $stats['ultimo_registro'] ?></p>
                </div>
            </div>

            <div class="smart-card" style="margin-top: 2rem;">
                <h3><i class="fas fa-list"></i> Lista de Empleados</h3>
                <div class="dashboard-grid">
                    <?php foreach ($empleados as $empleado): ?>
                        <div class="smart-card">
                            <p><strong>ID:</strong> <?= $empleado['id'] ?></p>
                            <p><strong>Nombre:</strong> <?= htmlspecialchars($empleado['nombre']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div style="max-width: 400px; margin: 5rem auto; padding: 2rem;">
            <div class="smart-card">
                <h2 style="text-align: center;"><i class="fas fa-sign-in-alt"></i> Login</h2>
                <?php if(isset($error)): ?>
                    <div style="color: red; margin: 1rem 0;"><?= $error ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="text" name="nombre" placeholder="Usuario" required 
                           style="width: 100%; padding: 0.8rem; margin: 0.5rem 0;">
                    <input type="password" name="password" placeholder="Contraseña" required 
                           style="width: 100%; padding: 0.8rem; margin: 0.5rem 0;">
                    <button type="submit" name="login" 
                            style="width: 100%; padding: 1rem; background: var(--primary-color); color: white; border: none;">
                        <i class="fas fa-unlock"></i> Ingresar
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
