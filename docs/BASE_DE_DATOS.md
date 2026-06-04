# Base de datos SGCE 1.0.91

El esquema oficial está en `install/SGCE.sql` y está pensado para instalación limpia desde cero.

## Entidades principales

- `Usuarios`
- `CiclosEscolares`
- `OfertasEducativas`
- `ProgramasEducativos`
- `EtapasAcademicas`
- `Grupos`
- `Alumnos`
- `AlumnoInscripciones`
- `MateriasCatalogo`
- `MateriasGrupo`
- `Asignaciones`
- `Calificaciones`
- `Asistencias`
- `Planeaciones`
- `KardexAlumno`
- `KardexDetalle`
- `BitacoraMovimientos`
- `BitacoraMovimientosArchivo`
- `SchemaMigrations`

## Notas de diseño

- `AlumnoInscripciones` conserva el historial por ciclo.
- `Alumnos.GrupoId` se usa como acceso rápido al grupo actual.
- `MateriasGrupo` representa una materia dentro de un grupo concreto.
- `Asignaciones` vincula docente, materia y grupo.
- `Asistencias` y `Calificaciones` tienen restricciones únicas para evitar duplicados.
- Las columnas de búsqueda normalizada ayudan a buscar sin acentos y con mejor rendimiento.
