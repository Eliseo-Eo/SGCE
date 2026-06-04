# Arquitectura SGCE 1.0.91

SGCE está organizado por responsabilidades para facilitar mantenimiento a largo plazo.

## Carpetas principales

- `assets/`: CSS, JavaScript, imágenes y recursos estáticos.
- `api/admin/`: endpoints parciales para filtros y paginación administrativa.
- `config/`: conexión y configuración local.
- `cron/`: tareas programadas.
- `docs/`: documentación técnica y de operación.
- `includes/`: funciones compartidas por responsabilidad.
- `includes/importacion/`: lectores y validadores de importación.
- `install/`: esquema oficial de instalación limpia.
- `modules/`: controladores de módulos.
- `repositories/`: consultas grandes y paginadas.
- `services/`: reglas de negocio de servicios.
- `views/`: vistas del panel administrativo.
- `reports/`: exportaciones y reportes.
- `storage/`: respaldos, logs y archivos internos protegidos.
- `tools/`: herramientas CLI de mantenimiento.

## Flujo administrativo

`Admin.php` carga `modules/Admin.php`, valida sesión, procesa acciones, carga datos y muestra la vista correspondiente desde `views/admin/`.

## Filtros grandes

Los módulos con mayor crecimiento usan endpoints en `api/admin/` para actualizar solo la parte necesaria de la tabla. Esto evita recargar toda la página y reduce el trabajo del navegador.

## Assets y layout

Los módulos usan `SgceCss()` y `SgceJs()` para cargar recursos locales con la versión central `SGCE_VERSION`. Esto evita cache-busters manuales dispersos. Los CDN de Bootstrap, FontAwesome y Google Fonts se conservan por decisión visual.

El CSS base queda reservado para estructura global. Los estilos de módulos viven en archivos específicos dentro de `assets/css/`.

## Pruebas

- `tests/RunStaticChecks.php`: Revisión estática de estructura, sintaxis, residuos y documentación.
- `tests/RunIntegrationChecks.php`: Prueba real con MySQL temporal cuando se configuran variables de entorno.

`tests/` está protegido para que no sea navegable en producción.
