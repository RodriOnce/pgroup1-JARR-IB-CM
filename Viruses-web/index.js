async function checkURL() {
    const inputElement = document.querySelector('.dropdown input');
    const url = inputElement.value.trim();

    if (!url) {
        alert("Por favor, ingrese una URL válida.");
        return;
    }

    try {
        // Aquí debes usar tu clave de API de VirusTotal
        const apiKey = "446f02363118eb9dc67c1c250a5eaacd971f6ccf4a26b93b6a07b175bbcc4777";
        const apiUrl = `https://www.virustotal.com/api/v3/urls`;

        // Convierte la URL a base64 sin relleno
        const encodedUrl = btoa(url).replace(/=+$/, '');

        const response = await fetch(`${apiUrl}/${encodedUrl}`, {
            method: "GET",
            headers: {
                "x-apikey": apiKey,
            },
        });

        if (!response.ok) {
            throw new Error(`Error en la consulta: ${response.statusText}`);
        }

        const data = await response.json();

        // Muestra los resultados
        if (data && data.data && data.data.attributes.last_analysis_stats) {
            const stats = data.data.attributes.last_analysis_stats;
            alert(`Resultado del análisis:
            - Maliciosos: ${stats.malicious}
            - Sospechosos: ${stats.suspicious}
            - Seguros: ${stats.harmless}`);
        } else {
            alert("No se pudo obtener el análisis para esta URL.");
        }
    } catch (error) {
        console.error("Error verificando la URL:", error);
        alert("Ocurrió un error al verificar la URL. Por favor, inténtelo de nuevo.");
    }
}
