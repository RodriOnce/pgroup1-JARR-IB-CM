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
    return mysql.connector.connect(
        host='localhost',
        user='root',
        password='momo',
        database='viruses'
    )

# Consultar hash en BD local
def consultar_hash_bd(hash_value):
    conexion = conectar_bd()
    cursor = conexion.cursor()
    cursor.execute("SELECT scan_state FROM archivos WHERE hash = %s", (hash_value,))
    resultado = cursor.fetchone()
    cursor.close()
    conexion.close()
    return resultado[0] if resultado else None

# Insertar resultados en BD
def insertar_en_bd(filename, hash_value, scan_user, scan_state):
    conexion = conectar_bd()
    cursor = conexion.cursor()
    cursor.execute("""
        INSERT INTO archivos (filename, hash, scan_date, scan_user, scan_state)
        VALUES (%s, %s, CURDATE(), %s, %s)
    """, (filename, hash_value, scan_user, scan_state))
    conexion.commit()
    cursor.close()
    conexion.close()

# Subir archivo a VirusTotal
def subir_archivo(filepath):
    headers = {"x-apikey": API_KEY}
    with open(filepath, "rb") as file:
        files = {"file": file}
        response = requests.post("https://www.virustotal.com/api/v3/files", headers=headers, files=files)
    return response.json()['data']['id'] if response.ok else None

# Obtener resultado por hash de VirusTotal
def obtener_resultado_hash(hash_value):
    headers = {"x-apikey": API_KEY}
    response = requests.get(f"https://www.virustotal.com/api/v3/files/{hash_value}", headers=headers)
    if response.status_code == 200:
        data = response.json()
        malicious = data['data']['attributes']['last_analysis_stats']['malicious']
        return 'infected' if malicious > 0 else 'safe'
    return None

# Registrar en CSV
def registrar_csv(data):
    existe_archivo = os.path.isfile(CSV_FILE)
    with open(CSV_FILE, 'a', newline='') as csvfile:
        writer = csv.DictWriter(csvfile, fieldnames=data.keys())
        if not existe_archivo:
            writer.writeheader()
        writer.writerow(data)

# Procesar archivo
def procesar_archivo(usuario, filename):
    filepath = os.path.join(BASE_DIR, usuario, filename)

    if not os.path.exists(filepath):
        print(f"Archivo no encontrado: {filepath}")
        return

    with open(filepath, 'rb') as f:
        hash_value = hashlib.sha256(f.read()).hexdigest()

    estado_bd = consultar_hash_bd(hash_value)
    if estado_bd:
        print(f"Resultado obtenido de BD local: {filename} ({estado_bd})")
        return

    print(f"Consultando VirusTotal para {filename}...")
    estado_vt = obtener_resultado_hash(hash_value)

    if not estado_vt:
        print("Archivo nuevo para VirusTotal, subiendo para análisis...")
        scan_id = subir_archivo(filepath)
        if not scan_id:
            print(f"Fallo al subir archivo: {filename}")
            return
        print("Esperando análisis inicial...")
        time.sleep(20)
        estado_vt = obtener_resultado_hash(hash_value)
        if not estado_vt:
            print(f"Análisis pendiente todavía para {filename}, intenta más tarde.")
            return

    if estado_vt == 'infected':
        os.remove(filepath)
        final_path = 'Eliminado'
        print(f"Archivo infectado eliminado: {filename}")
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
        print("Uso: python3 optimizado.py <usuario> <archivo>")
        sys.exit(1)

    procesar_archivo(sys.argv[1], sys.argv[2])
