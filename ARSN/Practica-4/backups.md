# Actividad 5B: AWS Backup

## 1. Objetivo

El objetivo de esta práctica es utilizar **AWS Backup** para realizar una copia de seguridad de un volumen EBS y restaurarlo posteriormente.

Para comprobar que la recuperación funciona, crearemos un archivo, realizaremos una copia, modificaremos su contenido y restauraremos la versión anterior.

## 2. Creación del volumen EBS

Primero utilizamos una instancia EC2 existente llamada `Front-server-EFS`.

Desde **AWS → EC2 → Volúmenes → Crear volumen**, configuramos:

| Parámetro | Configuración |
|---|---|
| Nombre | Backup |
| Tipo | gp3 |
| Tamaño | 2 GiB |
| Zona de disponibilidad | us-east-1c |
| Instantánea | Ninguna |

El volumen creado fue:

`vol-0fe185c1bac14a83f`

Después seleccionamos **Acciones → Asociar volumen** y lo conectamos a la instancia `Front-server-EFS` utilizando `/dev/sdd`, ya que otros nombres estaban ocupados.


## 3. Preparación del volumen en Linux

Accedimos por SSH a la instancia y comprobamos los discos:

```bash
lsblk -o NAME,SIZE,FSTYPE,MOUNTPOINTS
```

Identificamos:

- `/dev/nvme0n1`: disco principal de Ubuntu.
- `/dev/nvme1n1`: nuevo volumen EBS de 2 GiB.

Comprobamos que el volumen nuevo estaba vacío:

```bash
sudo file -s /dev/nvme1n1
```

Lo formateamos en ext4:

```bash
sudo mkfs -t ext4 /dev/nvme1n1
```

Creamos una carpeta y montamos el volumen:

```bash
sudo mkdir -p /mnt/datos
sudo mount /dev/nvme1n1 /mnt/datos
```

Comprobamos el montaje:

```bash
df -h /mnt/datos
```

**Importante:** el formateo solo debe realizarse sobre un volumen nuevo y vacío, nunca sobre el disco principal ni sobre un volumen con datos.

## 4. Creación del archivo de prueba

Creamos un archivo dentro del volumen EBS:

```bash
echo "Version al crear el backup" | sudo tee /mnt/datos/prueba-aws-backup.txt
sudo sync
```

Comprobamos su contenido:

```bash
cat /mnt/datos/prueba-aws-backup.txt
```

Resultado:

```text
Version al crear el backup
```

Este archivo nos permitirá comprobar posteriormente la recuperación de los datos.


## 5. Creación del Backup Vault

Entramos en **AWS Backup → Almacenes → Crear almacén de copia de seguridad**.

Configuramos:

| Parámetro | Valor |
|---|---|
| Nombre | actividad-5b |
| Tipo | Almacén de copia de seguridad |
| Clave KMS | Predeterminada del laboratorio |
| Vault Lock | Desactivado |

Un **Backup Vault** es el almacén donde AWS Backup guarda los puntos de recuperación.



## 6. Creación del plan de backup

Desde **AWS Backup → Planes de Backup → Crear plan**, creamos el plan:

`plan-actividad-5b`

Configuramos la regla:

| Parámetro | Valor |
|---|---|
| Nombre | regla-actividad-5b |
| Almacén | actividad-5b |
| Frecuencia | Diariamente |
| Retención | 1 día |
| Almacenamiento en frío | Desactivado |
| Almacén aislado lógicamente | Sin seleccionar |

AWS mostró una advertencia recomendando una retención de al menos 7 días para evitar determinados cargos adicionales. Para esta práctica utilizamos 1 día.

### Asignación del recurso

Desde **Asignar recursos**, configuramos:

| Parámetro | Valor |
|---|---|
| Nombre | asignacion-actividad-5b |
| Tipo de recurso | EBS |
| Volumen | vol-0fe185c1bac14a83f |
| Rol IAM | LabRole |

Inicialmente apareció un error `iam:CreateRole`, porque habíamos seleccionado **Use default role**.

Lo solucionamos seleccionando **Usar otro rol → LabRole**, que ya estaba creado en AWS Academy.

No fue necesario crear nuevos roles IAM ni modificar permisos.


## 7. Realización de la copia de seguridad

Accedimos a:

**AWS Backup → Recursos protegidos → Crear copia de seguridad bajo demanda**

Configuramos:

| Parámetro | Valor |
|---|---|
| Tipo de recurso | EBS |
| Volumen | vol-0fe185c1bac14a83f |
| Inicio | Crear copia ahora |
| Almacén | actividad-5b |
| Retención | 1 día |
| Rol IAM | LabRole |
| Índice de backup | Desactivado |
| Almacenamiento en frío | Desactivado |

Iniciamos la copia y comprobamos su estado desde:

**AWS Backup → Trabajos → Trabajos de copia de seguridad**

El trabajo pasó de **En ejecución** a **Completado**.

Después comprobamos que existía un punto de recuperación dentro del almacén `actividad-5b`.


## 8. Modificación del archivo original

Una vez completada la copia, modificamos el archivo para simular un cambio posterior al backup:

```bash
echo "Version posterior al backup" | sudo tee /mnt/datos/prueba-aws-backup.txt
sudo sync
```

Comprobamos el contenido:

```bash
cat /mnt/datos/prueba-aws-backup.txt
```

Resultado:

```text
Version posterior al backup
```

De esta forma, el volumen original contiene información diferente a la que existía cuando realizamos la copia.

## 9. Restauración del volumen EBS

Accedimos a:

**AWS Backup → Almacenes → actividad-5b → Puntos de recuperación**

Seleccionamos el punto de recuperación y pulsamos **Restaurar**.

Configuramos:

| Parámetro | Valor |
|---|---|
| Tipo de recurso | Volumen de EBS |
| Tipo de volumen | gp3 |
| Tamaño | 2 GiB |
| Zona de disponibilidad | us-east-1c |
| Rol de restauración | LabRole |

La restauración creó un volumen EBS nuevo, sin sobrescribir el original.

| Volumen | ID |
|---|---|
| Original | vol-0fe185c1bac14a83f |
| Restaurado | vol-016cfcecbc59fbdab |

Desde **EC2 → Volúmenes**, seleccionamos el volumen restaurado y lo asociamos a la instancia `Front-server-EFS`.


## 10. Comprobación de la restauración

Entramos nuevamente por SSH en la instancia y comprobamos los discos:

```bash
lsblk -o NAME,SIZE,FSTYPE,MOUNTPOINTS
```

Obtuvimos:

| Dispositivo | Función |
|---|---|
| /dev/nvme0n1 | Ubuntu |
| /dev/nvme1n1 | Volumen original |
| /dev/nvme2n1 | Volumen restaurado |

Creamos una carpeta para montar el volumen restaurado:

```bash
sudo mkdir -p /mnt/recuperacion
```

Lo montamos en modo solo lectura:

```bash
sudo mount -o ro,noload /dev/nvme2n1 /mnt/recuperacion
```

El modo solo lectura permite comprobar la información recuperada sin modificar el volumen.

### Comparación de los archivos

Comprobamos el archivo original:

```bash
cat /mnt/datos/prueba-aws-backup.txt
```

Resultado:

```text
Version posterior al backup
```

Comprobamos el archivo restaurado:

```bash
cat /mnt/recuperacion/prueba-aws-backup.txt
```

Resultado:

```text
Version al crear el backup
```

**La restauración se realizó correctamente**, ya que recuperamos el contenido que tenía el archivo antes de modificarlo.


## 11. Limpieza de recursos

Una vez verificada la restauración, debemos limpiar los recursos temporales autorizados.

Primero desmontamos el volumen restaurado:

```bash
sudo umount /mnt/recuperacion
```

Si el volumen original también deja de utilizarse:

```bash
sudo umount /mnt/datos
```

Después, desde AWS:

1. Ir a **EC2 → Volúmenes** y desconectar los volúmenes temporales.
2. Eliminar únicamente los volúmenes de prueba que no necesitemos.
3. Ir a **AWS Backup → Planes de Backup**, retirar la asignación y eliminar el plan de esta actividad.
4. Eliminar el punto de recuperación cuando esté permitido.
5. Eliminar el almacén `actividad-5b` únicamente cuando esté vacío y ya no sea necesario.

No debemos eliminar la instancia `Front-server-EFS`, la VPC, las subredes ni los recursos de otras prácticas.

**Esta limpieza queda pendiente de comprobar.**

## 12. Conclusión

En esta práctica hemos aprendido a utilizar AWS Backup para proteger y recuperar un volumen EBS.

Primero creamos un archivo de prueba, realizamos una copia de seguridad y modificamos su contenido.

Después restauramos el punto de recuperación como un volumen nuevo y comprobamos que conservaba la versión anterior del archivo.

Esto demuestra que **AWS Backup permite recuperar información almacenada en un volumen EBS después de que los datos originales hayan cambiado**.

La recuperación completa de WordPress, indicada como aplicación adicional en el enunciado, no se ha realizado porque requiere autorización del docente.