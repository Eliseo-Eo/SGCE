# SGCE Version 1 - Control académico por ciclo escolar

Esta versión rediseña el control escolar para conservar historial por ciclo y evitar que las boletas, asistencias o calificaciones de un grado se mezclen con otro.

## Cambios principales

- Los grupos ahora pertenecen a un ciclo escolar específico (`Grupos.CicloId`).
- Las asignaciones de materias/docentes pertenecen a un ciclo (`Asignaciones.CicloId`).
- Las asistencias se guardan por ciclo (`Asistencias.CicloId`).
- Se agregó `AlumnoInscripciones` para registrar en qué grupo estuvo cada alumno durante cada ciclo escolar.
- La boleta individual se genera usando el ciclo del periodo seleccionado.
- El expediente del alumno permite revisar ciclos anteriores sin tomar el grupo actual.
- Se agregó exportación de historial académico completo tipo certificado.
- Configuración incluye migración segura de ciclo escolar.

## Migración escolar

Desde Configuración se puede migrar:

- Un grupo específico: por ejemplo, 1B del ciclo cerrado pasa a 2B del ciclo activo.
- Un ciclo completo: todos los grupos del ciclo cerrado se procesan de una sola vez.

Reglas:

- Solo se puede migrar desde un ciclo inactivo/cerrado.
- El destino siempre debe ser el ciclo activo.
- No se renombra el grupo viejo; se crea o reutiliza el grupo equivalente del nuevo ciclo.
- Primero pasa a segundo.
- Segundo pasa a tercero.
- Tercero queda como egresado.
- Si un alumno ya tiene inscripción en el ciclo destino, se omite como conflicto para evitar duplicados.

## Flujo recomendado

1. Terminar de capturar/calcular el ciclo actual.
2. En Configuración, crear/activar el nuevo ciclo escolar.
3. Verificar que el ciclo anterior quedó inactivo.
4. Usar “Migrar ciclo completo” o “Migrar grupo”.
5. Importar alumnos nuevos de primaria en primer grado del ciclo activo.
6. Revisar expedientes y boletas por periodo/ciclo.

## Seguridad de historial

No se debe modificar directamente el grado de un grupo histórico. SGCE Version 1 conserva el grupo original para que el historial de primero, segundo y tercero se mantenga separado.
