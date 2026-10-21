<?php
require "conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: registro.php");
    exit;
}

$nombre = trim($_POST["nombre"] ?? "");
$apellido = trim($_POST["apellido"] ?? "");
$fecha_nacimiento = $_POST["fecha_nacimiento"] ?? "";
$telefono = trim($_POST["telefono"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$fecha_registro = trim($_POST["correo"] ?? "");
$contrasena = $_POST["contrasena"] ?? "";
$confirmar = $_POST["confirmar"] ?? "";

// Validaciones básicas
if (
    $nombre === "" ||
    $apellido === "" ||
    $fecha_nacimiento === "" ||
    $estado === "" ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
    strlen($nombre) > 60 ||
    strlen($apellido) > 60 ||
    strlen($estado) > 60 ||
    strlen($correo) > 120 ||
    strlen($telefono) > 20 ||
    $telefono === "" ||
    strlen($contrasena) < 8
) {
    header("Location: registro.php?error=datos");
    exit;
}

if ($contrasena !== $confirmar) {
    header("Location: registro.php?error=contrasenas");
    exit;
}

// Iniciamos una transacción para asegurar que ambas inserciones ocurran juntas
$conexion->begin_transaction();

try {
    // PASO 1: Insertar en la tabla 'usuarios'
    $sql_usuario = "INSERT INTO usuarios (nombre, contrasena) VALUES (?, ?)";
    $stmt_usuario = $conexion->prepare($sql_usuario);
    $stmt_usuario->bind_param("ss", $nombre, $contrasena);
    $stmt_usuario->execute();
    
    $id_usuario = $conexion->insert_id;
    $stmt_usuario->close();

    // PASO 2: Insertar en la tabla 'pacientes' en el orden estricto de tu tabla
    $sql_paciente = "INSERT INTO pacientes 
        (nombre, apellido, fecha_nacimiento, telefono, correo, id_usuario, estatus, contrasena, estado) 
        VALUES (?, ?, ?, ?, ?, ?, 'Activo', ?, ?)";
    
    $stmt_paciente = $conexion->prepare($sql_paciente);
    
    // Tipos de datos en orden:
    // nombre(s), apellido(s), fecha_nacimiento(s), telefono(s), correo(s), id_usuario(i), contrasena(s), estado(s)
    // Nota: 'estatus' se pasa directo como 'Activo' en el SQL, por lo que no ocupa parámetro extra en bind_param.
    // Formato total: "sssssiss"
    $stmt_paciente->bind_param(
        "sssssiss",
        $nombre,
        $apellido,
        $fecha_nacimiento,
        $telefono,
        $correo,
        $id_usuario,
        $contrasena,
        $estado
    );
    $stmt_paciente->execute();
    $stmt_paciente->close();

    // Si todo salió bien, guardamos los cambios definitivamente
    $conexion->commit();
    
    header("Location: login.php?registro=ok");
    exit;

} catch (mysqli_sql_exception $exception) {
    // Si algo falla, revertimos todo
    $conexion->rollback();

    if ($conexion->errno === 1062) {
        header("Location: registro.php?error=duplicado");
    } else {
        header("Location: login.php?registro=error");
    }
    exit;
}

$conexion->close();
?>