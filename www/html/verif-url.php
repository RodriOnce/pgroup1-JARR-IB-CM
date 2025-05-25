<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análisis de URL con VirusTotal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h1, h2 {
            text-align: center;
            color: #444;
        }

        p {
            font-size: 1rem;
            line-height: 1.6;
            margin: 10px 0;
        }

        ul {
            list-style-type: none;
            padding: 0;
        }

        ul li {
            background: #f9f9f9;
            margin: 5px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            text-align: center;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            text-align: center;
        }

        .info {
            background-color: #cce5ff;
            color: #004085;
            border: 1px solid #b8daff;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            text-align: center;
        }

        .warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            text-align: center;
        }

        button {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Análisis de URL con VirusTotal</h1>
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
                    echo "<div class='error'>Error al conectar con la API de VirusTotal.</div>";
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
                                echo "<div class='error'>Error al obtener el resultado del análisis.</div>";
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
                                    echo "<div class='info'>Esperando a que el análisis se complete...</div>";
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
                                echo "<div class='warning'>¡Advertencia! Esta URL es considerada maliciosa.</div>";
                            } else {
                                echo "<div class='success'>Esta URL es confiable.</div>";
                            }
                        } else {
                            echo "<div class='error'>El análisis no se completó después de varios intentos. Inténtalo nuevamente más tarde.</div>";
                        }
                    } else {
                        echo "<div class='error'>No se pudo obtener un ID de análisis válido.</div>";
                    }
                }
            } else {
                echo "<div class='error'>Por favor, ingrese una URL válida.</div>";
            }
        } else {
            echo "<div class='error'>No se ha proporcionado una URL.</div>";
        }
        ?>
        <button onclick="window.location.href='index.html';">Volver a intentar</button>
    </div>
</body>
</html>
