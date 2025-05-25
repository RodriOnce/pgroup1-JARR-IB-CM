<?php

/*───────────────────────────────────────────*/
/* CABECERAS DE SEGURIDAD                    */
/*───────────────────────────────────────────*/
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; frame-ancestors 'none'");

/*───────────────────────────────────────────*/
/* SESIÓN + TOKEN CSRF                       */
/*───────────────────────────────────────────*/
session_set_cookie_params([
    'secure'   => false,           // pon true cuando uses HTTPS
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/*───────────────────────────────────────────*/
/* CONEXIÓN PDO                              */
/*───────────────────────────────────────────*/
require_once __DIR__ . '/../seguridad/config.php';
try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('Error de base de datos: La conexión falló.');
}

/*───────────────────────────────────────────*/
/* VARIABLES PARA LA VISTA                   */
/*───────────────────────────────────────────*/
$alert  = '';
$values = ['name'=>'','user'=>'','mail'=>'','dpt'=>'','password'=>'','confirm_password'=>''];


/*───────────────────────────────────────────*/
/* MAPEO DE TRADUCCIONES DE DEPARTAMENTOS    */
/*───────────────────────────────────────────*/
$departamento_traducciones = [
    'IT'   => 'IT',
    'DIR'  => 'Dirección',
    'ADM'  => 'Administración',
    'SL'   => 'Ventas',
    'MKT'  => 'Marketing',
    'LGTC' => 'Logística',
    // Añade aquí cualquier otro departamento que exista en tu columna 'dpt'
];


/*───────────────────────────────────────────*/
/* OBTENER DEPARTAMENTOS DISPONIBLES         */
/*───────────────────────────────────────────*/
$departamentos_disponibles = [];
try {
    $stmt_dpts = $pdo->prepare("SELECT DISTINCT dpt FROM empleados ORDER BY dpt ASC");
    $stmt_dpts->execute();
    $departamentos_disponibles = $stmt_dpts->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error al obtener departamentos: " . $e->getMessage());
}

// Lógica para pre-seleccionar el departamento si $values['dpt'] tiene un valor
if (!isset($values['dpt'])) {
    $values['dpt'] = '';
}


/*───────────────────────────────────────────*/
/* PROCESAR FORMULARIO                       */
/*───────────────────────────────────────────*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* CSRF */
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $alert = 'Petición CSRF no válida.';
    } else {

        /* Recoger datos */
        $name     = trim($_POST['name']         ?? '');
        $user     = trim($_POST['user']         ?? '');
        $mail     = trim($_POST['mail']         ?? '');
        $pass     =          $_POST['password']         ?? '';
        $cpass    =          $_POST['confirm_password'] ?? '';
        $dpt      = trim($_POST['dpt']          ?? '');
        $pregunta = trim($_POST['pregunta']     ?? '');
        $respuesta = trim($_POST['respuesta']   ?? '');

        // Actualizar $values con los datos posteados para que el formulario se rellene en caso de error
        $values = compact('name','user','mail','dpt','pregunta','respuesta');


        /* Validaciones */
        $errors = [];
        if ($name === '' || $user === '' || $mail === '' || $pass === '' || $cpass === '' || $dpt === '' || $pregunta === '' || $respuesta === '') {
            $errors[] = 'Todos los campos son obligatorios.';
        }
        if (!preg_match('/^[A-Za-z0-9_\-]{3,30}$/', $user)) {
            $errors[] = 'Usuario: 3-30 letras, números, guion o guion bajo.';
        }
        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Correo electrónico no válido.';
        }
        if ($pass !== $cpass) {
            $errors[] = 'Las contraseñas no coinciden.';
        }
        if (strlen($pass) < 8) {
            $errors[] = 'La contraseña necesita al menos 8 caracteres.';
        }
        // Validar que el dpt seleccionado sea uno de los disponibles (el código corto/clave del array)
        if (!array_key_exists($dpt, $departamento_traducciones) || !in_array($dpt, $departamentos_disponibles)) {
            $errors[] = 'Departamento seleccionado no es válido.';
        }


        /* Duplicados */
        if (!$errors) {
            $dup = $pdo->prepare('SELECT COUNT(*) FROM empleados WHERE user = :u OR mail = :m');
            $dup->execute(['u'=>$user,'m'=>$mail]);
            if ($dup->fetchColumn() > 0) {
                $errors[] = 'Usuario o correo ya registrado.';
            }
        }

        if ($errors) {
            $alert = implode('<br>', $errors);
        } else {
            /* Insertar */
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $ins = $pdo->prepare(
                'INSERT INTO empleados (name, user, mail, pass, dpt, status, pregunta_seguridad, respuesta_seguridad)
                 VALUES (:n,:u,:m,:p,:d, "pendiente", :ps, :rs)'
            );
            $ins->execute([
                'n'=>$name,
                'u'=>$user,
                'm'=>$mail,
                'p'=>$hash,
                'd'=>$dpt,
                'ps'=>$pregunta,
                'rs'=>$respuesta
            ]);

            echo "<script>alert('Registro exitoso. Ahora puedes iniciar sesión.'); window.location.href='login.html';</script>";
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Registro de usuario para la plataforma TrackZero">
  <title>Registro | TrackZero</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

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

    /* === layout === */
    body{
      margin:0;
      padding:0;
      font-family:'Roboto',sans-serif;
      background:var(--background-gradient-light);
      color:var(--text-color-light);
      display:flex;
      justify-content:center;
      align-items:center;
      min-height:100vh;
      overflow:hidden;
      transition:background .3s,color .3s;
    }
    body[data-theme="dark"]{
      background:var(--background-gradient-dark);
      color:var(--text-color-dark);
    }
    .container{
      backdrop-filter:blur(15px);
      background:var(--card-background-light);
      padding:40px;
      border-radius:15px;
      box-shadow:0 10px 30px var(--shadow-light);
      width:400px;
      text-align:center;
      animation:fadeIn 1s ease-in-out;
      position:relative;
      transition:background .3s,box-shadow .3s;
    }
    body[data-theme="dark"] .container{
      background:var(--card-background-dark);
      box-shadow:0 10px 30px var(--shadow-dark);
    }
    @keyframes fadeIn{
      from{opacity:0;transform:translateY(20px)}
      to{opacity:1;transform:translateY(0)}
    }
    h1{
      font-size:2.5rem;
      margin-bottom:20px;
      color:var(--header-color-light);
      font-weight:bold;
      transition:color .3s;
    }
    body[data-theme="dark"] h1{
      color:var(--header-color-dark);
    }

    /* === form === */
    .form-group{
      margin-bottom:20px;
      text-align:left;
      }
    .form-group label{
      display:block;
      font-size:1rem;
      margin-bottom:5px;
      font-weight:bold;
      }
    .form-group input{
      width:100%;
      padding:12px;
      border:1px solid rgba(0,0,0,.2);
      border-radius:8px;
      background:rgba(0,0,0,.05);
      color:inherit;
      font-size:1rem;
      outline:none;
      transition:border-color .3s,box-shadow .3s;
    }
    .form-group input:focus{
      border-color:var(--input-focus-light);
      box-shadow:0 0 10px var(--input-focus-light);
    }
    body[data-theme="dark"] .form-group input:focus{
      border-color:var(--input-focus-dark);
      box-shadow:0 0 10px var(--input-focus-dark);
    }

    /* --- ESTILOS PARA EL SELECT --- */
    .form-group select {
      width: 100%;
      padding: 12px;
      border: 1px solid rgba(0,0,0,.2);
      border-radius: 8px;
      background: rgba(0,0,0,.05);
      color: inherit;
      font-size: 1rem;
      outline: none;
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      cursor: pointer;
      transition: border-color .3s, box-shadow .3s, background .3s;
    }

    .form-group select:focus {
      border-color: var(--input-focus-light);
      box-shadow: 0 0 10px var(--input-focus-light);
    }

    /* Estilos para el modo oscuro del select */
    body[data-theme="dark"] .form-group select {
    }

    body[data-theme="dark"] .form-group select:focus {
      border-color: var(--input-focus-dark);
      box-shadow: 0 0 10px var(--input-focus-dark);
    }


    /* === buttons === */
    .btn{
      display:inline-block;
      width:100%;
      padding:12px;
      font-size:1.2rem;
      color:#fff;
      background:var(--button-gradient-light);
      border:none;
      border-radius:8px;
      cursor:pointer;
      font-weight:bold;
      text-transform:uppercase;
      transition:transform .2s,background .3s;
      box-shadow:0 4px 20px var(--shadow-light);
    }
    .btn:hover{
      background:var(--button-hover-light);
      transform:translateY(-2px);
    }
    body[data-theme="dark"] .btn{
      background:var(--button-gradient-dark);
      box-shadow:0 4px 20px var(--shadow-dark);
    }
    body[data-theme="dark"] .btn:hover{
      background:var(--button-hover-dark);
    }
    .btn:active{transform:translateY(0)}

    .link{
      margin-top:15px;
      font-size:.9rem;
      color:#6a11cb;
      text-decoration:none;
    }
    .link:hover{text-decoration:underline}

    /* === misc === */
    .theme-toggle{
      position:absolute;
      top:20px;
      right:20px;
      background:var(--button-gradient-light);
      color:#fff;
      border:none;
      padding:10px 15px;
      border-radius:50px;
      cursor:pointer;
      font-weight:bold;
      transition:background .3s;
    }
    body[data-theme="dark"] .theme-toggle{background:var(--button-gradient-dark)}
    .theme-toggle::after{content:"Modo Oscuro"}
    body[data-theme="dark"] .theme-toggle::after{content:"Modo Claro"}

    /* Estilos para el logo y la marca */
    .brand-container {
      position: absolute;
      top: 20px;
      left: 20px;
      z-index: 1000; /* Asegura que esté por encima de otros elementos */
    }

    .brand {
      display: flex; /* Para alinear el logo y el texto horizontalmente */
      align-items: center; /* Centra verticalmente el logo y el texto */
      text-decoration: none; /* Quita el subrayado del enlace */
      color: var(--header-color-light); /* Hereda el color del encabezado */
      font-size: 1.8rem; /* Tamaño de fuente para el nombre de la marca */
      font-weight: bold;
      transition: color .3s; /* Transición para el cambio de color en modo oscuro */
    }

    .brand img {
      height: 40px; /* Tamaño del logo. Ajusta según tu imagen */
      margin-right: 10px; /* Espacio entre el logo y el texto */
      vertical-align: middle; /* Alineación vertical */
      /* Transición para el filtro */
      transition: filter .3s ease;
    }

    /* Estilos para el modo oscuro del logo y texto */
    body[data-theme="dark"] .brand {
      color: var(--header-color-dark);
    }

    body[data-theme="dark"] .brand img {
      /* Invierte los colores y ajusta el brillo para que sea blanco */
      filter: invert(1) brightness(1.5); /* Puedes ajustar el valor de brightness si el blanco no es puro */
    }


    .dynamic-background{
      position:absolute;
      top:0;left:0;width:100%;height:100%;
      z-index:-1;overflow:hidden;
    }
    .dynamic-background div{
      position:absolute;
      background:rgba(0,0,0,.05);
      border-radius:50%;
      animation:float 12s infinite ease-in-out;
    }
    body[data-theme="dark"] .dynamic-background div{
      background:rgba(255,255,255,.08);
    }
    @keyframes float{
      0%,100%{transform:translateY(0)}
      50%{transform:translateY(-30px)}
    }
    .dynamic-background .circle-1{width:300px;height:300px;top:10%;left:20%}
    .dynamic-background .circle-2{width:200px;height:200px;bottom:15%;right:25%;animation-delay:4s}
    .dynamic-background .circle-3{width:150px;height:150px;top:50%;left:50%;animation-delay:6s}

    .alert{
      margin-bottom:20px;
      padding:12px;
      border-radius:8px;
      background:#fdecea;
      color:#b71c1c;
      border:1px solid #f5c2c0;
    }
  </style>


  <script>
    function toggleTheme(){
      const body=document.body;
      body.dataset.theme = body.dataset.theme === 'light' ? 'dark' : 'light';
    }
  </script>

</head>
<body data-theme="light">

  <div class="brand-container">
    <a href="index.html" class="brand">
      <img src="img/logo.png" alt="Logo TrackZero" />TrackZero
    </a>
  </div>

  <button class="theme-toggle" onclick="toggleTheme()"></button>

  <div class="dynamic-background">
    <div class="circle-1"></div>
    <div class="circle-2"></div>
    <div class="circle-3"></div>
  </div>

<div class="container">
    <h1>Registro</h1>

    <?php if ($alert): ?>
      <div class="alert"><?php echo $alert; ?></div>
    <?php endif; ?>

    <form action="registro.php" method="post" autocomplete="off">
      <div class="form-group">
        <label for="name"><strong>Nombre Completo</strong></label>
        <input type="text" id="name" name="name"
               value="<?php echo htmlspecialchars($values['name']); ?>" required>
      </div>

      <div class="form-group">
        <label for="user"><strong>Nombre de Usuario</strong></label>
        <input type="text" id="user" name="user"
               pattern="[A-Za-z0-9_\-]{3,30}"
               value="<?php echo htmlspecialchars($values['user']); ?>" required>
      </div>

      <div class="form-group">
        <label for="mail"><strong>Correo Electrónico</strong></label>
        <input type="email" id="mail" name="mail"
               value="<?php echo htmlspecialchars($values['mail']); ?>" required>
      </div>

      <div class="form-group">
        <label for="password"><strong>Contraseña</strong></label>
        <input type="password" id="password" name="password"
               minlength="8" required>
      </div>

      <div class="form-group">
        <label for="confirm_password"><strong>Confirmar Contraseña</strong></label>
        <input type="password" id="confirm_password"
               name="confirm_password" minlength="8" required>
      </div>

      <div class="form-group">
        <label for="dpt"><strong>Departamento</strong></label>
        <select id="dpt" name="dpt" required
                class="form-group select"
                style="width:100%; padding:12px; border:1px solid rgba(0,0,0,.2); border-radius:8px; background:rgba(0,0,0,.05); color:inherit; font-size:1rem; outline:none; appearance:none; -webkit-appearance:none; -moz-appearance:none; cursor:pointer; transition:border-color .3s,box-shadow .3s,background .3s;">
          <option value="">-- Selecciona un Departamento --</option>
          <?php
          foreach($departamentos_disponibles as $dpt_code):
              // Usar el mapeo para obtener el nombre traducido
              $dpt_display_name = isset($departamento_traducciones[$dpt_code]) ? $departamento_traducciones[$dpt_code] : $dpt_code;
          ?>
            <option value="<?= htmlspecialchars($dpt_code) ?>"
                    <?php if ($dpt_code == $values['dpt']) echo 'selected'; ?>>
              <?= htmlspecialchars($dpt_display_name) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="pregunta"><strong>Pregunta de seguridad</strong></label>
        <select id="pregunta" name="pregunta" required>
          <option value="">-- Selecciona una pregunta --</option>
          <option value="Nombre de tu primera mascota">Nombre de tu primera mascota</option>
          <option value="Ciudad donde naciste">Ciudad donde naciste</option>
          <option value="Nombre de tu escuela primaria">Nombre de tu escuela primaria</option>
        </select>
      </div>

      <div class="form-group">
        <label for="respuesta"><strong>Respuesta</strong></label>
        <input type="text" id="respuesta" name="respuesta" required>
      </div>

      <input type="hidden" name="csrf"
             value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">

      <button type="submit" class="btn">Registrarse</button>
    </form>

    <a href="login.html" class="link">¿Ya tienes cuenta? Inicia sesión</a>

  </div>

</body>
</html>
