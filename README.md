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
7. Check the Table Structure (to verify the structure of the 'empleados' table):
   ```sql
   DESCRIBE empleados;
   ```
8. Insert Data into the Table
   ```sql
   INSERT INTO empleados (name, user, pass, dpt, mail, status) VALUES
   ('Alice', 'alice123', SHA2('securepassword', 256), 'HR', 'alice@empresa.com', 'activo'),
   ('Bob', 'bobm', SHA2('mypassword', 256), 'IT', 'bob@empresa.com', 'pendiente'),
   ('Charlie', 'charlie01', SHA2('testpass', 256), 'ADM', 'charlie@empresa.com', 'inactivo');
   ```
9. Verify the Insrted Data
    ```sql
    SELECT * FROM empleados;
    ```
10. Updating an Employee Record
    ```sql
    UPDATE empleados SET status = 'activo' WHERE user = 'bobm';
    ```
11. Deleting an Employee Record
    ```sql
    DELETE FROM empleados WHERE user = 'charlie01';
    ```
12. Exiting MariaDB
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

![File and Folder Permissions](https://github.com/user-attachments/assets/eab73461-dff7-4ee1-9774-84c339392c10)

---

## **Notes**

- This document will continue to be updated and expanded with additional content and instructions.

