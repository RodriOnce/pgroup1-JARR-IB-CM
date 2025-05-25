<?php
session_start();

// Manejar el cierre de sesión antes de cualquier verificación
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.html");
    exit();
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

//Carpeta del usuario
$user = $_SESSION['username'];
$basePath = "/var/www/html/archivos/";
$userFolder = $basePath . $user . "/";

// Verificar si la carpeta del usuario existe, si no, crearla
if (!is_dir($userFolder)) {
    mkdir($userFolder, 0755, true);
}

// Obtener la lista de archivos en la carpeta del usuario
$files = scandir($userFolder);
$files = array_diff($files, array('.', '..'));   // Filtrar "." y ".."

// Eliminar un archivo si se ha solicitado
if (isset($_GET['delete'])) {
    $fileToDelete = $userFolder . basename($_GET['delete']);
    if (file_exists($fileToDelete)) {
        unlink($fileToDelete);   // Eliminar el archivo
        header("Location: inicio-1.php");   // Recargar la página
        exit();
    }
}

// Compartir un archivo si se ha solicitado
if (isset($_POST['compartir'])) {
    $archivo = $_POST['archivo'];
    $tipo_destino = $_POST['tipo_destino'];
    $current_user = $user;

    $conn = new mysqli("localhost", "root", "momo", "empresa");

    if ($tipo_destino === 'departamento') {
        $departamento = $_POST['departamento_destino'];

        // Obtener usuarios del departamento
        $stmt = $conn->prepare("SELECT user FROM empleados
                                  WHERE dpt = ?
                                  AND status = 'activo'
                                  AND user != ?");
        $stmt->bind_param("ss", $departamento, $current_user);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $usuario_destino = $row['user'];
            $file_src = $userFolder . $archivo;
            $carpeta_destino = $basePath . $usuario_destino . "/";

            if (!is_dir($carpeta_destino)) {
                mkdir($carpeta_destino, 0755, true);
            }

            if (copy($file_src, $carpeta_destino . $archivo)) {
                // Insertar en tabla shared
                $stmt_insert = $conn->prepare("INSERT INTO shared
                                                  (file_src, user_src, user_dst)
                                                  VALUES (?, ?, ?)");
                $stmt_insert->bind_param("sss", $file_src, $current_user, $usuario_destino);
                $stmt_insert->execute();
            }
        }
    } else {
        // Lógica original para usuario individual
        $usuario_destino = $_POST['usuario_destino'];
        $file_src = $userFolder . $archivo;
        $carpeta_destino = $basePath . $usuario_destino . "/";

        if (!is_dir($carpeta_destino)) {
            mkdir($carpeta_destino, 0755, true);
        }

        if (copy($file_src, $carpeta_destino . $archivo)) {
            $stmt_insert = $conn->prepare("INSERT INTO shared
                                         (file_src, user_src, user_dst)
                                         VALUES (?, ?, ?)");
            $stmt_insert->bind_param("sss", $file_src, $current_user, $usuario_destino);
            $stmt_insert->execute();
        }
    }

    $conn->close();
    header("Location: inicio-1.php");
    exit();
}



// Obtener la lista de usuarios disponibles para compartir
$conn = new mysqli("localhost", "root", "momo", "empresa");
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener departamentos
$mapeoDepartamentos = [
    'ADM' => 'Administración',
    'DIR' => 'Dirección',
    'IT' => 'Informática',
    'LGTC' => 'Logística',
    'MKT' => 'Marketing',
    'SL' => 'Ventas'

];

$query_dept = "SELECT DISTINCT dpt FROM empleados
               WHERE dpt IN ('IT', 'DIR', 'LGTC', 'ADM', 'SL', 'MKT')
               AND dpt IS NOT NULL";

$result_dept = $conn->query($query_dept);
$departamentos = [];
while ($row = $result_dept->fetch_assoc()) {
    $departamentos[] = $row['dpt'];
}

// Obtener usuarios activos
$sql = "SELECT user FROM empleados WHERE user != ? AND status = 'activo'"; // <- Agregado filtro de status
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();
$usuarios = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();


// Obtener la lista de análisis del usuario
$conn = new mysqli("localhost", "root", "momo", "viruses");
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$sql = "SELECT filename, scan_date, scan_state FROM archivos WHERE scan_user = ? ORDER BY scan_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();
$analisis = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de <?php echo $user; ?></title>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700&display=swap'); /* Añadida para el FAQ y Generador */

    :root {
        --background-color: #f9fafc;
        --text-color: #233172;
        --card-background: #ffffff;
        --header-background: #3146b4;
        --button-background: linear-gradient(135deg, #3146b4, #5366e3);
        --button-hover-background: linear-gradient(135deg, #5366e3, #7f8cff);
        --dropdown-background: #f4f6fa;
        --dropdown-border: #b2b9ce;
        --section-line-color: #3146b4;
        --sidebar-background: #f4f6fa;
        --sidebar-border-color: #b2b9ce;
        --sidebar-link-hover: linear-gradient(135deg, #3146b4, #5366e3);
        --sidebar-active-link: #3146b4;
        --sidebar-text-color: #233172;

        /* Variables para el Centro de Ayuda (adaptadas a tus nombres existentes) */
        --faq-question-bg: var(--header-background);
        --faq-question-hover-bg: var(--button-hover-background);
        --faq-answer-bg: var(--dropdown-background);
        --faq-answer-border: var(--section-line-color);

        /* Variables para el Generador (adaptadas a tus nombres existentes) */
        --success: #28a745;
        --danger: #dc3545;
        --result-bg: #eaf0fb; /* Reusado del FAQ original, ahora en main */
    }

    [data-theme="dark"] {
        --background-color: #181b22;
        --text-color: #f9fafc;
        --card-background: #23253a;
        --header-background: #3146b4;
        --button-background: linear-gradient(135deg, #7f8cff, #5366e3);
        --button-hover-background: linear-gradient(135deg, #5366e3, #3146b4);
        --dropdown-background: #23253a;
        --dropdown-border: #3146b4;
        --section-line-color: #7f8cff;
        --sidebar-background: #23253a;
        --sidebar-border-color: #3146b4;
        --sidebar-link-hover: linear-gradient(135deg, #7f8cff, #5366e3);
        --sidebar-active-link: #7f8cff;
        --sidebar-text-color: #f9fafc;

        /* Variables para el Centro de Ayuda (dark mode) */
        --faq-question-bg: var(--header-background);
        --faq-question-hover-bg: var(--button-hover-background);
        --faq-answer-bg: var(--card-background);
        --faq-answer-border: var(--section-line-color);

        /* Variables para el Generador (dark mode) */
        --success: #3cb859; /* Un poco más brillante en oscuro */
        --danger: #ff5263; /* Un poco más brillante en oscuro */
        --result-bg: #21233b; /* Reusado del FAQ original, ahora en main */
    }

    body {
        font-family: 'Roboto', sans-serif;
        margin: 0;
        padding: 0;
        display: flex; /* Utiliza flexbox para el layout principal */
        background-color: var(--background-color);
        color: var(--text-color);
        transition: background-color 0.3s ease, color 0.3s ease;
        min-height: 100vh; /* Asegura que el body ocupe al menos toda la altura de la ventana */
    }

    .custom-button,
    .dark-mode-toggle,
    .logout-button {
        background: var(--button-background);
        color: white;
        border: none;
        padding: 12px 20px;
        margin: 10px 5px 0 0;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 500;
        font-size: 1rem;
        transition: background 0.3s ease, box-shadow 0.3s ease, transform 0.2s ease;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .custom-button:hover,
    .dark-mode-toggle:hover,
    .logout-button:hover {
        background: var(--button-hover-background);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        transform: translateY(-1px);
    }

    .sidebar {
        width: 250px;
        background-color: var(--sidebar-background);
        padding: 20px;
        position: sticky; /* Cambiado a sticky */
        top: 0; /* Se pega arriba */
        height: 100vh; /* Ocupa toda la altura de la vista */
        border-right: 1px solid var(--sidebar-border-color);
        box-shadow: 2px 0 8px rgba(0, 0, 0, 0.05);
        border-radius: 0 12px 12px 0;
        z-index: 1001; /* Asegura que el sidebar esté por encima de todo */
        flex-shrink: 0; /* Evita que el sidebar se encoja */
    }

    .sidebar h2 {
        font-size: 1.5rem;
        margin-bottom: 20px;
        color: var(--sidebar-text-color);
        text-align: center;
        font-weight: bold;
    }

    .sidebar ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar ul li {
        margin-bottom: 15px;
    }

    .sidebar ul li a {
        text-decoration: none;
        color: var(--sidebar-text-color);
        font-size: 1rem;
        padding: 10px 15px;
        display: block;
        border-radius: 10px;
        transition: background 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
        font-weight: 500;
    }

    .sidebar ul li a:hover {
        background: var(--sidebar-link-hover);
        color: white;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .sidebar ul li a.active {
        background-color: var(--sidebar-active-link);
        color: white;
    }

    /* Contenedor principal para el header y las secciones de contenido */
    .main-content-wrapper {
        flex-grow: 1; /* Permite que este contenedor ocupe el espacio restante */
        display: flex;
        flex-direction: column; /* Para que header y content-sections se apilen verticalmente */
    }

    header {
        background-color: var(--header-background);
        color: white;
        padding: 20px;
        text-align: center;
        position: sticky; /* Se mantiene arriba */
        top: 0;
        z-index: 1000; /* Menor que el sidebar */
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: 0 0 12px 12px;
        margin-bottom: 2rem;
        width: 100%; /* Ocupa todo el ancho disponible de su padre (.main-content-wrapper) */
        box-sizing: border-box; /* Incluye padding en el width */
    }


    header h1 {
        margin: 0;
        font-size: 2rem;
    }

    /* Estilos para el contenido que se mostrará/ocultará */
    .content-section {
        padding: 20px;
        width: 100%; /* Ocupa el ancho completo de su contenedor padre */
        box-sizing: border-box; /* Incluye padding en el ancho */
        margin-bottom: 20px; /* Espacio entre secciones si hay varias visibles */
        flex-grow: 1; /* Permite que el contenido ocupe el espacio restante en la columna */
    }

    .hidden {
        display: none !important; /* !important para asegurar que se sobrescribe si hay otros estilos */
    }

    .dashboard-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }

    .section {
        flex: 1 1 calc(33.333% - 20px);
        background: var(--card-background);
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        padding: 20px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .section:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
    }

    .section-title {
        font-size: 1.5rem;
        margin-bottom: 10px;
        color: var(--text-color);
        border-bottom: 2px solid var(--section-line-color);
        padding-bottom: 5px;
    }

    .card {
        background: var(--card-background);
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        padding: 20px;
        margin-top: 10px;
    }

    .card h3 {
        font-size: 1.2rem;
        margin-bottom: 10px;
        color: var(--text-color);
    }

    .card p {
        font-size: 1rem;
        color: var(--text-color);
    }

    .file-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .file-list li {
        background-color: var(--card-background);
        margin: 10px 0;
        padding: 12px 16px;
        border: 2px solid #ccc;
        border-radius: 10px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: border-color 0.3s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem; /* Espacio entre columnas */
        text-align: center;
    }

    /* Aplica mismo ancho y centrado a todas las columnas */
    .file-list li .col {
        flex: 1;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .file-list li.safe {
        border-color: #28a745;
    }

    .file-list li.infected {
        border-color: #dc3545;
    }

    .file-list a {
        color: var(--section-line-color);
        text-decoration: none;
        margin-right: 10px;
    }

    .file-list a:hover {
        text-decoration: underline;
    }

    .file-list button {
        background-color: #f21515;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .file-list button:hover {
        background-color: #cc0000;
    }

    /* Contenedor de botones de análisis */
    .botones-analisis {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        align-items: center;
        justify-content: center;
    }

    /* Botones con ancho uniforme */
    .botones-analisis button {
        width: 80%; /* ajustable: 100% si quieres que ocupe todo el espacio */
        max-width: 300px;
    }

    /* Botón Analizar Todo ocupa más */
    .boton-analizar-todo {
        width: 100%;
        max-width: 500px;
    }

    .selector-tipo {
        margin: 10px 0;
        padding: 10px;
        background-color: var(--card-background);
        border-radius: 10px;
        border: 1px solid var(--dropdown-border);
    }

    .selector-tipo label {
        margin-right: 15px;
        cursor: pointer;
        color: var(--text-color);
        transition: color 0.3s ease;
    }

    .selector-tipo label:hover {
        color: var(--section-line-color);
    }

    .centered-buttons {
        display: flex;
        justify-content: center;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .full-width {
        width: 100%;
        margin-top: 20px;
    }

    #usuarioDestinoContainer,
    #departamentoDestinoContainer {
        margin: 15px 0;
        padding: 10px;
        background-color: var(--dropdown-background);
        border-radius: 10px;
        border: 1px solid var(--dropdown-border);
    }

    select {
        width: 100%;
        padding: 10px;
        margin: 8px 0;
        background-color: var(--dropdown-background);
        color: var(--text-color);
        border: 1px solid var(--dropdown-border);
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    select:focus {
        outline: none;
        border-color: var(--section-line-color);
        box-shadow: 0 0 0 3px rgba(111, 66, 193, 0.2);
    }

    #modalCompartir {
        background-color: var(--card-background);
        color: var(--text-color);
        border-radius: 12px;
        border: 1px solid var(--dropdown-border);
    }

    #modalCompartir button[type="submit"] {
        background: var(--button-background);
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 10px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    #modalCompartir button[type="submit"]:hover {
        background: var(--button-hover-background);
    }

    /* ------------------------------------- */
    /* Estilos para el Centro de Ayuda (FAQ) */
    /* ------------------------------------- */

    #ayuda-content {
        padding: 20px; /* Ya tiene padding del .content-section */
        max-width: 900px; /* Limita el ancho para mejor lectura */
        margin: 0 auto; /* Centra el contenedor en la sección */
        background-color: var(--background-color); /* Fondo consistente */
    }

    #ayuda-content h1 {
        font-family: 'Montserrat', Arial, sans-serif; /* Usar la fuente del FAQ original */
        font-size: 2.1rem;
        color: var(--section-line-color); /* Usar el color de línea existente */
        text-align: center;
        margin-bottom: 1.8rem;
        font-weight: 700;
        letter-spacing: 1px;
        border-bottom: 1.5px solid var(--dropdown-border); /* Usar un color de borde existente */
        padding-bottom: 0.7rem;
    }

    .faq {
        margin-bottom: 16px;
        border-radius: 8px; /* Para que las esquinas coincidan */
        overflow: hidden; /* Para que el borde redondeado funcione bien con el contenido */
    }

    .question {
        background: var(--faq-question-bg);
        color: white; /* Siempre blanco para el texto de la pregunta */
        padding: 13px 18px;
        border: none;
        width: 100%;
        text-align: left;
        font-size: 1.08rem;
        cursor: pointer;
        border-radius: 8px; /* Se elimina el redondeo si hay respuesta abierta */
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); /* Sombra más suave */
        margin-bottom: 0;
        transition: background 0.2s, color 0.2s;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    /* Para cuando la pregunta está abierta, elimina el border-radius inferior */
    .faq.open .question {
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
    }


    .question::after {
        content: '+';
        font-size: 1.5rem;
        transition: transform 0.3s ease;
    }

    .question.active::after {
        content: '-';
        transform: rotate(0deg); /* No rotar, solo cambiar el signo */
    }

    .answer {
        display: none;
        padding: 18px 18px 6px 18px;
        background: var(--faq-answer-bg);
        border-radius: 0 0 8px 8px; /* Solo esquinas inferiores redondeadas */
        margin-top: 0;
        font-size: 1.01rem;
        color: var(--text-color); /* Usar el color de texto general */
        border-left: 4px solid var(--faq-answer-border);
        text-align: left;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04); /* Sombra más suave */
    }

    .faq.open .answer {
        display: block;
        animation: fadeIn 0.33s ease-out; /* Agregado ease-out para una animación más fluida */
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 768px) {
        #ayuda-content {
            padding: 10px;
        }
        #ayuda-content h1 {
            font-size: 1.5rem;
        }
        .question {
            font-size: 0.95rem;
            padding: 10px 15px;
        }
        .answer {
            font-size: 0.9rem;
            padding: 15px 15px 5px 15px;
        }
    }


    /* ------------------------------------------- */
    /* Estilos para el Generador de Contraseñas */
    /* ------------------------------------------- */

    #generador-content {
        padding: 20px; /* Ya tiene padding del .content-section */
        display: flex; /* Para centrar el contenido */
        justify-content: center;
        align-items: flex-start; /* Alinea arriba si el contenido es corto */
        min-height: calc(100vh - 48px - 2rem); /* Altura para centrar, restando header y margin-bottom */
        box-sizing: border-box;
    }

    #generador-content .container {
        background-color: var(--card-background); /* Usar variable del dashboard */
        border-radius: 22px;
        box-shadow: 0 8px 32px 0 rgba(49, 70, 180, 0.14); /* Sombra más sutil */
        padding: 2.2rem 1.3rem 2rem 1.3rem;
        margin: 0; /* Eliminar márgenes para que el flexbox lo centre */
        max-width: 480px;
        min-width: 0;
        transition: background 0.3s, color 0.3s;
        position: relative;
    }
    #generador-content h1 {
        font-size: 1.7rem;
        color: var(--primary); /* Usar variable principal */
        border-bottom: 1.5px solid var(--dropdown-border); /* Usar variable de borde */
        padding-bottom: 0.7rem;
        margin-bottom: 1.3rem;
        font-family: 'Montserrat', Arial, sans-serif;
        text-align: center;
        font-weight: 700;
        letter-spacing: 1px;
    }
    #generador-content .radio-options {
        display: flex;
        justify-content: center;
        gap: 1.3rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.7rem;
        border-bottom: 1px solid var(--dropdown-border); /* Usar variable de borde */
    }
    #generador-content .form-check-input[type=radio] {
        accent-color: var(--primary);
        width: 1.1em;
        height: 1.1em;
        margin-top: 0;
        margin-right: 4px;
        vertical-align: middle;
        cursor: pointer;
    }
    #generador-content .form-check-label {
        font-size: 1rem;
        cursor: pointer;
        font-weight: 600;
        color: var(--primary); /* Usar variable principal */
    }
    #generador-content .input-group {
        display: flex;
        align-items: center;
        margin-bottom: 0.85rem;
        gap: 0.7rem;
    }
    #generador-content .input-group label {
        min-width: 116px;
        text-align: left;
        font-weight: 500;
        font-size: 1.01rem;
        color: var(--primary); /* Usar variable principal */
    }
    #generador-content .form-control {
        width: 90px;
        border-radius: 10px;
        border: 1.5px solid var(--secondary); /* Usar variable secundaria */
        padding: 0.63rem 0.8rem;
        font-size: 1rem;
        background: var(--background-color); /* Usar variable de fondo del dashboard */
        color: var(--text-color); /* Usar variable de texto del dashboard */
        transition: border-color 0.18s;
        outline: none;
    }
    #generador-content .form-control:focus {
        border-color: var(--primary); /* Usar variable principal */
    }
    #generador-content .btn-primary {
        width: 100%;
        padding: 0.74rem;
        margin-top: 0.7rem;
        background-color: var(--primary); /* Usar variable principal */
        border: none;
        border-radius: 13px;
        color: var(--primary);
        font-weight: 700;
        font-size: 1.09rem;
        box-shadow: 0 2px 8px 0 rgba(49, 70, 180, 0.08);
        cursor: pointer;
        transition: background 0.17s, transform 0.13s;
    }
    #generador-content .btn-primary:hover, #generador-content .btn-primary:focus {
        background-color: var(--secondary); /* Usar variable secundaria */
        transform: translateY(-1.5px) scale(1.015);
    }
    #generador-content .btn-secondary { /* Este estilo solo si hubiese un botón "volver" */
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
    #generador-content .btn-secondary:hover {
        background-color: var(--primary);
    }
    #generador-content .btn-sm.btn-outline-secondary {
        background: transparent;
        color: var(--primary); /* Usar variable principal */
        border: 1.3px solid var(--primary); /* Usar variable principal */
        font-weight: 600;
        padding: 0.35rem 0.6rem;
        font-size: 0.93rem;
        border-radius: 8px;
        margin-left: 0.6rem;
        transition: background 0.15s, color 0.15s, border 0.15s;
    }
    #generador-content .btn-sm.btn-outline-secondary:hover {
        background: var(--primary); /* Usar variable principal */
        color: #fff;
        border-color: var(--secondary); /* Usar variable secundaria */
    }
    #generador-content h2 {
        font-size: 1.18rem;
        margin-top: 1.3rem;
        border-bottom: 1px solid var(--dropdown-border); /* Usar variable de borde */
        padding-bottom: 0.5rem;
        color: var(--secondary); /* Usar variable secundaria */
        font-weight: 700;
        text-align: left;
        margin-bottom: 0.9rem;
    }
    #generador-content #resultado-container {
        background-color: var(--result-bg); /* Usar variable result-bg */
        border-radius: 11px;
        padding: 0.53rem 1rem 0.53rem 0.7rem;
        margin-top: 0.3rem;
        margin-bottom: 1rem;
        text-align: left;
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 48px;
        overflow-x: auto;
        max-width: 100%;
    }
    #generador-content #resultado-container pre {
        margin: 0;
        font-size: 1.07rem;
        flex-grow: 1;
        background: transparent;
        color: var(--text-color); /* Usar variable de texto del dashboard */
        font-family: 'Montserrat', 'Consolas', monospace; /* Mantener la fuente para monospace */
        letter-spacing: 1px;
        user-select: all;
        white-space: pre-wrap;
        word-break: break-all;
        min-width: 0;
        max-width: 70vw; /* Ajuste para evitar desbordamiento en móviles */
    }
    #generador-content #copiado-notificacion {
        position: fixed; /* Mantener fijo para la notificación */
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background-color: var(--primary); /* Usar variable principal */
        color: white;
        padding: 0.62rem 1.2rem;
        border-radius: 7px;
        font-size: 1rem;
        font-weight: 600;
        opacity: 0;
        transition: opacity 0.3s;
        z-index: 200;
        pointer-events: none;
    }
    @media (max-width: 600px) {
        #generador-content .container {
            padding: 1.1rem 0.4rem;
            max-width: 98vw;
        }
        #generador-content #resultado-container pre {
            max-width: 65vw;
            font-size: 0.97rem;
        }
    }
    </style>

</head>
<body data-theme="light">
    <div class="sidebar">
        <h2>Menú</h2>
        <ul>
            <li><a href="#" class="sidebar-link active" data-target="main-dashboard-content">Editar Perfil</a></li>
            <li><a href="#">Cambiar Idioma</a></li>
            <li><a href="#" class="sidebar-link" data-target="ayuda-content">Centro de Ayuda</a></li>
            <li><a href="#" class="sidebar-link" data-target="generador-content">Generador de contraseñas</a></li>
        </ul>
    </div>

    <div class="main-content-wrapper">
        <header>
            <button class="dark-mode-toggle" onclick="toggleDarkMode()">Modo Oscuro</button>
            <h1>Bienvenido, <?php echo $user; ?>!</h1>
            <form method="POST" style="margin: 0;">
                <button type="submit" name="logout" class="logout-button">Cerrar Sesión</button>
            </form>
        </header>

        <div id="main-dashboard-content" class="content-section">
            <div class="dashboard-container">

                <div class="section">
                    <h2 class="section-title">Mis Documentos</h2>
                    <div class="card">
                        <h3>Documentos recientes</h3>
                        <p>Accede rápidamente a los documentos más recientes.</p>
                        <div class="dropdown">
                            <ul class="file-list">
                                <?php
                                if (empty($files)) {
                                    echo "<li>No hay archivos en tu carpeta.</li>";
                                } else {
                                    foreach ($files as $file) {
                                        $filePath = $userFolder . $file;
                                        $fileUrl = "archivos/" . $user . "/" . $file;   // Ruta para acceder al archivo
                                        echo "<li>
                                                    <span>$file</span>
                                                    <div>
                                                        <a href='$fileUrl' target='_blank'>Abrir</a>
                                                        <a href='descargar.php?file=$file&user=$user'>Descargar</a>
                                                        <button onclick=\"window.location.href='inicio-1.php?delete=$file'\">Eliminar</button>
                                                        <button onclick=\"mostrarModal('$file')\">Compartir</button>
                                                    </div>
                                                </li>";
                                    }
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <div id="modalCompartir" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); z-index: 1000;">
                    <h3>Compartir archivo</h3>
                    <form method="POST">
                        <input type="hidden" id="archivoCompartir" name="archivo">
                        <div class="selector-tipo">
                            <label>
                                <input type="radio" name="tipo_destino" value="usuario" checked onclick="toggleDestino('usuario')"> Usuario
                            </label>
                            <label>
                                <input type="radio" name="tipo_destino" value="departamento" onclick="toggleDestino('departamento')"> Departamento
                            </label>
                        </div>

                        <div id="usuarioDestinoContainer">
                            <label for="usuario_destino">Selecciona un usuario:</label>
                            <select name="usuario_destino" id="usuario_destino">
                                <?php foreach ($usuarios as $usuario): ?>
                                    <option value='<?= $usuario['user'] ?>'><?= $usuario['user'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="departamentoDestinoContainer" style="display: none;">
                            <label for="departamento_destino">Departamento:</label>
                            <select name="departamento_destino" id="departamento_destino">
                                <?php foreach ($departamentos as $codigo): ?>
                                    <option value="<?= $codigo ?>">
                                        <?= $mapeoDepartamentos[$codigo] ?? $codigo ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" name="compartir">Compartir</button>
                        <button type="button" onclick="cerrarModal()">Cancelar</button>
                    </form>
                </div>

    <div class="section">
                    <h2 class="section-title">Analizar Archivos o Carpetas</h2>
                    <div class="card">
                        <form action="/archivos/upload-archivos.php" method="post" enctype="multipart/form-data">

                            <input type="file"
                                id="file-input"
                                name="files[]"
                                multiple
                                style="display: none;">

                            <input type="file"
                                id="folder-input"
                                name="folders[]"
                                webkitdirectory
                                multiple
                                style="display: none;">

                            <div class="botones-analisis">
                                <button type="button"
                                            class="custom-button"
                                            onclick="document.getElementById('file-input').click()">
                                    📄 Seleccionar Archivos
                                </button>

                                <button type="button"
                                            class="custom-button"
                                            onclick="document.getElementById('folder-input').click()">
                                    📁 Seleccionar Carpeta
                                </button>

                                <button type="submit" class="custom-button boton-analizar-todo">
                                    🔍 Analizar Todo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

    <div class="section">
                    <h2 class="section-title">Mis Análisis</h2>
                    <div class="card">
                        <h3>Resultados de análisis</h3>
                        <p>Consulta los detalles de tus análisis realizados.</p>
                        <div class="dropdown">
                            <ul class="file-list">
                                <?php
                                if (empty($analisis)) {
                                    echo "<li>No hay análisis disponibles.</li>";
                                } else {
                                    foreach ($analisis as $analisisItem) {
                                        $estado = strtolower($analisisItem['scan_state']);
                                        $claseEstado = ($estado === 'safe') ? 'safe' : (($estado === 'infected') ? 'infected' : '');
                                        echo "<li class='$claseEstado'>
                                                    <span class='col filename'>{$analisisItem['filename']}</span>
                                                    <span class='col fecha'>Fecha: {$analisisItem['scan_date']}</span>
                                                    <span class='col estado'>Estado: {$analisisItem['scan_state']}</span>
                                                </li>";
                                    }
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="ayuda-content" class="content-section hidden">
            <h1>Centro de Ayuda Trackzero</h1>

            <div class="faq">
                <button class="question">¿Qué es un malware?</button>
                <div class="answer">
                    Malware es cualquier software malicioso diseñado para dañar, explotar o comprometer dispositivos, redes y datos. Ejemplos: virus, troyanos, ransomware, spyware y más.
                </div>
            </div>

            <div class="faq">
                <button class="question">¿Qué es un falso positivo?</button>
                <div class="answer">
                    Un falso positivo ocurre cuando una herramienta de seguridad detecta erróneamente un archivo o URL legítimo como una amenaza. Es importante revisar los reportes antes de eliminar archivos.
                </div>
            </div>

            <div class="faq">
                <button class="question">¿Qué tipos de archivos pueden analizarse en Trackzero?</button>
                <div class="answer">
                    Puedes analizar la mayoría de archivos comunes: documentos, imágenes, ejecutables, archivos comprimidos, scripts, entre otros. Recomendamos evitar archivos demasiado grandes o formatos inusuales.
                </div>
            </div>

            <div class="faq">
                <button class="question">¿Qué es un enlace malicioso?</button>
                <div class="answer">
                    Un enlace malicioso (URL maliciosa) es un sitio web que intenta infectar tu dispositivo o robar información. Puede usar técnicas como phishing, descargas automáticas o explotación de vulnerabilidades.
                </div>
            </div>

            <div class="faq">
                <button class="question">¿Cómo puedo analizar una URL?</button>
                <div class="answer">
                    Copia y pega la URL sospechosa en la sección correspondiente de la página de análisis. Trackzero verificará si el sitio es seguro o está reportado como peligroso usando diferentes motores y bases de datos de ciberseguridad.
                </div>
            </div>

            <div class="faq">
                <button class="question">¿Qué debo hacer si se detecta malware en un archivo?</button>
                <div class="answer">
                    Si Trackzero detecta malware, evita abrir el archivo, elimínalo de tu dispositivo y consulta con tu responsable de TI. No intentes desinfectar el archivo manualmente salvo que tengas experiencia técnica.
                </div>
            </div>

            <div class="faq">
                <button class="question">¿Cómo proteger mis datos personales al analizar archivos?</button>
                <div class="answer">
                    Asegúrate de no subir documentos sensibles si no es necesario. Trackzero respeta tu privacidad, pero es recomendable anonimizar información privada antes de subirla para análisis.
                </div>
            </div>

            <div class="faq">
                <button class="question">¿Qué es el phishing y cómo lo detecta Trackzero?</button>
                <div class="answer">
                    El phishing es un engaño que busca robar tus datos haciéndose pasar por servicios legítimos. Trackzero analiza los enlaces buscando patrones y reputación para ayudarte a identificar este tipo de fraudes.
                </div>
            </div>

            <div class="faq">
                <button class="question">¿Qué ocurre con los archivos infectados?</button>
                <div class="answer">
                    Los archivos infectados se eliminan de nuestro servidor tras ser analizados para proteger la privacidad y la integridad del sistema y los usuarios.
                </div>
            </div>

            <div class="faq">
                <button class="question">¿Cómo puedo compartir resultados de análisis?</button>
                <div class="answer">
                    Puedes utilizar el botón “Compartir” en el reporte de resultados para generar un enlace temporal que puedes enviar a otros usuarios o responsables de seguridad.
                </div>
            </div>

            <div class="faq">
                <button class="question">¿Por qué debería usar un generador de contraseñas?</button>
                <div class="answer">
                    Generar contraseñas seguras y aleatorias reduce el riesgo de que tus cuentas sean vulneradas. Trackzero incluye un generador profesional de contraseñas y frases.
                </div>
            </div>

            <div class="faq">
                <button class="question">¿Qué significa que un archivo está “limpio”?</button>
                <div class="answer">
                    Un archivo limpio es aquel que, tras ser analizado, no presenta indicios de malware conocidos ni comportamientos sospechosos en los motores de Trackzero.
                </div>
            </div>

            <div class="faq">
                <button class="question">¿Es seguro subir archivos confidenciales?</button>
                <div class="answer">
                    Todos los análisis se realizan bajo cifrado y con máxima privacidad, pero recomendamos analizar solo lo imprescindible y eliminar el archivo del sistema tras el análisis.
                </div>
            </div>

        </div>

        <div id="generador-content" class="content-section hidden">
            <div class="container">
                <h1>Generador de Contraseñas o Frases</h1>

                <div class="radio-options">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="modo" value="pass" checked>
                        <label class="form-check-label">Contraseña</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="modo" value="frase">
                        <label class="form-check-label">Frase</label>
                    </div>
                </div>

                <form>
                    <div id="pass-options">
                        <div class="input-group">
                            <label for="longitud">Longitud total</label>
                            <input type="number" id="longitud" class="form-control" value="12" min="6" max="50">
                        </div>
                        <div class="input-group">
                            <label for="mayus">Mayúsculas</label>
                            <input type="number" id="mayus" class="form-control" value="2" min="0" max="20">
                        </div>
                        <div class="input-group">
                            <label for="minus">Minúsculas</label>
                            <input type="number" id="minus" class="form-control" value="4" min="0" max="20">
                        </div>
                        <div class="input-group">
                            <label for="nums">Números</label>
                            <input type="number" id="nums" class="form-control" value="3" min="0" max="20">
                        </div>
                        <div class="input-group">
                            <label for="symbols">Símbolos</label>
                            <input type="number" id="symbols" class="form-control" value="3" min="0" max="20">
                        </div>
                    </div>

                    <div id="frase-options" style="display:none;">
                        <div class="input-group">
                            <label for="numPalabras">Número de palabras</label>
                            <input type="number" id="numPalabras" class="form-control" value="4" min="2" max="70">
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary" onclick="generar()">Generar</button>
                </form>

                <h2>Resultado:</h2>
                <div id="resultado-container">
                    <pre id="resultado"></pre>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copiarResultado()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard" viewBox="0 0 16 16">
                            <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V13a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1h1v-1z"/>
                            <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3z"/>
                        </svg>
                    </button>
                </div>
                <div id="copiado-notificacion">
                    ¡Copiado!
                </div>
            </div>
        </div>
    </div> <script>
        function toggleDarkMode() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            body.setAttribute('data-theme', newTheme);
            const toggleButton = document.querySelector('.dark-mode-toggle');
            toggleButton.textContent = newTheme === 'light' ? 'Modo Oscuro' : 'Modo Claro';
        }

        function mostrarModal(archivo) {
            document.getElementById('archivoCompartir').value = archivo;
            document.getElementById('modalCompartir').style.display = 'block';
        }

        function cerrarModal() {
            document.getElementById('modalCompartir').style.display = 'none';
        }

        function toggleDestino(tipo) {
            const usuarioContainer = document.getElementById('usuarioDestinoContainer');
            const deptContainer = document.getElementById('departamentoDestinoContainer');

            if (tipo === 'usuario') {
                usuarioContainer.style.display = 'block';
                deptContainer.style.display = 'none';
            } else {
                usuarioContainer.style.display = 'none';
                deptContainer.style.display = 'block';
            }
        }

        // Script para controlar la visibilidad de los contenidos
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            const mainDashboardContent = document.getElementById('main-dashboard-content');
            const ayudaContent = document.getElementById('ayuda-content');
            const generadorContent = document.getElementById('generador-content'); // Nuevo

            // Función para mostrar un contenido y ocultar los demás
            function showContent(targetId) {
                mainDashboardContent.classList.add('hidden');
                ayudaContent.classList.add('hidden');
                generadorContent.classList.add('hidden'); // Ocultar el generador

                document.getElementById(targetId).classList.remove('hidden');

                sidebarLinks.forEach(link => {
                    if (link.dataset.target === targetId) {
                        link.classList.add('active');
                    } else {
                        link.classList.remove('active');
                    }
                });
            }

            // Inicializar el contenido (mostrar dashboard por defecto)
            showContent('main-dashboard-content');

            // Asignar el manejador de eventos a los enlaces del sidebar
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function(event) {
                    event.preventDefault();
                    const targetId = this.dataset.target;
                    showContent(targetId);
                });
            });

            // Función para el FAQ expand/collapse (solo para el centro de ayuda)
            const faqQuestions = document.querySelectorAll('#ayuda-content .question');
            faqQuestions.forEach(button => {
                button.addEventListener('click', () => {
                    const parent = button.parentElement;
                    parent.classList.toggle('open');
                    button.classList.toggle('active');
                });
            });

            // Lógica del Generador de Contraseñas
            const palabras = [
                "automovil", "desarrollo", "programacion", "computadora", "tecnologia", "bateria",
                "electricidad", "computing", "aplicaciones", "desplazamiento", "despertador",
                "informatico", "instrucciones", "configuracion", "paisajismo", "estrategia", "estructura",
                "circuitos", "desarrollador", "desglosar", "entrenamiento", "matematica", "profesorado",
                "inteligencia", "escalera", "espectacular", "programador", "funcionalidad",
                "reconocimiento", "mejoramiento", "procesador", "colaboracion", "manipulacion",
                "industria", "sostenibilidad", "hardware", "software", "comunicacion", "seguridad",
                "arquitectura", "construccion", "importante", "precaucion", "acelerador", "despliegue",
                "sostenible", "configurar", "escritura", "documentacion", "herramientas", "entorno"
            ];

            function generarContraseña(longitud, mayus, minus, nums, symbols) {
                const upper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
                const lower = "abcdefghijklmnopqrstuvwxyz";
                const numbers = "0123456789";
                const symbs = "!@#$%&*+-_?";

                if (mayus + minus + nums + symbols > longitud) return "⚠️ La suma de tipos excede la longitud.";

                let chars = [
                    ...getRandom(upper, mayus),
                    ...getRandom(lower, minus),
                    ...getRandom(numbers, nums),
                    ...getRandom(symbs, symbols)
                ];

                let resto = longitud - chars.length;
                if (resto > 0) {
                    chars.push(...getRandom(lower, resto));
                }

                return shuffle(chars).join('');
            }

            function generarFrase(n) {
                let frase = [];
                for (let i = 0; i < n; i++) {
                    frase.push(palabras[Math.floor(Math.random() * palabras.length)]);
                }
                return frase.join('-');
            }

            function getRandom(str, count) {
                return Array.from({length: count}, () => str[Math.floor(Math.random() * str.length)]);
            }

            function shuffle(arr) {
                for (let i = arr.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [arr[i], arr[j]] = [arr[j], arr[i]];
                }
                return arr;
            }

            // Hacer que generar() sea global para poder llamarla desde el onclick en el HTML
            window.generar = function() {
                const modo = document.querySelector('#generador-content input[name=modo]:checked').value;
                let resultado = '';
                if (modo === 'pass') {
                    const l = +document.getElementById('longitud').value;
                    const m = +document.getElementById('mayus').value;
                    const mi = +document.getElementById('minus').value;
                    const n = +document.getElementById('nums').value;
                    const s = +document.getElementById('symbols').value;

                    if (m + mi + n + s > l) {
                        resultado = "⚠️ La suma de mayúsculas, minúsculas, números y símbolos no puede ser mayor que la longitud total.";
                    } else {
                        resultado = generarContraseña(l, m, mi, n, s);
                    }
                } else {
                    const num = +document.getElementById('numPalabras').value;
                    resultado = generarFrase(num);
                }
                document.getElementById('resultado').textContent = resultado;
            };

            // Hacer que copiarResultado() sea global
            window.copiarResultado = function() {
                const resultadoTexto = document.getElementById('resultado').textContent;
                const notificacion = document.getElementById('copiado-notificacion');

                const copiarTexto = (texto) => {
                    if (navigator.clipboard) {
                        return navigator.clipboard.writeText(texto);
                    } else {
                        const tempInput = document.createElement('textarea');
                        tempInput.value = texto;
                        document.body.appendChild(tempInput);
                        tempInput.select();
                        document.execCommand('copy');
                        document.body.removeChild(tempInput);
                        return Promise.resolve();
                    }
                };

                copiarTexto(resultadoTexto)
                    .then(() => {
                        notificacion.style.opacity = 1;
                        setTimeout(() => {
                            notificacion.style.opacity = 0;
                        }, 1500);
                    })
                    .catch(err => {
                        alert('No se pudo copiar al portapapeles.');
                    });
            };

            // Listener para los radios de modo (Contraseña/Frase) dentro del generador
            document.querySelectorAll('#generador-content input[name=modo]').forEach(el => {
                el.addEventListener('change', () => {
                    document.getElementById('pass-options').style.display = el.value === 'pass' ? 'block' : 'none';
                    document.getElementById('frase-options').style.display = el.value === 'frase' ? 'block' : 'none';
                });
            });
        });

        // La función `toggleTheme()` que ya tenías para el modo oscuro/claro
        // No necesita ser modificada, ya está en el head y usa `document.body.setAttribute`
        // Lo que significa que también aplica a las variables del Generador porque usan :root
    </script>
</body>
</html>
