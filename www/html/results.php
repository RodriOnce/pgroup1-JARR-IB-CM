<?php
session_start(); // Iniciar sesión

// Verificar si hay resultados
if (!isset($_SESSION['resultados']) || empty($_SESSION['resultados'])) {
    echo "<div style='font-family: Arial, sans-serif; text-align: center; margin-top: 50px;'>
            <h1>No hay resultados para mostrar</h1>
          </div>";
    exit;
}

// Mostrar los resultados con diseño
echo "<div style='font-family: Arial, sans-serif; margin: 20px auto; max-width: 600px; text-align: center;'>
        <h1 style='color: #4CAF50;'>Resultados del análisis</h1>
        <div style='border: 1px solid #ccc; border-radius: 10px; padding: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);'>";

// Mostrar cada resultado dentro de un bloque
foreach ($_SESSION['resultados'] as $resultado) {
    echo "<div style='background-color: #f9f9f9; margin: 10px 0; padding: 10px; border-radius: 5px;'>
            $resultado
          </div>";
}

echo "  </div>
      </div>";

// Limpiar la variable de sesión
unset($_SESSION['resultados']);
?>

