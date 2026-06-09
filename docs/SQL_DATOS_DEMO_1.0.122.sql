-- SGCE 1.0.122 - Datos demo opcionales
-- Ejecutar solo en una instalación de prueba recién instalada.
-- Usuario demo docente: DOCENTEDEMO / Demo_12345

INSERT INTO Usuarios (Username, Password, NombreCompleto, NombreBusqueda, Rol, Activo)
VALUES ('DOCENTEDEMO', '$2y$10$UjDCv3vTneD2t1PcQUPZr.w5IFzpRFyuCzKgCTt2apnl1fUwRAj5G', 'DOCENTE DEMO', 'DOCENTE DEMO', 'maestro', 1)
ON DUPLICATE KEY UPDATE Activo = 1;

-- Los grupos, materias y alumnos demo se recomiendan cargar desde importación para validar el flujo real.
