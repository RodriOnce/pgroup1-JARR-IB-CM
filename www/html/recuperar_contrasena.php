<?php
session_start();

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "momo";
$dbname = "empresa";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

$show_security = false;
$pregunta_seguridad = "";
$error_msg = "";

// Proceso para mostrar solo un formulario a la vez
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_or_email'])) {
    $user_or_email = trim($_POST['user_or_email']);

    try {
        $stmt = $pdo->prepare("SELECT id, user, pregunta_seguridad FROM empleados WHERE user = ? OR mail = ?");
        $stmt->execute([$user_or_email, $user_or_email]);
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($empleado) {
            $_SESSION['recuperacion_id_empleado'] = $empleado['id'];
            $_SESSION['recuperacion_pregunta'] = $empleado['pregunta_seguridad'];
            $show_security = true;
            $pregunta_seguridad = $empleado['pregunta_seguridad'];
        } else {
            $error_msg = "Usuario o correo electrónico no encontrado.";
        }
    } catch (PDOException $e) {
        $error_msg = "Error al buscar cuenta: " . htmlspecialchars($e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña | Trackzero</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts para el estilo profesional -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3146b4;
            --secondary: #5366e3;
            --background: #f9fafc;
            --text: #212529;
            --card-bg: #ffffff;
            --header-bg: #3146b4;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
        }
        [data-theme="dark"] {
            --primary: #7f8cff;
            --secondary: #5366e3;
            --background: #181b22;
            --text: #f9fafc;
            --card-bg: #23253a;
            --header-bg: #3146b4;
        }
        html, body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: 'Montserrat', Arial, sans-serif;
            background: linear-gradient(135deg, var(--primary), #141831 90%);
            color: var(--text);
        }
        .container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: var(--card-bg);
            border-radius: 28px;
            box-shadow: 0 8px 32px 0 rgba(49, 70, 180, 0.17);
            padding: 2.7rem 2.1rem 2.1rem 2.1rem;
            width: 100%;
            max-width: 440px;
            margin: 24px;
            text-align: center;
            transition: background 0.3s, color 0.3s;
            position: relative;
        }
        .logo {
            font-size: 2.1rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--primary);
            margin-bottom: 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .logo img {
            height: 35px;
            vertical-align: middle;
        }
        h2 {
            color: var(--primary);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            margin-top: 0.2rem;
        }
        label {
            display: block;
            margin-bottom: 0.22rem;
            font-weight: 600;
            color: var(--primary);
            text-align: left;
            font-size: 1.04rem;
        }
        .form-group {
            margin-bottom: 1.1rem;
            text-align: left;
        }
        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 0.72rem 0.85rem;
            border: 1.7px solid var(--secondary);
            border-radius: 13px;
            font-size: 1.02rem;
            background: var(--background);
            color: var(--text);
            outline: none;
            box-sizing: border-box;
            margin-top: 0.1rem;
            margin-bottom: 0.1rem;
            transition: border-color 0.22s;
        }
        input[type="text"]:focus, input[type="email"]:focus {
            border-color: var(--primary);
        }
        .btn {
            width: 100%;
            padding: 0.9rem 0;
            border: none;
            border-radius: 15px;
            font-size: 1.13rem;
            font-weight: 700;
            cursor: pointer;
            background: var(--primary);
            color: #fff;
            margin-top: 0.12rem;
            margin-bottom: 0.4rem;
            box-shadow: 0 2px 8px 0 rgba(49, 70, 180, 0.07);
            transition: background 0.18s, transform 0.13s;
        }
        .btn:hover, .btn:focus {
            background: var(--secondary);
            transform: translateY(-1.5px) scale(1.017);
        }
        .message {
            margin-top: 1.2rem;
            font-size: 1rem;
            font-weight: 500;
        }
        .error {
            color: var(--danger);
        }
        .success {
            color: var(--success);
        }
        hr {
            margin: 2.1rem 0 1.2rem 0;
            border: none;
            border-top: 2px solid #e5e8f2;
        }
        .pregunta-titulo {
            color: var(--secondary);
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .pregunta-texto {
            color: var(--text);
            font-size: 1.09rem;
            font-weight: 600;
            margin-bottom: 1.15rem;
        }
        .form-section {
            margin-top: 1.6rem;
        }
        @media (max-width: 600px) {
            .card {
                padding: 1.2rem 0.4rem;
                max-width: 97vw;
            }
            .logo { font-size: 1.3rem; }
        }
        .theme-switch {
            position: absolute;
            top: 18px;
            right: 18px;
            cursor: pointer;
            background: var(--card-bg);
            color: var(--primary);
            border: 1.5px solid var(--secondary);
            padding: 7px 13px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            transition: background 0.25s, color 0.25s;
            z-index: 3;
        }
        .theme-switch:hover {
            background: var(--primary);
            color: #fff;
        }
    </style>
    <script>
        function toggleTheme() {
            let html = document.documentElement;
            let theme = html.getAttribute('data-theme');
            if (theme === 'dark') {
                html.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
            } else {
                html.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
        }
        window.onload = function() {
            let saved = localStorage.getItem('theme');
            if (saved) document.documentElement.setAttribute('data-theme', saved);
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="card">
            <button class="theme-switch" onclick="toggleTheme()">🌙 / ☀️</button>
            <div class="logo">
                <img src="https://cdn-icons-png.flaticon.com/512/3602/3602123.png" alt="Trackzero" />
                TRACKZERO
            </div>
            <h2>Recuperar Contraseña</h2>
            <!-- Formulario de búsqueda de cuenta -->
            <?php if (!$show_security): ?>
                <form action="recuperar_contrasena.php" method="POST" autocomplete="off" class="form-section">
                    <div class="form-group">
                        <label for="user_or_email">Usuario o Correo Electrónico</label>
                        <input type="text" id="user_or_email" name="user_or_email" required
                            placeholder="Ingresa tu usuario o correo">
                    </div>
                    <button type="submit" class="btn">Buscar Cuenta</button>
                </form>
                <?php if ($error_msg): ?>
                    <div class='message error'><?= htmlspecialchars($error_msg) ?></div>
                <?php endif; ?>
            <?php endif; ?>
            <!-- Formulario de pregunta de seguridad -->
            <?php if ($show_security): ?>
                <form action="verificar_respuesta.php" method="POST" autocomplete="off" class="form-section">
                    <hr>
                    <div class="pregunta-titulo">Pregunta de Seguridad</div>
                    <div class="pregunta-texto"><?= htmlspecialchars($pregunta_seguridad) ?></div>
                    <div class="form-group">
                        <label for="respuesta_usuario">Tu Respuesta</label>
                        <input type="text" id="respuesta_usuario" name="respuesta_usuario" required
                               placeholder="Escribe tu respuesta">
                    </div>
                    <button type="submit" class="btn">Verificar Respuesta</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
