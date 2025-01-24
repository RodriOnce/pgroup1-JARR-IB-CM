#Bibliotecas necesarias
import os
import csv
import time
import requests
import hashlib
import mysql.connector
from datetime import datetime

# Configuración de variables

API_KEY = '446f02363118eb9dc67c1c250a5eaacd971f6ccf4a26b93b6a07b175bbcc4777'
DIRECTORIO_ORIGEN = '/var/www/html/escanear'
DIRECTORIO_SANO = '/var/www/html/sano'
DIRECTORIO_INFECCION = '/var/www/html/infectado'
today = datetime.now().strftime('%Y-%m-%d')
CSV_FILE = f'/var/www/html/CSV/scan_results_{today}.csv'

# Conexión a la base de datos

def conectar_bd():
    return mysql.connector.connect(
        host='localhost',
        user='root',
        password='momo',
        database='viruses'
    )

# Insertar datos en la base de datos "Archivos"

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
        print(f"Error al insertar en la base de datos: {err}")
    finally:
        cursor.close()
        conexion.close()

# Función para calcular el hash de un archivo, insertado en "hash value"

def calcular_hash(file_path, algoritmo='sha256'):
    hash_func = hashlib.new(algoritmo)
    with open(file_path, 'rb') as f:
        while chunk := f.read(8192):
            hash_func.update(chunk)
    return hash_func.hexdigest()

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

        if status == 'completed':
            return response
        print("Esperando a que el análisis se complete...")
        time.sleep(15) 

# Función para procesar archivos de un directorio

def procesar_archivos():

    with open(CSV_FILE, mode='a', newline='') as csvfile:  # Modo 'a' para añadir datos sin sobrescribir los que ya hay
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

                # Calcular hash del archivo
                hash_value = calcular_hash(final_path)

                # Obtener la fecha, hora y usuario actual
                date = datetime.now().strftime('%Y-%m-%d')
                time_now = datetime.now().strftime('%H:%M:%S')
                #user = os.getlogin()
                user = os.environ.get("USER") or os.environ.get("USERNAME") or "www-data"

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

                # Insertar en la base de datos
                insertar_en_bd(filename, hash_value, user, status)
                print(f'Archivo {filename} procesado y movido a {destino}. Datos registrados en la base de datos.')
            else:
                print(f'Error al subir el archivo: {filename}')

if __name__ == "__main__":
    procesar_archivos()
