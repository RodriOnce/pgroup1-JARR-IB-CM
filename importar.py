import os
import requests
import time
import mysql.connector
import hashlib
import getpass
import csv
from datetime import datetime

# Configura tus variables
API_KEY = '446f02363118eb9dc67c1c250a5eaacd971f6ccf4a26b93b6a07b175bbcc4777'
DIRECTORIO_ORIGEN = '/home/user_admin/escanear'
DIRECTORIO_SANO = '/home/user_admin/sano'
DIRECTORIO_INFECCION = '/home/user_admin/infectado'

# Generar nombre de archivo CSV basado en la fecha actual
today = datetime.now().strftime('%Y-%m-%d')
CSV_FILE = f'scan_results_{today}.csv'

# Crea las carpetas si no existen
os.makedirs(DIRECTORIO_SANO, exist_ok=True)
os.makedirs(DIRECTORIO_INFECCION, exist_ok=True)

# Crear archivo CSV si no existe
if not os.path.exists(CSV_FILE):
    with open(CSV_FILE, mode='w', newline='') as csvfile:
        writer = csv.writer(csvfile)
        writer.writerow(['File Name', 'Hash', 'Scan Date', 'Scan User', 'Scan State', 'Error'])

# Conectar a la base de datos
def conectar_bd():
    return mysql.connector.connect(
        host='localhost',
        user='root',
        password='grupo1',
        database='viruses'
    )

def insertar_en_bd(filename, hash_value, scan_user, scan_state):
    try:
        conexion = conectar_bd()
        cursor = conexion.cursor()

        query = "INSERT INTO archivos (filename, hash, scan_date, scan_user, scan_state) VALUES (%s, %s, %s, %s, %s)"
        cursor.execute(query, (filename, hash_value, datetime.now().isoformat(), scan_user, scan_state))
        conexion.commit()
    except mysql.connector.Error as err:
        print(f"Error al insertar en la base de datos: {err}")
    finally:
        cursor.close()
        conexion.close()

def calcular_hash(file_path, algoritmo='sha256'):
    hash_func = hashlib.new(algoritmo)

    with open(file_path, 'rb') as f:
        while chunk := f.read(8192):
            hash_func.update(chunk)

    return hash_func.hexdigest()

def enviar_a_virustotal(file_path):
    url = 'https://www.virustotal.com/api/v3/files'
    with open(file_path, 'rb') as f:
        files = {'file': (os.path.basename(file_path), f)}
        headers = {
            'x-apikey': API_KEY
        }
        response = requests.post(url, files=files, headers=headers)
        return response.json(), response.status_code

def verificar_resultado(file_id):
    url = f'https://www.virustotal.com/api/v3/analyses/{file_id}'
    headers = {
        'x-apikey': API_KEY
    }
    response = requests.get(url, headers=headers)

    # Manejo de errores en la respuesta
    if response.status_code != 200:
        print(f"Error al obtener el anÃ¡lisis: {response.status_code}")
        return {}, response.status_code

    return response.json(), response.status_code

def manejar_error_api(code):
    error_messages = {
        400: "Bad Request: La solicitud no se pudo entender.",
        403: "Forbidden: Acceso denegado a la API.",
        404: "Not Found: Recurso no encontrado.",
        415: "Unsupported Media Type: Tipo de archivo no soportado.",
        429: "Too Many Requests: Has superado el lÃ­mite de solicitudes.",
        500: "Internal Server Error: Error del servidor de VirusTotal.",
        503: "Service Unavailable: El servicio no estÃ¡ disponible temporalmente.",
        504: "Gateway Timeout: La solicitud tardÃ³ demasiado en completarse.",
        401: "Unauthorized: No estÃ¡s autorizado para acceder a este recurso.",
        402: "Payment Required: La cuenta requiere pago para acceder a este recurso.",
        408: "Request Timeout: La solicitud tardÃ³ demasiado tiempo en completarse.",
    }
    return error_messages.get(code, "Error desconocido.")

def log_to_csv(file_name, hash_value, scan_user, scan_state, stats=None, analysis_results=None, error=None):
    with open(CSV_FILE, mode='a', newline='') as csvfile:
        writer = csv.writer(csvfile)
        writer.writerow([file_name, hash_value, datetime.now().isoformat(), scan_user, scan_state, error])

        # Escribir las estadÃ­sticas de anÃ¡lisis, si estÃ¡n disponibles
        if stats:
            writer.writerow(["EstadÃ­sticas de anÃ¡lisis:", stats])

        # Escribir los resultados detallados de cada motor, si estÃ¡n disponibles
        if analysis_results:
            writer.writerow(["Motor Antivirus", "Resultado", "MÃ©todo", "Ãšltima ActualizaciÃ³n"])
            for engine, result in analysis_results.items():
                writer.writerow([result['engine_name'], result.get('result', 'N/A'), result.get('method', 'N/A'), result.get('engine_update', 'N/A')])

def clasificar_archivos():
    scan_user = getpass.getuser()  # Obtiene el nombre del usuario actual
    for filename in os.listdir(DIRECTORIO_ORIGEN):
        file_path = os.path.join(DIRECTORIO_ORIGEN, filename)

        if os.path.isfile(file_path):
            # Calcular el hash del archivo
            hash_value = calcular_hash(file_path, 'sha256')

            try:
                print(f'Enviando {filename} a VirusTotal...')
                result, status_code = enviar_a_virustotal(file_path)

                # Verifica si la solicitud fue exitosa
                if status_code == 200 and 'data' in result:
                    file_id = result['data']['id']
                    # Espera unos segundos para que se procese el archivo
                    time.sleep(15)

                    # Verifica el resultado del anÃ¡lisis
                    result_detail, status_code = verificar_resultado(file_id)

                    if status_code == 200 and 'data' in result_detail and 'attributes' in result_detail['data']:
                        attributes = result_detail['data']['attributes']
                        stats = attributes.get('last_analysis_stats', {})  # Extraer las estadÃ­sticas
                        analysis_results = attributes.get('last_analysis_results', {})  # Extraer los resultados por motor

                        # Clasificar segÃºn el anÃ¡lisis
                        if stats.get('malicious', 0) > 0:
                            destino = DIRECTORIO_INFECCION
                            scan_state = False  # Infectado
                        else:
                            destino = DIRECTORIO_SANO
                            scan_state = True  # Sano

                        # Mueve el archivo a la carpeta correspondiente
                        os.rename(file_path, os.path.join(destino, filename))
                        print(f'Archivo {filename} movido a {destino}.')

                        # Inserta la informaciÃ³n en la base de datos
                        insertar_en_bd(filename, hash_value, scan_user, scan_state)

                        # Loguear en el CSV
                        log_to_csv(filename, hash_value, scan_user, scan_state, stats, analysis_results)

                    else:
                        error_message = f'Error al obtener el resultado para {filename}. Detalles: {result_detail}'
                        print(error_message)
                        log_to_csv(filename, hash_value, scan_user, None, error=error_message)
                else:
                    error_message = manejar_error_api(status_code)
                    print(error_message)
                    log_to_csv(filename, hash_value, scan_user, None, error=error_message)

            except Exception as e:
                error_message = str(e)
                print(f'Error inesperado al procesar {filename}: {error_message}')
                log_to_csv(filename, hash_value, scan_user, None, error=error_message)

if __name__ == '__main__':
    clasificar_archivos()