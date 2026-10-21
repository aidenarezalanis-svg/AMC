<?php
/**
 * Fotos de perfil de pacientes.
 *
 * AMC-16  Almacenamiento: el archivo se guarda en uploads/fotos/ con un nombre aleatorio
 *         y la columna pacientes.foto_perfil guarda ese nombre. La foto nueva reemplaza
 *         a la anterior (se borra el archivo viejo). La carpeta no es accesible desde el
 *         navegador: las fotos se entregan con foto.php, que revisa quién las pide.
 * AMC-17  Carga: solo JPG o PNG reales (se revisa el contenido, no la extensión)
 *         y de máximo 2 MB.
 */

const FOTO_MAX_BYTES = 2 * 1024 * 1024;                       // 2 MB
const FOTO_TIPOS = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png'];
const FOTO_PATRON = '/^[a-f0-9]{32}\.(jpg|png)$/';           // nombres que genera el sistema

function carpeta_fotos(): string
{
    return dirname(__DIR__) . '/uploads/fotos';
}

/** Ruta del archivo en disco, o null si el nombre no es válido o no existe. */
function ruta_foto(?string $archivo): ?string
{
    if (!$archivo || !preg_match(FOTO_PATRON, $archivo) || !is_file(carpeta_fotos() . '/' . $archivo)) {
        return null;
    }
    return carpeta_fotos() . '/' . $archivo;
}

/** URL para mostrar la foto (pasa por foto.php), o null si el paciente no tiene. */
function url_foto(int $idPaciente, ?string $archivo): ?string
{
    if (!ruta_foto($archivo)) {
        return null;
    }
    // El nombre del archivo en la URL hace que el navegador pida la foto nueva al cambiarla
    return 'foto.php?id=' . $idPaciente . '&v=' . substr($archivo, 0, 8);
}

/**
 * AMC-17: revisa el archivo recibido. Regresa [extensión, null] o [null, mensaje de error].
 */
function validar_foto(?array $archivo): array
{
    $codigo = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($codigo === UPLOAD_ERR_NO_FILE) {
        return [null, 'Selecciona una imagen JPG o PNG.'];
    }
    if ($codigo === UPLOAD_ERR_INI_SIZE || $codigo === UPLOAD_ERR_FORM_SIZE) {
        return [null, 'La imagen pesa más de 2 MB. Elige una más ligera.'];
    }
    if ($codigo !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
        return [null, 'No se pudo recibir la imagen. Inténtalo de nuevo.'];
    }
    if ($archivo['size'] > FOTO_MAX_BYTES) {
        return [null, 'La imagen pesa ' . number_format($archivo['size'] / 1048576, 1) . ' MB; el máximo es 2 MB.'];
    }
    // Se lee el contenido real: un .php renombrado a .jpg no pasa esta prueba
    $info = @getimagesize($archivo['tmp_name']);
    if (!$info || !isset(FOTO_TIPOS[$info[2]])) {
        return [null, 'Formato no permitido. Solo se aceptan imágenes JPG o PNG.'];
    }
    return [FOTO_TIPOS[$info[2]], null];
}

/** Crea uploads/fotos con un .htaccess que bloquea todo acceso directo desde el navegador. */
function preparar_carpeta_fotos(): void
{
    $dir = carpeta_fotos();
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $htaccess = $dir . '/.htaccess';
    if (!is_file($htaccess)) {
        file_put_contents($htaccess, "# Las fotos solo se entregan a través de foto.php\nRequire all denied\n");
    }
}

/**
 * AMC-16 + AMC-17: valida, guarda el archivo, actualiza pacientes.foto_perfil
 * y borra la foto anterior. Regresa null si todo salió bien, o el mensaje de error.
 */
function guardar_foto_paciente(int $idPaciente, ?array $archivo): ?string
{
    $pac = fila('SELECT * FROM pacientes WHERE id_paciente = ?', [$idPaciente]);
    if (!$pac) {
        return 'El paciente no existe.';
    }
    if (!array_key_exists('foto_perfil', $pac)) {
        return 'Falta preparar la base: ejecuta database/amc16_foto_perfil.sql en phpMyAdmin.';
    }
    [$extension, $error] = validar_foto($archivo);
    if ($error) {
        return $error;
    }

    preparar_carpeta_fotos();
    $nombre = bin2hex(random_bytes(16)) . '.' . $extension;
    $destino = carpeta_fotos() . '/' . $nombre;
    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        return 'No se pudo guardar la imagen en el servidor (revisa permisos de la carpeta uploads).';
    }

    try {
        db()->prepare('UPDATE pacientes SET foto_perfil = ? WHERE id_paciente = ?')->execute([$nombre, $idPaciente]);
    } catch (PDOException $e) {
        @unlink($destino);                     // si la base falla, no se deja el archivo huérfano
        return mensaje_bd($e);
    }

    // La foto nueva reemplaza a la anterior
    $anterior = $pac['foto_perfil'];
    if ($anterior && preg_match(FOTO_PATRON, $anterior) && $anterior !== $nombre) {
        @unlink(carpeta_fotos() . '/' . $anterior);
    }

    // Queda constancia en el historial (si la tabla existe en esta versión de la base)
    try {
        db()->prepare('INSERT INTO historial_cambios_pacientes (id_paciente, cambios_realizados, fecha_modificacion) VALUES (?, ?, ?)')
            ->execute([$idPaciente, $anterior ? 'Foto de perfil reemplazada' : 'Foto de perfil agregada', date('Y-m-d H:i:s')]);
    } catch (PDOException $e) {
        // El historial es opcional: la foto ya se guardó
    }
    return null;
}
