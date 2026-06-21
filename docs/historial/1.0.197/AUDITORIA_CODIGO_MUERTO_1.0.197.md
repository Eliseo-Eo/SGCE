# SGCE 1.0.197 - Auditoría de código muerto

## Alcance

Se revisaron funciones públicas con prefijo `Sgce` en:

- `includes/`
- `services/`
- `repositories/`

También se revisaron clases en `src/` bajo namespace `Sgce\`.

## Resultado cuantitativo

- Funciones `Sgce*` detectadas después de la limpieza: 422.
- Funciones `Sgce*` sin llamador real fuera de su propia definición: 0.
- Clases en `src/`: 5.
- Clases sin referencia externa: 0.

## Eliminado

| Elemento | Archivo | Motivo |
|---|---|---|
| `SgceValidarXlsxImportacionSeguro()` | `includes/importacion/SGCE_ImportacionXlsx.php` | No tenía llamadores reales. Era un wrapper de tres líneas sobre `SgceAbrirXlsxImportacionSeguro()` y no aportaba validación adicional. |

## Conservado con uso interno directo

Se conservaron helpers que no son llamados desde otro archivo pero sí tienen llamador real dentro de su propio archivo. Estos no son código muerto porque forman parte de una unidad funcional encapsulada. Ejemplos representativos:

- Helpers de layout en `includes/SGCE_Layout.php`, usados para componer head/CSS/JS.
- Helpers de seguridad en `includes/SGCE_Seguridad.php`, usados por las funciones públicas de sesión, HTTPS y permisos.
- Helpers de archivado y mantenimiento en `includes/SGCE_Mantenimiento.php`, llamados por `SgceMantenimientoDiario()` y cron.
- Helpers de migración en `services/migracion/MigracionService.php`, llamados por los flujos blindados de migración.
- Helpers de repositorio `Where/Filtros` en `repositories/*Repository.php`, llamados por contadores/listados.
- Helpers de importación XLSX en `includes/importacion/SGCE_ImportacionXlsx.php`, llamados por `LeerFilasXlsx()`.

## Funciones nuevas revisadas explícitamente

- `SgceAsistenciaTablaParaCiclo()` se conserva: llamada por reportes/exportaciones y archivado para resolver tabla activa o histórica.
- Funciones de cron/mantenimiento se conservan: llamadas por `cron/mantenimiento_diario.php`, `cron/archivar_asistencias.php`, `cron/archivar_bitacora.php` y respaldos.
- Funciones de respaldo CSRF del instalador no usan prefijo `Sgce`, pero se revisaron en la prueba fallback: `InstalarPersistirCsrfArchivo()` y `InstalarValidarCsrfArchivo()` tienen llamadas reales y fueron ejecutadas.

## Clases `Sgce\` conservadas

| Clase | Uso |
|---|---|
| `Sgce\Foundation\Version` | Versión global, assets e instalador. |
| `Sgce\Foundation\Path` | Rutas de storage, backups, logs y runtime. |
| `Sgce\Support\Text` | Normalización y salida segura. |
| `Sgce\Support\Search` | Normalización de búsqueda. |
| `Sgce\Support\AcademicCalculator` | Promedios y normalización académica. |
