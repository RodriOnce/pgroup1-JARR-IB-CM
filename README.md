# **pgroup1-JARR-IB-CM**

## **Operating System (OS)**

We used **Ubuntu Server 22.04 (Jellyfish)**.

---

## **How to Get the API Key (via VirusTotal)**

1. Go to the VirusTotal homepage and log in to your account.
2. Access the **API Key** section.
3. In your profile, your API key should appear (hidden).

---

## **MariaDB Installation and Configuration**

1. Install MariaDB server and client:
   ```bash
   sudo apt install mariadb-server
   sudo apt install mariadb-client
   ```
2. Start the MariaDB service:
   ```bash
   sudo systemctl start mariadb
   sudo systemctl enable mariadb
   ```
3. Secure the installation:
   ```bash
   sudo mysql_secure_installation
   ```
4. Access MariaDB:
   ```bash
   sudo mysql -u root -p
   ```
5. Create a database:
   ```sql
   CREATE DATABASE name_db;
   USE name_db;
   ```
6. Select the Database (Use the following command to select the 'empresa' database).
   ```sql
   USE empresa;
   ```
   ```
   USE viruses;
   ```
7. Check the Table Structure (to verify the structure of the 'empleados' table):
   ```sql
   DESCRIBE empleados;
   ```
   ```
   DESCRIBE archivos;
   ```
8. Insert Data into the Table
   ```sql
      # Tabla `empleados`
   CREATE TABLE empleados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    user VARCHAR(50) NOT NULL UNIQUE,
    pass CHAR(64) NOT NULL,  -- SHA2(256) devuelve 64 caracteres hexadecimales
    dpt VARCHAR(50) NOT NULL,
    mail VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('activo', 'pendiente', 'inactivo') NOT NULL
    );
   ```

   ```sql
   # Tabla `shared`
   CREATE TABLE shared (
    id_shared INT AUTO_INCREMENT PRIMARY KEY,
    file_src VARCHAR(255) NOT NULL,
    user_src VARCHAR(255) NOT NULL,
    dpt_src VARCHAR(255),
    user_dst VARCHAR(255) NOT NULL,
    dpt_dst VARCHAR(255),
    share_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()
   );
   ```

   ```sql
      # Tabla `archivos`
   CREATE TABLE archivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    hash VARCHAR(255) NOT NULL,
    scan_date DATE NOT NULL,
    scan_user VARCHAR(100) NOT NULL,
    scan_state VARCHAR(50) NOT NULL,
    download_count INT DEFAULT 0
   );
   ```

   ```sql
   INSERT INTO empleados (name, user, pass, dpt, mail, status) VALUES
   ('Alice', 'alice123', SHA2('securepassword', 256), 'HR', 'alice@empresa.com', 'activo'),
   ('Bob', 'bobm', SHA2('mypassword', 256), 'IT', 'bob@empresa.com', 'pendiente'),
   ('Charlie', 'charlie01', SHA2('testpass', 256), 'ADM', 'charlie@empresa.com', 'inactivo');
   ```
   ```sql
   INSERT INTO archivos (filename, hash, scan_date, scan_user, scan_state) VALUES
   ('document.pdf', 'a5d3f4b6789c...', '2025-03-08', 'scanner01', 'clean'),
   ('malware.exe', 'b7e6a1c9d2f8...', '2025-03-08', 'scanner02', 'infected'),
   ('report.docx', 'c9f8e7d6a5b4...', '2025-03-08', 'scanner03', 'clean');
   ```
10. Verify the Insrted Data
    ```sql
    SELECT * FROM empleados;
    ```
    ```
    SELECT * FROM archivos;
    ```
11. Updating an Employee Record
    ```sql
    UPDATE empleados SET status = 'activo' WHERE user = 'bobm';
    ```
    ```
    UPDATE archivos SET scan_state = 'quarantined' WHERE filename = `malware.exe`
    ```
12. Deleting an Employee Record
    ```sql
    DELETE FROM empleados WHERE user = 'charlie01';
    ```
    ```
    DELETE FROM archivos WHERE filename = 'report.docx'
    ```
13. Exiting MariaDB
    ```sql
    EXIT;
    ```
## **MongoDB Installation and Configuration with Docker**

For MongoDB installation using Docker, refer to the document `Docker_Mongo.pdf` located in the main folder.

---

## **Install Python and Pip**

1. Install Python, Pip, and the Python virtual environment:
   ```bash
   sudo apt install python3 python3-pip python3-venv
   ```
2. Check installed versions:
   ```bash
   python3 --version
   pip --version
   ```

---

## **Install Apache**

1. Install Apache web server:
   ```bash
   sudo apt install apache2
   ```
2. Start and enable Apache:
   ```bash
   sudo systemctl start apache2
   sudo systemctl enable apache2
   ```
3. **Root Directory:**
   - All files and folders are located in: `/var/www/html`

---

## **Install PHP**

1. Install PHP and necessary modules:
   ```bash
   sudo apt install php libapache2-mod-php php-mysql
   ```
2. Verify the PHP installation:
   ```bash
   php --version
   ```

---

## **Permissions and Owners**

All files and folders are located in the `/var/www/html/` directory. Below is an example image showing the permissions and ownership setup:

![imagen](https://github.com/user-attachments/assets/ab9a96ae-97af-4f7b-9b5f-4b13292353d5)


---

## **Notes**

- This document will continue to be updated and expanded with additional content and instructions.



__________________________________________________


# TrackZero &mdash; README de Seguridad  
**Última actualización:** 22 may 2025  

---

## 1. Resumen de mejoras

| Capa | Implementación | Riesgo mitigado |
|------|---------------|-----------------|
| **Contraseñas** | `password_hash()` + `password_verify()` (bcrypt) | Descifrado de hashes, rainbow-tables |
| **SQL** | PDO + *prepared statements* | Inyección SQL |
| **CSRF** | Token de 32 bytes (`csrf_token.php`) + fetch en `login.html` | Cross-Site Request Forgery |
| **Sesiones** | Cookies `HttpOnly`, `SameSite=Strict`, `secure` listo para HTTPS &nbsp;+&nbsp;`session_regenerate_id()` | Robo / fijación de sesión |
| **Cabeceras** | `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, CSP (↓) | Click-jacking, MIME sniffing, bloqueo XSS inline |
| **Errores** | Mensaje genérico (“Credenciales incorrectas”) | Enumeración de usuarios |
| **Fuerza bruta** | `sleep(1)` tras fallo (ampliable a contador/IP) | Bruteforce masivo |
| **Subidas públicas** | `move_uploaded_file()` → `/escanear/` (`750`), nombres saneados | Listado / ejecución arbitraria |
| **Archivos usuario** | `/archivos/<uid>/` auto-creado + `Options -Indexes` | Descarga directa / listados |
| **Cache busting** | `Cache-Control: no-store` solo en `login.html` | Token CSRF vacío por HTML cacheado |

---

## 2. Rutas y ficheros clave

| Ruta | Propósito |
|------|-----------|
| `/var/www/html/login.html` | Formulario + fetch de token |
| `/var/www/html/csrf_token.php` | Devuelve `{"token":"…"}` |
| `/var/www/html/login.php` | Valida usuario, crea carpeta `<uid>` |
| `/var/www/html/registro.php` | Alta segura de usuarios |
| `/var/www/html/archivos/` | Almacén de ficheros por usuario |
| `/var/www/html/escanear/` | Subidas anónimas analizadas por `bueno.py` |
| `/var/www/html/.htaccess` | Cabeceras globales + CSP + no-cache login |
| `/var/www/html/archivos/.htaccess` | `Options -Indexes` |

---

## 3. CSP aplicada

```text
default-src 'self';
style-src  'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net;
script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;
font-src   'self' https://fonts.gstatic.com https://cdn.jsdelivr.net;
img-src    'self' data: https:;
frame-ancestors 'none';
