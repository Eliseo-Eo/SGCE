# Migración de ciclo escolar — SGCE 1.0.122

## Objetivo

La migración escolar prepara un ciclo nuevo completo sin borrar el ciclo anterior. El ciclo anterior queda como historial académico y el ciclo activo nuevo queda listo para operar.

## Principio principal

La migración no es solo mover alumnos. También debe preparar estructura académica:

1. Crear o reactivar grupos equivalentes en el ciclo nuevo.
2. Conservar grupos de nuevo ingreso vacíos para alumnos nuevos.
3. Copiar materias por grupo al ciclo nuevo.
4. Copiar asignaciones/docentes solo si el administrador lo solicita.
5. Promover alumnos de etapas intermedias.
6. Egresar alumnos de la etapa terminal.
7. Congelar kardex del ciclo origen.
8. Conservar intacto el ciclo anterior.

## Enfoque multinivel

El sistema no asume que todas las escuelas son secundaria. La migración usa la configuración de la oferta educativa y el orden de `EtapasAcademicas`.

Ejemplos:

- Primaria: 1° a 6°.
- Secundaria: 1° a 3°.
- Bachillerato: Semestres, años o cuatrimestres.
- Universidad: Semestres, cuatrimestres, módulos o etapas configuradas.
- Cursos: Niveles o módulos.

La última etapa configurada se considera terminal y sus alumnos egresan.

## Qué se conserva del ciclo anterior

El ciclo anterior conserva:

- Grupos.
- Materias por grupo.
- Asignaciones.
- Alumnos inscritos históricamente.
- Calificaciones.
- Asistencias.
- Planeaciones.
- Kardex congelado.
- Bitácora.

Nada del ciclo anterior debe borrarse durante la migración.

## Qué se crea en el ciclo nuevo

Para cada grupo activo del ciclo origen, el sistema crea o reactiva un grupo equivalente en el ciclo destino con la misma oferta, programa, etapa, grupo y turno.

Ejemplo secundaria:

- 1° A del ciclo anterior crea 1° A del ciclo nuevo vacío.
- 2° A del ciclo anterior crea 2° A del ciclo nuevo.
- 3° A del ciclo anterior crea 3° A del ciclo nuevo.

Después, los alumnos se promueven:

- Alumnos de 1° A pasan a 2° A del ciclo nuevo.
- Alumnos de 2° A pasan a 3° A del ciclo nuevo.
- Alumnos de 3° A quedan egresados.

## Copia de materias por grupo

Las materias se copian como plantilla del mismo grado, semestre, módulo o etapa del ciclo anterior hacia el grupo equivalente del ciclo nuevo.

Ejemplo correcto:

- Materias de 1° A 2026-2027 se copian a 1° A 2027-2028.
- Materias de 2° A 2026-2027 se copian a 2° A 2027-2028.
- Materias de 3° A 2026-2027 se copian a 3° A 2027-2028.

Esto evita que un grupo nuevo quede sin materias y evita copiar materias de una etapa equivocada.

## Copia segura de asignaciones

La copia de asignaciones es opcional.

Si el administrador marca la opción, el sistema copia asignaciones con docente activo hacia el grupo equivalente del ciclo nuevo.

Si una materia no existe o el docente está inactivo, la asignación se omite y se reporta.

Recomendación operativa: Usar la copia de asignaciones como plantilla inicial y revisar manualmente cargas docentes del nuevo ciclo.

## Diagnóstico previo

Antes de migrar, el panel muestra:

- Ciclo origen.
- Ciclo destino activo.
- Periodos disponibles.
- Grupos origen.
- Grupos a preparar.
- Materias a copiar.
- Alumnos inscritos.
- Alumnos a promover.
- Alumnos a egresar.
- Estado general de validación.

Si falta algo crítico, el sistema bloquea la migración.

## Seguridad

La migración incluye:

- Acceso solo para administrador.
- CSRF en acciones POST.
- Confirmación fuerte escrita.
- Respaldo obligatorio automático.
- Respaldo obligatorio antes de modificar datos.
- Transacción completa.
- Bloqueo de migración simultánea.
- Doble migración bloqueada por registro histórico.
- Bloqueo de doble migración.
- Registro formal en `MigracionesCiclo`.
- Bitácora de acciones.

## Flujo recomendado

1. Crear y activar el nuevo ciclo escolar.
2. Verificar que el ciclo destino tenga periodos.
3. Entrar a Migración.
4. Revisar diagnóstico previo.
5. Ejecutar simulación.
6. Decidir si se copiarán asignaciones/docentes.
7. Escribir la confirmación exacta.
8. Ejecutar migración.
9. Revisar grupos, materias, alumnos, asignaciones y kardex del ciclo activo.

## Resultado esperado

Después de migrar, el ciclo nuevo debe tener:

- Todos los grupos equivalentes.
- Grupos de nuevo ingreso vacíos.
- Materias por grupo copiadas.
- Alumnos promovidos a la etapa siguiente.
- Alumnos terminales egresados.
- Kardex congelado del ciclo anterior.

El ciclo anterior debe seguir disponible como historial.
