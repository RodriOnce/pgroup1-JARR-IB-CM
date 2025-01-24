<?php
// Habilitar la visualización de errores (solo para desarrollo)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Clave de API de VirusTotal (debes obtenerla desde tu cuenta de VirusTotal)
$apiKey = '446f02363118eb9dc67c1c250a5eaacd971f6ccf4a26b93b6a07b175bbcc4777';

// Verificar si se ha enviado una URL
if (isset($_POST['url'])) {
    $url = trim($_POST['url']);

    if (!empty($url)) {
        // Codificar la URL en Base64 (requerido por VirusTotal)
        $encodedUrl = base64_encode($url);

        // URL de la API de VirusTotal para analizar URLs
        $apiUrl = "https://www.virustotal.com/api/v3/urls";

        // Configurar la solicitud HTTP
        $options = [
            "http" => [
                "method" => "POST",
                "header" => "x-apikey: $apiKey\r\n" .
                            "Content-Type: application/x-www-form-urlencoded\r\n",
                "content" => "url=$url"
            ]
        ];

        // Crear el contexto de la solicitud
        $context = stream_context_create($options);

        // Enviar la solicitud a la API de VirusTotal
        $response = file_get_contents($apiUrl, false, $context);

        if ($response === FALSE) {
            echo "Error al conectar con la API de VirusTotal.";
        } else {
            // Decodificar la respuesta JSON
            $data = json_decode($response, true);

            // Verificar si la respuesta contiene datos válidos
            if (isset($data['data']['id'])) {
                // Obtener el ID del análisis
                $analysisId = $data['data']['id'];

                // URL para obtener el resultado del análisis
                $analysisUrl = "https://www.virustotal.com/api/v3/analyses/$analysisId";

                // Configurar la solicitud para obtener el resultado del análisis
                $options = [
                    "http" => [
                        "method" => "GET",
                        "header" => "x-apikey: $apiKey\r\n"
                    ]
                ];

                $context = stream_context_create($options);

                // Esperar a que el análisis se complete
                $status = '';
                $attempts = 0;
                $max_attempts = 10;
                $completed = false;
                
                while (!$completed && $attempts < $max_attempts) {
                    // Obtener el resultado del análisis
                    $analysisResponse = file_get_contents($analysisUrl, false, $context);

                    if ($analysisResponse === FALSE) {
                        echo "Error al obtener el resultado del análisis.";
                        break;
                    } else {
                        // Decodificar la respuesta del análisis
                        $analysisData = json_decode($analysisResponse, true);

                        // Verificar si el análisis está completo
                        if (isset($analysisData['data']['attributes']['status']) && 
                            $analysisData['data']['attributes']['status'] === 'completed') {
                            $status = 'completed';
                            $completed = true;
                        } else {
                            $status = 'in progress';
                            // Esperar antes de intentar nuevamente
                            echo "Esperando a que el análisis se complete...<br>";
                            sleep(15); // Esperar 15 segundos antes de la siguiente consulta
                            $attempts++;
                        }
                    }
                }

                if ($status === 'completed') {
                    // Obtener las estadísticas del análisis
                    $stats = $analysisData['data']['attributes']['stats'];

                    // Mostrar los resultados
                    echo "<h2>Resultado del análisis para la URL: $url</h2>";
                    echo "<ul>";
                    echo "<li>Maliciosos: " . $stats['malicious'] . "</li>";
                    echo "<li>Sospechosos: " . $stats['suspicious'] . "</li>";
                    echo "<li>Seguros: " . $stats['harmless'] . "</li>";
                    echo "<li>No clasificados: " . $stats['undetected'] . "</li>";
                    echo "</ul>";

                    // Determinar si la URL es maliciosa o confiable
                    if ($stats['malicious'] > 0) {
                        echo "<p style='color: red;'>¡Advertencia! Esta URL es considerada maliciosa.</p>";
                    } else {
                        echo "<p style='color: green;'>Esta URL es confiable.</p>";
                    }
                } else {
                    echo "El análisis no se completó después de varios intentos. Inténtalo nuevamente más tarde.";
                }
            } else {
                echo "No se pudo obtener un ID de análisis válido.";
            }
        }
    } else {
        echo "Por favor, ingrese una URL válida.";
    }
} else {
    echo "No se ha proporcionado una URL.";
}
?>
