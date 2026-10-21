<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$nombre = $_SESSION["nombre"];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Medica | Inicio</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>

<div class="contenedor">
    <div class="encabezado">
        <h1>✚ App Medica</h1>
        <p>Tu espacio de salud</p>
    </div>

    <h2>¡Bienvenido, <?php
        echo htmlspecialchars($nombre, ENT_QUOTES, "UTF-8");
    ?>!</h2>

    <p>Has iniciado sesión correctamente.</p>

    <a href="cerrar_sesion.php" class="boton-secundario">
        Cerrar sesión
    </a>
</div>

</body>
</html>