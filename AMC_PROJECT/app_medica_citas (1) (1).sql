-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-10-2026 a las 04:45:35
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `app_medica_citas`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_agendar_cita` (IN `p_id_paciente` INT, IN `p_id_horario` INT, IN `p_id_especialidad` INT, IN `p_motivo` VARCHAR(255), OUT `p_id_cita_generada` INT)   BEGIN
    DECLARE v_existe_paciente INT DEFAULT 0;
    DECLARE v_existe_especialidad INT DEFAULT 0;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
    START TRANSACTION;
    SELECT COUNT(*) INTO v_existe_paciente FROM Pacientes WHERE id_paciente = p_id_paciente;
    SELECT COUNT(*) INTO v_existe_especialidad FROM Especialidades WHERE id_especialidad = p_id_especialidad;
    IF v_existe_paciente = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Paciente no encontrado.';
    ELSEIF v_existe_especialidad = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Especialidad no encontrada.';
    END IF;
    INSERT INTO Citas (id_paciente, id_horario, id_especialidad, motivo_consulta)
    VALUES (p_id_paciente, p_id_horario, p_id_especialidad, p_motivo);
    SET p_id_cita_generada = LAST_INSERT_ID();
    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_cancelar_cita` (IN `p_id_cita` INT, OUT `p_mensaje` VARCHAR(255))   BEGIN
    DECLARE v_estatus_actual VARCHAR(20) DEFAULT NULL;

    SELECT estatus_cita INTO v_estatus_actual FROM Citas WHERE id_cita = p_id_cita;

    IF v_estatus_actual IS NULL THEN
        SET p_mensaje = 'La cita indicada no existe.';
    ELSEIF v_estatus_actual = 'Cancelada' THEN
        SET p_mensaje = 'La cita ya se encontraba cancelada.';
    ELSEIF v_estatus_actual = 'Completada' THEN
        SET p_mensaje = 'No se puede cancelar una cita que ya fue completada.';
    ELSE
        UPDATE Citas SET estatus_cita = 'Cancelada' WHERE id_cita = p_id_cita;
        SET p_mensaje = 'Cita cancelada correctamente.';
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_citas_por_medico` (IN `p_id_usuario` INT)   BEGIN
    SELECT c.id_cita, CONCAT(p.nombre,' ',p.ap_paterno) AS paciente,
           h.fecha, h.hora_inicio, co.nombre_consultorio, c.estatus_cita
    FROM Citas c
    JOIN Horarios_Disponibles h ON c.id_horario = h.id_horario
    JOIN Medicos m ON h.id_medico = m.id_medico
    JOIN Pacientes p ON c.id_paciente = p.id_paciente
    JOIN Consultorios co ON h.id_consultorio = co.id_consultorio
    WHERE m.id_usuario = p_id_usuario
    ORDER BY h.fecha, h.hora_inicio;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_citas_por_paciente` (IN `p_id_usuario` INT)   BEGIN
    SELECT c.id_cita, CONCAT(m.nombre,' ',m.ap_paterno) AS medico,
           e.nombre_especialidad, h.fecha, h.hora_inicio,
           co.nombre_consultorio, c.estatus_cita
    FROM Citas c
    JOIN Pacientes p ON c.id_paciente = p.id_paciente
    JOIN Horarios_Disponibles h ON c.id_horario = h.id_horario
    JOIN Medicos m ON h.id_medico = m.id_medico
    JOIN Consultorios co ON h.id_consultorio = co.id_consultorio
    JOIN Especialidades e ON c.id_especialidad = e.id_especialidad
    WHERE p.id_usuario = p_id_usuario
    ORDER BY h.fecha DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_citas_recepcion` ()   BEGIN
    SELECT c.id_cita, CONCAT(p.nombre,' ',p.ap_paterno) AS paciente,
           CONCAT(m.nombre,' ',m.ap_paterno) AS medico,
           h.fecha, h.hora_inicio, co.nombre_consultorio, c.estatus_cita
    FROM Citas c
    JOIN Pacientes p ON c.id_paciente = p.id_paciente
    JOIN Horarios_Disponibles h ON c.id_horario = h.id_horario
    JOIN Medicos m ON h.id_medico = m.id_medico
    JOIN Consultorios co ON h.id_consultorio = co.id_consultorio
    ORDER BY h.fecha, h.hora_inicio;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_directorio_general_usuarios` ()   BEGIN
    -- Unifica en una sola lista los nombres y correos de pacientes y médicos usando UNION
    SELECT nombre, ap_paterno, correo, 'Paciente' AS tipo_usuario 
    FROM Pacientes
    
    UNION
    
    SELECT nombre, ap_paterno, correo, 'Medico' AS tipo_usuario 
    FROM Medicos
    
    ORDER BY tipo_usuario, nombre;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_insertar_paciente_seguro` (IN `p_nombre` VARCHAR(100), IN `p_ap_paterno` VARCHAR(100), IN `p_correo` VARCHAR(150), IN `p_id_usuario` INT)   BEGIN
    -- Manejador para el código de error 1062 (Duplicate entry por índice único)
    DECLARE EXIT HANDLER FOR 1062
    BEGIN
        SELECT 'Error de Restricción Única: El correo electrónico introducido ya pertenece a otro paciente registrado en el sistema.' AS mensaje_error;
    END;

    -- Intento de inserción
    INSERT INTO pacientes (nombre, ap_paterno, correo, id_usuario, fecha_nacimiento, genero, curp, telefono)
    VALUES (p_nombre, p_ap_paterno, p_correo, p_id_usuario, '1995-01-01', 'M', 'XXXX950101HDFXXXXX', '7770000000');

    SELECT 'Éxito: Paciente registrado correctamente.' AS mensaje_exito;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_login_paciente` (IN `p_curp` CHAR(18), OUT `p_id_usuario` INT, OUT `p_contrasena_hash` VARCHAR(255), OUT `p_estatus` VARCHAR(20))   BEGIN
    SELECT u.id_usuario, u.contrasena_hash, u.estatus
    INTO p_id_usuario, p_contrasena_hash, p_estatus
    FROM Pacientes p JOIN Usuarios u ON p.id_usuario = u.id_usuario
    WHERE p.curp = p_curp;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_login_personal` (IN `p_correo` VARCHAR(100), OUT `p_id_usuario` INT, OUT `p_contrasena_hash` VARCHAR(255), OUT `p_estatus` VARCHAR(20), OUT `p_rol` VARCHAR(20))   BEGIN
    SELECT u.id_usuario, u.contrasena_hash, u.estatus, u.rol
    INTO p_id_usuario, p_contrasena_hash, p_estatus, p_rol
    FROM Medicos m JOIN Usuarios u ON m.id_usuario = u.id_usuario
    WHERE m.correo = p_correo;

    IF p_id_usuario IS NULL THEN
        SELECT u.id_usuario, u.contrasena_hash, u.estatus, u.rol
        INTO p_id_usuario, p_contrasena_hash, p_estatus, p_rol
        FROM Personal pe JOIN Usuarios u ON pe.id_usuario = u.id_usuario
        WHERE pe.correo = p_correo;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_acceso` (IN `p_id_usuario` INT)   BEGIN
    UPDATE Usuarios SET ultimo_acceso = NOW() WHERE id_usuario = p_id_usuario;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_cliente_seguro` (IN `p_nombre` VARCHAR(50), IN `p_ap_paterno` VARCHAR(50), IN `p_ap_materno` VARCHAR(50), IN `p_fecha_nacimiento` DATE, IN `p_genero` CHAR(1), IN `p_curp` CHAR(18), IN `p_telefono` VARCHAR(15), IN `p_correo` VARCHAR(100), IN `p_contrasena_hash` VARCHAR(255), OUT `p_resultado` VARCHAR(255))   BEGIN
    DECLARE v_id_usuario INT;

    -- Bloque "CATCH": se activa si el INSERT viola la restricción UNIQUE de correo o CURP (error 1062)
    DECLARE EXIT HANDLER FOR 1062
    BEGIN
        ROLLBACK;
        SET p_resultado = 'Error: ya existe un cliente registrado con ese correo o CURP.';
    END;

    -- Bloque "TRY": intento normal de inserción
    START TRANSACTION;
    INSERT INTO Usuarios (rol, contrasena_hash) VALUES ('Paciente', p_contrasena_hash);
    SET v_id_usuario = LAST_INSERT_ID();
    INSERT INTO Pacientes (nombre, ap_paterno, ap_materno, fecha_nacimiento, genero, curp, telefono, correo, id_usuario)
    VALUES (p_nombre, p_ap_paterno, p_ap_materno, p_fecha_nacimiento, p_genero, p_curp, p_telefono, p_correo, v_id_usuario);
    SET p_resultado = 'Cliente registrado correctamente.';
    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_paciente` (IN `p_nombre` VARCHAR(50), IN `p_ap_paterno` VARCHAR(50), IN `p_ap_materno` VARCHAR(50), IN `p_fecha_nacimiento` DATE, IN `p_genero` CHAR(1), IN `p_curp` CHAR(18), IN `p_telefono` VARCHAR(15), IN `p_correo` VARCHAR(100), IN `p_contrasena_hash` VARCHAR(255), OUT `p_id_paciente` INT)   BEGIN
    DECLARE v_id_usuario INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
    START TRANSACTION;
    INSERT INTO Usuarios (rol, contrasena_hash) VALUES ('Paciente', p_contrasena_hash);
    SET v_id_usuario = LAST_INSERT_ID();
    INSERT INTO Pacientes (nombre, ap_paterno, ap_materno, fecha_nacimiento, genero, curp, telefono, correo, id_usuario)
    VALUES (p_nombre, p_ap_paterno, p_ap_materno, p_fecha_nacimiento, p_genero, p_curp, p_telefono, p_correo, v_id_usuario);
    SET p_id_paciente = LAST_INSERT_ID();
    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_reporte_clientes_vigentes_trimestre` (IN `p_anio` INT)   BEGIN
    SELECT DISTINCT p.id_paciente,
           CONCAT(p.nombre,' ',p.ap_paterno,' ',IFNULL(p.ap_materno,'')) AS cliente,
           p.correo, p.telefono
    FROM Pacientes p
    JOIN Venta v ON v.id_paciente = p.id_paciente
    WHERE v.fecha_venta BETWEEN CONCAT(p_anio,'-01-01 00:00:00') AND CONCAT(p_anio,'-03-31 23:59:59')
    ORDER BY cliente;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_test_conectividad` ()   BEGIN
    SELECT 'OK' AS estatus_conexion, NOW() AS fecha_prueba;
    SELECT
        (SELECT COUNT(*) FROM Usuarios)             AS total_usuarios,
        (SELECT COUNT(*) FROM Pacientes)             AS total_pacientes,
        (SELECT COUNT(*) FROM Medicos)               AS total_medicos,
        (SELECT COUNT(*) FROM Personal)              AS total_personal,
        (SELECT COUNT(*) FROM Citas)                 AS total_citas,
        (SELECT COUNT(*) FROM Medicamentos)          AS total_medicamentos,
        (SELECT COUNT(*) FROM Venta)                 AS total_ventas;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_ventas_diarias` (IN `p_fecha` DATE)   BEGIN
    SELECT 
        v.id_venta,
        CONCAT(p.nombre, ' ', p.ap_paterno, ' ', IFNULL(p.ap_materno, '')) AS paciente,
        v.fecha_venta,
        v.metodo_pago,
        v.estatus_venta,
        v.total
    FROM venta v
    JOIN pacientes p 
        ON v.id_paciente = p.id_paciente
    WHERE DATE(v.fecha_venta) = p_fecha
    ORDER BY v.fecha_venta ASC;
    
    SELECT 
        p_fecha AS fecha_consultada,
        COUNT(*) AS total_ventas,
        COALESCE(SUM(v.total), 0) AS total_ingresos
    FROM venta v
    WHERE DATE(v.fecha_venta) = p_fecha;
END$$

--
-- Funciones
--
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_calcular_edad` (`p_fecha_nacimiento` DATE) RETURNS INT(11) DETERMINISTIC BEGIN
    RETURN TIMESTAMPDIFF(YEAR, p_fecha_nacimiento, CURDATE());
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id_cita` int(11) NOT NULL,
  `id_paciente` int(11) NOT NULL,
  `id_horario` int(11) NOT NULL,
  `id_especialidad` int(11) NOT NULL,
  `motivo_consulta` varchar(255) NOT NULL,
  `estatus_cita` enum('Programada','Confirmada','Cancelada','Completada') NOT NULL DEFAULT 'Programada',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `citas`
--

INSERT INTO `citas` (`id_cita`, `id_paciente`, `id_horario`, `id_especialidad`, `motivo_consulta`, `estatus_cita`, `fecha_creacion`) VALUES
(40, 180, 29, 1, 'Control de rutina general', 'Programada', '2026-09-25 12:33:25'),
(41, 181, 30, 1, 'Control de rutina general', 'Programada', '2026-09-25 12:33:25'),
(42, 182, 31, 1, 'Control de rutina general', 'Programada', '2026-09-25 12:33:25'),
(43, 183, 32, 1, 'Control de rutina general', 'Programada', '2026-09-25 12:33:25'),
(44, 184, 33, 1, 'Control de rutina general', 'Programada', '2026-09-25 12:33:25'),
(45, 185, 34, 1, 'Control de rutina general', 'Programada', '2026-09-25 12:33:25'),
(46, 186, 35, 1, 'Control de rutina general', 'Programada', '2026-09-25 12:33:25'),
(47, 187, 36, 1, 'Control de rutina general', 'Programada', '2026-09-25 12:33:25'),
(48, 188, 37, 1, 'Control de rutina general', 'Programada', '2026-09-25 12:33:25'),
(49, 189, 38, 1, 'Control de rutina general', 'Programada', '2026-09-25 12:33:25'),
(55, 210, 49, 4, 'Consulta General', 'Confirmada', '2026-09-25 12:42:57'),
(56, 241, 50, 3, 'Seguimiento', 'Programada', '2026-09-28 08:07:11'),
(57, 246, 51, 2, 'Prueba leo', 'Confirmada', '2026-09-29 08:24:44');

--
-- Disparadores `citas`
--
DELIMITER $$
CREATE TRIGGER `trg_after_cita_cancelada` AFTER UPDATE ON `citas` FOR EACH ROW BEGIN
    IF NEW.estatus_cita = 'Cancelada' AND OLD.estatus_cita <> 'Cancelada' THEN
        UPDATE Horarios_Disponibles SET estatus = 'Disponible' WHERE id_horario = NEW.id_horario;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_after_cita_insert` AFTER INSERT ON `citas` FOR EACH ROW BEGIN
    UPDATE Horarios_Disponibles SET estatus = 'Ocupado' WHERE id_horario = NEW.id_horario;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_cita_insert` BEFORE INSERT ON `citas` FOR EACH ROW BEGIN
    DECLARE v_estatus VARCHAR(20);
    DECLARE v_fecha DATE;
    DECLARE v_hora_inicio TIME;
    SELECT estatus, fecha, hora_inicio INTO v_estatus, v_fecha, v_hora_inicio
    FROM Horarios_Disponibles WHERE id_horario = NEW.id_horario;
    IF v_estatus IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El horario indicado no existe.';
    ELSEIF v_estatus <> 'Disponible' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El horario seleccionado ya no está disponible.';
    ELSEIF TIMESTAMP(v_fecha, v_hora_inicio) < NOW() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se pueden agendar citas en fechas u horas que ya pasaron.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compra`
--

CREATE TABLE `compra` (
  `id_compra` int(11) NOT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `numero_factura` varchar(100) DEFAULT NULL,
  `fecha_compra` date NOT NULL,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `estado` varchar(30) NOT NULL DEFAULT 'Completada',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `compra`
--

INSERT INTO `compra` (`id_compra`, `id_proveedor`, `numero_factura`, `fecha_compra`, `total`, `estado`, `fecha_registro`) VALUES
(1, 2, NULL, '2026-09-28', 1200.00, 'Completada', '2026-09-29 02:35:04'),
(2, NULL, NULL, '2026-09-29', 18000.00, 'Completada', '2026-09-29 14:28:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consultorios`
--

CREATE TABLE `consultorios` (
  `id_consultorio` int(11) NOT NULL,
  `nombre_consultorio` varchar(80) NOT NULL,
  `direccion` varchar(150) NOT NULL,
  `ciudad` varchar(60) NOT NULL,
  `telefono` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `consultorios`
--

INSERT INTO `consultorios` (`id_consultorio`, `nombre_consultorio`, `direccion`, `ciudad`, `telefono`) VALUES
(1, 'Consultorio Centro', 'Av. Reforma 100', 'Temixco', '7778880001'),
(2, 'Consultorio Norte', 'Blvd. Cuauhnahuac 250', 'Cuernavaca', '7778880002'),
(3, 'Consultorio Jiutepec', 'Av. Central 15', 'Jiutepec', '7778880003'),
(4, 'Consultorio Xochitepec', 'Calle Hidalgo 60', 'Xochitepec', '7778880004'),
(5, 'Consultorio Sur', 'Av. Emiliano Zapata 88', 'Cuernavaca', '7778880005');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_compra`
--

CREATE TABLE `detalle_compra` (
  `id_detalle` int(11) NOT NULL,
  `id_compra` int(11) NOT NULL,
  `id_medicamento` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_compra`
--

INSERT INTO `detalle_compra` (`id_detalle`, `id_compra`, `id_medicamento`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 18, 10, 120.00, 1200.00),
(2, 2, 24, 100, 180.00, 18000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `id_venta` int(11) NOT NULL,
  `id_medicamento` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL CHECK (`cantidad` > 0),
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `detalle_venta`
--

INSERT INTO `detalle_venta` (`id_venta`, `id_medicamento`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(96, 3, 1, 60.00, 60.00),
(96, 6, 1, 75.50, 75.50),
(122, 3, 1, 60.00, 60.00),
(122, 13, 1, 320.00, 320.00),
(124, 24, 60, 180.00, 10800.00),
(125, 18, 70, 120.00, 8400.00);

--
-- Disparadores `detalle_venta`
--
DELIMITER $$
CREATE TRIGGER `trg_after_detalle_venta_insert` AFTER INSERT ON `detalle_venta` FOR EACH ROW BEGIN
    UPDATE Medicamentos SET stock_actual = stock_actual - NEW.cantidad
    WHERE id_medicamento = NEW.id_medicamento;

    UPDATE Venta SET total = (
        SELECT COALESCE(SUM(subtotal),0) FROM Detalle_Venta WHERE id_venta = NEW.id_venta
    ) WHERE id_venta = NEW.id_venta;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_detalle_venta_insert` BEFORE INSERT ON `detalle_venta` FOR EACH ROW BEGIN
    DECLARE v_stock INT;
    DECLARE v_precio DECIMAL(10,2);

    SELECT stock_actual, precio_unitario INTO v_stock, v_precio
    FROM Medicamentos WHERE id_medicamento = NEW.id_medicamento;

    IF v_stock IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El medicamento indicado no existe.';
    ELSEIF v_stock < NEW.cantidad THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuficiente para este medicamento.';
    END IF;

    IF NEW.precio_unitario IS NULL OR NEW.precio_unitario = 0 THEN
        SET NEW.precio_unitario = v_precio;
    END IF;
    SET NEW.subtotal = NEW.cantidad * NEW.precio_unitario;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidades`
--

CREATE TABLE `especialidades` (
  `id_especialidad` int(11) NOT NULL,
  `nombre_especialidad` varchar(60) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `especialidades`
--

INSERT INTO `especialidades` (`id_especialidad`, `nombre_especialidad`, `descripcion`) VALUES
(1, 'Medicina General', 'Atención médica primaria y preventiva.'),
(2, 'Pediatria', 'Atención médica para bebés, niños y adolescentes.'),
(3, 'Cardiologia', 'Diagnóstico y tratamiento de enfermedades del corazón.'),
(4, 'Dermatologia', 'Diagnóstico y tratamiento de enfermedades de la piel.'),
(5, 'Ginecologia', 'Salud del sistema reproductivo femenino.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_cambios_pacientes`
--

CREATE TABLE `historial_cambios_pacientes` (
  `id_cambio` int(11) NOT NULL,
  `id_paciente` int(11) NOT NULL,
  `cambios_realizados` text NOT NULL,
  `fecha_modificacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `historial_cambios_pacientes`
--

INSERT INTO `historial_cambios_pacientes` (`id_cambio`, `id_paciente`, `cambios_realizados`, `fecha_modificacion`) VALUES
(1, 209, 'Ap. Materno: \'Rodriguez\' a \'Perez\'', '2026-09-25 18:53:40'),
(3, 233, 'Ap. Paterno: \'Esquivel\' a \'lopez\'', '2026-09-29 05:23:41'),
(4, 246, 'Ap. Paterno: \'Estrada\' a \'Estraada\'', '2026-09-29 14:25:32'),
(5, 247, 'Foto de perfil agregada', '2026-10-16 02:25:07'),
(6, 247, 'Foto de perfil reemplazada', '2026-10-16 02:27:01'),
(7, 247, 'Apellido paterno: \'Nares Alanis\' a \'Nares\'; Apellido materno: \'Nares Alanis\' a \'Alanis\'', '2026-10-17 02:43:27'),
(8, 247, 'Nombre(s): \'Valeria\' a \'Valeriii\'', '2026-10-17 02:44:22'),
(9, 247, 'Nombre(s): \'Valeriii\' a \'Valeria\'; Fecha de nacimiento: \'2007-02-15\' a \'2007-02-16\'', '2026-10-17 02:44:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_medico`
--

CREATE TABLE `historial_medico` (
  `id_historial` int(11) NOT NULL,
  `id_paciente` int(11) NOT NULL,
  `id_cita` int(11) NOT NULL,
  `diagnostico` varchar(255) NOT NULL,
  `tratamiento` varchar(255) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios_disponibles`
--

CREATE TABLE `horarios_disponibles` (
  `id_horario` int(11) NOT NULL,
  `id_medico` int(11) NOT NULL,
  `id_consultorio` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL CHECK (`hora_fin` > `hora_inicio`),
  `estatus` enum('Disponible','Ocupado','Cancelado') NOT NULL DEFAULT 'Disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `horarios_disponibles`
--

INSERT INTO `horarios_disponibles` (`id_horario`, `id_medico`, `id_consultorio`, `fecha`, `hora_inicio`, `hora_fin`, `estatus`) VALUES
(1, 1, 1, '2026-09-01', '09:00:00', '09:30:00', 'Disponible'),
(2, 2, 2, '2026-09-01', '10:00:00', '10:30:00', 'Ocupado'),
(3, 3, 3, '2026-09-02', '11:00:00', '11:30:00', 'Disponible'),
(4, 4, 4, '2026-09-02', '12:00:00', '12:30:00', 'Ocupado'),
(5, 5, 5, '2026-09-03', '16:00:00', '16:30:00', 'Ocupado'),
(6, 1, 1, '2026-09-04', '09:00:00', '09:30:00', 'Ocupado'),
(7, 2, 2, '2026-09-04', '10:00:00', '10:30:00', 'Ocupado'),
(8, 3, 3, '2026-09-04', '11:00:00', '11:30:00', 'Ocupado'),
(9, 4, 4, '2026-09-05', '09:00:00', '09:30:00', 'Ocupado'),
(10, 5, 5, '2026-09-05', '10:00:00', '10:30:00', 'Ocupado'),
(11, 1, 1, '2026-09-05', '11:00:00', '11:30:00', 'Ocupado'),
(12, 2, 2, '2026-09-06', '09:00:00', '09:30:00', 'Ocupado'),
(13, 3, 3, '2026-09-06', '10:00:00', '10:30:00', 'Ocupado'),
(14, 4, 4, '2026-09-07', '09:00:00', '09:30:00', 'Ocupado'),
(15, 5, 5, '2026-09-07', '10:00:00', '10:30:00', 'Ocupado'),
(16, 5, 3, '2026-09-30', '09:00:00', '09:30:00', 'Ocupado'),
(18, 5, 3, '2026-09-25', '12:00:00', '12:30:00', 'Ocupado'),
(19, 3, 3, '2026-09-25', '10:00:00', '10:30:00', 'Ocupado'),
(20, 3, 3, '2026-09-28', '12:00:00', '12:30:00', 'Ocupado'),
(21, 3, 3, '2026-09-25', '18:00:00', '18:30:00', 'Ocupado'),
(22, 3, 1, '2026-09-23', '16:00:00', '16:30:00', 'Ocupado'),
(23, 5, 3, '2026-09-24', '08:00:00', '08:30:00', 'Ocupado'),
(24, 3, 3, '2026-09-24', '08:00:00', '08:30:00', 'Ocupado'),
(25, 3, 1, '2026-09-24', '09:00:00', '09:30:00', 'Disponible'),
(26, 1, 5, '2026-09-24', '10:00:00', '10:30:00', 'Ocupado'),
(27, 4, 4, '2026-09-25', '11:00:00', '11:30:00', 'Ocupado'),
(28, 1, 1, '2026-09-25', '11:00:00', '11:30:00', 'Ocupado'),
(29, 1, 1, '2026-10-01', '09:00:00', '09:30:00', 'Ocupado'),
(30, 2, 2, '2026-10-01', '10:00:00', '10:30:00', 'Ocupado'),
(31, 3, 3, '2026-10-01', '11:00:00', '11:30:00', 'Ocupado'),
(32, 4, 4, '2026-10-02', '09:00:00', '09:30:00', 'Ocupado'),
(33, 5, 5, '2026-10-02', '10:00:00', '10:30:00', 'Ocupado'),
(34, 1, 2, '2026-10-03', '09:00:00', '09:30:00', 'Ocupado'),
(35, 2, 3, '2026-10-03', '10:00:00', '10:30:00', 'Ocupado'),
(36, 3, 4, '2026-10-04', '11:00:00', '11:30:00', 'Ocupado'),
(37, 4, 5, '2026-10-04', '12:00:00', '12:30:00', 'Ocupado'),
(38, 5, 1, '2026-10-05', '16:00:00', '16:30:00', 'Ocupado'),
(39, 1, 1, '2026-12-01', '09:00:00', '09:30:00', 'Disponible'),
(40, 2, 2, '2026-12-01', '10:00:00', '10:30:00', 'Disponible'),
(41, 3, 3, '2026-12-01', '11:00:00', '11:30:00', 'Disponible'),
(42, 4, 4, '2026-12-02', '09:00:00', '09:30:00', 'Disponible'),
(43, 5, 5, '2026-12-02', '10:00:00', '10:30:00', 'Disponible'),
(44, 1, 2, '2026-12-03', '09:00:00', '09:30:00', 'Disponible'),
(45, 2, 3, '2026-12-03', '10:00:00', '10:30:00', 'Disponible'),
(46, 3, 4, '2026-12-04', '11:00:00', '11:30:00', 'Disponible'),
(47, 4, 5, '2026-12-04', '12:00:00', '12:30:00', 'Disponible'),
(48, 5, 1, '2026-12-05', '16:00:00', '16:30:00', 'Disponible'),
(49, 4, 4, '2026-09-29', '12:00:00', '12:30:00', 'Ocupado'),
(50, 3, 3, '2026-09-30', '11:00:00', '11:30:00', 'Ocupado'),
(51, 2, 2, '2026-09-29', '13:00:00', '13:30:00', 'Ocupado');

--
-- Disparadores `horarios_disponibles`
--
DELIMITER $$
CREATE TRIGGER `trg_before_horario_insert` BEFORE INSERT ON `horarios_disponibles` FOR EACH ROW BEGIN
    IF NEW.fecha < CURDATE() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se pueden crear horarios en fechas pasadas.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicamentos`
--

CREATE TABLE `medicamentos` (
  `id_medicamento` int(11) NOT NULL,
  `nombre_comercial` varchar(100) NOT NULL,
  `principio_activo` varchar(100) NOT NULL,
  `presentacion` varchar(50) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL CHECK (`precio_unitario` >= 0),
  `stock_actual` int(11) NOT NULL DEFAULT 0 CHECK (`stock_actual` >= 0),
  `requiere_receta` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `medicamentos`
--

INSERT INTO `medicamentos` (`id_medicamento`, `nombre_comercial`, `principio_activo`, `presentacion`, `precio_unitario`, `stock_actual`, `requiere_receta`) VALUES
(1, 'Paracetamol MK', 'Paracetamol', 'Tabletas 500mg', 45.00, 197, 0),
(2, 'Amoxil', 'Amoxicilina', 'Cápsulas 500mg', 120.00, 149, 1),
(3, 'Loratadina Genfar', 'Loratadina', 'Tabletas 10mg', 60.00, 175, 0),
(4, 'Losartan Pharma', 'Losartán potásico', 'Tabletas 50mg', 95.00, 98, 1),
(5, 'Ibuprofeno MK', 'Ibuprofeno', 'Tabletas 400mg', 55.00, 217, 0),
(6, 'Metformina MK', 'Metformina', 'Tabletas 850mg', 75.50, 159, 1),
(7, 'Omeprazol Genfar', 'Omeprazol', 'Cápsulas 20mg', 52.00, 240, 0),
(8, 'Ketorolaco Maver', 'Ketorolaco', 'Tabletas 10mg', 68.00, 109, 1),
(9, 'Cetirizina Pharma', 'Cetirizina', 'Tabletas 10mg', 58.50, 130, 0),
(10, 'Ciprofloxacino Bio', 'Ciprofloxacino', 'Tabletas 500mg', 145.00, 95, 1),
(11, 'Salbutamol Aerosol', 'Salbutamol', 'Inhalador 100mcg', 185.00, 80, 1),
(12, 'Naproxeno Sódico', 'Naproxeno', 'Tabletas 550mg', 62.00, 174, 0),
(13, 'Atorvastatina Med', 'Atorvastatina', 'Tabletas 20mg', 320.00, 103, 1),
(14, 'Complejo B Forte', 'Tiamina/Piridoxina/Cianocobalamina', 'Grageas', 89.00, 210, 0),
(15, 'Clotrimazol Crema', 'Clotrimazol', 'Tubo 30g al 1%', 48.00, 140, 0),
(16, 'Paracetamol 500mg', 'Paracetamol', 'Caja con 20 tabletas', 45.50, 150, 0),
(17, 'Ibuprofeno 400mg', 'Ibuprofeno', 'Caja con 30 cápsulas', 65.00, 120, 0),
(18, 'Amoxicilina 500mg', 'Amoxicilina', 'Caja con 15 cápsulas', 120.00, 0, 1),
(19, 'Loratadina 10mg', 'Loratadina', 'Caja con 10 tabletas', 35.00, 200, 0),
(20, 'Omeprazol 20mg', 'Omeprazol', 'Caja con 14 cápsulas', 85.00, 95, 0),
(21, 'Metformina 850mg', 'Metformina', 'Caja con 30 tabletas', 90.00, 110, 1),
(22, 'Losartana 50mg', 'Losartana', 'Caja con 16 tabletas', 110.00, 75, 1),
(23, 'Aspirina Protect 100mg', 'Ácido Acetilsalicílico', 'Caja con 28 tabletas', 95.00, 140, 0),
(24, 'Diclofenaco 100mg', 'Diclofenaco', 'Caja con 10 ampolletas', 180.00, 100, 1),
(25, 'Salbutamol Inhalador', 'Salbutamol', 'Frasco aerosol 200 dosis', 220.00, 50, 1),
(26, 'Paracetamol Gen', 'Paracetamol', 'Tabletas 500mg', 25.00, 100, 0);

--
-- Disparadores `medicamentos`
--
DELIMITER $$
CREATE TRIGGER `trg_before_medicamento_insert` BEFORE INSERT ON `medicamentos` FOR EACH ROW BEGIN
    DECLARE v_existe INT DEFAULT 0;
    SELECT COUNT(*) INTO v_existe FROM Medicamentos
    WHERE nombre_comercial = NEW.nombre_comercial AND presentacion = NEW.presentacion;
    IF v_existe > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ya existe un medicamento con ese nombre comercial y presentación (control de inventario duplicado).';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_before_medicamento_update` BEFORE UPDATE ON `medicamentos` FOR EACH ROW BEGIN
    DECLARE v_existe INT DEFAULT 0;
    SELECT COUNT(*) INTO v_existe FROM Medicamentos
    WHERE nombre_comercial = NEW.nombre_comercial
      AND presentacion = NEW.presentacion
      AND id_medicamento <> OLD.id_medicamento;
    IF v_existe > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ya existe otro medicamento con ese nombre comercial y presentación (control de inventario duplicado).';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicos`
--

CREATE TABLE `medicos` (
  `id_medico` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `ap_paterno` varchar(50) NOT NULL,
  `ap_materno` varchar(50) DEFAULT NULL,
  `cedula_profesional` varchar(20) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `medicos`
--

INSERT INTO `medicos` (`id_medico`, `nombre`, `ap_paterno`, `ap_materno`, `cedula_profesional`, `telefono`, `correo`, `fecha_ingreso`, `id_usuario`) VALUES
(1, 'Ricardo', 'Solis', 'Mendoza', 'CED-10234', '7770001111', 'r.solis@clinica.com', '2019-02-01', 6),
(2, 'Paola', 'Vargas', 'Nunez', 'CED-10456', '7770002222', 'p.vargas@clinica.com', '2020-06-15', 7),
(3, 'Jorge', 'Castillo', 'Rios', 'CED-10789', '7770003333', 'j.castillo@clinica.com', '2018-09-10', 8),
(4, 'Sofia', 'Reyes', 'Cabrera', 'CED-11023', '7770004444', 's.reyes@clinica.com', '2021-01-20', 9),
(5, 'Miguel', 'Aguilar', 'Perez', 'CED-11256', '7770005555', 'm.aguilar@clinica.com', '2017-04-05', 10);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medico_consultorio`
--

CREATE TABLE `medico_consultorio` (
  `id_medico` int(11) NOT NULL,
  `id_consultorio` int(11) NOT NULL,
  `dias_atencion` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `medico_consultorio`
--

INSERT INTO `medico_consultorio` (`id_medico`, `id_consultorio`, `dias_atencion`) VALUES
(1, 1, 'Lun,Mie,Vie'),
(1, 2, 'Sab'),
(2, 2, 'Mar,Jue'),
(3, 3, 'Lun,Mar,Mie'),
(4, 4, 'Jue,Vie'),
(5, 5, 'Lun,Mie,Vie');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medico_especialidad`
--

CREATE TABLE `medico_especialidad` (
  `id_medico` int(11) NOT NULL,
  `id_especialidad` int(11) NOT NULL,
  `fecha_certificacion` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `medico_especialidad`
--

INSERT INTO `medico_especialidad` (`id_medico`, `id_especialidad`, `fecha_certificacion`) VALUES
(1, 1, '2019-03-01'),
(1, 3, '2022-01-15'),
(2, 2, '2020-07-01'),
(3, 3, '2018-10-01'),
(4, 4, '2021-02-01'),
(5, 5, '2017-05-01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes`
--

CREATE TABLE `pacientes` (
  `id_paciente` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `ap_paterno` varchar(50) NOT NULL,
  `ap_materno` varchar(50) DEFAULT NULL,
  `fecha_nacimiento` date NOT NULL,
  `genero` char(1) NOT NULL CHECK (`genero` in ('M','F','O')),
  `curp` char(18) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) NOT NULL,
  `estatus` varchar(20) NOT NULL DEFAULT 'Activo',
  `foto_perfil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pacientes`
--

INSERT INTO `pacientes` (`id_paciente`, `nombre`, `ap_paterno`, `ap_materno`, `fecha_nacimiento`, `genero`, `curp`, `telefono`, `correo`, `fecha_registro`, `id_usuario`, `estatus`, `foto_perfil`) VALUES
(180, 'Esteban', 'Pérez', 'García', '1990-07-07', 'M', 'PEGE900707HDFMR51', '7771115001', 'esteban51.perez@correo.com', '2026-09-25 12:26:42', 60, 'Activo', NULL),
(181, 'Andrea', 'Gómez', 'Cruz', '1996-09-18', 'F', 'GOCA960918MDFMR52', '7771115002', 'andrea52.gomez@correo.com', '2026-09-25 12:26:42', 61, 'Activo', NULL),
(182, 'Ricardo', 'Silva', 'Jiménez', '1982-03-22', 'M', 'SIJR820322HDFMR53', '7771115003', 'ricardo53.silva@correo.com', '2026-09-25 12:26:42', 62, 'Activo', NULL),
(183, 'Mónica', 'López', 'Domínguez', '1991-11-05', 'F', 'LODM911105MDFMR54', '7771115004', 'monica54.lopez@correo.com', '2026-09-25 12:26:42', 63, 'Activo', NULL),
(184, 'Jorge', 'Hernández', 'Cruz', '1984-05-14', 'M', 'HECJ840514HDFMR55', '7771115005', 'jorge55.hernandez@correo.com', '2026-09-25 12:26:42', 64, 'Activo', NULL),
(185, 'Alberto', 'Sánchez', 'López', '1978-12-10', 'M', 'SALA781210HDFM01', '7772221001', 'alberto.sanchez@correo.com', '2026-09-25 12:28:01', 65, 'Activo', NULL),
(186, 'Rosa', 'Jiménez', 'Pérez', '1997-02-15', 'F', 'JIPR970215MDFM02', '7772221002', 'rosa.jimenez@correo.com', '2026-09-25 12:28:01', 66, 'Activo', NULL),
(187, 'Luis', 'Ramírez', 'Torres', '1989-06-28', 'M', 'RATL890628HDFM03', '7772221003', 'luis.ramirez@correo.com', '2026-09-25 12:28:01', 67, 'Activo', NULL),
(188, 'Patricia', 'Gómez', 'Flores', '1995-10-04', 'F', 'GOFP951004MDFM04', '7772221004', 'patricia.gomez@correo.com', '2026-09-25 12:28:01', 68, 'Activo', NULL),
(189, 'Manuel', 'Ortíz', 'Castro', '1981-01-19', 'M', 'ORCM810119HDFM05', '7772221005', 'manuel.ortiz@correo.com', '2026-09-25 12:28:01', 69, 'Activo', NULL),
(190, 'Adriana', 'Ruiz', 'Morales', '1992-07-11', 'F', 'RUMA920711MDFM06', '7772221006', 'adriana.ruiz@correo.com', '2026-09-25 12:28:01', 70, 'Activo', NULL),
(191, 'Francisco', 'Vega', 'Navarro', '1986-04-03', 'M', 'VENF860403HDFM07', '7772221007', 'francisco.vega@correo.com', '2026-09-25 12:28:01', 71, 'Activo', NULL),
(192, 'Beatriz', 'Soto', 'Rojas', '1999-12-20', 'F', 'SORB991220MDFM08', '7772221008', 'beatriz.soto@correo.com', '2026-09-25 12:28:01', 72, 'Activo', NULL),
(193, 'Gabriel', 'Morales', 'Paz', '1984-08-14', 'M', 'MOPG840814HDFM09', '7772221009', 'gabriel.morales@correo.com', '2026-09-25 12:28:01', 73, 'Activo', NULL),
(194, 'Elena', 'Castillo', 'Ríos', '1991-03-29', 'F', 'CARE910329MDFM10', '7772221010', 'elena.castillo@correo.com', '2026-09-25 12:28:01', 74, 'Activo', NULL),
(195, 'Sergio', 'Paredes', 'Meza', '1977-11-02', 'M', 'PAMS771102HDFM11', '7772221011', 'sergio.paredes@correo.com', '2026-09-25 12:28:01', 75, 'Activo', NULL),
(196, 'Natalia', 'Rios', 'Alanis', '2001-05-18', 'F', 'RIAN010518MDFM12', '7772221012', 'natalia.rios@correo.com', '2026-09-25 12:28:01', 76, 'Activo', NULL),
(197, 'Arturo', 'Medina', 'Solares', '1988-09-12', 'M', 'MESA880912HDFM13', '7772221013', 'arturo.medina@correo.com', '2026-09-25 12:28:01', 77, 'Activo', NULL),
(198, 'Carmen', 'Silva', 'Duarte', '1994-01-25', 'F', 'SIDC940125MDFM14', '7772221014', 'carmen.silva@correo.com', '2026-09-25 12:28:01', 78, 'Activo', NULL),
(199, 'Daniel', 'Fuentes', 'Rojas', '1983-10-09', 'M', 'FURD831009HDFM15', '7772221015', 'daniel.fuentes@correo.com', '2026-09-25 12:28:01', 79, 'Activo', NULL),
(200, 'Laura', 'Luna', 'Vázquez', '1996-04-14', 'F', 'LUVL960414MDFM16', '7772221016', 'laura.luna@correo.com', '2026-09-25 12:28:01', 80, 'Activo', NULL),
(201, 'Humberto', 'Garza', 'Salinas', '1975-07-21', 'M', 'GASH750721HDFM17', '7772221017', 'humberto.garza@correo.com', '2026-09-25 12:28:01', 81, 'Activo', NULL),
(202, 'Teresa', 'Cruz', 'Mendoza', '1990-09-08', 'F', 'CUMT900908MDFM18', '7772221018', 'teresa.cruz@correo.com', '2026-09-25 12:28:01', 82, 'Activo', NULL),
(203, 'Mario', 'Benítez', 'León', '1982-12-30', 'M', 'BELM821230HDFM19', '7772221019', 'mario.benitez@correo.com', '2026-09-25 12:28:01', 83, 'Activo', NULL),
(204, 'Julieta', 'Morales', 'Estrada', '1998-06-05', 'F', 'MOEJ980605MDFM20', '7772221020', 'julieta.morales@correo.com', '2026-09-25 12:28:01', 84, 'Activo', NULL),
(205, 'Fernando', 'Ramos', 'Guerrero', '1987-03-17', 'M', 'RAGF870317HDFM21', '7772221021', 'fernando.ramos@correo.com', '2026-09-25 12:28:01', 85, 'Activo', NULL),
(206, 'Sofía', 'Duarte', 'Ponce', '2002-08-22', 'F', 'DUPS020822MDFM22', '7772221022', 'sofia.duarte@correo.com', '2026-09-25 12:28:01', 86, 'Activo', NULL),
(207, 'Ignacio', 'Lara', 'Valdez', '1979-10-01', 'M', 'LAVI791001HDFM23', '7772221023', 'ignacio.lara@correo.com', '2026-09-25 12:28:01', 87, 'Activo', NULL),
(208, 'Paula', 'Méndez', 'Navas', '1993-12-14', 'F', 'MENP931214MDFM24', '7772221024', 'paula.mendez@correo.com', '2026-09-25 12:28:01', 88, 'Activo', NULL),
(209, 'Vicente', 'Zúñiga', 'Perez', '1985-05-27', 'M', 'ZUAV850527HDFM25', '7772221025', 'vicente.zuniga@correo.com', '2026-09-25 12:28:01', 89, 'Activo', NULL),
(210, 'Amairani', 'Mejia', 'Rivera', '2000-01-01', 'F', 'NAGH4YY63HKASH23J3', '8117923748', 'amai@gmail.com', '2026-09-25 12:42:28', 90, 'Activo', NULL),
(211, 'Héctor', 'Vargas', 'Jiménez', '1985-03-12', 'M', 'VAJH850312HDFM01', '7774441001', 'hector.vargas@correo.com', '2026-09-25 16:23:31', 91, 'Activo', NULL),
(212, 'Valeria', 'Rojas', 'Soto', '1992-05-18', 'F', 'ROSV920518MDFM02', '7774441002', 'valeria.rojas@correo.com', '2026-09-25 16:23:31', 92, 'Activo', NULL),
(213, 'Esteban', 'Ponce', 'Lara', '1978-11-22', 'M', 'POLE781122HDFM03', '7774441003', 'esteban.ponce@correo.com', '2026-09-25 16:23:31', 93, 'Activo', NULL),
(214, 'Diana', 'Medina', 'Ríos', '1995-07-04', 'F', 'MERD950704MDFM04', '7774441004', 'diana.medina@correo.com', '2026-09-25 16:23:31', 94, 'Activo', NULL),
(215, 'Mauricio', 'Salazar', 'Cruz', '1988-12-30', 'M', 'SACM881230HDFM05', '7774441005', 'mauricio.salazar@correo.com', '2026-09-25 16:23:31', 95, 'Activo', NULL),
(216, 'Brenda', 'Flores', 'Navarro', '1990-04-15', 'F', 'FLNB900415MDFM06', '7774441006', 'brenda.flores@correo.com', '2026-09-25 16:23:31', 96, 'Activo', NULL),
(217, 'Gabriel', 'Ortega', 'Meza', '1982-09-09', 'M', 'OAMG820909HDFM07', '7774441007', 'gabriel.ortega@correo.com', '2026-09-25 16:23:31', 97, 'Activo', NULL),
(218, 'Jimena', 'Castro', 'Pacheco', '1999-01-25', 'F', 'CAPJ990125MDFM08', '7774441008', 'jimena.castro@correo.com', '2026-09-25 16:23:31', 98, 'Activo', NULL),
(219, 'Raúl', 'Domínguez', 'Acosta', '1975-06-11', 'M', 'DOAR750611HDFM09', '7774441009', 'raul.dominguez@correo.com', '2026-09-25 16:23:31', 99, 'Activo', NULL),
(220, 'Claudia', 'Núñez', 'Valdez', '1993-10-30', 'F', 'NUVC931030MDFM10', '7774441010', 'claudia.nunez@correo.com', '2026-09-25 16:23:31', 100, 'Activo', NULL),
(221, 'Alejandro', 'Ríos', 'Campos', '1980-08-05', 'M', 'RICA800805HDFM11', '7774441011', 'alejandro.rios@correo.com', '2026-09-25 16:23:31', 101, 'Activo', NULL),
(222, 'Daniela', 'Serrano', 'Mora', '1997-02-14', 'F', 'SEMD970214MDFM12', '7774441012', 'daniela.serrano@correo.com', '2026-09-25 16:23:31', 102, 'Activo', NULL),
(223, 'Federico', 'Blanco', 'Vega', '1986-12-03', 'M', 'BLVF861203HDFM13', '7774441013', 'federico.blanco@correo.com', '2026-09-25 16:23:31', 103, 'Activo', NULL),
(224, 'Sara', 'Lira', 'Estrada', '1994-06-21', 'F', 'LIES940621MDFM14', '7774441014', 'sara.lira@correo.com', '2026-09-25 16:23:31', 104, 'Activo', NULL),
(225, 'Ignacio', 'Trujillo', 'Murillo', '1979-04-19', 'M', 'TUMI790419HDFM15', '7774441015', 'ignacio.trujillo@correo.com', '2026-09-25 16:23:31', 105, 'Activo', NULL),
(226, 'Mónica', 'Álvarez', 'Peña', '1991-09-08', 'F', 'ALPM910908MDFM16', '7774441016', 'monica.alvarez@correo.com', '2026-09-25 16:23:31', 106, 'Activo', NULL),
(227, 'Leonardo', 'Navarro', 'Sosa', '1983-01-27', 'M', 'NASL830127HDFM17', '7774441017', 'leonardo.navarro@correo.com', '2026-09-25 16:23:31', 107, 'Activo', NULL),
(228, 'Teresa', 'Guerrero', 'Benítez', '1996-11-04', 'F', 'GUBT961104MDFM18', '7774441018', 'teresa.guerrero@correo.com', '2026-09-25 16:23:31', 108, 'Activo', NULL),
(229, 'Arturo', 'Pacheco', 'Leal', '1976-05-30', 'M', 'PALA760530HDFM19', '7774441019', 'arturo.pacheco@correo.com', '2026-09-25 16:23:31', 109, 'Activo', NULL),
(230, 'Beatriz', 'Galindo', 'Rangel', '1998-08-12', 'F', 'GARB980812MDFM20', '7774441020', 'beatriz.galindo@correo.com', '2026-09-25 16:23:31', 110, 'Activo', NULL),
(231, 'Samuel', 'Cordero', 'Puente', '1984-03-01', 'M', 'COPS840301HDFM21', '7774441021', 'samuel.cordero@correo.com', '2026-09-25 16:23:31', 111, 'Activo', NULL),
(232, 'Adriana', 'Vela', 'Padilla', '2000-12-05', 'F', 'VEPA001205MDFM22', '7774441022', 'adriana.vela@correo.com', '2026-09-25 16:23:31', 112, 'Activo', NULL),
(233, 'Vicente', 'lopez', 'Roldán', '1981-07-16', 'M', 'ESRV810716HDFM23', '7774441023', 'vicente.esquivel@correo.com', '2026-09-25 16:23:31', 113, 'Activo', NULL),
(234, 'Rosa', 'Peralta', 'Díaz', '1995-10-10', 'F', 'PEDR951010MDFM24', '7774441024', 'rosa.peralta@correo.com', '2026-09-25 16:23:31', 114, 'Inactivo', NULL),
(235, 'Ernesto', 'Lozano', 'Cervantes', '1987-02-28', 'M', 'LOCE870228HDFM25', '7774441025', 'ernesto.lozano@correo.com', '2026-09-25 16:23:31', 115, 'Activo', NULL),
(241, 'Carlos', 'Gómez', 'Ríos', '1990-01-01', 'M', 'GORC900101HDFXXX02', '7771234567', 'carlos.gomez@correo.com', '2026-09-27 12:53:48', 1, 'Activo', NULL),
(243, 'Martina', 'Reyes', 'Flores', '1995-01-01', 'F', 'REYM950101MDFXXX09', '7771112233', 'martina.reyes.test@correo.com', '2026-09-27 13:32:51', 120, 'Activo', NULL),
(245, 'Ana', 'Torres', 'Gil', '1995-04-10', 'F', 'TOGA950410MMSXXX01', '7771112222', 'ana.torres.nueva@correo.com', '2026-09-28 21:24:19', 122, 'Inactivo', NULL),
(246, 'Leonel', 'Estraada', 'Farias', '2007-07-06', 'M', 'Eaplz060707ksfd', '7777354244', 'leoestra5662@gmail.com', '2026-09-29 08:22:45', 123, 'Activo', NULL),
(247, 'Valeria', 'Nares', 'Alanis', '2007-02-16', 'F', 'NAAV070216MMSRLLA8', '7772574853', 'al07141747@tecmilenio.mx', '2026-10-15 19:48:22', 124, 'Activo', '2127a23ffdaa493b0187ab0ad49ac39e.jpg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL,
  `id_cita` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL CHECK (`monto` >= 0),
  `metodo_pago` enum('Efectivo','Tarjeta','Transferencia') NOT NULL,
  `fecha_pago` datetime NOT NULL DEFAULT current_timestamp(),
  `estatus_pago` enum('Pendiente','Pagado','Reembolsado') NOT NULL DEFAULT 'Pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal`
--

CREATE TABLE `personal` (
  `id_personal` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `ap_paterno` varchar(50) NOT NULL,
  `ap_materno` varchar(50) DEFAULT NULL,
  `correo` varchar(100) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `fecha_ingreso` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `personal`
--

INSERT INTO `personal` (`id_personal`, `id_usuario`, `nombre`, `ap_paterno`, `ap_materno`, `correo`, `telefono`, `fecha_ingreso`) VALUES
(1, 11, 'Daniela', 'Cruz', 'Morales', 'd.cruz@clinica.com', '7776001001', '2021-03-01'),
(2, 12, 'Ivan', 'Guerrero', 'Paz', 'i.guerrero@clinica.com', '7776001002', '2022-05-15'),
(3, 13, 'Brenda', 'Salas', 'Cortes', 'b.salas@clinica.com', '7776001003', '2020-11-20'),
(4, 14, 'Hector', 'Villareal', 'Nava', 'h.villareal@clinica.com', '7776001004', '2018-01-10'),
(5, 15, 'Karla', 'Espinoza', 'Duran', 'k.espinoza@clinica.com', '7776001005', '2019-08-25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id_proveedor`, `nombre`, `telefono`, `email`, `direccion`, `fecha_registro`) VALUES
(1, 'FarmaGlobal S.A. de C.V.', '7773330001', 'contacto@farmaglobal.com', 'Av. Insurgentes Sur 100, CDMX', '2026-09-25 22:23:30'),
(2, 'Distribuidora Médica del Sur', '7773330002', 'ventas@medicasur.com', 'Calle Morelos 45, Cuernavaca', '2026-09-25 22:23:30'),
(3, 'Laboratorios Quimilab', '7773330003', 'pedidos@quimilab.net', 'Blvd. Juarez 89, Puebla', '2026-09-25 22:23:30'),
(4, 'Insumos Hospitalarios de Morelos', '7773330004', 'info@insumosmor.com', 'Av. Plan de Ayala 500, Cuernavaca', '2026-09-25 22:23:30'),
(5, 'Biológicos y Reactivos S.A.', '7773330005', 'contacto@bioreactivos.com', 'Paseo de la Reforma 200, CDMX', '2026-09-25 22:23:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguimiento_clientes`
--

CREATE TABLE `seguimiento_clientes` (
  `id_seguimiento` int(11) NOT NULL,
  `id_paciente` int(11) NOT NULL,
  `nombre_cliente` varchar(120) NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `seguimiento_clientes`
--

INSERT INTO `seguimiento_clientes` (`id_seguimiento`, `id_paciente`, `nombre_cliente`, `fecha_hora`) VALUES
(29, 185, 'Alberto Sánchez López', '2026-09-25 12:28:01'),
(30, 186, 'Rosa Jiménez Pérez', '2026-09-25 12:28:01'),
(31, 187, 'Luis Ramírez Torres', '2026-09-25 12:28:01'),
(32, 188, 'Patricia Gómez Flores', '2026-09-25 12:28:01'),
(33, 189, 'Manuel Ortíz Castro', '2026-09-25 12:28:01'),
(34, 190, 'Adriana Ruiz Morales', '2026-09-25 12:28:01'),
(35, 191, 'Francisco Vega Navarro', '2026-09-25 12:28:01'),
(36, 192, 'Beatriz Soto Rojas', '2026-09-25 12:28:01'),
(37, 193, 'Gabriel Morales Paz', '2026-09-25 12:28:01'),
(38, 194, 'Elena Castillo Ríos', '2026-09-25 12:28:01'),
(39, 195, 'Sergio Paredes Meza', '2026-09-25 12:28:01'),
(40, 196, 'Natalia Rios Alanis', '2026-09-25 12:28:01'),
(41, 197, 'Arturo Medina Solares', '2026-09-25 12:28:01'),
(42, 198, 'Carmen Silva Duarte', '2026-09-25 12:28:01'),
(43, 199, 'Daniel Fuentes Rojas', '2026-09-25 12:28:01'),
(44, 200, 'Laura Luna Vázquez', '2026-09-25 12:28:01'),
(45, 201, 'Humberto Garza Salinas', '2026-09-25 12:28:01'),
(46, 202, 'Teresa Cruz Mendoza', '2026-09-25 12:28:01'),
(47, 203, 'Mario Benítez León', '2026-09-25 12:28:01'),
(48, 204, 'Julieta Morales Estrada', '2026-09-25 12:28:01'),
(49, 205, 'Fernando Ramos Guerrero', '2026-09-25 12:28:01'),
(50, 206, 'Sofía Duarte Ponce', '2026-09-25 12:28:01'),
(51, 207, 'Ignacio Lara Valdez', '2026-09-25 12:28:01'),
(52, 208, 'Paula Méndez Navas', '2026-09-25 12:28:01'),
(53, 209, 'Vicente Zúñiga Ávila', '2026-09-25 12:28:01'),
(54, 210, 'Amairani Mejia Rivera', '2026-09-25 12:43:48'),
(55, 211, 'Héctor Vargas Jiménez', '2026-09-25 16:23:31'),
(56, 212, 'Valeria Rojas Soto', '2026-09-25 16:23:31'),
(57, 213, 'Esteban Ponce Lara', '2026-09-25 16:23:31'),
(58, 214, 'Diana Medina Ríos', '2026-09-25 16:23:31'),
(59, 215, 'Mauricio Salazar Cruz', '2026-09-25 16:23:31'),
(60, 216, 'Brenda Flores Navarro', '2026-09-25 16:23:31'),
(61, 217, 'Gabriel Ortega Meza', '2026-09-25 16:23:31'),
(62, 218, 'Jimena Castro Pacheco', '2026-09-25 16:23:31'),
(63, 219, 'Raúl Domínguez Acosta', '2026-09-25 16:23:31'),
(64, 220, 'Claudia Núñez Valdez', '2026-09-25 16:23:31'),
(65, 221, 'Alejandro Ríos Campos', '2026-09-25 16:23:31'),
(66, 222, 'Daniela Serrano Mora', '2026-09-25 16:23:31'),
(67, 223, 'Federico Blanco Vega', '2026-09-25 16:23:31'),
(68, 224, 'Sara Lira Estrada', '2026-09-25 16:23:31'),
(69, 225, 'Ignacio Trujillo Murillo', '2026-09-25 16:23:31'),
(70, 226, 'Mónica Álvarez Peña', '2026-09-25 16:23:31'),
(71, 227, 'Leonardo Navarro Sosa', '2026-09-25 16:23:31'),
(72, 228, 'Teresa Guerrero Benítez', '2026-09-25 16:23:31'),
(73, 229, 'Arturo Pacheco Leal', '2026-09-25 16:23:31'),
(74, 230, 'Beatriz Galindo Rangel', '2026-09-25 16:23:31'),
(75, 231, 'Samuel Cordero Puente', '2026-09-25 16:23:31'),
(76, 232, 'Adriana Vela Padilla', '2026-09-25 16:23:31'),
(77, 233, 'Vicente Esquivel Roldán', '2026-09-25 16:23:31'),
(78, 234, 'Rosa Peralta Díaz', '2026-09-25 16:23:31'),
(79, 235, 'Ernesto Lozano Cervantes', '2026-09-25 16:23:31'),
(80, 185, 'Alberto Sánchez López', '2026-09-26 16:18:26'),
(81, 185, 'Alberto Sánchez López', '2026-09-27 13:02:03'),
(82, 222, 'Daniela Serrano Mora', '2026-09-28 22:03:31'),
(83, 216, 'Brenda Flores Navarro', '2026-09-29 08:29:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `rol` enum('Paciente','Medico','Recepcionista','Administrador') NOT NULL,
  `contrasena_hash` varchar(255) NOT NULL,
  `estatus` enum('Activo','Inactivo','Bloqueado') NOT NULL DEFAULT 'Activo',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `ultimo_acceso` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `rol`, `contrasena_hash`, `estatus`, `fecha_creacion`, `ultimo_acceso`) VALUES
(1, 'Paciente', 'HASH$demo$p1', 'Inactivo', '2026-08-27 22:32:15', NULL),
(2, 'Paciente', 'HASH$demo$p2', 'Activo', '2026-08-27 22:32:15', NULL),
(3, 'Paciente', 'HASH$demo$p3', 'Activo', '2026-08-27 22:32:15', NULL),
(4, 'Paciente', 'HASH$demo$p4', 'Activo', '2026-08-27 22:32:15', NULL),
(5, 'Paciente', 'HASH$demo$p5', 'Activo', '2026-08-27 22:32:15', NULL),
(6, 'Medico', '$2y$10$wpFD8A4ChnYMF91hdUr1ce2Lv2iCogT239IU6CGCZA3ksCQm6m5Lm', 'Activo', '2026-08-27 22:32:15', '2026-10-16 07:40:11'),
(7, 'Medico', 'HASH$demo$m2', 'Activo', '2026-08-27 22:32:15', NULL),
(8, 'Medico', 'HASH$demo$m3', 'Activo', '2026-08-27 22:32:15', NULL),
(9, 'Medico', 'HASH$demo$m4', 'Activo', '2026-08-27 22:32:15', NULL),
(10, 'Medico', 'HASH$demo$m5', 'Activo', '2026-08-27 22:32:15', NULL),
(11, 'Recepcionista', '$2y$10$c4MEe8TmkUgDXrCjX1pj9u4y45hvHG..p5dGCM5s2xb3GJV7oNIhe', 'Activo', '2026-08-27 22:32:15', '2026-10-16 20:42:36'),
(12, 'Recepcionista', 'HASH$demo$r2', 'Activo', '2026-08-27 22:32:15', NULL),
(13, 'Recepcionista', 'HASH$demo$r3', 'Activo', '2026-08-27 22:32:15', NULL),
(14, 'Administrador', 'HASH$demo$a1', 'Activo', '2026-08-27 22:32:15', NULL),
(15, 'Administrador', 'HASH$demo$a2', 'Activo', '2026-08-27 22:32:15', NULL),
(16, 'Paciente', 'HASH$demo$p6', 'Activo', '2026-08-28 09:11:18', NULL),
(17, 'Paciente', 'HASH$demo$p7', 'Activo', '2026-08-28 09:11:18', NULL),
(18, 'Paciente', 'HASH$demo$p8', 'Activo', '2026-08-28 09:11:18', NULL),
(19, 'Paciente', 'HASH$demo$p9', 'Activo', '2026-08-28 09:11:18', NULL),
(20, 'Paciente', 'HASH$demo$p10', 'Activo', '2026-08-28 09:11:18', NULL),
(21, 'Paciente', 'HASH$demo$p11', 'Activo', '2026-08-28 09:11:18', NULL),
(22, 'Paciente', 'HASH$demo$p12', 'Activo', '2026-08-28 09:11:18', NULL),
(23, 'Paciente', 'HASH$demo$p13', 'Activo', '2026-08-28 09:11:18', NULL),
(24, 'Paciente', 'HASH$demo$p14', 'Activo', '2026-08-28 09:11:18', NULL),
(25, 'Paciente', 'HASH$demo$p15', 'Activo', '2026-08-28 09:11:18', NULL),
(26, 'Paciente', 'HASH$demo$x1', 'Activo', '2026-09-23 00:13:58', NULL),
(27, 'Paciente', 'HASH$demo$auto343', 'Activo', '2026-09-23 09:36:42', NULL),
(32, 'Paciente', 'HASH$demo$auto658', 'Activo', '2026-09-24 08:56:23', NULL),
(33, 'Paciente', 'HASH$demo$auto581', 'Activo', '2026-09-24 19:37:27', NULL),
(36, 'Paciente', 'HASH$demo$x1', 'Activo', '2026-09-24 23:41:01', NULL),
(38, 'Paciente', 'HASH$demo$auto914', 'Activo', '2026-09-25 00:23:40', NULL),
(39, 'Paciente', 'HASH$demo$auto316', 'Activo', '2026-09-25 08:40:51', NULL),
(40, 'Paciente', 'HASH$demo$U101', 'Activo', '2026-09-25 12:24:48', NULL),
(41, 'Paciente', 'HASH$demo$U102', 'Activo', '2026-09-25 12:24:48', NULL),
(42, 'Paciente', 'HASH$demo$U103', 'Activo', '2026-09-25 12:24:48', NULL),
(43, 'Paciente', 'HASH$demo$U104', 'Activo', '2026-09-25 12:24:48', NULL),
(44, 'Paciente', 'HASH$demo$U105', 'Activo', '2026-09-25 12:24:48', NULL),
(45, 'Paciente', 'HASH$demo$U106', 'Activo', '2026-09-25 12:24:48', NULL),
(46, 'Paciente', 'HASH$demo$U107', 'Activo', '2026-09-25 12:24:48', NULL),
(47, 'Paciente', 'HASH$demo$U108', 'Activo', '2026-09-25 12:24:48', NULL),
(48, 'Paciente', 'HASH$demo$U109', 'Activo', '2026-09-25 12:24:48', NULL),
(49, 'Paciente', 'HASH$demo$U110', 'Activo', '2026-09-25 12:24:48', NULL),
(50, 'Paciente', 'HASH$demo$X201', 'Activo', '2026-09-25 12:25:38', NULL),
(51, 'Paciente', 'HASH$demo$X202', 'Activo', '2026-09-25 12:25:38', NULL),
(52, 'Paciente', 'HASH$demo$X203', 'Activo', '2026-09-25 12:25:38', NULL),
(53, 'Paciente', 'HASH$demo$X204', 'Activo', '2026-09-25 12:25:38', NULL),
(54, 'Paciente', 'HASH$demo$X205', 'Activo', '2026-09-25 12:25:38', NULL),
(55, 'Paciente', 'HASH$demo$X206', 'Activo', '2026-09-25 12:25:38', NULL),
(56, 'Paciente', 'HASH$demo$X207', 'Activo', '2026-09-25 12:25:38', NULL),
(57, 'Paciente', 'HASH$demo$X208', 'Activo', '2026-09-25 12:25:38', NULL),
(58, 'Paciente', 'HASH$demo$X209', 'Activo', '2026-09-25 12:25:38', NULL),
(59, 'Paciente', 'HASH$demo$X210', 'Activo', '2026-09-25 12:25:38', NULL),
(60, 'Paciente', 'HASH$demo$U501', 'Activo', '2026-09-25 12:26:42', NULL),
(61, 'Paciente', 'HASH$demo$U502', 'Activo', '2026-09-25 12:26:42', NULL),
(62, 'Paciente', 'HASH$demo$U503', 'Activo', '2026-09-25 12:26:42', NULL),
(63, 'Paciente', 'HASH$demo$U504', 'Activo', '2026-09-25 12:26:42', NULL),
(64, 'Paciente', 'HASH$demo$U505', 'Activo', '2026-09-25 12:26:42', NULL),
(65, 'Paciente', 'HASH$demo$U601', 'Activo', '2026-09-25 12:28:01', NULL),
(66, 'Paciente', 'HASH$demo$U602', 'Activo', '2026-09-25 12:28:01', NULL),
(67, 'Paciente', 'HASH$demo$U603', 'Activo', '2026-09-25 12:28:01', NULL),
(68, 'Paciente', 'HASH$demo$U604', 'Activo', '2026-09-25 12:28:01', NULL),
(69, 'Paciente', 'HASH$demo$U605', 'Activo', '2026-09-25 12:28:01', NULL),
(70, 'Paciente', 'HASH$demo$U606', 'Activo', '2026-09-25 12:28:01', NULL),
(71, 'Paciente', 'HASH$demo$U607', 'Activo', '2026-09-25 12:28:01', NULL),
(72, 'Paciente', 'HASH$demo$U608', 'Activo', '2026-09-25 12:28:01', NULL),
(73, 'Paciente', 'HASH$demo$U609', 'Activo', '2026-09-25 12:28:01', NULL),
(74, 'Paciente', 'HASH$demo$U610', 'Activo', '2026-09-25 12:28:01', NULL),
(75, 'Paciente', 'HASH$demo$U611', 'Activo', '2026-09-25 12:28:01', NULL),
(76, 'Paciente', 'HASH$demo$U612', 'Activo', '2026-09-25 12:28:01', NULL),
(77, 'Paciente', 'HASH$demo$U613', 'Activo', '2026-09-25 12:28:01', NULL),
(78, 'Paciente', 'HASH$demo$U614', 'Activo', '2026-09-25 12:28:01', NULL),
(79, 'Paciente', 'HASH$demo$U615', 'Activo', '2026-09-25 12:28:01', NULL),
(80, 'Paciente', 'HASH$demo$U616', 'Activo', '2026-09-25 12:28:01', NULL),
(81, 'Paciente', 'HASH$demo$U617', 'Activo', '2026-09-25 12:28:01', NULL),
(82, 'Paciente', 'HASH$demo$U618', 'Activo', '2026-09-25 12:28:01', NULL),
(83, 'Paciente', 'HASH$demo$U619', 'Activo', '2026-09-25 12:28:01', NULL),
(84, 'Paciente', 'HASH$demo$U620', 'Activo', '2026-09-25 12:28:01', NULL),
(85, 'Paciente', 'HASH$demo$U621', 'Activo', '2026-09-25 12:28:01', NULL),
(86, 'Paciente', 'HASH$demo$U622', 'Activo', '2026-09-25 12:28:01', NULL),
(87, 'Paciente', 'HASH$demo$U623', 'Activo', '2026-09-25 12:28:01', NULL),
(88, 'Paciente', 'HASH$demo$U624', 'Activo', '2026-09-25 12:28:01', NULL),
(89, 'Paciente', 'HASH$demo$U625', 'Activo', '2026-09-25 12:28:01', NULL),
(90, 'Paciente', 'HASH$demo$auto591', 'Inactivo', '2026-09-25 12:42:28', NULL),
(91, 'Paciente', 'HASH$demo$U701', 'Activo', '2026-09-25 16:23:31', NULL),
(92, 'Paciente', 'HASH$demo$U702', 'Activo', '2026-09-25 16:23:31', NULL),
(93, 'Paciente', 'HASH$demo$U703', 'Activo', '2026-09-25 16:23:31', NULL),
(94, 'Paciente', 'HASH$demo$U704', 'Activo', '2026-09-25 16:23:31', NULL),
(95, 'Paciente', 'HASH$demo$U705', 'Activo', '2026-09-25 16:23:31', NULL),
(96, 'Paciente', 'HASH$demo$U706', 'Activo', '2026-09-25 16:23:31', NULL),
(97, 'Paciente', 'HASH$demo$U707', 'Activo', '2026-09-25 16:23:31', NULL),
(98, 'Paciente', 'HASH$demo$U708', 'Activo', '2026-09-25 16:23:31', NULL),
(99, 'Paciente', 'HASH$demo$U709', 'Activo', '2026-09-25 16:23:31', NULL),
(100, 'Paciente', 'HASH$demo$U710', 'Activo', '2026-09-25 16:23:31', NULL),
(101, 'Paciente', 'HASH$demo$U711', 'Activo', '2026-09-25 16:23:31', NULL),
(102, 'Paciente', 'HASH$demo$U712', 'Activo', '2026-09-25 16:23:31', NULL),
(103, 'Paciente', 'HASH$demo$U713', 'Activo', '2026-09-25 16:23:31', NULL),
(104, 'Paciente', 'HASH$demo$U714', 'Activo', '2026-09-25 16:23:31', NULL),
(105, 'Paciente', 'HASH$demo$U715', 'Activo', '2026-09-25 16:23:31', NULL),
(106, 'Paciente', 'HASH$demo$U716', 'Activo', '2026-09-25 16:23:31', NULL),
(107, 'Paciente', 'HASH$demo$U717', 'Activo', '2026-09-25 16:23:31', NULL),
(108, 'Paciente', 'HASH$demo$U718', 'Activo', '2026-09-25 16:23:31', NULL),
(109, 'Paciente', 'HASH$demo$U719', 'Activo', '2026-09-25 16:23:31', NULL),
(110, 'Paciente', 'HASH$demo$U720', 'Activo', '2026-09-25 16:23:31', NULL),
(111, 'Paciente', 'HASH$demo$U721', 'Activo', '2026-09-25 16:23:31', NULL),
(112, 'Paciente', 'HASH$demo$U722', 'Activo', '2026-09-25 16:23:31', NULL),
(113, 'Paciente', 'HASH$demo$U723', 'Activo', '2026-09-25 16:23:31', NULL),
(114, 'Paciente', 'HASH$demo$U724', 'Inactivo', '2026-09-25 16:23:31', NULL),
(115, 'Paciente', 'HASH$demo$U725', 'Inactivo', '2026-09-25 16:23:31', NULL),
(116, 'Paciente', 'HASH$demo$x1', 'Activo', '2026-09-25 17:38:38', NULL),
(117, 'Paciente', 'HASH$demo$x1', 'Activo', '2026-09-25 17:42:00', NULL),
(118, 'Paciente', 'HASH$demo$x1', 'Activo', '2026-09-25 17:43:39', NULL),
(119, 'Paciente', 'HASH$demo$x1', 'Activo', '2026-09-27 13:13:38', NULL),
(120, 'Paciente', 'HashDePrueba123', 'Inactivo', '2026-09-27 13:32:51', NULL),
(122, 'Paciente', 'HASH$demo$x1', 'Activo', '2026-09-28 21:24:19', NULL),
(123, 'Paciente', 'HASH$demo$auto300', 'Activo', '2026-09-29 08:22:45', NULL),
(124, 'Paciente', '$2y$10$5D0FgPQa2dA74HO2DLYhtODEdDjz9MKJfFcAwnxMeUH6n4sN9ijNm', 'Activo', '2026-10-15 19:48:22', '2026-10-16 20:42:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta`
--

CREATE TABLE `venta` (
  `id_venta` int(11) NOT NULL,
  `id_paciente` int(11) NOT NULL,
  `id_cita` int(11) DEFAULT NULL,
  `fecha_venta` datetime NOT NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL DEFAULT 0.00 CHECK (`total` >= 0),
  `metodo_pago` enum('Efectivo','Tarjeta','Transferencia') NOT NULL,
  `estatus_venta` enum('Completada','Cancelada') NOT NULL DEFAULT 'Completada',
  `paciente_nombre_historico` varchar(150) DEFAULT NULL,
  `paciente_curp_historico` char(18) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `venta`
--

INSERT INTO `venta` (`id_venta`, `id_paciente`, `id_cita`, `fecha_venta`, `total`, `metodo_pago`, `estatus_venta`, `paciente_nombre_historico`, `paciente_curp_historico`) VALUES
(71, 185, NULL, '2026-01-12 10:00:00', 650.00, 'Efectivo', 'Completada', NULL, NULL),
(72, 186, NULL, '2026-01-20 11:30:00', 1200.00, 'Tarjeta', 'Completada', NULL, NULL),
(73, 187, NULL, '2026-02-05 09:15:00', 850.00, 'Transferencia', 'Completada', NULL, NULL),
(74, 188, NULL, '2026-02-18 14:00:00', 430.00, 'Efectivo', 'Completada', NULL, NULL),
(75, 189, NULL, '2026-03-02 12:00:00', 1450.00, 'Tarjeta', 'Completada', NULL, NULL),
(76, 190, NULL, '2026-03-22 16:30:00', 510.00, 'Efectivo', 'Completada', NULL, NULL),
(77, 191, NULL, '2026-04-10 11:00:00', 920.00, 'Transferencia', 'Completada', NULL, NULL),
(78, 192, NULL, '2026-04-25 15:20:00', 1100.00, 'Tarjeta', 'Completada', NULL, NULL),
(79, 193, NULL, '2026-05-08 09:30:00', 380.00, 'Efectivo', 'Completada', NULL, NULL),
(80, 194, NULL, '2026-05-19 13:10:00', 1350.00, 'Tarjeta', 'Completada', NULL, NULL),
(81, 195, NULL, '2026-06-07 10:45:00', 720.00, 'Transferencia', 'Completada', NULL, NULL),
(82, 196, NULL, '2026-06-21 14:20:00', 490.00, 'Efectivo', 'Completada', NULL, NULL),
(83, 197, NULL, '2026-07-04 11:15:00', 1600.00, 'Tarjeta', 'Completada', NULL, NULL),
(84, 198, NULL, '2026-07-18 16:00:00', 980.00, 'Transferencia', 'Completada', NULL, NULL),
(85, 199, NULL, '2026-08-03 09:00:00', 550.00, 'Efectivo', 'Completada', NULL, NULL),
(86, 200, NULL, '2026-08-15 12:30:00', 1150.00, 'Tarjeta', 'Completada', NULL, NULL),
(87, 201, NULL, '2026-09-01 10:20:00', 890.00, 'Transferencia', 'Completada', NULL, NULL),
(88, 202, NULL, '2026-09-05 15:40:00', 410.00, 'Efectivo', 'Completada', NULL, NULL),
(89, 203, NULL, '2026-09-10 11:10:00', 1280.00, 'Tarjeta', 'Completada', NULL, NULL),
(90, 204, NULL, '2026-09-12 13:50:00', 760.00, 'Transferencia', 'Completada', NULL, NULL),
(91, 205, NULL, '2026-09-15 09:40:00', 620.00, 'Efectivo', 'Completada', NULL, NULL),
(92, 206, NULL, '2026-09-18 14:15:00', 1420.00, 'Tarjeta', 'Completada', NULL, NULL),
(93, 207, NULL, '2026-09-20 16:00:00', 950.00, 'Transferencia', 'Completada', NULL, NULL),
(94, 208, NULL, '2026-09-22 10:30:00', 480.00, 'Efectivo', 'Completada', NULL, NULL),
(95, 209, NULL, '2026-09-24 12:00:00', 1190.00, 'Tarjeta', 'Completada', NULL, NULL),
(96, 210, 55, '2026-09-25 12:43:48', 135.50, 'Transferencia', 'Completada', NULL, NULL),
(97, 211, NULL, '2026-01-10 11:00:00', 455.00, 'Efectivo', 'Completada', NULL, NULL),
(98, 212, NULL, '2026-01-15 14:30:00', 1200.00, 'Tarjeta', 'Completada', NULL, NULL),
(99, 213, NULL, '2026-02-02 09:15:00', 890.00, 'Transferencia', 'Completada', NULL, NULL),
(100, 214, NULL, '2026-02-14 16:00:00', 350.00, 'Efectivo', 'Completada', NULL, NULL),
(101, 215, NULL, '2026-03-05 10:20:00', 1450.00, 'Tarjeta', 'Completada', NULL, NULL),
(102, 216, NULL, '2026-03-20 12:00:00', 620.00, 'Transferencia', 'Completada', NULL, NULL),
(103, 217, NULL, '2026-04-04 11:30:00', 780.00, 'Efectivo', 'Completada', NULL, NULL),
(104, 218, NULL, '2026-04-18 15:10:00', 1100.00, 'Tarjeta', 'Completada', NULL, NULL),
(105, 219, NULL, '2026-05-02 09:40:00', 950.00, 'Transferencia', 'Completada', NULL, NULL),
(106, 220, NULL, '2026-05-20 13:00:00', 530.00, 'Efectivo', 'Completada', NULL, NULL),
(107, 221, NULL, '2026-06-01 10:00:00', 1320.00, 'Tarjeta', 'Completada', NULL, NULL),
(108, 222, NULL, '2026-06-15 14:20:00', 840.00, 'Transferencia', 'Completada', NULL, NULL),
(109, 223, NULL, '2026-07-03 11:10:00', 490.00, 'Efectivo', 'Completada', NULL, NULL),
(110, 224, NULL, '2026-07-22 16:40:00', 1500.00, 'Tarjeta', 'Completada', NULL, NULL),
(111, 225, NULL, '2026-08-05 09:30:00', 910.00, 'Transferencia', 'Completada', NULL, NULL),
(112, 226, NULL, '2026-08-19 12:15:00', 390.00, 'Efectivo', 'Completada', NULL, NULL),
(113, 227, NULL, '2026-09-02 10:45:00', 1250.00, 'Tarjeta', 'Completada', NULL, NULL),
(114, 228, NULL, '2026-09-10 15:20:00', 770.00, 'Transferencia', 'Completada', NULL, NULL),
(115, 229, NULL, '2026-09-14 11:00:00', 510.00, 'Efectivo', 'Completada', NULL, NULL),
(116, 230, NULL, '2026-09-16 13:30:00', 1400.00, 'Tarjeta', 'Completada', NULL, NULL),
(117, 231, NULL, '2026-09-18 09:15:00', 990.00, 'Transferencia', 'Completada', NULL, NULL),
(118, 232, NULL, '2026-09-20 16:00:00', 430.00, 'Efectivo', 'Completada', NULL, NULL),
(119, 233, NULL, '2026-09-22 10:30:00', 1150.00, 'Tarjeta', 'Completada', NULL, NULL),
(120, 234, NULL, '2026-09-23 12:45:00', 680.00, 'Transferencia', 'Completada', NULL, NULL),
(121, 235, NULL, '2026-09-24 15:00:00', 570.00, 'Efectivo', 'Completada', NULL, NULL),
(122, 185, 45, '2026-09-26 16:18:26', 380.00, 'Tarjeta', 'Completada', NULL, NULL),
(123, 185, NULL, '2026-09-27 13:02:03', 350.00, 'Efectivo', 'Completada', NULL, NULL),
(124, 222, NULL, '2026-09-28 22:03:31', 10800.00, 'Efectivo', 'Completada', NULL, NULL),
(125, 216, NULL, '2026-09-29 08:29:25', 8400.00, 'Efectivo', 'Completada', NULL, NULL);

--
-- Disparadores `venta`
--
DELIMITER $$
CREATE TRIGGER `trg_after_venta_insert_seguimiento` AFTER INSERT ON `venta` FOR EACH ROW BEGIN
    DECLARE v_nombre_cliente VARCHAR(120);
    SELECT CONCAT(nombre,' ',ap_paterno,' ',IFNULL(ap_materno,''))
    INTO v_nombre_cliente
    FROM Pacientes WHERE id_paciente = NEW.id_paciente;

    INSERT INTO seguimiento_clientes (id_paciente, nombre_cliente, fecha_hora)
    VALUES (NEW.id_paciente, v_nombre_cliente, NOW());
END
$$
DELIMITER ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id_cita`),
  ADD KEY `fk_cita_horario` (`id_horario`),
  ADD KEY `fk_cita_paciente` (`id_paciente`),
  ADD KEY `fk_cita_especialidad` (`id_especialidad`);

--
-- Indices de la tabla `compra`
--
ALTER TABLE `compra`
  ADD PRIMARY KEY (`id_compra`),
  ADD KEY `fk_compra_proveedor` (`id_proveedor`);

--
-- Indices de la tabla `consultorios`
--
ALTER TABLE `consultorios`
  ADD PRIMARY KEY (`id_consultorio`);

--
-- Indices de la tabla `detalle_compra`
--
ALTER TABLE `detalle_compra`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `fk_detalle_compra` (`id_compra`),
  ADD KEY `fk_detalle_medicamento` (`id_medicamento`);

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id_venta`,`id_medicamento`),
  ADD KEY `fk_dv_medicamento` (`id_medicamento`);

--
-- Indices de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  ADD PRIMARY KEY (`id_especialidad`),
  ADD UNIQUE KEY `nombre_especialidad` (`nombre_especialidad`);

--
-- Indices de la tabla `historial_cambios_pacientes`
--
ALTER TABLE `historial_cambios_pacientes`
  ADD PRIMARY KEY (`id_cambio`),
  ADD KEY `fk_historial_paciente` (`id_paciente`);

--
-- Indices de la tabla `historial_medico`
--
ALTER TABLE `historial_medico`
  ADD PRIMARY KEY (`id_historial`),
  ADD UNIQUE KEY `id_cita` (`id_cita`),
  ADD KEY `fk_hist_paciente` (`id_paciente`);

--
-- Indices de la tabla `horarios_disponibles`
--
ALTER TABLE `horarios_disponibles`
  ADD PRIMARY KEY (`id_horario`),
  ADD UNIQUE KEY `uq_horario` (`id_medico`,`fecha`,`hora_inicio`),
  ADD KEY `fk_hd_consultorio` (`id_consultorio`);

--
-- Indices de la tabla `medicamentos`
--
ALTER TABLE `medicamentos`
  ADD PRIMARY KEY (`id_medicamento`);

--
-- Indices de la tabla `medicos`
--
ALTER TABLE `medicos`
  ADD PRIMARY KEY (`id_medico`),
  ADD UNIQUE KEY `cedula_profesional` (`cedula_profesional`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `medico_consultorio`
--
ALTER TABLE `medico_consultorio`
  ADD PRIMARY KEY (`id_medico`,`id_consultorio`),
  ADD KEY `fk_mc_consultorio` (`id_consultorio`);

--
-- Indices de la tabla `medico_especialidad`
--
ALTER TABLE `medico_especialidad`
  ADD PRIMARY KEY (`id_medico`,`id_especialidad`),
  ADD KEY `fk_me_especialidad` (`id_especialidad`);

--
-- Indices de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id_paciente`),
  ADD UNIQUE KEY `curp` (`curp`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`),
  ADD UNIQUE KEY `uq_pacientes_correo` (`correo`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD UNIQUE KEY `id_cita` (`id_cita`);

--
-- Indices de la tabla `personal`
--
ALTER TABLE `personal`
  ADD PRIMARY KEY (`id_personal`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id_proveedor`);

--
-- Indices de la tabla `seguimiento_clientes`
--
ALTER TABLE `seguimiento_clientes`
  ADD PRIMARY KEY (`id_seguimiento`),
  ADD KEY `fk_seguimiento_paciente` (`id_paciente`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`);

--
-- Indices de la tabla `venta`
--
ALTER TABLE `venta`
  ADD PRIMARY KEY (`id_venta`),
  ADD KEY `fk_venta_paciente` (`id_paciente`),
  ADD KEY `fk_venta_cita` (`id_cita`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id_cita` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT de la tabla `compra`
--
ALTER TABLE `compra`
  MODIFY `id_compra` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `consultorios`
--
ALTER TABLE `consultorios`
  MODIFY `id_consultorio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `detalle_compra`
--
ALTER TABLE `detalle_compra`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  MODIFY `id_especialidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `historial_cambios_pacientes`
--
ALTER TABLE `historial_cambios_pacientes`
  MODIFY `id_cambio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `historial_medico`
--
ALTER TABLE `historial_medico`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `horarios_disponibles`
--
ALTER TABLE `horarios_disponibles`
  MODIFY `id_horario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT de la tabla `medicamentos`
--
ALTER TABLE `medicamentos`
  MODIFY `id_medicamento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `medicos`
--
ALTER TABLE `medicos`
  MODIFY `id_medico` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id_paciente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=250;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `personal`
--
ALTER TABLE `personal`
  MODIFY `id_personal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `seguimiento_clientes`
--
ALTER TABLE `seguimiento_clientes`
  MODIFY `id_seguimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT de la tabla `venta`
--
ALTER TABLE `venta`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `fk_cita_especialidad` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidades` (`id_especialidad`),
  ADD CONSTRAINT `fk_cita_horario` FOREIGN KEY (`id_horario`) REFERENCES `horarios_disponibles` (`id_horario`),
  ADD CONSTRAINT `fk_cita_paciente` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id_paciente`) ON DELETE CASCADE;

--
-- Filtros para la tabla `compra`
--
ALTER TABLE `compra`
  ADD CONSTRAINT `fk_compra_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_compra`
--
ALTER TABLE `detalle_compra`
  ADD CONSTRAINT `fk_detalle_compra` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_medicamento` FOREIGN KEY (`id_medicamento`) REFERENCES `medicamentos` (`id_medicamento`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `fk_dv_medicamento` FOREIGN KEY (`id_medicamento`) REFERENCES `medicamentos` (`id_medicamento`),
  ADD CONSTRAINT `fk_dv_venta` FOREIGN KEY (`id_venta`) REFERENCES `venta` (`id_venta`) ON DELETE CASCADE;

--
-- Filtros para la tabla `historial_cambios_pacientes`
--
ALTER TABLE `historial_cambios_pacientes`
  ADD CONSTRAINT `fk_historial_paciente` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id_paciente`) ON DELETE CASCADE;

--
-- Filtros para la tabla `historial_medico`
--
ALTER TABLE `historial_medico`
  ADD CONSTRAINT `fk_hist_cita` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id_cita`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_hist_paciente` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id_paciente`) ON DELETE CASCADE;

--
-- Filtros para la tabla `horarios_disponibles`
--
ALTER TABLE `horarios_disponibles`
  ADD CONSTRAINT `fk_hd_consultorio` FOREIGN KEY (`id_consultorio`) REFERENCES `consultorios` (`id_consultorio`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_hd_medico` FOREIGN KEY (`id_medico`) REFERENCES `medicos` (`id_medico`) ON DELETE CASCADE;

--
-- Filtros para la tabla `medicos`
--
ALTER TABLE `medicos`
  ADD CONSTRAINT `fk_medico_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `medico_consultorio`
--
ALTER TABLE `medico_consultorio`
  ADD CONSTRAINT `fk_mc_consultorio` FOREIGN KEY (`id_consultorio`) REFERENCES `consultorios` (`id_consultorio`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mc_medico` FOREIGN KEY (`id_medico`) REFERENCES `medicos` (`id_medico`) ON DELETE CASCADE;

--
-- Filtros para la tabla `medico_especialidad`
--
ALTER TABLE `medico_especialidad`
  ADD CONSTRAINT `fk_me_especialidad` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidades` (`id_especialidad`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_me_medico` FOREIGN KEY (`id_medico`) REFERENCES `medicos` (`id_medico`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD CONSTRAINT `fk_paciente_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pago_cita` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id_cita`) ON DELETE CASCADE;

--
-- Filtros para la tabla `personal`
--
ALTER TABLE `personal`
  ADD CONSTRAINT `fk_personal_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `seguimiento_clientes`
--
ALTER TABLE `seguimiento_clientes`
  ADD CONSTRAINT `fk_seguimiento_paciente` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id_paciente`) ON DELETE CASCADE;

--
-- Filtros para la tabla `venta`
--
ALTER TABLE `venta`
  ADD CONSTRAINT `fk_venta_cita` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id_cita`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_venta_paciente` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id_paciente`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
