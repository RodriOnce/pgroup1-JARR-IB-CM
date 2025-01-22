<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Registro de usuario para la plataforma TrackZero">
    <title>Registro | TrackZero</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');

        :root {
            --background-gradient-light: radial-gradient(circle, #f5f7fa, #c3cfe2);
            --background-gradient-dark: radial-gradient(circle, #1a1b27, #111118);
            --card-background-light: rgba(255, 255, 255, 0.95);
            --card-background-dark: rgba(255, 255, 255, 0.07);
            --text-color-light: #333;
            --text-color-dark: #eaeaea;
            --header-color-light: #6a11cb;
            --header-color-dark: #ffffff;
            --button-gradient-light: linear-gradient(to right, #6a11cb, #2575fc);
            --button-gradient-dark: linear-gradient(to right, #6a11cb, #2575fc);
            --button-hover-light: linear-gradient(to right, #2575fc, #6a11cb);
            --button-hover-dark: linear-gradient(to right, #2575fc, #6a11cb);
            --input-focus-light: rgba(130, 88, 255, 0.4);
            --input-focus-dark: rgba(130, 88, 255, 0.6);
            --shadow-light: rgba(0, 0, 0, 0.1);
            --shadow-dark: rgba(0, 0, 0, 0.5);
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Roboto', sans-serif;
            background: var(--background-gradient-light);
            color: var(--text-color-light);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }

        body[data-theme="dark"] {
            background: var(--background-gradient-dark);
            color: var(--text-color-dark);
        }

        .container {
            backdrop-filter: blur(15px);
            background: var(--card-background-light);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px var(--shadow-light);
            width: 400px;
            text-align: center;
            animation: fadeIn 1s ease-in-out;
            position: relative;
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }

        body[data-theme="dark"] .container {
            background: var(--card-background-dark);
            box-shadow: 0 10px 30px var(--shadow-dark);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: var(--header-color-light);
            font-weight: bold;
            transition: color 0.3s ease;
        }

        body[data-theme="dark"] h1 {
            color: var(--header-color-dark);
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-size: 1rem;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.05);
            color: inherit;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--input-focus-light);
            box-shadow: 0 0 10px var(--input-focus-light);
        }

        body[data-theme="dark"] .form-group input:focus {
            border-color: var(--input-focus-dark);
            box-shadow: 0 0 10px var(--input-focus-dark);
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 12px;
            font-size: 1.2rem;
            color: white;
            background: var(--button-gradient-light);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            text-transform: uppercase;
            transition: transform 0.2s ease, background 0.3s ease;
            box-shadow: 0 4px 20px var(--shadow-light);
        }

        .btn:hover {
            background: var(--button-hover-light);
            transform: translateY(-2px);
        }

        body[data-theme="dark"] .btn {
            background: var(--button-gradient-dark);
            box-shadow: 0 4px 20px var(--shadow-dark);
        }

        body[data-theme="dark"] .btn:hover {
            background: var(--button-hover-dark);
        }

        .btn:active {
            transform: translateY(0);
        }

        .link {
            margin-top: 15px;
            font-size: 0.9rem;
            color: #6a11cb;
            text-decoration: none;
        }

        .link:hover {
            text-decoration: underline;
        }

        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--button-gradient-light);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        body[data-theme="dark"] .theme-toggle {
            background: var(--button-gradient-dark);
        }

        .theme-toggle::after {
            content: "Modo Oscuro";
        }

        body[data-theme="dark"] .theme-toggle::after {
            content: "Modo Claro";
        }

        .dynamic-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .dynamic-background div {
            position: absolute;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 50%;
            animation: float 12s infinite ease-in-out;
        }

        body[data-theme="dark"] .dynamic-background div {
            background: rgba(255, 255, 255, 0.08);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-30px); }
        }

        .dynamic-background .circle-1 {
            width: 300px;
            height: 300px;
            top: 10%;
            left: 20%;
        }

        .dynamic-background .circle-2 {
            width: 200px;
            height: 200px;
            bottom: 15%;
            right: 25%;
            animation-delay: 4s;
        }

        .dynamic-background .circle-3 {
            width: 150px;
            height: 150px;
            top: 50%;
            left: 50%;
            animation-delay: 6s;
        }

    </style>
    <script>
        function toggleTheme() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            body.setAttribute('data-theme', newTheme);
        }
    </script>
</head>
<body data-theme="light">
    <?php
        // Datos de conexión a la base de datos
        $host = 'localhost';
        $dbname = 'empresa';
        $username = 'root';
        $password = ''; // Cambia esto si tu contraseña es diferente

        try {
            // Crear conexión PDO
            $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Verificar si se enviaron datos desde el formulario
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $user = trim($_POST['username']);
                $pass = trim($_POST['password']);
                $confirm_pass = trim($_POST['confirm_password']);

                // Validar que todos los campos estén completos
                if (empty($user) || empty($pass) || empty($confirm_pass)) {
                    echo "<script>alert('Por favor, completa todos los campos.');</script>";
                } elseif ($pass !== $confirm_pass) {
                    // Verificar que las contraseñas coincidan
                    echo "<script>alert('Las contraseñas no coinciden.');</script>";
                } else {
                    // Encriptar la contraseña
                    $hashed_password = hash('sha256', $pass);

                    // Comprobar si el usuario ya existe
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM empleados WHERE nombre = :nombre");
                    $stmt->bindParam(':nombre', $user);
                    $stmt->execute();
                    $count = $stmt->fetchColumn();

                    if ($count > 0) {
                        echo "<script>alert('El usuario ya existe. Por favor, elige otro nombre.');</script>";
                    } else {
                        // Insertar el nuevo usuario en la base de datos
                        $stmt = $conn->prepare("INSERT INTO empleados (nombre, password) VALUES (:nombre, :password)");
                        $stmt->bindParam(':nombre', $user);
                        $stmt->bindParam(':password', $hashed_password);
                        $stmt->execute();

                        echo "<script>alert('Registro exitoso. Ahora puedes iniciar sesión.'); window.location.href = 'login.html';</script>";
                    }
                }
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    ?>
    <button class="theme-toggle" onclick="toggleTheme()"></button>
    <div class="dynamic-background">
        <div class="circle-1"></div>
        <div class="circle-2"></div>
        <div class="circle-3"></div>
    </div>

    <div class="container">
        <h1>Registro</h1>
        <form method="POST">
            <div class="form-group">
                <label for="username"><strong>Nombre de Usuario</strong></label>
                <input type="text" id="username" name="username" placeholder="Ingrese su nombre de usuario" required>
            </div>
            <div class="form-group">
                <label for="password"><strong>Contraseña</strong></label>
                <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" minlength="8" required>
            </div>
            <div class="form-group">
                <label for="confirm_password"><strong>Confirmar Contraseña</strong></label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirme su contraseña" minlength="8" required>
            </div>
            <button type="submit" class="btn">Registrarse</button>
        </form>
        <a href="login.html" class="link">¿Ya tienes cuenta? Inicia sesión</a>
    </div>
</body>
</html>
