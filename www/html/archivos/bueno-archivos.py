import os
import csv
import time
import requests
import hashlib
import mysql.connector
from datetime import datetime
import sys

# Configuración
API_KEY = '446f02363118eb9dc67c1c250a5eaacd971f6ccf4a26b93b6a07b175bbcc4777'
BASE_DIR = '/var/www/html/archivos/'
CSV_FILE = f'/var/www/html/CSV/scan_results_{datetime.now().strftime("%Y-%m-%d")}.csv'

# Conectar a la base de datos
def conectar_bd():
    """Establece y devuelve una conexión a la base de datos MySQL."""
    try:
        return mysql.connector.connect(
            host='localhost',
            user='root',
            password='momo',
            database='viruses'
        )
    except mysql.connector.Error as err:
        print(f"Error al conectar a la base de datos: {err}")
        sys.exit(1) # Salir si no se puede conectar a la BD

# Consultar hash en BD local
def consultar_hash_bd(hash_value):
    """
    Consulta un valor hash en la base de datos local y devuelve el estado de escaneo.
    Asegura que el cursor y la conexión se cierren correctamente.
    """
    conexion = None
    cursor = None
    try:
        conexion = conectar_bd()
        cursor = conexion.cursor()
        cursor.execute("SELECT scan_state FROM archivos WHERE hash = %s", (hash_value,))
        resultado = cursor.fetchone()

        # Importante: Asegurarse de que el cursor haya procesado todos los resultados
        # Esto previene el error "Unread result found"
        for _ in cursor:
            pass # Consumir cualquier resultado pendiente

        return resultado[0] if resultado else None
    except mysql.connector.Error as err:
        print(f"Error al consultar el hash en la BD: {err}")
        return None
    finally:
        if cursor:
            cursor.close()
        if conexion:
            conexion.close()

# Insertar resultados en BD
def insertar_en_bd(filename, hash_value, scan_user, scan_state):
    """Inserta los resultados del escaneo en la base de datos."""
    conexion = None
    cursor = None
    try:
        conexion = conectar_bd()
        cursor = conexion.cursor()
        cursor.execute("""
            INSERT INTO archivos (filename, hash, scan_date, scan_user, scan_state)
            VALUES (%s, %s, CURDATE(), %s, %s)
        """, (filename, hash_value, scan_user, scan_state))
        conexion.commit() # Confirma los cambios
    except mysql.connector.Error as err:
        print(f"Error al insertar en la BD: {err}")
    finally:
        # Asegura que el cursor y la conexión se cierren siempre
        if cursor:
            cursor.close()
        if conexion:
            conexion.close()

# Subir archivo a VirusTotal
def subir_archivo(filepath):
    """Sube un archivo a VirusTotal y devuelve el ID de escaneo."""
    headers = {"x-apikey": API_KEY}
    try:
        with open(filepath, "rb") as file:
            files = {"file": file}
            response = requests.post("https://www.virustotal.com/api/v3/files", headers=headers, files=files)
        response.raise_for_status() # Lanza excepción si la solicitud no fue exitosa
        return response.json()['data']['id']
    except requests.exceptions.RequestException as e:
        print(f"Error al subir archivo a VirusTotal: {e}")
        return None
    except (KeyError, ValueError): # Captura si 'data' o 'id' no existen o si no es JSON válido
        print(f"Error: La respuesta de VirusTotal no es válida o no contiene 'data' o 'id'.")
        return None

# Obtener resultado por hash de VirusTotal (o ID de análisis)
def obtener_resultado_hash(hash_or_id):
    """Obtiene el resultado del análisis de un hash o ID de VirusTotal."""
    headers = {"x-apikey": API_KEY}
    try:
        response = requests.get(f"https://www.virustotal.com/api/v3/files/{hash_or_id}", headers=headers)
        response.raise_for_status()
        data = response.json()
        malicious = data['data']['attributes']['last_analysis_stats']['malicious']
        return 'infected' if malicious > 0 else 'safe'
    except requests.exceptions.RequestException as e:
        print(f"Error al obtener resultado de VirusTotal: {e}")
        return None
    except (KeyError, ValueError):
        print(f"Error: La respuesta de VirusTotal no contiene los atributos esperados o no es JSON válido.")
        return None

# Registrar en CSV
def registrar_csv(data):
    """Registra los datos del escaneo en un archivo CSV."""
    existe_archivo = os.path.isfile(CSV_FILE)
    try:
        with open(CSV_FILE, 'a', newline='', encoding='utf-8') as csvfile:
            writer = csv.DictWriter(csvfile, fieldnames=data.keys())
            if not existe_archivo:
                writer.writeheader()
            writer.writerow(data)
    except IOError as e:
        print(f"Error al escribir en el archivo CSV: {e}")

# Procesar archivo
def procesar_archivo(usuario, filename):
    """
    Procesa un archivo: calcula su hash, consulta la BD local y VirusTotal,
    y registra los resultados.
    """
    filepath = os.path.join(BASE_DIR, usuario, filename)

    if not os.path.exists(filepath):
        print(f"Archivo no encontrado: {filepath}")
        return

    try:
        with open(filepath, 'rb') as f:
            hash_value = hashlib.sha256(f.read()).hexdigest()
    except IOError as e:
        print(f"Error al leer el archivo {filepath}: {e}")
        return

    estado_bd = consultar_hash_bd(hash_value)
    if estado_bd:
        print(f"Resultado obtenido de BD local: {filename} ({estado_bd})")
        if estado_bd == 'infected':
            print(f"Archivo previamente marcado como infectado y eliminado: {filename}")
        return

    print(f"Consultando VirusTotal para {filename}...")
    estado_vt = obtener_resultado_hash(hash_value) # Intentamos obtener resultado por hash primero

    if not estado_vt:
        print("Archivo nuevo para VirusTotal, subiendo para análisis...")
        scan_id = subir_archivo(filepath)
        if not scan_id:
            print(f"Fallo al subir archivo: {filename}")
            return
        print("Esperando análisis inicial...")
        time.sleep(20) # Espera 20 segundos para que VirusTotal procese el archivo
        estado_vt = obtener_resultado_hash(scan_id) # Ahora usamos el scan_id para obtener el resultado
        if not estado_vt:
            print(f"Análisis pendiente todavía para {filename}, intenta más tarde.")
            return

    if estado_vt == 'infected':
        try:
            os.remove(filepath)
            final_path = 'Eliminado'
            print(f"Archivo infectado eliminado: {filename}")
        except OSError as e:
            print(f"Error al intentar eliminar el archivo {filepath}: {e}. Moverlo manualmente si es necesario.")
            final_path = 'Error al eliminar'
    else:
        final_path = filepath
        print(f"Archivo seguro: {filename}")

    insertar_en_bd(filename, hash_value, usuario, estado_vt)

    registrar_csv({
        'filename': filename,
        'hash': hash_value,
        'status': estado_vt,
        'date': datetime.now().strftime('%Y-%m-%d'),
        'time': datetime.now().strftime('%H:%M:%S'),
        'user': usuario,
        'final_path': final_path
    })

    print(f"Procesado completado: {filename} ({estado_vt})")

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Uso: python3 bueno-archivos.py <usuario> <archivo_o_ruta_relativa>")
        sys.exit(1)

    procesar_archivo(sys.argv[1], sys.argv[2])
