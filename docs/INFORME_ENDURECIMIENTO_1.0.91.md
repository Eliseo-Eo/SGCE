# Informe de endurecimiento SGCE 1.0.91

## Objetivo

Preparar SGCE para una instalación más limpia y profesional en producción, reduciendo herencia CSS, protegiendo archivos de prueba, agregando una prueba real con MySQL temporal y dejando claro qué archivos pertenecen al servidor y cuáles pertenecen al repositorio de desarrollo.

## Cambios principales

- Se protegió `tests/` con `.htaccess` e `index.html` para evitar ejecución o descarga directa desde navegador.
- Se conservó `tests/` en el paquete porque es útil para GitHub, auditoría y mantenimiento, pero quedó documentado que puede excluirse del paquete de producción.
- Se redujo `assets/css/sgce-base.min.css` separando estilos específicos de módulos hacia sus hojas correspondientes.
- Se redujo la dependencia de reglas heredadas dentro de `sgce-base.min.css`; el archivo base queda enfocado en estructura global, variables, tarjetas, hero, formularios y utilidades comunes.
- Se agregaron helpers de assets en `SGCE_UI.php`: `SgceVersion()`, `SgceAssetUrl()`, `SgceCss()` y `SgceJs()`.
- Se centralizó la versión del sistema en `config/Conexion.php` mediante `SGCE_VERSION`.
- Se actualizaron módulos independientes para usar helpers de assets locales, reduciendo cache-busters escritos manualmente.
- Se agregó `tests/RunIntegrationChecks.php` para instalar el esquema en una base temporal de MySQL y probar inserciones mínimas de ciclo, oferta, grupo, alumno, materia, asignación, calificación y asistencia.
- Se agregó `.sgce-production-exclude` como guía para construir paquetes sin archivos de desarrollo.

## Resultado del CSS base

El CSS base pasó de ser una mezcla de estilos globales y reglas específicas de módulos a un archivo más contenido. Las reglas de módulos quedaron en archivos como:

```text
assets/css/admin-paginacion-busqueda.css
assets/css/asignaciones-botones-metalicos.css
assets/css/dashboard-colores-suaves.css
assets/css/expedientes-botones-metalicos.css
assets/css/grupos-alumnos-botones-metalicos.css
assets/css/maestros-botones-metalicos.css
assets/css/planeaciones-botones-metalicos.css
```

## Prueba real con MySQL temporal

Para ejecutarla:

```bash
SGCE_TEST_DB_HOST=localhost \
SGCE_TEST_DB_USER=root \
SGCE_TEST_DB_PASS='tu_password' \
php tests/RunIntegrationChecks.php
```

La prueba crea una base temporal, carga `install/SGCE.sql`, valida tablas clave, inserta datos mínimos y elimina la base temporal al terminar.

## Nota

No se localizó Bootstrap, FontAwesome ni fuentes porque se decidió conservar CDN por calidad visual y consistencia del diseño actual.
