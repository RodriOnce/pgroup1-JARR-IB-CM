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

// Obtener todos los empleados
$empleados = $conn->query("SELECT * FROM empleados")->fetchAll(PDO::FETCH_ASSOC);

// Datos simulados para demostración
$datos_simulados = [
    'archivos_subidos' => [
        ['id' => 1, 'nombre' => 'informe.pdf', 'fecha' => '2024-03-15'],
        ['id' => 2, 'nombre' => 'presentacion.pptx', 'fecha' => '2024-03-16']
    ],
    'archivos_descargados' => [
        ['id' => 3, 'nombre' => 'manual.pdf', 'fecha' => '2024-03-14']
    ],
    'archivos_eliminados' => [],
    'usuarios_activos' => $empleados,
    'usuarios_pendientes' => [
        ['id' => 99, 'nombre' => 'nuevo_usuario', 'password' => '...']
    ],
    'usuarios_inactivos' => []
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

        .detalles-panel {
            display: none;
            margin-top: 1rem;
            padding: 1rem;
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .tabla-detalles {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-detalles th, .tabla-detalles td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        .detalles-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .cerrar-detalles {
            cursor: pointer;
            padding: 5px 10px;
            background: var(--primary);
            color: white;
            border-radius: 5px;
        }
    </style>
</head>
<body data-theme="light">
    <!-- Mantener el mismo header y sidebar del código anterior -->

    <div class="stats-grid">
        <!-- Tarjetas principales -->
        <div class="stat-card" onclick="mostrarDetalles('archivos_subidos')">
            <h3><i class="fas fa-upload"></i> Archivos Subidos</h3>
            <p><?= count($datos_simulados['archivos_subidos']) ?></p>
        </div>

        <div class="stat-card" onclick="mostrarDetalles('usuarios_activos')">
            <h3><i class="fas fa-user-check"></i> Usuarios Activos</h3>
            <p><?= count($datos_simulados['usuarios_activos']) ?></p>
        </div>

        <!-- Resto de tarjetas... -->
    </div>

    <!-- Contenedor para detalles -->
    <div id="detalles-container"></div>

    <script>
        function mostrarDetalles(tipo) {
            const contenedor = document.getElementById('detalles-container');
            const datos = <?= json_encode($datos_simulados) ?>[tipo];
            
            let html = `
                <div class="detalles-panel">
                    <div class="detalles-header">
                        <h3>${tipo.replace(/_/g, ' ').toUpperCase()}</h3>
                        <div class="cerrar-detalles" onclick="cerrarDetalles()">&times; Cerrar</div>
                    </div>
                    <table class="tabla-detalles">
                        <thead>
                            <tr>
                                ${generarEncabezados(tipo)}
                            </tr>
                        </thead>
                        <tbody>
                            ${generarFilas(datos, tipo)}
                        </tbody>
                    </table>
                </div>
            `;

            contenedor.innerHTML = html;
            contenedor.style.display = 'block';
        }

        function generarEncabezados(tipo) {
            if (tipo.includes('archivos')) {
                return '<th>ID</th><th>Nombre</th><th>Fecha</th>';
            }
            return '<th>ID</th><th>Nombre</th><th>Usuario</th>';
        }

        function generarFilas(datos, tipo) {
            return datos.map(item => `
                <tr>
                    <td>${item.id}</td>
                    <td>${item.nombre}</td>
                    ${tipo.includes('archivos') ? `<td>${item.fecha || ''}</td>` : `<td>${item.password ? '***' : ''}</td>`}
                </tr>
            `).join('');
        }

        function cerrarDetalles() {
            document.getElementById('detalles-container').style.display = 'none';
        }
    </script>
</body>
</html>
