# Changelog SGCE

## SGCE 1.0.91 - Endurecimiento para producción

- Se redujo `assets/css/sgce-base.min.css` y se movieron reglas específicas hacia CSS por módulo.
- Se retiraron reglas heredadas de Bitácora, Asignaciones, Expedientes, Dashboard, Avisos y otros módulos desde el CSS base.
- Se centralizó la versión del sistema con `SGCE_VERSION`.
- Se agregaron helpers `SgceCss()` y `SgceJs()` para homologar assets locales en módulos independientes.
- Se protegió `tests/` con `.htaccess` e `index.html`.
- Se agregó `tests/RunIntegrationChecks.php` para una prueba real con MySQL temporal.
- Se agregó `docs/PRODUCCION.md` para separar archivos de producción y archivos de GitHub/desarrollo.
- Se agregó `.sgce-production-exclude` como manifiesto de exclusión para paquetes productivos.
- Se agregó `tools/CrearPaqueteProduccion.php` para construir un paquete sin carpetas de desarrollo cuando el servidor tenga extensión ZIP.
- Se conservaron Bootstrap, FontAwesome y fuentes por CDN para mantener el diseño visual actual.
