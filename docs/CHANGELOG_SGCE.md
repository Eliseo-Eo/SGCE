# Changelog SGCE

## 1.0.2 — Motor multiescolar

- Agregado soporte para primaria, secundaria, bachillerato, universidad, maestría, doctorado, cursos y diplomados.
- Agregadas tablas `OfertasEducativas`, `EtapasAcademicas` y `Carreras`.
- `Grupos` ahora puede depender de oferta educativa, etapa académica y carrera/programa.
- `AlumnoInscripciones` conserva historial por ciclo, grupo, etapa y carrera.
- La migración ya no usa la regla fija de secundaria; ahora usa la siguiente etapa académica configurada.
- El instalador permite configurar nivel educativo, periodización, cantidad de etapas y carreras iniciales.
- Configuración permite revisar/actualizar la estructura académica.
- En universidad/bachillerato técnico, grupos iguales pueden existir en carreras diferentes.
- Kardex histórico congela oferta, carrera y etapa para proteger boletas antiguas.

## 1.0.1 — Ciclos, kardex e interinatos

- Agregado kardex congelado por alumno/ciclo.
- Agregado historial de docentes por asignación.
- Bloqueos de seguridad para no eliminar docentes con asignaciones activas.
- La materia queda estable y el maestro funciona como responsable actual.

## 1.0.0 — Base SGCE

- Módulos principales de alumnos, maestros, grupos, asignaciones, calificaciones, asistencias, reportes y configuración.
