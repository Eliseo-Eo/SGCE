# Revisión técnica de funciones - SGCE

## Rendimiento

- Alumnos: listado paginado desde MySQL, filtros preparados e índices por estado, grupo y nombre.
- Asignaciones: listado paginado desde MySQL con filtros por maestro, grupo y búsqueda.
- Bitácora: consulta limitada por defecto, paginación real e índices por fecha, usuario, rol y acción.
- Reportes: validación de rangos y protección contra consultas demasiado amplias.
- Asistencia: índices por fecha, asignación, alumno y estado.
- Calificaciones: índices por periodo, asignación, alumno y calificación.
- Dashboard: carga únicamente contadores y resúmenes necesarios.

## Seguridad

- Instalador bloqueado después de instalar mediante `storage/install.lock`.
- Login con consulta preparada, límite de intentos y rehash automático de contraseña.
- Sesiones con cookie segura, `HttpOnly`, `SameSite=Strict` y regeneración de ID.
- CSRF en formularios POST.
- Validación estricta de archivos subidos.
- Restauración limitada a respaldos oficiales SGCE.
- Consulta pública con coincidencia exacta y límite de intentos.

## Estructura

- `repositories/`: consultas SQL optimizadas.
- `services/`: lógica de negocio reutilizable.
- `modules/`: pantallas internas.
- `reports/`: exportaciones.
- `public/`: consultas públicas.
- `tests/`: validación estática.

## Estado

Paquete listo para instalación limpia y prueba funcional completa en servidor real.
