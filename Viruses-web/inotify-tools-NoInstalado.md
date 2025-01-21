monitoreo carpeta escaneo



1. Instalar inotify-tools

Si no lo tienes instalado, instala inotify-tools, que es un conjunto de herramientas que incluye un comando llamado inotifywait para monitorear eventos en directorios y archivos.

sudo apt-get install inotify-tools

2. Crear un script para monitorear el directorio

Ahora, vamos a crear un script que use inotifywait para vigilar la carpeta /var/www/html/escanear. Cada vez que un archivo nuevo se suba o se modifique, el script ejecutará tu archivo Python.

Aquí tienes un ejemplo de cómo hacerlo:
Script monitor.sh:

#!/bin/bash

# Directorio a monitorear
DIRECTORIO="/var/www/html/escanear"

# Comando que se ejecutará cuando un archivo sea subido
COMANDO="python3 /var/www/html/bueno.py"

# Usamos inotifywait para esperar la creación de archivos
inotifywait -m -e create --format '%f' "$DIRECTORIO" | while read archivo
do
    echo "Nuevo archivo detectado: $archivo"
    
    # Ejecuta el script Python cuando se detecta un nuevo archivo
    $COMANDO
done

3. Dar permisos de ejecución al script

Asegúrate de que el script tenga permisos de ejecución para que pueda ser ejecutado:

chmod +x /ruta/al/script/monitor.sh

4. Ejecutar el script en segundo plano

Para que el script se ejecute en segundo plano, puedes ejecutarlo en una sesión de terminal o configurar un servicio systemd para que se ejecute automáticamente cuando el servidor se inicie.
Opción 1: Ejecutarlo manualmente en segundo plano

Puedes ejecutar el script manualmente en segundo plano con nohup o screen para que siga funcionando incluso si cierras la terminal:

nohup /ruta/al/script/monitor.sh &

Esto ejecutará el script y continuará corriendo después de que cierres la terminal.
Opción 2: Crear un servicio systemd (opcional)

Si quieres que este script se ejecute automáticamente cuando el servidor arranque, puedes crear un servicio systemd.

    Crea un archivo de servicio en /etc/systemd/system/monitor.service:

sudo nano /etc/systemd/system/monitor.service

    Añade lo siguiente al archivo:

[Unit]
Description=Monitor directorio y ejecutar script Python

[Service]
ExecStart=/ruta/al/script/monitor.sh
Restart=always
User=www-data
Group=www-data

[Install]
WantedBy=multi-user.target

    Recarga los servicios de systemd y habilita el servicio:

sudo systemctl daemon-reload
sudo systemctl enable monitor.service
sudo systemctl start monitor.service

Esto hará que el script se ejecute automáticamente cuando arranque el sistema.
5. Probar el sistema

Una vez que el script esté en ejecución, cada vez que se suba un archivo a la carpeta /var/www/html/escanear, se ejecutará el script Python bueno.py.
Resumen:

    Instalar inotify-tools en tu servidor.
    Crear un script Bash (monitor.sh) que utilice inotifywait para monitorear la carpeta y ejecutar el script Python cuando se suban archivos.
    Ejecutar el script manualmente o como servicio systemd para que se ejecute en segundo plano.

Esto debería permitirte ejecutar automáticamente el archivo Python cada vez que un archivo nuevo se suba a la carpeta /var/www/html/escanear.