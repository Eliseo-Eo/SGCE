# Informe de depuración SGCE 1.0.91

## Objetivo

Preparar una versión limpia para instalación desde cero, sin archivos de ajuste temporal, sin migraciones históricas innecesarias, sin funciones no usadas y con documentación alineada a la versión actual.

## Revisión realizada

Se revisaron estructura de carpetas, módulos PHP, servicios, repositorios, endpoints AJAX, CSS, JavaScript, SQL de instalación, documentación, pruebas, cron, herramientas CLI y carpetas internas de almacenamiento.

## Limpieza aplicada

- Se retiró el informe de auditoría anterior y se generó este informe de depuración.
- Se eliminó el script de migración técnica antiguo de referencia; `install/SGCE.sql` queda como fuente oficial para instalación limpia.
- Se retiraron funciones no usadas de bitácora, seguridad, configuración, planeaciones, base de datos, importación, migraciones, servicios de maestros/grupos/alumnos/reportes y helpers de búsqueda.
- Se agregaron carpetas protegidas faltantes para respaldos, logs y reportes temporales de importación.
- Se actualizó la documentación principal a `1.0.91`.
- Se simplificó el changelog para esta entrega limpia.
- Se reforzó `tests/RunStaticChecks.php` para revisar residuos, documentación, cache-busters, carpetas de almacenamiento y funciones retiradas.

## Archivos retirados

```text
Informe de auditoría anterior.
Migración técnica histórica de referencia.
```

## Funciones retiradas por no tener uso directo

```text
CrearTablaBitacoraSiNoExiste
CrearTablaRateLimitSiNoExiste
SgceCrearTablaConfiguracionSiNoExiste
SgceCrearTablaPlaneacionesSiNoExiste
SgceDbIndiceExiste
SgceImportacionReportesDepurar
SgceMigracionesEstado
SgceGrupoListarPaginado
SgceMaestroListarPaginado
SgceAlumnoListarFiltradoTodos
SgceAsignacionListarFiltradasTodas
SgceReporteBitacoraPaginadaTodas
SgceLikeBusqueda
SgceBusquedaUsarFullText
SgceUrlAdminConParametros
TextoNodosExcel
SgceRepoAlumnoListarTodos
SgceRepoAsignacionListarTodas
SgceRepoBitacoraListarTodos
```

## Estructura final recomendada

```text
api/             Endpoints parciales del panel administrador.
assets/          CSS, JavaScript, imágenes y librerías locales.
config/          Conexión y configuración local de base de datos.
cron/            Tareas programadas.
docs/            Documentación técnica, instalación y mantenimiento.
includes/        Utilidades compartidas.
install/         SQL oficial de instalación.
modules/         Controladores del sistema.
public/          Consulta pública protegida.
reports/         Exportaciones y respaldos.
repositories/    Consultas centralizadas.
services/        Lógica de negocio.
storage/         Archivos internos protegidos.
tests/           Pruebas estáticas y fixtures.
tools/           Herramientas CLI.
views/           Vistas del panel administrativo.
```

## Resultado

La versión queda preparada como paquete base para instalación desde cero. La limpieza fue conservadora: se retiró lo que no tenía uso detectado y se mantuvieron las piezas necesarias para operación, mantenimiento, respaldos, pruebas y documentación.
