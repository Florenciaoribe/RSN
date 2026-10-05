# Práctica 0 - PaiportArbolado

## Descripción

En esta práctica se realiza el despliegue de una aplicación web CRUD para la gestión del arbolado del Ayuntamiento de Paiporta.

La aplicación permite realizar las siguientes acciones:

- Crear nuevos árboles.
- Listar los árboles registrados.
- Editar la información de un árbol.
- Eliminar árboles.
- Buscar árboles.
- Registrar las acciones realizadas.

## Tecnologías utilizadas

- VirtualBox
- Ubuntu Server 26.04
- Apache
- PHP 8.x
- MariaDB/MySQL
- Git
- GitHub

## Arquitectura del despliegue

La aplicación se desplegará sobre una máquina virtual creada con VirtualBox.

La arquitectura utilizada será:

Cliente
↓
Apache
↓
PHP
↓
MariaDB

## 1. Creación de la máquina virtual

Se crea una máquina virtual en VirtualBox con Ubuntu Server 26.04.

Características utilizadas:

- Sistema operativo: Ubuntu Server 26.04
- Hipervisor: VirtualBox
- Memoria RAM: 4 GB
- Disco duro virtual: 25 GB
- Adaptador de red: Adaptador puente

Instalacion de apache: 
```bash
apt install apache2 -y
```


```bash
sudo apt install php libapache2-mod-php php-mysql -y
```