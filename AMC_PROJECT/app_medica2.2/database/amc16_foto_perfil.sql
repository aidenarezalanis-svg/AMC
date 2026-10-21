-- ============================================================
-- AMC-16  Almacenamiento de la foto en la base de datos
-- Ejecutar UNA vez en phpMyAdmin → app_medica_citas → pestaña SQL.
--
-- La base guarda solo el NOMBRE del archivo (ej. 3f9a...c1.jpg).
-- La imagen se guarda en la carpeta uploads/fotos/ del proyecto.
-- Así la tabla no crece con datos binarios y las consultas siguen rápidas.
-- ============================================================
USE app_medica_citas;

ALTER TABLE pacientes
  ADD COLUMN foto_perfil VARCHAR(255) NULL DEFAULT NULL
  COMMENT 'Nombre del archivo en uploads/fotos (NULL = sin foto)';
