# Changelog SGCE

## Versión final

- Preparación de paquete limpio para instalación desde cero.
- Paginación real desde MySQL en módulos de alto crecimiento.
- Separación de consultas SQL en repositorios.
- Refuerzo de seguridad en instalador, login, sesiones, CSRF, archivos y respaldos.
- Normalización de cache de assets.
- Limpieza de rastros de paquetes anteriores.
- Manuales y README actualizados.

## 1.0.1 - Ciclos, kardex e interinatos

- Se agregó kardex congelado por alumno/ciclo para proteger boletas históricas.
- Se agregó historial de docentes por asignación para soportar relevos e interinatos.
- Se agregó catálogo de materias para separar materia estable de docente responsable.
- Se bloquea la desactivación de docentes con asignaciones activas en el ciclo actual.
- Se bloquea la desactivación de asignaciones con calificaciones o asistencias.
- Se protege materia y grupo cuando una asignación ya tiene datos académicos; solo se permite relevo docente.
- La migración de ciclo ahora congela kardex antes de promover o egresar alumnos.
- El historial académico PDF prioriza kardex congelado y usa cálculo dinámico solo como respaldo.
