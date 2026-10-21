-- ============================================================
-- Contraseñas de prueba para iniciar sesión en App Médica (Apex)
-- Ejecutar UNA vez en phpMyAdmin → app_medica_citas → pestaña SQL.
-- Todas las cuentas de abajo quedan con la contraseña:  Apex2026
-- (se guarda cifrada con bcrypt; no es texto plano)
-- ============================================================
USE app_medica_citas;

UPDATE usuarios SET contrasena_hash = '$2y$10$g45rI2ah1KKDq74Kj/eAwuyWYe4MKWFkBoEoP7ZHIAbbYWVgGNSou'
WHERE id_usuario IN (
  14,  -- Administrador: h.villareal@clinica.com
  11,  -- Recepcionista: d.cruz@clinica.com
  6,   -- Médico:        r.solis@clinica.com
  65   -- Paciente:      alberto.sanchez@correo.com  (CURP SALA781210HDFM01)
);
