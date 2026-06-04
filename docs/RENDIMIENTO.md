# Rendimiento SGCE 1.0.91

## Principios

- No cargar tablas grandes completas al navegador.
- Filtrar desde MySQL usando índices, ciclo activo, etapa, grupo, turno y fechas.
- Usar rangos obligatorios en reportes pesados.
- Archivar bitácora antigua mediante cron.

## Filtros parciales

Los filtros de Alumnos, Materias, Asignaciones y Bitácora usan endpoints parciales. La búsqueda por texto consulta desde 2 letras o al limpiar el campo; los filtros por select consultan de inmediato. El navegador actualiza solo:

- filas del `tbody`
- paginación
- contador cuando aplica
- modales de edición de la página actual

## Reportes pesados

Los reportes de asistencia deben trabajar con rango de fechas. Evita exportar históricos completos sin rango.


## Endpoints parciales

Los endpoints de `api/admin/` devuelven JSON con `tbody`, contador, paginación y modales de la página actual. En 1.0.91 esos fragmentos salen directamente de parciales PHP reutilizables, lo que reduce trabajo interno y evita depender de extracción de HTML.
