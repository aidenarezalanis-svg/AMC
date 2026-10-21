<?php
$servidor = "localhost";
$usuario = "root";
$contrasena = "";
$base_datos = "app_medica_citas";

$conexion = new mysqli(
    $servidor,
    $usuario,
    $contrasena,
    $base_datos
);

if ($conexion->connect_error) {
    die("Error al conectar con la base de datos.");
}

$conexion->set_charset("utf8mb4");
?>