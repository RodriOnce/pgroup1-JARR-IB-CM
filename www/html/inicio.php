<?php
session_start();

// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "momo";
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
        header("Location: inicio.php");
        exit();
    } else {
        $error = "Credenciales incorrectas";
    }
}

// CAMBIAR USUARIO DE DEPARTAMENTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_departamento'])) {
    $usuario_id = $_POST['usuario'];
    $nuevo_dpto = $_POST['departamento'];

    $stmt = $conn->prepare("UPDATE empleados SET dpt = :dpt WHERE id = :id");
    $stmt->bindParam(':dpt', $nuevo_dpto);
    $stmt->bindParam(':id', $usuario_id);
    $stmt->execute();

    header("Location: inicio.php");
    exit();
}

// Dar de baja usuario (ELIMINAR usuario de la base de datos)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['baja_usuario'])) {
    $usuario_id = $_POST['baja_usuario'];
    $stmt = $conn->prepare("DELETE FROM empleados WHERE id = :id");
    $stmt->bindParam(':id', $usuario_id);
    $stmt->execute();
    header("Location: inicio.php?baja=ok");
    exit();
}

if(isset($_GET['aceptar'])) {
    $usuario_id = $_GET['aceptar'];
    $stmt = $conn->prepare("UPDATE empleados SET status = 'activo' WHERE id = :id");
    $stmt->bindParam(':id', $usuario_id);
    $stmt->execute();

    header("Location: inicio.php");
    exit();
}

if(isset($_GET['rechazar'])) {
    $usuario_id = $_GET['rechazar'];
    $stmt = $conn->prepare("UPDATE empleados SET status = 'inactivo' WHERE id = :id");
    $stmt->bindParam(':id', $usuario_id);
    $stmt->execute();

    header("Location: inicio.php");
    exit();
}

// Cerrar sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.html");
    exit();
}

// Obtener datos
$usuarios_activos = $conn->query("SELECT * FROM empleados WHERE status = 'activo'")->fetchAll(PDO::FETCH_ASSOC);
$usuarios_pendientes = $conn->query("SELECT * FROM empleados WHERE status = 'pendiente'")->fetchAll(PDO::FETCH_ASSOC);
$usuarios_inactivos = $conn->query("SELECT * FROM empleados WHERE status = 'inactivo'")->fetchAll(PDO::FETCH_ASSOC);

// Función para obtener archivos
function obtenerArchivosUnicos($directorio) {
    $archivos = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorio));

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $nombre_archivo = $file->getBasename();
            $archivos[$nombre_archivo] = true;
        }
    }
    return array_keys($archivos);
}

$directorio_archivos = '/var/www/html/archivos/';
$archivos_subidos = obtenerArchivosUnicos($directorio_archivos);

$archivos = [
    'subidos' => [],
    'descargados' => [],
    'eliminados' => []
];
?>

<?php
    // --- CONFIGURACIÓN Y CONEXIÓN A LA BASE DE DATOS 'viruses' ---
    $servername_viruses = "localhost";
    $username_viruses = "root";
    $password_viruses = "momo";
    $dbname_viruses = "viruses";

    try {
        $pdo_viruses = new PDO("mysql:host=$servername_viruses;dbname=$dbname_viruses;charset=utf8mb4", $username_viruses, $password_viruses);
        $pdo_viruses->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo_viruses->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo_viruses->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    } catch (PDOException $e) {
        die("Error de conexión a la base de datos 'viruses': " . $e->getMessage());
    }

    // --- LÓGICA PARA OBTENER LOS ARCHIVOS DESCARGADOS ---
    $archivos_descargados = [];
    try {
        // Ordenamos por download_count de forma descendente para ver los más descargados primero.
        $stmt_descargados = $pdo_viruses->query("SELECT filename, download_count FROM archivos WHERE download_count > 0 ORDER BY download_count DESC");
        $archivos_descargados = $stmt_descargados->fetchAll();
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error al cargar archivos descargados: " . $e->getMessage() . "</p>";
    }
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3146b4;
            --secondary: #5366e3;
            --background: #f9fafc;
            --text: #233172;
            --card-bg: #ffffff;
            --header-bg: #3146b4;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --result-bg: #eaf0fb; /* Añadido para el generador de contraseñas */
        }

        [data-theme="dark"] {
            --primary: #7f8cff;
            --secondary: #5366e3;
            --background: #181b22;
            --text: #f9fafc;
            --card-bg: #23253a;
            --header-bg: #3146b4;
            --result-bg: #21233b; /* Añadido para el generador de contraseñas */
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background: var(--background);
            color: var(--text);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
         /* Estilos generales del generador de contraseñas */
        #generador-contrasenas {
            background: var(--background); /* Ajuste para que el fondo del generador sea el mismo que el main-content */
            padding: 2rem;
            border-radius: 15px;
            margin: 2rem 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .container-password-gen { /* Renombrado de .container a .container-password-gen para evitar conflictos */
            background-color: var(--card-bg);
            border-radius: 22px;
            box-shadow: 0 8px 32px 0 rgba(49, 70, 180, 0.14);
            padding: 2.2rem 1.3rem 2rem 1.3rem;
            margin: 2.5rem auto;
            max-width: 480px;
            min-width: 0;
            transition: background 0.3s, color 0.3s;
            position: relative;
        }
        h1.password-gen-title { /* Clase específica para el título del generador */
            text-align: center;
            margin-bottom: 1.3rem;
            font-size: 1.7rem;
            font-weight: 700;
            color: var(--primary);
            border-bottom: 1.5px solid #e5e8f2;
            padding-bottom: 0.7rem;
            letter-spacing: 1px;
        }
        .radio-options {
            display: flex;
            justify-content: center;
            gap: 1.3rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.7rem;
            border-bottom: 1px solid #e0e0e0;
        }
        .form-check-input[type=radio] {
            accent-color: var(--primary);
            width: 1.1em;
            height: 1.1em;
            margin-top: 0;
            margin-right: 4px;
            vertical-align: middle;
            cursor: pointer;
        }
        .form-check-label {
            font-size: 1rem;
            cursor: pointer;
            font-weight: 600;
            color: var(--primary);
        }
        .input-group {
            display: flex;
            align-items: center;
            margin-bottom: 0.85rem;
            gap: 0.7rem;
        }
        .input-group label {
            min-width: 116px;
            text-align: left;
            font-weight: 500;
            font-size: 1.01rem;
            color: var(--primary);
        }
        .form-control {
            width: 90px;
            border-radius: 10px;
            border: 1.5px solid var(--secondary);
            padding: 0.63rem 0.8rem;
            font-size: 1rem;
            background: var(--background);
            color: var(--text);
            transition: border-color 0.18s;
            outline: none;
        }
        .form-control:focus {
            border-color: var(--primary);
        }
        .btn-primary-password-gen { /* Clase específica para el botón primario del generador */
            width: 100%;
            padding: 0.74rem;
            margin-top: 0.7rem;
            background-color: var(--primary);
            border: none;
            border-radius: 13px;
            color: #fff;
            font-weight: 700;
            font-size: 1.09rem;
            box-shadow: 0 2px 8px 0 rgba(49, 70, 180, 0.08);
            cursor: pointer;
            transition: background 0.17s, transform 0.13s;
        }
        .btn-primary-password-gen:hover, .btn-primary-password-gen:focus {
            background-color: var(--secondary);
            transform: translateY(-1.5px) scale(1.015);
        }
        .btn-secondary-password-gen { /* Clase específica para el botón secundario del generador */
            background-color: var(--secondary);
            border: none;
            color: #fff;
            border-radius: 10px;
            padding: 0.55rem 1.1rem;
            font-size: 1rem;
            font-weight: 600;
            transition: background 0.15s;
            margin-top: 0.2rem;
            cursor: pointer;
        }
        .btn-secondary-password-gen:hover {
            background-color: var(--primary);
        }
        .btn-sm.btn-outline-secondary {
            background: transparent;
            color: var(--primary);
            border: 1.3px solid var(--primary);
            font-weight: 600;
            padding: 0.35rem 0.6rem;
            font-size: 0.93rem;
            border-radius: 8px;
            margin-left: 0.6rem;
            transition: background 0.15s, color 0.15s, border 0.15s;
        }
        .btn-sm.btn-outline-secondary:hover {
            background: var(--primary);
            color: #fff;
            border-color: var(--secondary);
        }
        h2.password-gen-subtitle { /* Clase específica para el subtítulo del generador */
            font-size: 1.18rem;
            margin-top: 1.3rem;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 0.5rem;
            color: var(--secondary);
            font-weight: 700;
            text-align: left;
            margin-bottom: 0.9rem;
        }
        #resultado-container {
            background-color: var(--result-bg);
            border-radius: 11px;
            padding: 0.53rem 1rem 0.53rem 0.7rem;
            margin-top: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        #resultado {
            color: var(--text);
            font-size: 1.1rem;
            word-break: break-all;
            flex-grow: 1;
            padding: 0.3rem 0;
            min-height: 25px; /* Para mantener la altura aunque no haya resultado */
        }
        #copiar-btn {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 0.6rem 0.9rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }
        #copiar-btn:hover {
            background: var(--primary);
        }
        .range-wrap {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .range-wrap input[type="range"] {
            flex-grow: 1;
            -webkit-appearance: none;
            width: 100%;
            height: 8px;
            background: #d3d3d3;
            outline: none;
            opacity: 0.7;
            -webkit-transition: .2s;
            transition: opacity .2s;
            border-radius: 5px;
        }
        .range-wrap input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary);
            cursor: pointer;
            box-shadow: 0 0 5px rgba(0,0,0,0.2);
        }
        .range-wrap input[type="range"]::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary);
            cursor: pointer;
            box-shadow: 0 0 5px rgba(0,0,0,0.2);
        }
        .range-value {
            min-width: 30px;
            text-align: center;
            font-weight: 600;
            color: var(--text);
        }
        .form-check {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }
        .form-check input[type="checkbox"] {
            accent-color: var(--primary);
            width: 1.1em;
            height: 1.1em;
            margin-right: 8px;
        }
        .form-check label {
            font-size: 1rem;
            color: var(--text);
        }

        /* Estilos existentes */
        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background: var(--card-bg);
            padding: 2rem;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
        }

        .main-content {
            padding: 2rem;
        }

        .stats-grid {
            display: grid;
            gap: 1.5rem;
            margin: 2rem 0;
            align-items: start;
        }

        /* Ajuste para la cuadrícula de 2 columnas (Archivos) */
        .stats-grid.two-columns {
            grid-template-columns: repeat(2, 1fr); /* Ocupa el ancho completo, dividido en 2 columnas */
            margin-bottom: 0; /* Eliminar margen inferior para que se vea más pegado a la siguiente fila */
        }

        /* Ajuste para la cuadrícula de 3 columnas (Usuarios) */
        .stats-grid.three-columns {
            grid-template-columns: repeat(3, 1fr);
            margin-top: 1.5rem; /* Espacio entre las dos filas de estadísticas */
        }


        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
            transition: height 0.3s ease;
        }

        .stat-card:hover::after {
            height: 100%;
            opacity: 0.1;
        }

        .detalles-panel {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            background: var(--card-bg);
            margin-top: 1rem;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 0 0 15px 15px;
            width: 100%;
            padding: 0 1rem;
            box-sizing: border-box;
            padding-top: 0;
            padding-bottom: 0;
        }

        .detalles-panel.abierto {
            max-height: 400px;
            overflow-y: auto;
            padding: 1rem;
            border: 1px solid rgba(0,0,0,0.1);
        }

        .tabla-detalles {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .tabla-detalles th,
        .tabla-detalles td {
            padding: 12px 15px;
            text-align: left;
        }

        .tabla-detalles th {
            background: var(--primary);
            color: white;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .tabla-detalles tr:not(:last-child) td {
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .tabla-detalles td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 250px;
        }

        .gestion-section {
            display: none;
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 15px;
            margin: 2rem 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .usuario-pendiente {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            margin: 1rem 0;
            background: rgba(0,0,0,0.05);
            border-radius: 8px;
        }
    </style>
</head>
<body data-theme="light">
    <?php if(isset($_SESSION['username'])): ?>
        <div class="dashboard-container">
            <div class="sidebar">
                <h2 style="color: var(--primary); text-align: center; margin-bottom: 2rem;">
                    <i class="fas fa-rocket"></i> Menú
                </h2>
                <div class="stat-card" onclick="mostrarSeccion('panel-control')">
                    <i class="fas fa-tachometer-alt"></i> Panel de Control
                </div>
                <div class="stat-card" onclick="mostrarSeccion('contenedor-gestion-usuario')">
                    <i class="fas fa-users-cog"></i> Opciones de Usuario
                </div>
                <div class="stat-card" onclick="mostrarSeccion('gestion-pendientes')">
                    <i class="fas fa-user-check"></i> Gestionar Pendientes
                </div>
                <div class="stat-card" onclick="mostrarSeccion('generador-contrasenas')">
                    <i class="fas fa-question-circle"></i> Generador de Contraseñas
                </div>
            </div>

            <div class="main-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding: 1.5rem; background: var(--header-bg); color: white; border-radius: 15px;">
                    <div>
                        <button onclick="toggleTheme()" style="background: none; border: none; color: white; cursor: pointer; padding: 0.8rem 1.2rem; border-radius: 8px;">
                            <i class="fas fa-moon"></i> Tema
                        </button>
                    </div>
                    <h1 style="margin: 0;">Bienvenido, <?= htmlspecialchars($_SESSION['username']) ?></h1>
                    <a href="?logout=1" style="color: white; text-decoration: none; padding: 0.8rem 1.2rem; border-radius: 8px; background: var(--danger);">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>

        <div id="panel-control">
                    <div class="stats-grid two-columns">
                        <div class="stat-card" onclick="toggleDetalles('subidos')">
                            <h3><i class="fas fa-upload"></i> Archivos Subidos</h3>
                            <p style="font-size: 2.5rem; margin: 1rem 0;"><?= count($archivos_subidos) ?></p>
                            <div class="detalles-panel" id="subidos">
                                <table class="tabla-detalles">
                                    <thead><tr><th>Nombre</th></tr></thead>
                                    <tbody>
                                        <?php if (count($archivos_subidos) > 0): ?>
                                            <?php foreach ($archivos_subidos as $archivo): ?>
                                                <tr><td><?= htmlspecialchars($archivo) ?></td></tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td>No hay archivos subidos</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

        <div class="stat-card" onclick="toggleDetalles('descargados')">
                            <h3><i class="fas fa-download"></i> Descargados</h3>
                            <p style="font-size: 2.5rem; margin: 1rem 0;"><?= count($archivos_descargados) ?></p>

                            <div class="detalles-panel" id="descargados">
                                <table class="tabla-detalles">
                                    <thead>
                                        <tr>
                                            <th>Nombre del Fichero</th>
                                            <th>Nº de descargas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($archivos_descargados)): ?>
                                            <?php foreach ($archivos_descargados as $archivo): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($archivo['filename']) ?></td>
                                                    <td><?= htmlspecialchars($archivo['download_count']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="2">No hay archivos registrados.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

        <div class="stats-grid three-columns">
                        <div class="stat-card" onclick="toggleDetalles('activos')">
                            <h3><i class="fas fa-user-check"></i> Activos</h3>
                            <p style="font-size: 2.5rem; margin: 1rem 0;"><?= count($usuarios_activos) ?></p>
                            <div class="detalles-panel" id="activos">
                                <table class="tabla-detalles">
                                    <thead><tr><th>ID</th><th>Nombre</th><th>Dept.</th></tr></thead>
                                    <tbody>
                                        <?php foreach($usuarios_activos as $usuario): ?>
                                        <tr>
                                            <td><?= $usuario['id'] ?></td>
                                            <td><?= htmlspecialchars($usuario['name']) ?></td>
                                            <td><?= htmlspecialchars($usuario['dpt']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

        <div class="stat-card" onclick="toggleDetalles('pendientes')">
                            <h3><i class="fas fa-user-clock"></i> Pendientes</h3>
                            <p style="font-size: 2.5rem; margin: 1rem 0;"><?= count($usuarios_pendientes) ?></p>
                            <div class="detalles-panel" id="pendientes">
                                <table class="tabla-detalles">
                                    <thead><tr><th>Nombre</th></tr></thead>
                                    <tbody>
                                        <?php if (count($usuarios_pendientes) > 0): ?>
                                            <?php foreach ($usuarios_pendientes as $usuario): ?>
                                                <tr><td><?= htmlspecialchars($usuario['name']) ?></td></tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td>No hay usuarios pendientes</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

        <div class="stat-card" onclick="toggleDetalles('inactivos')">
                            <h3><i class="fas fa-user-slash"></i> Inactivos</h3>
                            <p style="font-size: 2.5rem; margin: 1rem 0;"><?= count($usuarios_inactivos) ?></p>
                            <div class="detalles-panel" id="inactivos">
                                <table class="tabla-detalles">
                                    <thead><tr><th>Nombre</th></tr></thead>
                                    <tbody>
                                        <?php if (count($usuarios_inactivos) > 0): ?>
                                            <?php foreach ($usuarios_inactivos as $usuario): ?>
                                                <tr><td><?= htmlspecialchars($usuario['name']) ?></td></tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td>No hay usuarios inactivos</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

        <div id="contenedor-gestion-usuario" class="gestion-section" style="display: flex; flex-wrap: wrap; gap: 2rem;">

                <div id="gestion-departamentos" style="flex: 1; min-width: 300px;"> <h2><i class="fas fa-building"></i> Cambiar Departamento</h2>
                    <form method="POST" style="margin-top: 2rem;">
                        <div style="display: grid; gap: 1rem; max-width: 500px;">
                            <select name="usuario" required style="padding: 1rem; border-radius: 8px;">
                                <?php foreach($usuarios_activos as $usuario): ?>
                                    <option value="<?= $usuario['id'] ?>"><?= htmlspecialchars($usuario['name']) ?> (<?= $usuario['dpt'] ?>)</option>
                                <?php endforeach; ?>
                            </select>

                            <select name="departamento" required style="padding: 1rem; border-radius: 8px;">
                                <option value="IT">IT</option>
                                <option value="DIR">Dirección</option>
                                <option value="ADM">Administración</option>
                                <option value="SL">Ventas</option>
                                <option value="MKT">Marketing</option>
                                <option value="LGTC">Logística</option>
                            </select>

                            <button type="submit" name="cambiar_departamento"
                                    style="padding: 1rem; background: var(--primary); color: white; border: none; border-radius: 8px;">
                                <i class="fas fa-sync-alt"></i> Actualizar Departamento
                            </button>
                        </div>
                    </form>
                </div>

                <div id="gestion-usuarios-eliminar" style="flex: 1; min-width: 300px; margin-top: 60px"> <h2><i class="fas fa-users"></i> Eliminar Usuarios</h2>

                    <form method="POST">
                        <table class="tabla-detalles">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Dpto.</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($usuarios_activos as $usuario): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($usuario['name']) ?></td>
                                        <td><?= htmlspecialchars($usuario['dpt']) ?></td>
                                        <td>
                                            <button type="submit" name="baja_usuario" value="<?= $usuario['id'] ?>"
                                                    onclick="return confirm('¿Seguro que quieres eliminar este usuario?');"
                                                    style="background-color: #dc3545; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                </div>

                </div>


        <div id="gestion-pendientes" class="gestion-section">
                    <h2><i class="fas fa-user-clock"></i> Usuarios Pendientes</h2>
                    <div style="margin-top: 2rem;">
                        <?php foreach($usuarios_pendientes as $usuario): ?>
                            <div class="usuario-pendiente">
                                <div>
                                    <h3><?= htmlspecialchars($usuario['name']) ?></h3>
                                    <p><?= $usuario['mail'] ?></p>
                                </div>
                                <div style="display: flex; gap: 1rem;">
                                    <a href="?aceptar=<?= $usuario['id'] ?>"
                                       style="padding: 0.5rem 1rem; background: var(--success); color: white; text-decoration: none; border-radius: 5px;">
                                        <i class="fas fa-check"></i> Aceptar
                                    </a>
                                    <a href="?rechazar=<?= $usuario['id'] ?>"
                                       style="padding: 0.5rem 1rem; background: var(--danger); color: white; text-decoration: none; border-radius: 5px;">
                                        <i class="fas fa-times"></i> Rechazar
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

        <div id="generador-contrasenas" class="gestion-section">
                    <div class="container-password-gen">
                        <h1 class="password-gen-title">Generador de Contraseñas o Frases</h1>
                        <div class="radio-options">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipoGeneracion" id="opcionContrasena" value="contrasena" checked>
                                <label class="form-check-label" for="opcionContrasena">Contraseña</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipoGeneracion" id="opcionFrase" value="frase">
                                <label class="form-check-label" for="opcionFrase">Frase</label>
                            </div>
                        </div>

                        <div id="opcionesContrasena">
                            <div class="range-wrap">
                                <label for="longitud">Longitud:</label>
                                <input type="range" id="longitud" min="8" max="64" value="12">
                                <span class="range-value" id="longitud-valor">12</span>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="incluirMayusculas" checked>
                                <label class="form-check-label" for="incluirMayusculas">Incluir Mayúsculas</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="incluirMinusculas" checked>
                                <label class="form-check-label" for="incluirMinusculas">Incluir Minúsculas</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="incluirNumeros" checked>
                                <label class="form-check-label" for="incluirNumeros">Incluir Números</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="incluirSimbolos">
                                <label class="form-check-label" for="incluirSimbolos">Incluir Símbolos</label>
                            </div>
                        </div>

                        <div id="opcionesFrase" style="display: none;">
                            <div class="range-wrap">
                                <label for="numeroPalabras">Número de palabras:</label>
                                <input type="range" id="numeroPalabras" min="5" max="50" value="5">
                                <span class="range-value" id="palabras-valor">4</span>
                            </div>
                            <div class="input-group">
                                <label for="separador">Separador:</label>
                                <input type="text" class="form-control" id="separador" value="-">
                                <button class="btn-sm btn-outline-secondary" onclick="document.getElementById('separador').value='-';">Guion</button>
                                <button class="btn-sm btn-outline-secondary" onclick="document.getElementById('separador').value=' ';">Espacio</button>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="capitalizarFrase">
                                <label class="form-check-label" for="capitalizarFrase">Capitalizar primera letra</label>
                            </div>
                        </div>

                        <button class="btn-primary-password-gen" id="generar-btn">Generar</button>

                        <h2 class="password-gen-subtitle">Resultado:</h2>
                        <div id="resultado-container">
                            <span id="resultado"></span>
                            <button id="copiar-btn"><i class="fas fa-copy"></i> Copiar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <div style="max-width: 400px; margin: 5rem auto; padding: 2rem;">
            <div class="stat-card" style="padding: 2rem; text-align: center;">
                <h2 style="color: var(--primary); margin-bottom: 1.5rem;">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </h2>
                <?php if(isset($error)): ?>
                    <div style="color: var(--danger); margin-bottom: 1rem;"><?= $error ?></div>
                <?php endif; ?>
                <form method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
                    <input type="text" name="nombre" placeholder="Usuario" required
                           style="padding: 0.8rem; border-radius: 8px; border: 1px solid #ddd;">
                    <input type="password" name="password" placeholder="Contraseña" required
                           style="padding: 0.8rem; border-radius: 8px; border: 1px solid #ddd;">
                    <button type="submit"
                            style="padding: 1rem; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer;">
                        <i class="fas fa-unlock"></i> Acceder
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function toggleDetalles(id) {
            const todosPaneles = document.querySelectorAll('.detalles-panel');
            const panelActual = document.getElementById(id);

            todosPaneles.forEach(panel => {
                if (panel !== panelActual) panel.classList.remove('abierto');
            });

            panelActual.classList.toggle('abierto');

            if (panelActual.classList.contains('abierto')) {
                setTimeout(() => {
                    panelActual.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 300);
            }
        }

        function toggleTheme() {
            const body = document.body;
            const newTheme = body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        }

        function mostrarSeccion(seccionId) {
            document.querySelectorAll('.gestion-section, #panel-control').forEach(seccion => {
                seccion.style.display = 'none';
            });
            document.getElementById(seccionId).style.display = 'block';
        }

        document.body.setAttribute('data-theme', localStorage.getItem('theme') || 'light');
        window.onload = () => mostrarSeccion('panel-control');


        // --- LÓGICA DEL GENERADOR DE CONTRASEÑAS ---
        const opcionContrasena = document.getElementById('opcionContrasena');
        const opcionFrase = document.getElementById('opcionFrase');
        const opcionesContrasenaDiv = document.getElementById('opcionesContrasena');
        const opcionesFraseDiv = document.getElementById('opcionesFrase');
        const longitudInput = document.getElementById('longitud');
        const longitudValorSpan = document.getElementById('longitud-valor');
        const incluirMayusculasCheckbox = document.getElementById('incluirMayusculas');
        const incluirMinusculasCheckbox = document.getElementById('incluirMinusculas');
        const incluirNumerosCheckbox = document.getElementById('incluirNumeros');
        const incluirSimbolosCheckbox = document.getElementById('incluirSimbolos');
        const numeroPalabrasInput = document.getElementById('numeroPalabras');
        const palabrasValorSpan = document.getElementById('palabras-valor');
        const separadorInput = document.getElementById('separador');
        const capitalizarFraseCheckbox = document.getElementById('capitalizarFrase');
        const generarBtn = document.getElementById('generar-btn');
        const resultadoSpan = document.getElementById('resultado');
        const copiarBtn = document.getElementById('copiar-btn');

        const mayusculas = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        const minusculas = 'abcdefghijklmnopqrstuvwxyz';
        const numeros = '0123456789';
        const simbolos = '!@#$%^&*()_+[]{}|;:,.<>?';
        const palabrasComunes = ["casa", "perro", "gato", "sol", "luna", "arbol", "flor", "montaña", "rio", "playa", "coche", "libro", "ordenador", "telefono", "musica", "felicidad", "amor", "paz", "esperanza", "libertad", "aventura", "camino", "estrella", "tiempo", "universo"];

        longitudInput.addEventListener('input', () => {
            longitudValorSpan.textContent = longitudInput.value;
        });

        numeroPalabrasInput.addEventListener('input', () => {
            palabrasValorSpan.textContent = numeroPalabrasInput.value;
        });

        opcionContrasena.addEventListener('change', () => {
            opcionesContrasenaDiv.style.display = 'block';
            opcionesFraseDiv.style.display = 'none';
        });

        opcionFrase.addEventListener('change', () => {
            opcionesContrasenaDiv.style.display = 'none';
            opcionesFraseDiv.style.display = 'block';
        });

        generarBtn.addEventListener('click', () => {
            if (opcionContrasena.checked) {
                generarContrasena();
            } else {
                generarFrase();
            }
        });

        copiarBtn.addEventListener('click', () => {
            const texto = resultadoSpan.textContent;
            if (texto) {
                navigator.clipboard.writeText(texto).then(() => {
                    copiarBtn.textContent = '¡Copiado!';
                    setTimeout(() => {
                        copiarBtn.innerHTML = '<i class="fas fa-copy"></i> Copiar';
                    }, 2000);
                }).catch(err => {
                    console.error('Error al copiar: ', err);
                });
            }
        });

        function generarContrasena() {
            let caracteres = '';
            if (incluirMayusculasCheckbox.checked) caracteres += mayusculas;
            if (incluirMinusculasCheckbox.checked) caracteres += minusculas;
            if (incluirNumerosCheckbox.checked) caracteres += numeros;
            if (incluirSimbolosCheckbox.checked) caracteres += simbolos;

            if (caracteres === '') {
                resultadoSpan.textContent = 'Selecciona al menos un tipo de carácter.';
                return;
            }

            let contrasena = '';
            const longitud = parseInt(longitudInput.value);
            for (let i = 0; i < longitud; i++) {
                const randomIndex = Math.floor(Math.random() * caracteres.length);
                contrasena += caracteres[randomIndex];
            }
            resultadoSpan.textContent = contrasena;
        }

        function generarFrase() {
            const numPalabras = parseInt(numeroPalabrasInput.value);
            const separador = separadorInput.value;
            const capitalizar = capitalizarFraseCheckbox.checked;
            let frase = [];

            for (let i = 0; i < numPalabras; i++) {
                const randomIndex = Math.floor(Math.random() * palabrasComunes.length);
                let palabra = palabrasComunes[randomIndex];
                if (capitalizar) {
                    palabra = palabra.charAt(0).toUpperCase() + palabra.slice(1);
                }
                frase.push(palabra);
            }
            resultadoSpan.textContent = frase.join(separador);
        }
    </script>
</body>
</html>
