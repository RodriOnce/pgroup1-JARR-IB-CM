import os # Manejo archivos
import csv # Leer/escribir CSV
import time # Pausas/esperas
import requests # Peticiones HTTP
import hashlib # Hash archivos
import mysql.connector # Conexión MySQL
from datetime import datetime # Fecha actual

# Configura tus variables
API_KEY = '446f02363118eb9dc67c1c250a5eaacd971f6ccf4a26b93b6a07b175bbcc4777'
DIRECTORIO_ORIGEN = '/var/www/html/escanear'
DIRECTORIO_SANO = '/var/www/html/sano'
DIRECTORIO_INFECCION = '/var/www/html/infectado'
today = datetime.now().strftime('%Y-%m-%d')
CSV_FILE = f'/var/www/html/CSV/scan_results_{today}.csv'

# Función para conectarse a la base de datos
def conectar_bd():
    return mysql.connector.connect(
        host='localhost',
        user='root',
        password='momo',
        database='viruses'
    ) # Conexión MySQL

# Función para insertar datos en la base de datos
def insertar_en_bd(filename, hash_value, scan_user, scan_state):
    try:
        conexion = conectar_bd()
        cursor = conexion.cursor()

        query = """
        INSERT INTO archivos (filename, hash, scan_date, scan_user, scan_state)
        VALUES (%s, %s, CURDATE(), %s, %s)
        """
        cursor.execute(query, (filename, hash_value, scan_user, scan_state))
        conexion.commit()
    except mysql.connector.Error as err:
        print(f"Error al insertar en la base de datos: {err}") # Error base datos
    finally:
        cursor.close()
        conexion.close() # Siempre cerrar conexión

# Función para calcular el hash de un archivo
def calcular_hash(file_path, algoritmo='sha256'):
    hash_func = hashlib.new(algoritmo)
    with open(file_path, 'rb') as f:
        while chunk := f.read(8192): # Leer en partes
            hash_func.update(chunk)
    return hash_func.hexdigest() # Devolver hash

# Función para subir archivos a VirusTotal
# def subir_archivo(filepath):
#     url = 'https://www.virustotal.com/api/v3/files'
#     headers = {'x-apikey': API_KEY}
#     files = {'file': (os.path.basename(filepath), open(filepath, 'rb'))}
#     response = requests.post(url, headers=headers, files=files)
#     return response.json()

def subir_archivo(filepath):
    url = 'https://www.virustotal.com/api/v3/files' # URL API VT
    headers = {'x-apikey': API_KEY}
    with open(filepath, 'rb') as f:
        files = {'file': (os.path.basename(filepath), f)}
        response = requests.post(url, headers=headers, files=files) # Enviar archivo

    try:
        return response.json() # Intentar parsear JSON
    except Exception as e:
        print(f"Error al parsear JSON desde VirusTotal: {e}") # Error respuesta
        print(f"Código de estado HTTP: {response.status_code}")
        print(f"Contenido de la respuesta:\n{response.text}")
        return {}  # Devuelve un diccionario vacío si hay fallo


# Función para obtener el informe de VirusTotal con verificación de estado
def obtener_informe(file_id):
    url = f'https://www.virustotal.com/api/v3/analyses/{file_id}' # URL reporte VT
    headers = {'x-apikey': API_KEY}
    while True: # Esperar análisis completo
        response = requests.get(url, headers=headers).json()
        status = response.get('data', {}).get('attributes', {}).get('status')

        # Esperar hasta que el estado sea 'completed'
        if status == 'completed': # Si terminó, devolver
            return response
        print("Esperando a que el análisis se complete...") # Esperar análisis
        time.sleep(5)  # Espera adicional para que finalice el análisis 5 segundos

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

        for filename in os.listdir(DIRECTORIO_ORIGEN): # Iterar archivos
            filepath = os.path.join(DIRECTORIO_ORIGEN, filename)

            # Subir archivo y obtener el ID de análisis
            print(f'Subiendo archivo: {filename}') # Subiendo archivo
            response = subir_archivo(filepath) # Enviar VirusTotal
            file_id = response.get('data', {}).get('id') # ID análisis

            if file_id:
                # Obtener el informe completo cuando esté listo
                print(f'Obteniendo informe para: {filename}') # Esperando informe
                informe = obtener_informe(file_id) # Esperar resultado

                # Analizar los resultados
                stats = informe.get('data', {}).get('attributes', {}).get('stats', {}) # Estadísticas VT
                malicious = stats.get('malicious', 0)
                harmless = stats.get('harmless', 0)
                suspicious = stats.get('suspicious', 0)
                undetected = stats.get('undetected', 0)

                # Determinar si es seguro o infectado
                if malicious > 0 or suspicious > 0: # Detecta malware
                    destino = DIRECTORIO_INFECCION
                    status = 'infected'
                else:
                    destino = DIRECTORIO_SANO
                    status = 'safe'

                # Mover archivo a la carpeta correspondiente
                final_path = os.path.join(destino, filename) # Nueva ruta
                os.rename(filepath, final_path) # Mover archivo

                # Calcular hash del archivo
                hash_value = calcular_hash(final_path) # Hash archivo

                # Obtener la fecha, hora y usuario actual
                date = datetime.now().strftime('%Y-%m-%d')
                time_now = datetime.now().strftime('%H:%M:%S')
                #user = os.getlogin()
                user = os.environ.get('USER') or os.environ.get('USERNAME') or 'www-data'

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
                }) # Escribir CSV

                # Insertar en la base de datos
                insertar_en_bd(filename, hash_value, user, status) # Registrar base datos
                print(f'Archivo {filename} procesado y movido a {destino}. Datos registrados en la base de datos.')
            else:
                print(f'Error al subir el archivo: {filename}') # Error subida

if __name__ == "__main__":
    procesar_archivos() # Iniciar script
