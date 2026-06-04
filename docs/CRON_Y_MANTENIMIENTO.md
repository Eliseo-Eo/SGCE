# Cron y mantenimiento SGCE 1.0.91

## Respaldos

Scripts disponibles:

- `cron/backup_diario.php`
- `cron/backup_semanal.php`

Ejemplo:

```bash
php /ruta/SGCE/cron/backup_diario.php
```

## Archivado de bitácora

Script:

```bash
php /ruta/SGCE/cron/archivar_bitacora.php
```

Mueve movimientos antiguos a `BitacoraMovimientosArchivo` para mantener ágil la bitácora principal.

## Reindexar búsqueda

```bash
php /ruta/SGCE/tools/ReindexarBusqueda.php
```

Regenera columnas de búsqueda normalizada para alumnos, usuarios, materias, asignaciones y bitácora.

## Migraciones técnicas

```bash
php /ruta/SGCE/tools/AplicarMigraciones.php
```

Las migraciones técnicas son para mantenimiento del esquema. No son la promoción académica de alumnos.
