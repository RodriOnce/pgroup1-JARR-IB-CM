import os
import csv
import time
import requests
from datetime import datetime

# Configura tus variables
API_KEY = '446f02363118eb9dc67c1c250a5eaacd971f6ccf4a26b93b6a07b175bbcc4777'
DIRECTORIO_ORIGEN = '/home/user_admin/escanear'
DIRECTORIO_SANO = '/home/user_admin/sano'
DIRECTORIO_INFECCION = '/home/user_admin/infectado'
today = datetime.now().strftime('%Y-%m-%d')
CSV_FILE = f'/home/user_admin/CSV/scan_results_{today}.csv'

# Función para subir archivos a VirusTotal
def subir_archivo(filepath):
    url = 'https://www.virustotal.com/api/v3/files'
    headers = {'x-apikey': API_KEY}
    files = {'file': (os.path.basename(filepath), open(filepath, 'rb'))}
    response = requests.post(url, headers=headers, files=files)
    return response.json()

# Función para obtener el informe de VirusTotal con verificación de estado
def obtener_informe(file_id):
    url = f'https://www.virustotal.com/api/v3/analyses/{file_id}'
    headers = {'x-apikey': API_KEY}
    while True:
        response = requests.get(url, headers=headers).json()
        status = response.get('data', {}).get('attributes', {}).get('status')

        # Esperar hasta que el estado sea 'completed'
        if status == 'completed':
            return response
        print("Esperando a que el análisis se complete...")
        time.sleep(15)  # Espera adicional para que finalice el análisis

# Función para procesar archivos de un directorio
def procesar_archivos():
    # Crear archivo CSV
    with open(CSV_FILE, mode='a', newline='') as csvfile:  # Modo 'a' para añadir datos sin sobrescribir
        fieldnames = ['filename', 'file_id', 'status', 'malicious', 'harmless', 'suspicious',
                      'undetected', 'date', 'time', 'user', 'final_path']
        writer = csv.DictWriter(csvfile, fieldnames=fieldnames)

        # Escribir encabezado si el archivo está vacío
        if csvfile.tell() == 0:
            writer.writeheader()

        for filename in os.listdir(DIRECTORIO_ORIGEN):
            filepath = os.path.join(DIRECTORIO_ORIGEN, filename)

            # Subir archivo y obtener el ID de análisis
            print(f'Subiendo archivo: {filename}')
            response = subir_archivo(filepath)
            file_id = response.get('data', {}).get('id')

            if file_id:
                # Obtener el informe completo cuando esté listo
                print(f'Obteniendo informe para: {filename}')
                informe = obtener_informe(file_id)

                # Analizar los resultados
                stats = informe.get('data', {}).get('attributes', {}).get('stats', {})
                malicious = stats.get('malicious', 0)
                harmless = stats.get('harmless', 0)
                suspicious = stats.get('suspicious', 0)
                undetected = stats.get('undetected', 0)

                # Determinar si es seguro o infectado
                if malicious > 0 or suspicious > 0:
                    destino = DIRECTORIO_INFECCION
                    status = 'infected'
                else:
                    destino = DIRECTORIO_SANO
                    status = 'safe'

                # Mover archivo a la carpeta correspondiente
                final_path = os.path.join(destino, filename)
                os.rename(filepath, final_path)

                # Obtener la fecha, hora y usuario actual
                date = datetime.now().strftime('%Y-%m-%d')
                time_now = datetime.now().strftime('%H:%M:%S')
                user = os.getlogin()

                # Guardar resultados en CSV
                writer.writerow({
                    'filename': filename,
                    'file_id': file_id,
                    'status': status,
                    'malicious': malicious,
                    'harmless': harmless,
                    'suspicious': suspicious,
                    'undetected': undetected,
                    'date': date,
                    'time': time_now,
                    'user': user,
                    'final_path': final_path
                })
                print(f'Archivo {filename} procesado y movido a {destino}')
            else:
                print(f'Error al subir el archivo: {filename}')

if __name__ == "__main__":
    procesar_archivos()
