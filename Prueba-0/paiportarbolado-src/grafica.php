<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - PaiportArbolado</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <h1>Dashboard de estadísticas</h1>

    <a href="index.php">Volver al listado</a>

    <h2>Árboles por estado</h2>

	<div style="width: 850px; height: 500px; margin: 30px auto;">
    	<canvas id="graficoEstados"></canvas>
	</div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/dashboard.js"></script>

</body>
</html>
