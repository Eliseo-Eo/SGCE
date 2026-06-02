# SGCE Version 1.0.1 - Ciclos, Kardex e Interinatos

## Objetivo

Esta versión refuerza el modelo escolar para secundaria: el alumno avanza de grado por ciclo escolar, conserva boletas históricas y permite cambios de docente por interinato sin romper la materia, calificaciones ni asistencias.

## Modelo correcto

SGCE 1.0.1 mantiene un solo registro del alumno en `Alumnos` y guarda su trayectoria en `AlumnoInscripciones`:

- 2024-2025 -> 1B -> PROMOVIDO
- 2025-2026 -> 2B -> PROMOVIDO
- 2026-2027 -> 3B -> INSCRITO / EGRESADO

Los grupos no se renombran. El sistema crea o reutiliza el grupo equivalente del nuevo ciclo:

- 1B del ciclo cerrado no se convierte físicamente en 2B.
- Se crea/reutiliza 2B del ciclo activo.
- El historial del 1B permanece intacto.

## Kardex congelado

Al migrar alumnos, SGCE congela su historial del ciclo origen en:

- `KardexAlumno`
- `KardexDetalle`

Esto genera una fotografía oficial de materias, parciales, promedio, grado, grupo, turno y ciclo. El historial académico PDF usa primero este kardex congelado; si todavía no existe kardex, usa cálculo dinámico como respaldo.

## Interinatos y cambios de docente

En secundaria es común que un docente cubra una materia temporalmente. Por eso, SGCE 1.0.1 separa la materia del docente:

- `MateriasCatalogo` conserva el nombre estable de la materia.
- `Asignaciones` conserva la materia/grupo/ciclo.
- `AsignacionDocenteHistorial` registra titulares, relevos e interinatos.

Si una asignación ya tiene calificaciones o asistencias:

- No se permite cambiar materia.
- No se permite cambiar grupo.
- Sí se permite cambiar docente como relevo/interinato.
- Las calificaciones y asistencias siguen ligadas a la misma asignación.

## Validaciones nuevas

### Docentes

No se puede desactivar un docente si tiene asignaciones activas en el ciclo actual. Primero se debe editar la asignación y registrar el relevo/interinato a otro docente.

### Asignaciones

No se puede desactivar una asignación si ya tiene calificaciones o asistencias. Si cambió el maestro, se debe editar la asignación y cambiar solo el docente.

### Materias por grupo

Solo debe existir una asignación activa por ciclo + grupo + materia. Si cambia el maestro, se usa la asignación existente y se registra el relevo.

### Migración escolar

La migración solo permite mover desde ciclos inactivos/cerrados hacia el ciclo activo. Antes de promover o egresar, SGCE congela el kardex del ciclo origen.

Reglas:

- 1° -> 2°
- 2° -> 3°
- 3° -> EGRESADO

## Flujo recomendado para fin de ciclo

1. Revisar que todas las calificaciones del ciclo actual estén capturadas.
2. Cerrar/inactivar el ciclo que terminó.
3. Crear/activar el nuevo ciclo escolar.
4. Usar Configuración -> Migración de ciclo escolar.
5. Migrar un grupo o el ciclo completo.
6. Revisar el resumen de promovidos, egresados, kardex congelados y conflictos.
7. Importar los nuevos alumnos de primer grado.
8. Ajustar asignaciones y docentes del nuevo ciclo.

## Flujo para interinato

1. Ir a Admin -> Asignaciones.
2. Buscar la materia/grupo.
3. Editar la asignación.
4. Cambiar el docente responsable.
5. Escribir el motivo, por ejemplo: `INTERINATO POR CONTRATO TEMPORAL`.
6. Guardar.

SGCE registrará el movimiento sin alterar materia, grupo, calificaciones ni asistencias.

## Tablas agregadas

- `MateriasCatalogo`
- `AsignacionDocenteHistorial`
- `KardexAlumno`
- `KardexDetalle`

## Archivos principales modificados

- `install/SGCE.sql`
- `includes/SGCE_Helpers.php`
- `modules/admin/AdminAcciones.php`
- `modules/admin/AdminVista.php`
- `modules/ConfiguracionAdmin.php`
- `reports/ExportarHistorialAlumno.php`

## Nota técnica

Esta versión está diseñada para que los registros históricos no dependan de que un docente siga activo. Los docentes pueden desactivarse si ya no tienen asignaciones actuales, pero su nombre permanece disponible en los reportes históricos y en el kardex.
