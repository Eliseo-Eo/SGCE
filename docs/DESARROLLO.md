# Desarrollo - SGCE 1.0.140

Esta carpeta contiene la misma base funcional que Producción más pruebas, fixtures y herramientas internas.

## No subir a producción

`Desarrollo/` no debe publicarse en un servidor abierto. Contiene pruebas y herramientas internas.

## Pruebas rápidas

```bash
php tests/RunStaticChecks.php
php tests/RunImportChecks.php
php tests/RunScenarioChecks.php
php tests/RunPermissionChecks.php
php tests/RunMigrationChecks.php
php tests/RunPlanningDefaultsChecks.php
php tests/RunPackageCleanChecks.php
```

## Pruebas con MySQL

Configura variables de entorno:

```bash
export SGCE_TEST_DB_HOST=localhost
export SGCE_TEST_DB_USER=usuario
export SGCE_TEST_DB_PASS=clave
export SGCE_TEST_DB_NAME=sgce_test
```

Después ejecuta:

```bash
php tests/RunMySQLChecks.php
php tests/RunBackupRestoreChecks.php
php tests/RunImportDatabaseChecks.php
```

## Empaquetado

La herramienta interna está en:

```text
tools/CrearPaqueteProduccion.php
```

La carpeta `tools/` está protegida por `.htaccess` e `index.html`.
