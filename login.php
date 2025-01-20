<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Inicio de sesión para el Verificador de Archivos.">
    <meta name="keywords" content="iniciar sesión, verificador, archivos, seguridad">
    <title>Iniciar Sesión</title>
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #f9f9f9;
        }

        .container {
            display: flex;
            width: 100%;
            max-width: 1100px;
            height: 600px;
            background: #ffffff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }

        .left-panel {
            flex: 1;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background-color: #ffffff;
        }

        .left-panel h1 {
            font-size: 2.5rem;
            color: #5a33a5;
            margin-bottom: 20px;
        }

        .left-panel p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-size: 1rem;
            color: #555;
            display: block;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
            background: #f9f9f9;
            transition: border-color 0.3s ease-in-out;
        }

        .form-group input:focus {
            outline: none;
            border-color: #5a33a5;
            background: #fff;
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 20px;
        }

        .forgot-password a {
            color: #5a33a5;
            text-decoration: none;
            font-size: 0.95rem;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: #5a33a5;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease-in-out;
        }

        .btn:hover {
            background: #4a2893;
        }

        .register {
            text-align: center;
            margin-top: 20px;
            font-size: 1rem;
        }

        .register a {
            color: #5a33a5;
            text-decoration: none;
        }

        .register a:hover {
            text-decoration: underline;
        }

        .right-panel {
            flex: 1;
            background: linear-gradient(135deg, #5a33a5, #9c6ee3);
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .right-panel img {
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .right-panel::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            border: 2px dashed rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                max-width: 90%;
                height: auto;
            }

            .right-panel {
                height: 250px;
            }

            .right-panel img {
                width: 150px;
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <h1>Iniciar Sesión</h1>
            <p>Ingresa los datos para continuar.</p>
            <form>
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" placeholder="Ingrese su correo" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" placeholder="Ingrese su contraseña" minlength="8" required>
                </div>
                <div class="forgot-password">
                    <a href="#">¿Has olvidado tu contraseña?</a>
                </div>
                <button type="submit" class="btn">Iniciar Sesión</button>
            </form>
            <div class="register">
                ¿No tienes cuenta? <a href="#">Regístrate</a>
            </div>
        </div>
        <div class="right-panel">
            <img src="esfera-geo.png">
        </div>
    </div>
</body>
</html>
