<?php
session_start();

// Configuración de la base de datos
$servername = "localhost";
$username = "tu_usuario";
$password = "tu_contraseña";
$dbname = "empresa";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener lista de empleados
    $stmt = $conn->prepare("SELECT id, nombre FROM empleados");
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Verificar login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $nombre = $_POST['nombre'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM empleados WHERE nombre = :nombre");
    $stmt->bindParam(':nombre', $nombre);
    $stmt->execute();
    $usuario = $stmt->fetch();
    
    if ($usuario && hash('sha256', $password) === $usuario['password']) {
        $_SESSION['username'] = $usuario['nombre'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Credenciales incorrectas";
    }
}

// Cerrar sesión
if (isset($_GET['logout'])) {
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
    <title>Panel de Empleados</title>
    <style>
        :root {
            --primary: #6f42c1;
            --background: #f8f9fa;
            --text: #212529;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 2rem;
            background: var(--background);
            color: var(--text);
        }

        .dashboard {
            max-width: 1200px;
            margin: 0 auto;
        }

        .empleados-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .empleado-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php if(isset($_SESSION['username'])): ?>
        <div class="dashboard">
            <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1>Bienvenido, <?= htmlspecialchars($_SESSION['username']) ?></h1>
                <a href="?logout=1" style="padding: 0.5rem 1rem; background: var(--primary); color: white; text-decoration: none; border-radius: 5px;">
                    Cerrar Sesión
                </a>
            </header>

            <h2>Lista de Empleados</h2>
            <div class="empleados-grid">
                <?php foreach ($empleados as $empleado): ?>
                    <div class="empleado-card">
                        <h3><?= htmlspecialchars($empleado['nombre']) ?></h3>
                        <p>ID: <?= $empleado['id'] ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="login-container">
            <h1>Iniciar Sesión</h1>
            <?php if(isset($error)): ?>
                <p style="color: red;"><?= $error ?></p>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="nombre" placeholder="Nombre" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <button type="submit">Ingresar</button>
            </form>
        </div>
    <?php endif; ?>
</body>
</html>
