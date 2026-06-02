# Manual técnico de instalación - SGCE

## Objetivo

Instalar SGCE desde cero en un servidor PHP/MySQL con seguridad básica, estructura limpia y rendimiento preparado para escuelas con cientos de alumnos y múltiples docentes.

## Requisitos del servidor

- PHP 8.1 o superior.
- MySQL 8 o MariaDB compatible.
- Extensiones PHP: `pdo_mysql`, `mbstring`, `zip`, `simplexml`, `fileinfo`.
- Permisos de escritura en `storage/` y `config/`.

## Instalación

```bash
cd /var/www/html
sudo unzip SGCE_FINAL_DESDE_0.zip
sudo chown -R www-data:www-data SGCE/storage SGCE/config
```

Abrir en navegador:

```text
http://tu-servidor/SGCE/Instalar.php
```

Completa el asistente con datos de base de datos, escuela, ciclo y administrador.

## Bloqueo del instalador

Al terminar se genera:

```text
storage/install.lock
```

Mientras exista ese archivo, `Instalar.php` queda bloqueado. Para reinstalar de forma controlada, elimina manualmente el archivo o define temporalmente `SGCE_ALLOW_REINSTALL=1`.

## Seguridad incluida

- Sesiones con `HttpOnly`, `SameSite=Strict` y regeneración de ID al iniciar sesión.
- CSRF en formularios POST.
- Rate limit de login y consulta pública.
- Encabezados HTTP de seguridad.
- Directorios internos protegidos con `.htaccess`.
- Validación de archivos subidos por extensión, tamaño, MIME y firma interna.
- Restauración de respaldos limitada a archivos SQL oficiales del sistema.

## Rendimiento

- Índices SQL para alumnos, asignaciones, asistencias, calificaciones, avisos y bitácora.
- Paginación real desde MySQL en módulos de alto crecimiento.
- Consultas con `LIMIT/OFFSET` y filtros preparados.
- Reportes con validación de rangos para evitar cargas excesivas.

## Prueba previa

```bash
cd /var/www/html/SGCE
php tests/RunStaticChecks.php
```
