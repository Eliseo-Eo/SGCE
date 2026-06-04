# Guía de paquete de producción SGCE 1.0.91

## Archivos que sí se suben al servidor

```text
api/
assets/
config/
cron/
includes/
install/
modules/
public/
reports/
repositories/
services/
storage/
views/
Admin.php
Asistencia.php
AvisosAdmin.php
Calificar.php
ConfiguracionAdmin.php
ConsultaCalificaciones.php
ConsultaPadre.php
DescargarPlaneacion.php
Exportar*.php
HistorialAlumno.php
Importar.php
Instalar.php
Logout.php
Maestro.php
MigracionAdmin.php
PeriodosAdmin.php
Planeaciones.php
PlaneacionesAdmin.php
ReportesAdmin.php
RespaldoBD.php
RestaurarBD.php
UsuariosAdmin.php
index.php
.htaccess
.user.ini
```

## Archivos que son para GitHub, auditoría o desarrollo

Estos pueden permanecer protegidos, pero no son necesarios para operar el sistema en producción:

```text
tests/
tools/
docs/INFORME_*.md
.gitignore
.sgce-production-exclude
```

## Seguridad recomendada

- Mantener `config/database.local.php` fuera del repositorio.
- Verificar que `storage/`, `config/`, `install/`, `tests/` y `tools/` no sean navegables.
- Después de instalar, confirmar que exista `storage/install.lock`.
- Si el instalador no elimina `install/`, protegerlo o retirarlo manualmente del servidor.
- Ejecutar respaldos con `cron/backup_diario.php` y probar restauración en una copia local.

## Pruebas antes de subir a producción

```bash
php tests/RunStaticChecks.php
php tests/RunIntegrationChecks.php
```

La segunda prueba requiere variables de entorno MySQL, porque crea y elimina una base temporal.
