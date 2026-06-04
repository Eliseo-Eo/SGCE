# Instalación en servidor real - SGCE 1.0.91

Esta guía está pensada para instalar SGCE desde cero en un servidor Apache/Plesk con PHP y MySQL/MariaDB.

## 1. Requisitos mínimos

- PHP 8.1 o superior.
- MySQL 5.7+ o MariaDB 10.5+.
- Apache con `mod_rewrite` activo.
- HTTPS activo en producción.
- Base de datos vacía antes de ejecutar `Instalar.php`.

## 2. Extensiones PHP requeridas o recomendadas

Requeridas:

- `pdo`
- `pdo_mysql`
- `mbstring`
- `zip`
- `fileinfo`
- `json`

Recomendadas:

- `dom` / `xml`
- `gd`, si después se usan logos o imágenes personalizadas.
- `opcache`, para mejorar rendimiento en producción.

## 3. Subida de archivos

Sube el contenido completo del proyecto al directorio público del dominio o subdominio.

Ejemplo:

```bash
/var/www/vhosts/tu-dominio.com/httpdocs/
```

No subas respaldos antiguos, bases exportadas ni archivos temporales dentro del proyecto público.

## 4. Configuración de base de datos

Puedes editar:

```text
config/database.php
```

O crear:

```text
config/database.local.php
```

tomando como base:

```text
config/database.local.example.php
```

La base oficial de instalación limpia está en:

```text
install/SGCE.sql
```

## 5. Permisos recomendados

Carpetas que deben poder escribir PHP:

```bash
storage/
storage/backups/
storage/logs/
storage/planeaciones/
```

En Linux, un ajuste común es:

```bash
sudo chown -R www-data:www-data storage
sudo find storage -type d -exec chmod 750 {} \;
sudo find storage -type f -exec chmod 640 {} \;
```

En Plesk, usa el usuario del dominio en lugar de `www-data`.

## 6. Instalación inicial

1. Crea una base de datos vacía.
2. Configura conexión en `config/database.php` o `database.local.php`.
3. Abre en el navegador:

```text
https://tu-dominio.com/Instalar.php
```

4. Ejecuta el asistente.
5. Confirma que se creó:

```text
storage/install.lock
```

Cuando `install.lock` existe, el instalador queda bloqueado.

## 7. Protección de carpetas internas

El proyecto ya incluye `.htaccess` para bloquear acceso directo a carpetas sensibles como:

```text
config/
includes/
modules/
services/
repositories/
storage/
install/
tools/
cron/
```

Aun así, en servidor real confirma que Apache respete `.htaccess` y que `AllowOverride` esté activo.

## 8. Plesk y PageSpeed

Si el servidor usa PageSpeed o algún optimizador que modifica CSS/JS, evita que altere archivos principales del sistema.

Ejemplo en `.htaccess` si el módulo existe:

```apache
<IfModule pagespeed_module>
  ModPagespeedDisallow "*/assets/js/Admin.js"
  ModPagespeedDisallow "*/assets/js/sgce-shared.js"
  ModPagespeedDisallow "*/assets/css/sgce-base.min.css"
  ModPagespeedDisallow "*/assets/css/admin-paginacion-busqueda.css"
</IfModule>
```

## 9. Cron recomendado

Puedes programar estos scripts:

```text
cron/backup_diario.php
cron/backup_semanal.php
cron/archivar_bitacora.php
```

Ejemplo:

```bash
php /ruta/al/proyecto/cron/backup_diario.php
php /ruta/al/proyecto/cron/backup_semanal.php
php /ruta/al/proyecto/cron/archivar_bitacora.php
```

## 10. Respaldos

Los respaldos generados por SGCE deben quedarse dentro de `storage/backups/`, que está protegido por `.htaccess`.

Recomendación:

- Descargar respaldos importantes fuera del servidor.
- No guardar respaldos SQL dentro de `public/` ni raíz accesible sin protección.
- Probar restauración en una copia antes de usarla en producción.

## 11. HTTPS y sesión

En producción usa HTTPS. SGCE usa cookies seguras, CSRF y encabezados de seguridad desde `config/Conexion.php` e `includes/SGCE_Seguridad.php`.

## 12. Verificación después de instalar

Revisa:

- Login de administrador.
- Alta de ciclo escolar.
- Configuración de estructura académica.
- Alta/importación de maestros.
- Alta/importación de grupos.
- Alta/importación de materias.
- Alta/importación de alumnos.
- Selects buscables en Asignaciones.
- Filtros AJAX en alumnos, materias, asignaciones y bitácora.
- Respaldos.
- Bitácora.

## 13. Archivos que no conviene subir a producción

No subas:

```text
*.zip
*.sql de respaldo
archivos temporales
carpetas de pruebas locales con datos reales
```

La carpeta `tests/fixtures/` solo trae ejemplos ficticios y puede conservarse para pruebas técnicas.

## Paquete de producción

Para producción revisa `docs/PRODUCCION.md`. Las carpetas `tests/` y `tools/` son útiles para auditoría, pruebas y GitHub; no son necesarias para operar el sistema en servidor. Si se suben, permanecen protegidas con `.htaccess`.

Antes de liberar en servidor real ejecuta:

```bash
php tests/RunStaticChecks.php
php tests/RunIntegrationChecks.php
```

La prueba de integración requiere variables de entorno MySQL para crear una base temporal.
