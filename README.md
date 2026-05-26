# SGCE - Versión revisada con módulos reales

Esta versión corrige la confusión de las funciones prometidas y deja visibles los módulos principales.

## Qué sí incluye esta versión

1. **Dashboard del administrador**
   - Totales de alumnos, maestros, grupos, asistencias de hoy, faltas de hoy y promedio general.
   - Alumnos con riesgo con nivel, motivo y puntaje.

2. **Reporte de alumnos con riesgo**
   - Considera faltas recientes, retardos recientes y promedio menor a 7 cuando el alumno ya tiene calificaciones.
   - Muestra el motivo: faltas, retardos, promedio bajo o combinación.

3. **Historial individual del alumno**
   - Desde alumnos puedes abrir expediente.
   - Incluye calificaciones, asistencia, promedio y conteos.
   - También incluye botón para generar boleta/reporte individual imprimible.

4. **Consulta pública para padres**
   - No requiere login.
   - Pide nombre completo, grado, grupo y turno.
   - No muestra listas completas.
   - Muestra avisos dirigidos a padres.

5. **Avisos o comunicados**
   - Nuevo archivo `AvisosAdmin.php`.
   - Puedes publicar avisos para TODOS, MAESTROS o PADRES.
   - Se muestran en el portal docente y en consulta de padres.

6. **Control de permisos base**
   - La base ya soporta roles: admin, maestro, director y prefecto.
   - Las pantallas principales ya restringen acceso según rol básico.

7. **Bitácora de movimientos**
   - Tabla `BitacoraMovimientos` incluida.
   - `Conexion.php` la crea si falta.
   - Registra altas, modificaciones, bajas lógicas, importaciones, asistencia, calificaciones, login, logout, respaldos y consultas importantes.
   - Se ve en la pestaña Bitácora del admin.

8. **Respaldos de base de datos**
   - Nuevo archivo `RespaldoBD.php`.
   - Desde el dashboard puedes descargar un respaldo `.sql`.

9. **Filtros en reportes**
   - Nuevo archivo `ReportesAdmin.php`.
   - Puedes exportar asistencias por grupo/asignación y filtrar por fecha inicial/final.
   - Puedes exportar calificaciones por grupo o asignación.
   - Puedes generar boleta individual.

10. **Base optimizada para crecer**
   - Índices en asistencias, alumnos, asignaciones, grupos y calificaciones.
   - Asistencias usa `BIGINT` y `FechaDia` indexada.
   - Un alumno puede tener varias asistencias al día, una por materia.

11. **Borrado lógico**
   - Alumnos, maestros, grupos y asignaciones usan `Activo = 0`.
   - Esto conserva historial.

12. **Panel del maestro mejorado**
   - Muestra estadísticas básicas y avisos.
   - Accesos para asistencia, calificaciones y exportaciones.

13. **Boleta individual PDF/imprimible**
   - Nuevo archivo `ExportarAlumno.php`.

14. **Validaciones contra duplicados**
   - La base trae llaves únicas para usuario, grupo/turno, alumno/grupo y asignación.

15. **Instalador inicial**
   - Nuevo archivo `Instalar.php`.
   - Revisa conexión, tablas y admin.
   - Permite instalar `ControlEscolar.sql` desde cero.
   - Por seguridad, elimina o renombra este archivo después de instalar.

## Instalación recomendada

1. Borra la base anterior si quieres iniciar limpio.
2. Importa `ControlEscolar.sql` o usa `Instalar.php`.
3. Entra con:
   - Usuario: `Admin`
   - Contraseña: `Admin123`
4. Después de instalar, elimina o renombra `Instalar.php`.

## Nota honesta

La paginación desde base de datos todavía puede mejorarse más para tablas enormes. El sistema ya usa índices y evita algunas cargas pesadas, pero una paginación 100% server-side con AJAX/DataTables sería la siguiente mejora si crece mucho.


## Corrección final visual y estructura

- El login se conserva con el diseño rojo/guinda institucional.
- Los botones del panel administrativo se normalizaron al estilo institucional: fondo blanco, borde guinda y hover relleno en tinto.
- Se corrigió la estructura de `Admin.php`: la pestaña Bitácora ahora es independiente y ya no queda pegada dentro de Asignaciones.
- Se revisó la sintaxis PHP de los archivos principales antes de generar esta versión.


## Mejoras aplicadas en esta entrega

- Se agregó protección CSRF automática para formularios POST.
- Se agregó rate limit para login y consulta pública de padres.
- El login ahora solo permite usuarios activos.
- Se agregó tabla `IntentosSeguridad` para bloqueo temporal por intentos fallidos.
- Se protegió `Instalar.php`: solo funciona si existe `PERMITIR_INSTALACION.lock`.
- Se quitó del dashboard el acceso directo al instalador para evitar reinstalaciones accidentales.
- Se agregó `sgce-fix.css` para homologar botones, modales, formularios y evitar conflictos de diseño entre archivos.
- Se mejoró el comportamiento de impresión en reportes PDF.
- Se mantuvieron contraseñas normales como se solicitó, pero se reforzó el resto del flujo de seguridad.

### Nota importante
Después de reemplazar archivos, conserva `Instalar.php` bloqueado. Si algún día necesitas reinstalar, crea temporalmente `PERMITIR_INSTALACION.lock`, instala y bórralo de inmediato.


## Corrección adicional

- Se agregó `favicon.png` además de `favicon.ico` para que funcionen correctamente las referencias `apple-touch-icon` y páginas que llaman el favicon en PNG.
- En `Calificar.php` la calificación mínima permitida ahora es 5 y la máxima 10. Si se escribe menos de 5, el servidor lo ajusta a 5; desde la interfaz ya no permite capturar menos de 5.

## Actualización FIX3 - Respaldos, importación y dashboard

- El botón **Avisos y comunicados** aparece primero en los módulos recomendados del dashboard.
- Se agregó **ExportarDatosBD.php** para descargar un respaldo **solo de datos**, pensado para restauración automática desde el sistema.
- Se agregó **RestaurarBD.php** para importar respaldos `.sql` de datos con tres modos:
  - Fusionar/agregar datos sin borrar primero.
  - Borrar datos escolares y luego importar, conservando usuarios.
  - Borrar todo y luego importar, incluyendo usuarios.
- Se agregó una opción controlada para **borrar datos escolares** sin perder los usuarios del sistema.
- `RespaldoBD.php` fue ajustado para no insertar columnas generadas como `FechaDia` y para seguir limpiando `SessionToken`.
- El instalador sigue protegido; para estructura/base vacía se usa `ControlEscolar.sql`, y para datos se usa `ExportarDatosBD.php` + `RestaurarBD.php`.
- Todos los archivos PHP fueron validados con `php -l`.

### Recomendación de uso de respaldos

1. Para copia diaria normal: usa **EXPORTAR SOLO DATOS**.
2. Para restaurar desde el sistema: entra a **IMPORTAR / RESTAURAR BASE DE DATOS** y sube el respaldo de datos.
3. Para instalación desde cero: instala primero la estructura con `ControlEscolar.sql` o `Instalar.php` protegido, y luego importa el respaldo de datos.
4. Para emergencia técnica/manual: usa **RESPALDO COMPLETO SQL**.


## Ajustes FIX4

- Dashboard más limpio: se dejó un solo acceso a `Respaldos e importación`.
- Se quitó el aviso/botón del instalador del dashboard para evitar redundancia.
- `RestaurarBD.php` corrige el error `There is no active transaction` usando `DELETE` en lugar de `TRUNCATE` dentro del flujo de restauración.
- La restauración automática acepta respaldos generados por `ExportarDatosBD.php` (solo datos).
- El respaldo completo sigue existiendo como herramienta manual de emergencia en `RespaldoBD.php`, pero no aparece duplicado en el dashboard.


## FIX7
- Se eliminó el archivo externo `sgce-fix.css`.
- Los estilos finales quedaron integrados dentro de cada archivo PHP para evitar problemas de caché y prioridad CSS.
- Los botones del Centro de reportes quedan rellenos color guinda como los módulos del dashboard.
- Los botones Volver a inicio y Cerrar sesión quedan blancos con texto guinda, sin relleno al pasar el mouse.
