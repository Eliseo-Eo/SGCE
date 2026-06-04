# Manual de usuario SGCE

## Administración

Desde el panel principal puedes administrar docentes, alumnos, grupos, materias, asignaciones, avisos, reportes, planeaciones, usuarios, ciclos y configuración.

## Configuración académica

La configuración académica define cómo trabaja la institución:

- Nivel educativo.
- Organización anual, semestral, cuatrimestral, trimestral o modular.
- Número de etapas académicas.
- Uso de programas educativos.
- Periodos de evaluación.
- Planeaciones por ciclo, periodo, unidad o semana.

Cuando ya existen grupos, calificaciones o planeaciones, SGCE bloquea cambios estructurales peligrosos para proteger el historial.

## Grupos y alumnos

Los grupos pertenecen a un ciclo, oferta, programa y etapa. Los alumnos se inscriben por ciclo mediante `AlumnoInscripciones`, lo que permite consultar boletas históricas sin mezclar generaciones.

## Planeaciones

Las planeaciones se administran por ciclo, oferta, programa educativo, docente y materia. Si un docente da la misma materia a varios grupos del mismo programa, no necesita subir la planeación varias veces.


## Migración de ciclo escolar

El módulo **Migración de ciclo escolar** es exclusivo para usuarios con rol **Administrador**. Permite migrar un grupo o un ciclo completo desde un ciclo cerrado/inactivo hacia el ciclo activo, congelando el kardex antes de promover alumnos para proteger boletas históricas.

Antes de ejecutar una migración, crea un respaldo completo desde el módulo de respaldos.

## Consulta pública

Si la institución usa programas educativos, la consulta pública solicita programa, etapa académica, grupo y turno para evitar ambigüedades.


## Módulo Materias

Antes de crear asignaciones docentes, registra las materias de cada grupo y las horas semanales correspondientes. El sistema no permite duplicar la misma materia en un grupo ni superar 40 horas semanales por grupo.

El archivo de importación puede usar columnas: Materia, Grado/Etapa, Grupo, Turno, Horas y Programa si aplica. En secundaria sin programas, el programa puede dejarse vacío y SGCE usará GENERAL.

Ejemplo: Ofimática puede registrarse en 1C, 2C y 3C porque son grupos distintos; lo que SGCE bloquea es registrar dos veces Ofimática en el mismo grupo del mismo ciclo.

## Reportes y rendimiento

Los reportes de asistencia piden rango de fechas. Para evitar que el sistema se vuelva lento con muchos años de información, SGCE limita exportaciones muy amplias y recomienda generar reportes por periodo, mes o ciclo.

La bitácora muestra por defecto los últimos 30 días. Puedes cambiar fechas desde los filtros.
