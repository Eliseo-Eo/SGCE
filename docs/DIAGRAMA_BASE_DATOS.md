# Diagrama simple de base de datos - SGCE 1.0.91

Este diagrama resume las relaciones principales del sistema. No sustituye el esquema oficial de `install/SGCE.sql`, pero sirve para mantenimiento y orientación técnica.

## Núcleo académico

```text
OfertasEducativas
  └── EtapasAcademicas
        └── Grupos
              ├── MateriasGrupo
              │     └── Asignaciones
              │           ├── Calificaciones
              │           └── Asistencias
              └── AlumnoInscripciones
                    └── Alumnos
```

## Docentes y asignaciones

```text
Usuarios(Rol = maestro)
  └── Asignaciones
        └── MateriasGrupo
              └── Grupos
```

Una asignación une a un docente con una materia registrada para un grupo y ciclo escolar.

## Alumnos, grupos e historial

```text
Alumnos
  └── AlumnoInscripciones
        ├── CiclosEscolares
        └── Grupos
```

El historial se conserva por ciclo mediante `AlumnoInscripciones`, no únicamente por el grupo actual del alumno.

## Calificaciones

```text
Asignaciones
  └── Calificaciones
        ├── Alumnos
        └── Periodos
```

Las calificaciones dependen de la asignación, alumno y periodo correspondiente.

## Asistencias

```text
Asignaciones
  └── Asistencias
        └── Alumnos
```

Las asistencias se registran por fecha, alumno y asignación.

## Ciclos escolares

```text
CiclosEscolares
  ├── Grupos
  ├── MateriasGrupo
  ├── Asignaciones
  └── Periodos
```

Solo debe existir un ciclo activo. SGCE incluye normalización para evitar ciclos activos duplicados.

## Planeaciones

```text
Usuarios(Rol = maestro)
  └── Planeaciones
        ├── Grupos
        ├── Periodos
        └── CiclosEscolares
```

Las planeaciones se relacionan con docente, grupo, periodo y ciclo.

## Bitácora y mantenimiento

```text
Usuarios
  └── Bitacora

SchemaMigrations
  └── Registro de estructura instalada/aplicada
```

La bitácora registra acciones críticas. `SchemaMigrations` documenta la base instalada y migraciones técnicas aplicadas.
