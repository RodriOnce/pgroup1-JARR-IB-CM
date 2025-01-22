# pgroup1-JARR-IB-CM
#S.O.
# 1. Falta poner como se sacaba la API (registro mediante VirusTotal).
- We used Ubuntu Server 22.04 (Jellyfish)

#MariaDB installation and configuration
-	Sudo apt install mariadb-server
-	Sudo apt install mariadb-client
-	Sudo systemctl start mariadb
-	Sudo systemctl enable mariadb
-	Sudo mysql_secure_installation
-	To use MariaDB:
-	Sudo mysql -u root -p
-	CREATE DATABASE name_db;
-	USE name_db;
-	*From here we create a table and enter data*

#MongoDB with Docker installation and configuration
-	In the main folder there is a document called “Docker_Mongo.pdf” with the installation of MongoDB using Docker.

#Install python and pip
-	sudo apt install python3 python3-pip python3-venv
-	python3 --version
-	pip --version

#Install Apache
-	sudo apt install apache2
-	sudo systemctl start apache2
-	sudo systemctl enable apache2
-	Root directory: /var/www/html

#Install PHP
-	sudo apt install php libapache2-mod-php php-mysql
-	php--version

