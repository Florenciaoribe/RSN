




<?php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']);

    $sql = "SELECT * FROM users
            WHERE username = '$username'
            AND password = '$password'";

    $result = $conn->query($sql);

    if ($result && $result->num_rows === 1) {

        $user = $result->fetch_assoc();

        $_SESSION['usuario'] = $user['username'];

        header("Location: index.php");
        exit();

    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - PaiportArbolado</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <h1>Iniciar sesión</h1>

    <?php if (!empty($error)): ?>
        <p><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">

        <label>Usuario:</label>
        <input type="text" name="username" required>

        <br>

        <label>Contraseña:</label>
        <input type="password" name="password" required>

        <br>

        <button type="submit">Entrar</button>

    </form>

</body>
</html>
