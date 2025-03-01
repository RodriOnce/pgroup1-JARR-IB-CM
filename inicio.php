<?php
session_start();

// Configuración de la base de datos (ajusta estos valores)
$servername = "localhost";
$username = "root";     // Usuario por defecto en XAMPP/MAMP
$password = "";         // Contraseña vacía por defecto
$dbname = "empresa";    // Nombre de tu base de datos

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: Verifica tus credenciales de MySQL. Detalle: " . $e->getMessage());
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

// Obtener lista de empleados
$empleados = [];
if (isset($_SESSION['username'])) {
    try {
        $stmt = $conn->query("SELECT id, nombre FROM empleados");
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        die("Error al obtener empleados: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Empleados</title>
    <style>
        :root {
            --primary: #6f42c1;
            --background: #f8f9fa;
            --text: #212529;
            --card-bg: #ffffff;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            min-height: 100vh;
            background: var(--background);
            color: var(--text);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .login-box {
            max-width: 400px;
            margin: 5rem auto;
            padding: 2rem;
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .empleados-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .empleado-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .empleado-card:hover {
            transform: translateY(-3px);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border-radius: 10px;
        }

        input, button {
            width: 100%;
            padding: 0.8rem;
            margin: 0.5rem 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            background: var(--primary);
            color: white;
            border: none;
            cursor: pointer;
            transition: opacity 0.3s;
        }

        button:hover {
            opacity: 0.9;
        }

        .error {
            color: #dc3545;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <?php if(isset($_SESSION['username'])): ?>
        <div class="container">
            <div class="header">
                <h1>Bienvenido, <?= htmlspecialchars($_SESSION['username']) ?></h1>
                <a href="?logout=1" style="color: white; text-decoration: none;">Cerrar Sesión</a>
            </div>

            <h2>Lista de Empleados</h2>
            <div class="empleados-grid">
                <?php foreach ($empleados as $empleado): ?>
                    <div class="empleado-card">
                        <h3><?= htmlspecialchars($empleado['nombre']) ?></h3>
                        <p>ID: <?= htmlspecialchars($empleado['id']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="container">
            <div class="login-box">
                <h1 style="text-align: center; margin-bottom: 2rem;">Iniciar Sesión</h1>
                <?php if(isset($error)): ?>
                    <div class="error"><?= $error ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="text" name="nombre" placeholder="Nombre de usuario" required>
                    <input type="password" name="password" placeholder="Contraseña" required>
                    <button type="submit" name="login">Ingresar</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
