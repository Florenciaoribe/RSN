
<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}


require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $especie = $conn->real_escape_string($_POST['especie']);
    $ubicacion = $conn->real_escape_string($_POST['ubicacion']);
    $fecha = $_POST['fecha_plantacion'];
    $usuario = $conn->real_escape_string($_POST['usuario']);

    // Por defecto, el árbol no tiene imagen
    $imagenes = null;

    // Comprobar si se ha seleccionado una imagen
    if (
        isset($_FILES['imagenes']) &&
        $_FILES['imagenes']['error'] === UPLOAD_ERR_OK
    ) {

        // Nombre original del archivo
        $nombreOriginal = basename($_FILES['imagenes']['name']);

        // Añadimos un identificador para evitar nombres duplicados
        $nombreArchivo = uniqid() . "_" . $nombreOriginal;

        // Ruta física donde PHP guardará la imagen
        $rutaFisica = __DIR__ . "/uploads/" . $nombreArchivo;

        // Ruta que guardaremos en MariaDB
        $rutaBD = "uploads/" . $nombreArchivo;

        if (move_uploaded_file(
            $_FILES['imagenes']['tmp_name'],
            $rutaFisica
        )) {
            $imagenes = $conn->real_escape_string($rutaBD);
        } else {
            die("Error al guardar la imagen en la carpeta uploads.");
        }
    }

    $sql = "INSERT INTO arboles (
                especie,
                ubicacion,
                fecha_plantacion,
                usuario_registro,
                imagenes
            )
            VALUES (
                '$especie',
                '$ubicacion',
                '$fecha',
                '$usuario',
                " . ($imagenes !== null ? "'$imagenes'" : "NULL") . "
            )";

    if ($conn->query($sql)) {

        registerAction(
            "Tree added $especie in $ubicacion",
            $usuario
        );

        header("Location: index.php");
        exit();

    } else {
        die("Error SQL: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>PaiportArbolado: Añadir Árbol</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <h1>Añadir Nuevo Árbol</h1>

    <form method="POST" enctype="multipart/form-data">

        <label>Especie:</label>
        <input type="text" name="especie" required>
        <br>

        <label>Ubicación:</label>
        <input type="text" name="ubicacion" required>
        <br>

        <label>Fecha de Plantación:</label>
        <input type="date" name="fecha_plantacion" required>
        <br>

        <label>Usuario:</label>
        <input type="text" name="usuario" required>
        <br>

        <label>Imagen:</label>
        <input type="file" name="imagenes" accept="image/*">
        <br>

        <button type="submit">Guardar</button>

    </form>

    <a href="index.php">Volver a la lista</a>

</body>

</html>

