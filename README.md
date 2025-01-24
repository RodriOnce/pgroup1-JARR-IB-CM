# pgroup1-JARR-IB-CM

#S.O.

- We used Ubuntu Server 22.04 (Jellyfish)


#How to get the API key (via VirusTotal)
- Go to the homepage and log in to your account.
- You access the API Key section.
- In your profile, your API key should appear (hidden).

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


****We will continue to expand the content***


////////////////////////////////////

All folders and files are created in the /var/www/html/ directory.

We add this image to show the permissions and owners of the files and folders:

![imagen](https://github.com/user-attachments/assets/eab73461-dff7-4ee1-9774-84c339392c10)


***We will continue to expand the content***
