# SGCE 1.0.91

Sistema Gestor de Control Escolar para administración de ciclos escolares, grupos, docentes, materias, alumnos, asignaciones, asistencia, calificaciones, expedientes, reportes, respaldos y bitácora.

## Enfoque de esta versión

La versión 1.0.91 es una base endurecida para instalación desde cero. Esta entrega reduce el CSS heredado, separa estilos específicos por módulo, protege `tests/`, centraliza cache-busters con `SGCE_VERSION`, agrega prueba real con MySQL temporal y documenta con mayor claridad qué pertenece a producción y qué pertenece a GitHub/desarrollo. Se mantiene CDN para Bootstrap, FontAwesome y fuentes porque conserva mejor el diseño visual actual.


## Endurecimiento 1.0.91

- `tests/` queda protegido con `.htaccess` e `index.html`.
- `assets/css/sgce-base.min.css` queda reducido y enfocado en estructura global.
- Los estilos específicos se extraen hacia hojas por módulo.
- Los assets locales usan helpers `SgceCss()` y `SgceJs()` para evitar cache-busters manuales dispersos.
- Se agrega `tests/RunIntegrationChecks.php` para prueba real con MySQL temporal.
- Se agrega `docs/PRODUCCION.md` para distinguir archivos de servidor y archivos de desarrollo/GitHub.

## Requisitos

- PHP 8.1 o superior recomendado.
- MySQL/MariaDB con InnoDB.
- Extensiones PHP: PDO MySQL, ZIP, JSON, mbstring y fileinfo. DOM/XML y GD son recomendadas.
- Apache con `.htaccess` habilitado.
- HTTPS recomendado para producción.

## Instalación rápida

1. Sube los archivos al servidor.
2. Crea una base de datos vacía.
3. Ajusta `config/database.php` o crea `config/database.local.php` usando `config/database.local.example.php`.
4. Abre `Instalar.php` en el navegador.
5. Completa el asistente de instalación.
6. Verifica que se haya creado `storage/install.lock`.

## Módulos principales

- Administrador
- Maestros
- Grupos
- Materias
- Alumnos
- Expedientes
- Asignaciones
- Asistencia
- Calificaciones
- Reportes
- Planeaciones
- Avisos
- Bitácora
- Respaldos
- Migración académica de ciclo

## Rendimiento

Los módulos con mayor crecimiento usan paginación y búsqueda desde servidor:

- Alumnos
- Materias
- Asignaciones
- Bitácora

La actualización visual usa endpoints parciales en `api/admin/`, con debounce de búsqueda, mínimo de 2 letras para consultar y actualización directa de filas, contador, paginación y modales mediante parciales PHP reutilizables.

## Mantenimiento

Revisa `docs/CRON_Y_MANTENIMIENTO.md` para tareas programadas de respaldos y archivado de bitácora.

## Seguridad

- Sesiones con cookie segura.
- CSRF en formularios POST.
- Protección de carpetas internas con `.htaccess`.
- Roles y permisos.
- Bitácora de acciones críticas.
- Validación de archivos de importación y planeaciones.

## Documentación

Consulta la carpeta `docs/` para arquitectura, base de datos, instalación en servidor real, rendimiento, migración académica, cron y roadmap.
