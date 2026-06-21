# SGCE 1.0.196 — Reporte de auditoría previo a cambios

Fecha de cierre: 2026-06-19.

## Alcance real revisado

Paquete auditado: `SGCE_1.0.195_Performance_Escalabilidad_Segura_Instalable(2).zip`.

Inventario técnico inicial antes de modificar archivos:

- Entradas en ZIP: 416.
- Tamaño comprimido aproximado: 2.34 MB.
- Tamaño descomprimido aproximado: 5.82 MB.
- Archivos PHP revisados por sintaxis: 189.
- Resultado `php -l`: 189 correctos, 0 errores de sintaxis.
- Entradas peligrosas en ZIP (`../` o rutas absolutas): 0.
- Versión declarada al inicio: `1.0.195`.

## Archivos autorizados para modificación

| Archivo | Motivo verificado | Tipo de cambio |
|---|---|---|
| `install/installer/InstallerCore.php` | `InstallerRuntime.php` reportaba un token temporal de instalador en `storage/locks`, pero `InstallerCore.php` solo validaba sesión/cookie. Si PHP pierde sesión y cookie durante POST, el instalador puede rechazar una solicitud legítima con CSRF inválido. | Corrección puntual de resiliencia CSRF del instalador. |
| `reports/ExportarKardexAlumno.php` | Consultaba periodos, asignaciones y calificaciones dentro de `foreach ($Ciclos as $Ciclo)`. Para N ciclos, hacía aproximadamente `2 + 3N` queries. | Consolidación de consultas por lote. |
| `assets/vendor/bootstrap/5.3.3/css/bootstrap.min.css` | Referencia final a `.map` de desarrollo no necesaria en producción. | Quitar comentario `sourceMappingURL`. |
| `assets/vendor/bootstrap/5.3.3/js/bootstrap.bundle.min.js` | Referencia final a `.map` de desarrollo no necesaria en producción. | Quitar comentario `sourceMappingURL`. |
| `assets/vendor/bootstrap/5.3.3/css/bootstrap.min.css.map` | Archivo de desarrollo, no necesario para ejecución. | Eliminación. |
| `assets/vendor/bootstrap/5.3.3/js/bootstrap.bundle.min.js.map` | Archivo de desarrollo, no necesario para ejecución. | Eliminación. |
| `VERSION.txt`, `src/Foundation/Version.php`, `README.md` | La entrega final debe declarar una versión de cierre nueva. | Actualización de versión/documentación. |
| `CHANGELOG_1.0.195.txt` y documentos `docs/*1.0.195*` | Documentos obsoletos después del cierre 1.0.196. Mantenerlos en paquete final genera confusión de versión. | Sustitución por documentación 1.0.196. |
| `docs/*1.0.196*`, `docs/manuales/*1.0.196*` | Entregables solicitados: auditoría, cambios, manuales, checklist y changelog. | Documentación nueva. |

No se autorizaron cambios en lógica de negocio, permisos, esquema SQL ni dependencias externas.

## Seguridad

### Hallazgo confirmado S-01 — Respaldo CSRF del instalador anunciado pero no implementado

Evidencia previa:

- `install/installer/InstallerRuntime.php` informaba que el token temporal en `storage/locks` se usa si PHP pierde la sesión durante el POST.
- `install/installer/InstallerCore.php` validaba el token solo contra `$_SESSION['InstalarCsrfToken']` y `$_COOKIE['SGCE_INSTALL_CSRF']`.

Vector cerrado: falsos negativos de CSRF durante instalación cuando el servidor pierde sesión/cookie por configuración de PHP, proxy, ruta de cookie o reinicio. No se relaja la protección: el token sigue siendo aleatorio de 64 caracteres hexadecimales y ahora también queda respaldado como hash en archivo temporal protegido.

Estado: corregido.

### Verificaciones de seguridad sin cambio

- Sesiones de aplicación: se verificó endurecimiento de cookies, `session.use_strict_mode`, `httponly`, `SameSite`, regeneración de sesión y validación de token persistente hasheado en `includes/SGCE_Seguridad.php` y `config/Conexion.php`.
- CSRF en módulos POST: se revisaron módulos administrativos, docentes y públicos. Los POST relevantes llaman `RequerirCsrfPost()` directamente o desde su contenedor de módulo. `services/admin/ConfiguracionAdminService.php` no lo llama internamente, pero `modules/ConfiguracionAdmin.php` protege el POST antes de delegar.
- Subida de archivos: `includes/SGCE_Archivos.php` valida `is_uploaded_file`, tamaño, extensión, MIME con `finfo` y firma binaria para PDF, Office antiguo y OOXML/ZIP con límites anti zip slip/zip bomb.
- Descarga de planeaciones: `modules/DescargarPlaneacion.php` verifica sesión, permiso/propietario, `realpath()` dentro de carpeta permitida y cabeceras privadas.
- Respaldos/exportación SQL: se verificó permiso de respaldos y salida firmada HMAC. La generación de llave de firma usa archivo persistente y `flock()`.

Limitación: estas verificaciones son estáticas y de sintaxis. No sustituyen pruebas manuales con navegador, servidor final, HTTPS real y usuarios reales.

## Rendimiento y base de datos

### Hallazgo confirmado P-01 — N+1 acotado en Kardex individual

Archivo: `reports/ExportarKardexAlumno.php`.

Evidencia previa: dentro de `foreach ($Ciclos as $Ciclo)` se ejecutaban tres consultas por ciclo: periodos, asignaciones y calificaciones.

Impacto estimado:

- Antes: `2 + 3N` consultas, donde N es el número de ciclos del alumno.
- Después: `2 + 3` consultas en el caso general: alumno, ciclos, periodos por lote, asignaciones por lote y calificaciones por lote.

Estado: corregido.

### Índices revisados

El esquema `install/SGCE.sql` incluye índices específicos para búsquedas frecuentes:

- Usuarios: sesión/token, rol/activo/nombre, búsqueda por nombre.
- Alumnos: CURP, búsqueda pública, nombre, estado.
- Grupos: ciclo/turno/grado/grupo y búsqueda pública.
- Asignaciones: ciclo/grupo, maestro/ciclo, materia.
- Periodos: ciclo/oferta/orden.
- Calificaciones: alumno/asignación/periodo y filtros de reporte.
- Asistencias: asignación/fecha/alumno, alumno/fecha/asignación, fecha/estado.
- Conducta: alumno/fecha, asignación/fecha y consulta pública.

No se agregaron índices nuevos en 1.0.196 porque no se detectó una consulta crítica sin cobertura evidente dentro del alcance cerrado. Agregar índices sin datos reales puede aumentar costo de escritura y mantenimiento.

## Frontend y peso de carga

### Hallazgo confirmado F-01 — Source maps de Bootstrap incluidos en producción

Archivos eliminados:

- `assets/vendor/bootstrap/5.3.3/css/bootstrap.min.css.map`
- `assets/vendor/bootstrap/5.3.3/js/bootstrap.bundle.min.js.map`

También se removieron los comentarios `sourceMappingURL` de los archivos minificados.

Impacto: reducción de paquete y eliminación de referencias de desarrollo. No cambia diseño visual ni comportamiento del navegador normal.

### Decisiones sin cambio

- No se generaron subsets de FontAwesome en esta versión. Razón: el sistema usa iconos en múltiples módulos y el usuario ha reportado históricamente que cambios en iconos pueden romper la apariencia. Hacer subset sin prueba visual completa por módulo introduce más riesgo que beneficio en cierre final.
- No se quitaron fuentes `.ttf`/`.woff` de fallback. Razón: aunque navegadores modernos usan `.woff2`, quitar fallbacks sin probar todos los navegadores objetivo puede generar iconos o fuentes rotas.
- No se cambió Bootstrap/FontAwesome de local a CDN ni viceversa. Razón: sería una decisión de despliegue, no una corrección verificada.

## Deuda técnica y mantenibilidad

Se detectó duplicación natural por wrappers raíz (`Admin.php`, `Calificar.php`, `ReportesAdmin.php`, etc.) que delegan a módulos internos. No se eliminó porque facilita rutas amigables y compatibilidad con enlaces existentes.

Se mantuvo la modularización actual en `modules/`, `services/`, `repositories/`, `includes/` y `views/`. No se hizo reescritura arquitectónica.

## Estado de confianza por área

| Área | Confianza | Razón |
|---|---:|---|
| Seguridad base | Alta | CSRF, sesiones, permisos, uploads y descargas revisados estáticamente con evidencia. |
| Instalación | Media-alta | Se corrigió resiliencia CSRF; requiere prueba real en servidor final. |
| Reportes/Kardex | Alta en sintaxis, media en datos reales | Se redujeron queries preservando estructura; requiere comparación visual PDF/Excel con datos reales. |
| Frontend | Media-alta | Solo se quitaron source maps; no se tocaron estilos visuales. |
| Carga/concurrencia | No concluyente | Requiere pruebas con usuarios concurrentes, red real y servidor final. |
