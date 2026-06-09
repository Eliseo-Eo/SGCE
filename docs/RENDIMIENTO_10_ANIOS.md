# Plan de rendimiento a 10 años - SGCE 1.0.122

SGCE debe trabajar siempre por ciclo escolar activo y consultar el historial solo cuando el usuario lo pida. Esta versión refuerza esa estrategia.

## Tablas que más crecerán

1. `Asistencias`: puede llegar a millones de filas si se toma asistencia por materia o por clase.
2. `BitacoraMovimientos`: crece todos los días con acciones administrativas y docentes.
3. `KardexDetalle`: conserva snapshots históricos.
4. `Planeaciones`: crece por ciclo, docente y materia.

## Reglas de rendimiento

- Los módulos diarios deben filtrar por `CicloId`.
- Los reportes grandes deben salir en Excel/CSV; PDF debe reservarse para rangos pequeños.
- La bitácora activa se archiva con `cron/mantenimiento_diario.php`.
- Los ciclos cerrados deben consultarse principalmente desde kardex congelado.
- Los respaldos temporales y sesiones vencidas se limpian automáticamente.

## Cron recomendado

```bash
0 2 * * * php /ruta/SGCE/cron/mantenimiento_diario.php >> /ruta/SGCE/storage/logs/mantenimiento.log 2>&1
```

## Pruebas de crecimiento

Desarrollo incluye `RunGrowth10CyclesChecks.php`, que revisa estructura, índices y límites esperados para soportar varios ciclos.
