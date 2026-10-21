-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-10-2026 a las 17:50:10
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes`
--

CREATE TABLE `pacientes` (
  `id_paciente` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(20) DEFAULT NULL,
  `fecha_nacimiento` date NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) NOT NULL,
  `estatus` varchar(20) NOT NULL DEFAULT 'Activo',
  `contrasena` varchar(20) DEFAULT NULL,
  `estado` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pacientes`
--

INSERT INTO `pacientes` (`id_paciente`, `nombre`, `apellido`, `fecha_nacimiento`, `telefono`, `correo`, `fecha_registro`, `id_usuario`, `estatus`, `contrasena`, `estado`) VALUES
(180, 'Esteban', 'García', '1990-07-07', '7771115001', 'esteban51.perez@correo.com', '2026-09-25 12:26:42', 60, 'Activo', '123456789', NULL),
(181, 'Andrea', 'Cruz', '1996-09-18', '7771115002', 'andrea52.gomez@correo.com', '2026-09-25 12:26:42', 61, 'Activo', NULL, NULL),
(182, 'Ricardo', 'Jiménez', '1982-03-22', '7771115003', 'ricardo53.silva@correo.com', '2026-09-25 12:26:42', 62, 'Activo', NULL, NULL),
(183, 'Mónica', 'Domínguez', '1991-11-05', '7771115004', 'monica54.lopez@correo.com', '2026-09-25 12:26:42', 63, 'Activo', NULL, NULL),
(184, 'Jorge', 'Cruz', '1984-05-14', '7771115005', 'jorge55.hernandez@correo.com', '2026-09-25 12:26:42', 64, 'Activo', NULL, NULL),
(185, 'Alberto', 'López', '1978-12-10', '7772221001', 'alberto.sanchez@correo.com', '2026-09-25 12:28:01', 65, 'Activo', NULL, NULL),
(186, 'Rosa', 'Pérez', '1997-02-15', '7772221002', 'rosa.jimenez@correo.com', '2026-09-25 12:28:01', 66, 'Activo', NULL, NULL),
(187, 'Luis', 'Torres', '1989-06-28', '7772221003', 'luis.ramirez@correo.com', '2026-09-25 12:28:01', 67, 'Activo', NULL, NULL),
(188, 'Patricia', 'Flores', '1995-10-04', '7772221004', 'patricia.gomez@correo.com', '2026-09-25 12:28:01', 68, 'Activo', NULL, NULL),
(189, 'Manuel', 'Castro', '1981-01-19', '7772221005', 'manuel.ortiz@correo.com', '2026-09-25 12:28:01', 69, 'Activo', NULL, NULL),
(190, 'Adriana', 'Morales', '1992-07-11', '7772221006', 'adriana.ruiz@correo.com', '2026-09-25 12:28:01', 70, 'Activo', NULL, NULL),
(191, 'Francisco', 'Navarro', '1986-04-03', '7772221007', 'francisco.vega@correo.com', '2026-09-25 12:28:01', 71, 'Activo', NULL, NULL),
(192, 'Beatriz', 'Rojas', '1999-12-20', '7772221008', 'beatriz.soto@correo.com', '2026-09-25 12:28:01', 72, 'Activo', NULL, NULL),
(193, 'Gabriel', 'Paz', '1984-08-14', '7772221009', 'gabriel.morales@correo.com', '2026-09-25 12:28:01', 73, 'Activo', NULL, NULL),
(194, 'Elena', 'Ríos', '1991-03-29', '7772221010', 'elena.castillo@correo.com', '2026-09-25 12:28:01', 74, 'Activo', NULL, NULL),
(195, 'Sergio', 'Meza', '1977-11-02', '7772221011', 'sergio.paredes@correo.com', '2026-09-25 12:28:01', 75, 'Activo', NULL, NULL),
(196, 'Natalia', 'Alanis', '2001-05-18', '7772221012', 'natalia.rios@correo.com', '2026-09-25 12:28:01', 76, 'Activo', NULL, NULL),
(197, 'Arturo', 'Solares', '1988-09-12', '7772221013', 'arturo.medina@correo.com', '2026-09-25 12:28:01', 77, 'Activo', NULL, NULL),
(198, 'Carmen', 'Duarte', '1994-01-25', '7772221014', 'carmen.silva@correo.com', '2026-09-25 12:28:01', 78, 'Activo', NULL, NULL),
(199, 'Daniel', 'Rojas', '1983-10-09', '7772221015', 'daniel.fuentes@correo.com', '2026-09-25 12:28:01', 79, 'Activo', NULL, NULL),
(200, 'Laura', 'Vázquez', '1996-04-14', '7772221016', 'laura.luna@correo.com', '2026-09-25 12:28:01', 80, 'Activo', NULL, NULL),
(201, 'Humberto', 'Salinas', '1975-07-21', '7772221017', 'humberto.garza@correo.com', '2026-09-25 12:28:01', 81, 'Activo', NULL, NULL),
(202, 'Teresa', 'Mendoza', '1990-09-08', '7772221018', 'teresa.cruz@correo.com', '2026-09-25 12:28:01', 82, 'Activo', NULL, NULL),
(203, 'Mario', 'León', '1982-12-30', '7772221019', 'mario.benitez@correo.com', '2026-09-25 12:28:01', 83, 'Activo', NULL, NULL),
(204, 'Julieta', 'Estrada', '1998-06-05', '7772221020', 'julieta.morales@correo.com', '2026-09-25 12:28:01', 84, 'Activo', NULL, NULL),
(205, 'Fernando', 'Guerrero', '1987-03-17', '7772221021', 'fernando.ramos@correo.com', '2026-09-25 12:28:01', 85, 'Activo', NULL, NULL),
(206, 'Sofía', 'Ponce', '2002-08-22', '7772221022', 'sofia.duarte@correo.com', '2026-09-25 12:28:01', 86, 'Activo', NULL, NULL),
(207, 'Ignacio', 'Valdez', '1979-10-01', '7772221023', 'ignacio.lara@correo.com', '2026-09-25 12:28:01', 87, 'Activo', NULL, NULL),
(208, 'Paula', 'Navas', '1993-12-14', '7772221024', 'paula.mendez@correo.com', '2026-09-25 12:28:01', 88, 'Activo', NULL, NULL),
(209, 'Vicente', 'Perez', '1985-05-27', '7772221025', 'vicente.zuniga@correo.com', '2026-09-25 12:28:01', 89, 'Activo', NULL, NULL),
(210, 'Amairani', 'Rivera', '2000-01-01', '8117923748', 'amai@gmail.com', '2026-09-25 12:42:28', 90, 'Activo', NULL, NULL),
(211, 'Héctor', 'Jiménez', '1985-03-12', '7774441001', 'hector.vargas@correo.com', '2026-09-25 16:23:31', 91, 'Activo', NULL, NULL),
(212, 'Valeria', 'Soto', '1992-05-18', '7774441002', 'valeria.rojas@correo.com', '2026-09-25 16:23:31', 92, 'Activo', NULL, NULL),
(213, 'Esteban', 'Lara', '1978-11-22', '7774441003', 'esteban.ponce@correo.com', '2026-09-25 16:23:31', 93, 'Activo', NULL, NULL),
(214, 'Diana', 'Ríos', '1995-07-04', '7774441004', 'diana.medina@correo.com', '2026-09-25 16:23:31', 94, 'Activo', NULL, NULL),
(215, 'Mauricio', 'Cruz', '1988-12-30', '7774441005', 'mauricio.salazar@correo.com', '2026-09-25 16:23:31', 95, 'Activo', NULL, NULL),
(216, 'Brenda', 'Navarro', '1990-04-15', '7774441006', 'brenda.flores@correo.com', '2026-09-25 16:23:31', 96, 'Activo', NULL, NULL),
(217, 'Gabriel', 'Meza', '1982-09-09', '7774441007', 'gabriel.ortega@correo.com', '2026-09-25 16:23:31', 97, 'Activo', NULL, NULL),
(218, 'Jimena', 'Pacheco', '1999-01-25', '7774441008', 'jimena.castro@correo.com', '2026-09-25 16:23:31', 98, 'Activo', NULL, NULL),
(219, 'Raúl', 'Acosta', '1975-06-11', '7774441009', 'raul.dominguez@correo.com', '2026-09-25 16:23:31', 99, 'Activo', NULL, NULL),
(220, 'Claudia', 'Valdez', '1993-10-30', '7774441010', 'claudia.nunez@correo.com', '2026-09-25 16:23:31', 100, 'Activo', NULL, NULL),
(221, 'Alejandro', 'Campos', '1980-08-05', '7774441011', 'alejandro.rios@correo.com', '2026-09-25 16:23:31', 101, 'Activo', NULL, NULL),
(222, 'Daniela', 'Mora', '1997-02-14', '7774441012', 'daniela.serrano@correo.com', '2026-09-25 16:23:31', 102, 'Activo', NULL, NULL),
(223, 'Federico', 'Vega', '1986-12-03', '7774441013', 'federico.blanco@correo.com', '2026-09-25 16:23:31', 103, 'Activo', NULL, NULL),
(224, 'Sara', 'Estrada', '1994-06-21', '7774441014', 'sara.lira@correo.com', '2026-09-25 16:23:31', 104, 'Activo', NULL, NULL),
(225, 'Ignacio', 'Murillo', '1979-04-19', '7774441015', 'ignacio.trujillo@correo.com', '2026-09-25 16:23:31', 105, 'Activo', NULL, NULL),
(226, 'Mónica', 'Peña', '1991-09-08', '7774441016', 'monica.alvarez@correo.com', '2026-09-25 16:23:31', 106, 'Activo', NULL, NULL),
(227, 'Leonardo', 'Sosa', '1983-01-27', '7774441017', 'leonardo.navarro@correo.com', '2026-09-25 16:23:31', 107, 'Activo', NULL, NULL),
(228, 'Teresa', 'Benítez', '1996-11-04', '7774441018', 'teresa.guerrero@correo.com', '2026-09-25 16:23:31', 108, 'Activo', NULL, NULL),
(229, 'Arturo', 'Leal', '1976-05-30', '7774441019', 'arturo.pacheco@correo.com', '2026-09-25 16:23:31', 109, 'Activo', NULL, NULL),
(230, 'Beatriz', 'Rangel', '1998-08-12', '7774441020', 'beatriz.galindo@correo.com', '2026-09-25 16:23:31', 110, 'Activo', NULL, NULL),
(231, 'Samuel', 'Puente', '1984-03-01', '7774441021', 'samuel.cordero@correo.com', '2026-09-25 16:23:31', 111, 'Activo', NULL, NULL),
(232, 'Adriana', 'Padilla', '2000-12-05', '7774441022', 'adriana.vela@correo.com', '2026-09-25 16:23:31', 112, 'Activo', NULL, NULL),
(233, 'Vicente', 'Roldán', '1981-07-16', '7774441023', 'vicente.esquivel@correo.com', '2026-09-25 16:23:31', 113, 'Activo', NULL, NULL),
(234, 'Rosa', 'Díaz', '1995-10-10', '7774441024', 'rosa.peralta@correo.com', '2026-09-25 16:23:31', 114, 'Inactivo', NULL, NULL),
(235, 'Ernesto', 'Cervantes', '1987-02-28', '7774441025', 'ernesto.lozano@correo.com', '2026-09-25 16:23:31', 115, 'Activo', NULL, NULL),
(241, 'Carlos', 'Ríos', '1990-01-01', '7771234567', 'carlos.gomez@correo.com', '2026-09-27 12:53:48', 1, 'Activo', NULL, NULL),
(243, 'Martina', 'Flores', '1995-01-01', '7771112233', 'martina.reyes.test@correo.com', '2026-09-27 13:32:51', 120, 'Activo', NULL, NULL),
(245, 'Ana', 'Gil', '1995-04-10', '7771112222', 'ana.torres.nueva@correo.com', '2026-09-28 21:24:19', 122, 'Inactivo', NULL, NULL),
(246, 'Leonel', 'Farias', '2007-07-06', '7777354244', 'leoestra5662@gmail.com', '2026-09-29 08:22:45', 123, 'Activo', NULL, NULL),
(253, 'x', 'x', '1990-08-09', '7', 'x@gmail.com', '2026-10-15 09:44:14', 127, 'Activo', 'qwertyuio', NULL),
(255, 'b', 'b', '1000-08-09', '6', 'x1@gmail.com', '2026-10-15 09:47:04', 129, 'Activo', '987654321', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id_paciente`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`),
  ADD UNIQUE KEY `uq_pacientes_correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id_paciente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=256;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD CONSTRAINT `fk_paciente_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
