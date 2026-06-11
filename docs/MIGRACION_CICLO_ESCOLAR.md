# Migración de ciclo escolar — SGCE 1.0.140

La migración promueve alumnos desde un ciclo origen hacia un ciclo destino, respetando estructura académica, grupos, programas y estados de inscripción.

## Reglas generales

- No se debe migrar desde un ciclo activo hacia sí mismo.
- El ciclo origen debe tener datos consistentes.
- El ciclo destino debe tener grupos preparados.
- La migración debe conservar historial y kardex.
- Los alumnos terminales no deben promoverse como grupo siguiente.

## Prueba recomendada

Antes de ejecutar migración real, genera respaldo completo desde el módulo Respaldos.

## Controles obligatorios

### Respaldo obligatorio

Antes de ejecutar una migración real, genera un respaldo completo desde el módulo Respaldos.

### Doble migración

El sistema debe evitar que el mismo alumno sea migrado dos veces al mismo ciclo destino.

### Copia de materias por grupo

Cuando el flujo lo requiera, las materias vinculadas al grupo origen deben revisarse contra el grupo destino para conservar continuidad académica.

### Copia segura de asignaciones

Las asignaciones deben copiarse o reconstruirse solo cuando existan docentes, materias y grupos válidos en el ciclo destino.
