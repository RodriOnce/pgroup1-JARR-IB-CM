Las carpetas estan en /var/www/html
son: escanear, CSV, sano, infectado

# Cambiar el propietario del directorio a www-data (usuario del servidor web)
sudo chown -R www-data:www-data /var/www/html/escanear/

# Asegurarte de que el directorio tenga permisos de escritura para www-data
sudo chmod 755 /var/www/html/escanear/

# Si el problema persiste, intenta dar permisos más amplios solo para pruebas
sudo chmod 777 /var/www/html/escanear/

# Asegúrate de que tenga permisos 1777, lo que permite la escritura de cualquier usuario en el directorio temporal. 
# Primero se guardan aqui los archivos despues ee mueven a la carpeta
sudo chmod 1777 /tmp