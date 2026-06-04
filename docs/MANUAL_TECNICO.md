# Manual técnico SGCE

## Modelo de datos académico

Las tablas principales son:

- `OfertasEducativas`
- `ConfiguracionesAcademicas`
- `ProgramasEducativos`
- `EtapasAcademicas`
- `CiclosEscolares`
- `PeriodosEvaluacion`
- `Grupos`
- `Alumnos`
- `AlumnoInscripciones`
- `MateriasCatalogo`
- `MateriasGrupo`
- `Asignaciones`
- `AsignacionDocenteHistorial`
- `Calificaciones`
- `Asistencias`
- `Planeaciones`
- `KardexAlumno`
- `KardexDetalle`

## Decisiones de integridad

- `Grupos.ProgramaId` es obligatorio. Para escuelas sin programa visible se usa el programa interno `GENERAL`.
- La llave única de grupos usa `CicloId`, `OfertaId`, `ProgramaId`, `EtapaId`, `Grupo` y `Turno`.
- Los periodos de evaluación pertenecen a `CicloId` y `OfertaId` y siempre se consultan con ambas claves.
- Las materias se registran primero en `MateriasCatalogo` y después en `MateriasGrupo` para definir qué materia corresponde a cada grupo y cuántas horas semanales tiene.
- `MateriasGrupo` evita duplicar la misma materia en el mismo grupo durante el mismo ciclo y valida que el grupo no supere 40 horas semanales.
- `Asignaciones` solo vincula un docente a una materia previamente registrada para un grupo.
- Las planeaciones pertenecen a `CicloId`, `OfertaId`, `ProgramaId`, `MaestroId`, `MateriaNombre` y número de entrega.
- No se permite cambiar la estructura de periodos si ya existen calificaciones.
- No se permite cambiar la estructura de planeaciones si ya existen archivos cargados.
- No se permite editar estructuralmente grupos con uso académico.
- Los cambios de docente se manejan como relevo/interinato, no como cambio de materia.
- Solo puede existir un ciclo escolar activo; la base de datos lo protege con `unico_ciclo_activo`.
- La baja ordinaria del alumno se registra en `AlumnoInscripciones.Estado`, no desactivando globalmente al alumno.

## Carga académica y horas

Las horas semanales se guardan en `MateriasGrupo`. Esto permite:

- Controlar que un grupo no rebase 40 horas por semana.
- Evitar capturar dos veces la misma materia en el mismo grupo.
- Saber cuántas horas toma cada docente al asignarle materias de grupo.
- Preparar reportes de carga académica y un futuro módulo de horarios.

## Respaldos y restauración

Los respaldos de solo datos incluyen `MateriasCatalogo` y `MateriasGrupo`, por lo que la estructura de materias por grupo se conserva al exportar e importar datos.

## Rendimiento

Las tablas grandes usan índices por ciclo, grupo, alumno, periodo, asignación, materia y estado. Esto permite separar consultas por ciclo escolar y evita recorrer todo el historial cuando se consulta el ciclo activo.

## SGCE 1.0.91 - Arquitectura y rendimiento

A partir de 1.0.91, `AdminVista.php` funciona como contenedor y las vistas del panel administrador están separadas en `views/admin/`.

`SGCE_Helpers.php` funciona como cargador y las funciones comunes están separadas por responsabilidad en `includes/`.

La revisión automática de esquema ya no se ejecuta en cada carga. Para instalaciones limpias, el esquema oficial es `install/SGCE.sql`. Para cambios futuros controlados se registra historial en `SchemaMigrations`.

Las búsquedas de alumnos, materias, asignaciones y bitácora usan columnas normalizadas e índices `FULLTEXT` cuando aplica. Para mantenimiento técnico se puede ejecutar:

```bash
php tools/ReindexarBusqueda.php
```

La edición de materias usa transacción y bloqueo del grupo con `FOR UPDATE` para evitar que operaciones simultáneas superen 40 horas semanales.

Los reportes de asistencia tienen rango máximo de 370 días por exportación para proteger rendimiento.
