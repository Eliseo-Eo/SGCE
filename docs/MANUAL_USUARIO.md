# Manual de usuario - SGCE 1.0.122

## Roles principales

- Administrador: Gestiona todo el sistema.
- Administrativo: Apoya procesos escolares según permisos.
- Maestro: Captura asistencia, calificaciones y planeaciones asignadas.

## Módulos principales

- Inicio.
- Docentes.
- Grupos.
- Materias.
- Alumnos.
- Asignaciones.
- Asistencia.
- Calificaciones.
- Planeaciones.
- Expedientes.
- Reportes.
- Ciclos y periodos.
- Migración.
- Respaldos.
- Configuración.

## Operación básica

1. Captura o importa docentes.
2. Captura o importa grupos.
3. Captura o importa materias por grupo.
4. Captura o importa alumnos.
5. Crea asignaciones de docentes a materias/grupos.
6. Los maestros capturan asistencia y calificaciones.
7. Administración revisa reportes, expedientes y planeaciones.

## Migración de ciclo

La migración sirve para pasar de un ciclo escolar a otro.

El sistema:

- Conserva intacto el ciclo anterior.
- Crea los grupos equivalentes del ciclo nuevo.
- Copia materias por grupo.
- Promueve alumnos a la siguiente etapa.
- Egresa alumnos de la etapa terminal.
- Copia asignaciones/docentes solo si el administrador lo marca.
- Genera respaldo antes de ejecutar.

Antes de migrar, revisa el diagnóstico y ejecuta simulación.

## Planeaciones

Por defecto, las planeaciones están configuradas por ciclo. Esto significa una planeación por materia durante todo el ciclo escolar, salvo que la escuela configure otro tipo de entrega.

## Respaldo

Antes de importaciones grandes o migraciones, crea respaldo desde el módulo correspondiente.
