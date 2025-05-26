import os
import csv
import time
import requests
import hashlib
import mysql.connector
from datetime import datetime
import sys

# Configuración de variables
API_KEY = '446f02363118eb9dc67c1c250a5eaacd971f6ccf4a26b93b6a07b175bbcc4777'
BASE_DIR = '/var/www/html/archivos/'
today = datetime.now().strftime('%Y-%m-%d')
CSV_FILE = f'/var/www/html/CSV/scan_results_{today}.csv'
ARCHIVOS_PROTEGIDOS = ['login.html', 'login.php']

def conectar_bd():
    return mysql.connector.connect(
        host='localhost',
        user='root',
        password='momo',
        database='viruses'
    )

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
        print(f"Error BD: {err}")
    finally:
        cursor.close()
        conexion.close()

def subir_archivo(filepath):
    url = "https://www.virustotal.com/api/v3/files"
    headers = {"x-apikey": API_KEY}
    try:
        with open(filepath, "rb") as file:
            files = {"file": (os.path.basename(filepath), file)}
            response = requests.post(url, headers=headers, files=files)
        if response.status_code == 200:
            return response.json()
        else:
            print(f"Error al subir: {response.text}")
            return None
    except Exception as e:
        print(f"Error en subir_archivo(): {e}")
        return None

def obtener_informe(file_id):
    url = f"https://www.virustotal.com/api/v3/analyses/{file_id}"
    headers = {"x-apikey": API_KEY}
    while True:
        try:
            response = requests.get(url, headers=headers)
            if response.status_code == 200:
                data = response.json()
                status = data.get('data', {}).get('attributes', {}).get('status')
                if status == 'completed':
                    return data
                print("Esperando análisis... (15 segundos)")
                time.sleep(15)
            else:
                print(f"Error al obtener informe: {response.text}")
                return None
        except Exception as e:
            print(f"Error en obtener_informe(): {e}")
            return None

def procesar_archivo(usuario, filename):
    user_dir = os.path.join(BASE_DIR, usuario)
    filepath = os.path.join(user_dir, filename)

    if not os.path.exists(filepath):
        print(f"Archivo no encontrado: {filepath}")
        return

    # Calcular hash primero
    try:
        with open(filepath, 'rb') as f:
            hash_value = hashlib.sha256(f.read()).hexdigest()
    except Exception as e:
        print(f"Error calculando hash: {e}")
        return

    # Subir archivo
    print(f"\nAnalizando: {filename}")
    response = subir_archivo(filepath)
    if not response or 'data' not in response:
        print(f"Error en subida: {filename}")
        return

    file_id = response['data'].get('id')
    if not file_id:
        print(f"Sin ID de análisis: {filename}")
        return

    # Obtener informe
    informe = obtener_informe(file_id)
    if not informe:
        print(f"Informe no obtenido: {filename}")
        return

    stats = informe.get('data', {}).get('attributes', {}).get('stats', {})
    status = 'safe' if stats.get('malicious', 0) == 0 else 'infected'

    # Eliminar archivo si está infectado
    final_path = 'Eliminado'
    if status == 'infected':
        try:
            os.remove(filepath)
            print(f"Archivo eliminado: {filename}")
        except Exception as e:
            print(f"Error eliminando archivo: {e}")
            return
    else:
        final_path = filepath

    # Registrar en CSV
    with open(CSV_FILE, 'a', newline='') as csvfile:
        fieldnames = ['filename', 'file_id', 'status', 'malicious', 'harmless',
                      'suspicious', 'undetected', 'date', 'time', 'user', 'final_path']
        writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
        writer.writerow({
            'filename': filename,
            'file_id': file_id,
            'status': status,
            'malicious': stats.get('malicious', 0),
            'harmless': stats.get('harmless', 0),
            'suspicious': stats.get('suspicious', 0),
            'undetected': stats.get('undetected', 0),
            'date': datetime.now().strftime('%Y-%m-%d'),
            'time': datetime.now().strftime('%H:%M:%S'),
            'user': usuario,
            'final_path': final_path
        })

    # Insertar en BD
    insertar_en_bd(filename, hash_value, usuario, status)
    print(f"Registro completado: {filename} ({status})")

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Uso: python3 bueno-archivos.py <usuario> <archivo>")
        sys.exit(1)

    usuario = sys.argv[1]
    archivo = sys.argv[2]
    print("=== INICIO DE ESCANEO ===")
    procesar_archivo(usuario, archivo)
    print("=== ESCANEO FINALIZADO ===")
