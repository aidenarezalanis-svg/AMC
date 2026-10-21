<?php
session_start();
require "conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

$nombre = trim($_POST["nombre"] ?? "");
$contrasena = $_POST["contrasena"] ?? "";

if ($nombre === "" || $contrasena === "") {
    header("Location: login.php?error=2");
    exit;
}

$sql = "SELECT id_paciente, nombre, contrasena
        FROM pacientes
        WHERE nombre = ?
        LIMIT 1";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $nombre);
$stmt->execute();

$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

// Se cambia password_verify por una comparación directa en texto plano
if (
    $usuario &&
    $contrasena === $usuario["contrasena"]
) {
    session_regenerate_id(true);

    $_SESSION["usuario_id"] = $usuario["id_usuario"];
    $_SESSION["nombre"] = $usuario["nombre"];

    header("Location: dashboard.php");
    exit;
}

header("Location: login.php?error=1");
exit;
?>