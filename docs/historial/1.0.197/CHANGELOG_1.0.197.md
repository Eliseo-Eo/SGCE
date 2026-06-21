# SGCE 1.0.197 - Changelog verificable

## Cambios de código

- Se cambió el refresco de caché activo de `microtime(true)` a contador incremental explícito.
- Se conectó el incremento del caché de ciclo activo después de cambios en `CiclosEscolares.Activo`.
- Se conectó el incremento del caché de oferta activa después de cambios en `OfertasEducativas.Activo`.
- Se refactorizó el Kardex individual hacia `SgceKardexAlumnoReporteDatos()` para permitir prueba directa de consultas agrupadas.
- Se eliminó `SgceValidarXlsxImportacionSeguro()` por no tener llamadores reales.
- Se actualizó la versión del sistema a `1.0.197`.

## Documentación

- Manuales extendidos 1.0.197 en MD, DOCX y PDF.
- Documentación histórica movida a `docs/historial/`.
- README raíz actualizado y único.

## Pruebas

- `php -l` completo: OK.
- PHPUnit: no disponible en el paquete; fallback PHP ejecutado: OK.
- CSRF instalador por archivo con sesión/cookie vacías: OK.
- Kardex 3 ciclos en SQLite real: 5 consultas totales: OK.

## No incluido por alcance

- No se agregó 2FA.
- No se agregó PIN ni token familiar.
- No se cambió la conducta académica, calificaciones, asistencia ni disciplina.

## No probado aquí por limitación de entorno

- Instalación real contra MySQL, porque el contenedor no tiene `pdo_mysql`, cliente `mysql` ni servidor `mysqld`.
