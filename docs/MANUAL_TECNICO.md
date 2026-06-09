# Manual técnico - SGCE 1.0.122

## Estructura general

```text
api/
assets/
config/
cron/
docs/
includes/
install/
modules/
public/
reports/
repositories/
services/
storage/
views/
```

## Configuración

- `config/Conexion.php`: arranque, sesión, constantes, conexión PDO y seguridad base.
- `config/database.php`: configuración base.
- `config/database.local.php`: configuración local generada por el instalador.

## Base de datos

El esquema principal está en:

```text
install/SGCE.sql
```

Tablas clave:

- `Usuarios`.
- `CiclosEscolares`.
- `OfertasEducativas`.
- `EtapasAcademicas`.
- `Grupos`.
- `MateriasCatalogo`.
- `MateriasGrupo`.
- `AlumnoInscripciones`.
- `Asignaciones`.
- `Calificaciones`.
- `Asistencias`.
- `Kardex`.
- `MigracionesCiclo`.
- `BitacoraMovimientos`.

## Seguridad

- CSRF centralizado.
- Sesiones con cookies `HttpOnly` y `SameSite=Strict`.
- Tokens persistentes hasheados.
- Roles por módulo.
- Acceso directo bloqueado en includes, modules, services y repositories.
- Carpetas internas protegidas por `.htaccess` e `index.html`.

## Migración escolar

La lógica principal está en:

```text
services/migracion/MigracionService.php
```

Responsabilidades:

- Diagnóstico previo.
- Copia de periodos.
- Preparación completa del ciclo destino.
- Copia de materias por grupo.
- Copia opcional de asignaciones/docentes.
- Promoción/egreso de alumnos.
- Kardex congelado.
- Registro en `MigracionesCiclo`.
- Transacción y bloqueo de concurrencia.

## Pruebas en Desarrollo

Desde `Desarrollo/`:

```bash
php tests/RunStaticChecks.php
php tests/RunImportChecks.php
php tests/RunScenarioChecks.php
php tests/RunPermissionChecks.php
php tests/RunMigrationChecks.php
php tests/RunPlanningDefaultsChecks.php
```

Pruebas MySQL profundas requieren variables de entorno `SGCE_TEST_DB_HOST`, `SGCE_TEST_DB_USER`, `SGCE_TEST_DB_PASS` y `SGCE_TEST_DB_NAME`.

## API interna AJAX

SGCE incluye una API interna en `api/admin/` para actualizar tablas administrativas sin recargar toda la página. No es una API pública externa; requiere sesión activa y permisos del panel. Ver `docs/API_INTERNA.md`.

## Instalador multinivel

El instalador captura desde el inicio los mismos parámetros que luego se administran desde Configuración:

- `TurnosDisponibles` en `ConfiguracionSistema`.
- `CalificacionMinima`, `CalificacionMaxima`, `CalificacionAprobatoria` y `CalificacionDecimales`.
- `MatriculaAutomatica` y `MatriculaPrefijo`.

El formulario `Instalar.php` valida estos valores en servidor y `assets/js/Instalar.js` agrega validación de cliente y vista previa del formato de matrícula.


## Mantenimiento diario

Configura `cron/mantenimiento_diario.php` para archivar bitácora antigua, limpiar sesiones vencidas, intentos de seguridad antiguos y respaldos temporales.
