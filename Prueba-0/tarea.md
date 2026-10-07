
# Práctica 0 - PaiportArbolado

## 1. Descripción

En esta práctica se realiza el despliegue de una aplicación web CRUD para la gestión del arbolado del Ayuntamiento de Paiporta.

La aplicación permite realizar las siguientes acciones:

- Crear nuevos árboles.
- Listar los árboles registrados.
- Editar los datos de un árbol.
- Eliminar árboles.
- Buscar árboles por especie o ubicación.
- Registrar las acciones realizadas mediante logs.

---

## 2. Tecnologías utilizadas

Para realizar el despliegue se han utilizado las siguientes tecnologías:

- VirtualBox
- Ubuntu Server 26.04
- Apache
- PHP 8.x
- MariaDB
- OpenSSL
- Chart.js

---

# Preparación de la infraestructura

## . Instalación de las tecnologías

Se instaló Apache:

```bash
sudo apt install apache2 -y
```

Se instaló PHP junto con los módulos necesarios para utilizarlo con Apache y MariaDB:

```bash
sudo apt install php libapache2-mod-php php-mysql -y
```

Se instaló MariaDB:

```bash
sudo apt install mariadb-server mariadb-client -y
```

---

## Configuración del DNS local

Para acceder a la aplicación mediante un nombre de dominio en lugar de utilizar directamente la dirección IP del servidor, se modificó el archivo `hosts` del equipo cliente.

Se añadió una entrada indicando la IP correspondiente al servidor:

![Configuración del archivo hosts](/IMG/image-5.png)

---

# Despliegue de la aplicación

## Ubicación de la aplicación

El código fuente de la aplicación se desplegó en:

```text
/var/www/arboles/paiportarbolado-src
```

Se asignó el propietario adecuado al directorio:

```bash
chown -R www-data:www-data /var/www/arboles/paiportarbolado-src
```

---

## Configuración del VirtualHost de Apache

Se creó un VirtualHost para asociar el dominio `arboles.paiporta.local` con el directorio de la aplicación.

Configuración HTTP:

```apache
<VirtualHost *:80>
    ServerName arboles.paiporta.local

    DocumentRoot /var/www/arboles/paiportarbolado-src

    <Directory /var/www/arboles/paiportarbolado-src>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/arboles_error.log
    CustomLog ${APACHE_LOG_DIR}/arboles_access.log combined
</VirtualHost>
```

---

## Creación de la base de datos

Se accedió a MariaDB como administrador:

```bash
sudo mariadb
```

Se creó la base de datos:

```sql
CREATE DATABASE PaiportArbolado;
```

Se seleccionó:

```sql
USE PaiportArbolado;
```

Se creó la tabla principal:

```sql
CREATE TABLE arboles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    especie VARCHAR(50) NOT NULL,
    ubicacion VARCHAR(100) NOT NULL,
    fecha_plantacion DATE,
    estado ENUM('sano','enfermo','talado') DEFAULT 'sano',
    usuario_registro VARCHAR(50)
);
```

---

## 8. Usuario de acceso a MariaDB

Se creó el usuario utilizado por la aplicación y se le asignaron permisos sobre la base de datos:

```sql
CREATE USER 'user_bd'@'localhost' IDENTIFIED BY 'passwdbd';

GRANT ALL PRIVILEGES
ON PaiportArbolado.*
TO 'user_bd'@'localhost';

FLUSH PRIVILEGES;
```

Se comprobó posteriormente la conexión:

```bash
mysql -u user_bd -p
```

---

# Resolución de problemas

## Error 500 al cargar la aplicación

Durante las primeras pruebas se obtuvo:

```text
500 Internal Server Error
```

Para diagnosticar el problema se consultó el log de Apache:

```bash
sudo tail -n 50 /var/log/apache2/arboles_error.log
```

El log indicaba un problema de conexión con MariaDB:

```text
Access denied for user 'user_bd'@'localhost'
```

El problema se resolvió creando correctamente el usuario `user_bd` que esperaba la aplicación y asignándole permisos sobre `PaiportArbolado`.

---

## Resolución del problema de logs vacíos

La aplicación estaba configurada para registrar las acciones realizadas en:

```text
/var/www/arboles/paiportarbolado-src/logs/actions.log
```

Al revisar el log de errores de Apache se comprobó que la carpeta `logs` no existía.

Se creó la carpeta y el archivo:

```bash
mkdir -p /var/www/arboles/paiportarbolado-src/logs
touch /var/www/arboles/paiportarbolado-src/logs/actions.log
```

Se asignaron los permisos correspondientes:

```bash
chown www-data:www-data /var/www/arboles/paiportarbolado-src/logs/actions.log
chmod 664 /var/www/arboles/paiportarbolado-src/logs/actions.log
```

Después de realizar estos cambios, las acciones comenzaron a registrarse correctamente.

---

# Mejoras de la aplicación

## 11. Subida de imágenes

Se añadió a la tabla `arboles` un campo destinado a guardar la ruta de las imágenes:

```sql
ALTER TABLE arboles
ADD imagenes VARCHAR(255) NULL;
```

Se creó el directorio donde se almacenan las imágenes:

```bash
mkdir -p /var/www/arboles/paiportarbolado-src/uploads
```

Se configuraron sus permisos para que Apache pudiera almacenar archivos:

```bash
chown -R www-data:www-data /var/www/arboles/paiportarbolado-src/uploads
chmod 755 /var/www/arboles/paiportarbolado-src/uploads
```

Se añadió un campo para seleccionar imágenes:

```html
<label>Imagen:</label>
<input type="file" name="imagenes" accept="image/*">
```

También se modificó el formulario de creación para permitir el envío de archivos:

```html
<form method="POST" enctype="multipart/form-data">
```

### Error al subir imágenes

Durante las primeras pruebas las imágenes no aparecían en la aplicación.
Uno de los problemas detectados fue que el directorio `uploads`, donde PHP debía guardar las imágenes, no existía.


---

# Autenticación de usuarios

## Creación de la tabla de usuarios

Para controlar el acceso a la aplicación se creó una tabla `users`:

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL
);
```

Se creó un usuario para realizar las pruebas de acceso:

```sql
INSERT INTO users (username, password)
VALUES ('flor', '1234');
```

---

## Sesiones PHP

Para mantener identificado al usuario después de iniciar sesión se utilizaron sesiones PHP mediante:

```php
session_start();
```

Cuando el usuario inicia sesión correctamente se almacena su nombre en la sesión:

```php
$_SESSION['usuario'] = $user['username'];
```

En las páginas protegidas se comprueba que exista una sesión activa:

```php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
```

De esta forma, si un usuario intenta acceder a una página protegida sin haber iniciado sesión, es redirigido a `login.php`.

Para cerrar la sesión se creó `logout.php`:

```php
session_start();

session_destroy();

header("Location: login.php");
exit();
```

---

# Configuración de HTTPS

## Creación del certificado SSL

Se habilitó el módulo SSL de Apache:

```bash
sudo a2enmod ssl
```

Se generó una clave privada:

```bash
openssl genrsa -out arboles.key
```

Se generó la solicitud de firma del certificado:

```bash
openssl req -new -key arboles.key -out arboles.csr
```

Se creó el certificado autofirmado:

```bash
openssl x509 -req -days 365 -in arboles.csr -signkey arboles.key -out arboles.crt
```

Posteriormente se configuró el VirtualHost del puerto `443` indicando la ubicación del certificado y de la clave privada:

```apache
<VirtualHost *:443>
    ServerName arboles.paiporta.local

    DocumentRoot /var/www/arboles/paiportarbolado-src

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/arboles.crt
    SSLCertificateKeyFile /etc/ssl/private/arboles.key
</VirtualHost>
```

---

## Problema: la página no cargaba con HTTPS

La aplicación funcionaba correctamente mediante HTTP, pero inicialmente no era accesible mediante HTTPS.

Se comprobó que generar el certificado no era suficiente.

También era necesario:
Redirigir el puerto 443 desde la VM. Una vez hecho esto ya teniamos en funcionamiento https para poder usar tranquilamente.
![alt text](/IMG/image-6.png)
Por lo que habrá que acceder de la siguiente forma: **http://arboles.paiporta.local:8443**
![alt text](/IMG/image.png)

---

# API REST

## Creación de la API de árboles

Para poder acceder a los datos de los árboles desde JavaScript se creó una API REST sencilla en PHP.
El archivo consulta los árboles almacenados en MariaDB y devuelve la información en formato JSON.

Se creó el directorio:

```bash
mkdir -p /var/www/arboles/paiportarbolado-src/api
```

Dentro del directorio se creó el archivo:

```text
api/arboles.php
```

```php
<?php

require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

$sql = "SELECT * FROM arboles";
$result = $conn->query($sql);

$arboles = [];

while ($row = $result->fetch_assoc()) {
    $arboles[] = $row;
}

echo json_encode($arboles, JSON_UNESCAPED_UNICODE);

$conn->close();
?>
```

La API se puede consultar desde:

```text
arboles.paiporta.local:8443/api/arboles.php
```

Al acceder a esta dirección se muestran los datos de los árboles en formato JSON.

---

## Consumo de la API con JavaScript

Para consumir los datos devueltos por la API se utilizó la función `fetch()` desde JavaScript.

En `js/script.js` se añadió:

```javascript
fetch('api/arboles.php')
    .then(response => response.json())
    .then(arboles => {
        console.log('Árboles obtenidos desde la API:');
        console.log(arboles);
    })
    .catch(error => {
        console.error('Error al consultar la API:', error);
    });
```

De esta forma, JavaScript realiza una petición a `arboles.php`, recibe los datos en formato JSON y puede utilizarlos posteriormente.

El funcionamiento es:

```text
MariaDB
   ↓
api/arboles.php
   ↓
JSON
   ↓
fetch()
   ↓
JavaScript
```

---

# Estadísticas con Chart.js

## Gráfico de árboles por estado

Para visualizar estadísticas de los árboles registrados se añadió una página de estadísticas utilizando la librería Chart.js.

La librería se carga mediante:

```html
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
```

El gráfico utiliza los datos obtenidos desde la API REST:

```text
/api/arboles.php
```

Los árboles se agrupan según su estado:

- Sanos
- Enfermos
- Talados

Los datos se obtienen mediante `fetch()` y se representan utilizando un gráfico de barras.

---


