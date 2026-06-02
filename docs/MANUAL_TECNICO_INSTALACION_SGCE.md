# Manual tecnico e instalacion - SGCE

## 1. Objetivo

Este manual explica como instalar, configurar, respaldar y mantener SGCE en un servidor PHP/MySQL.

## 2. Requisitos del servidor

- PHP 8.0 o superior recomendado.
- MySQL 5.7/8.0 o MariaDB compatible.
- Apache con soporte para `.htaccess`.
- Extension `pdo_mysql` habilitada.
- Permisos de escritura en `config/` y `storage/`.
- Navegador moderno para administracion.

## 3. Instalacion desde cero

### 3.1 Descomprimir

```bash
cd /var/www/html
sudo unzip SGCE.zip
```

### 3.2 Permisos

```bash
sudo chown -R www-data:www-data SGCE/storage SGCE/config
sudo find SGCE -type d -exec chmod 755 {} \;
sudo find SGCE -type f -exec chmod 644 {} \;
```

### 3.3 Crear base de datos

Desde MySQL:

```sql
CREATE DATABASE sgce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sgce_user'@'localhost' IDENTIFIED BY 'Cambia_Esta_Clave_2026!';
GRANT ALL PRIVILEGES ON sgce.* TO 'sgce_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3.4 Ejecutar instalador

Abrir en navegador:

```text
http://servidor/SGCE/Instalar.php
```

Capturar:

- Datos de conexion MySQL.
- Datos de la escuela.
- Ciclo activo.
- Tres periodos oficiales.
- Cantidad de planeaciones por ciclo.
- Usuario administrador.

El campo de planeaciones por ciclo no trae valor impuesto; la institucion decide la cantidad.

## 4. Configuracion generada

El instalador crea `config/database.local.php`. Este archivo no debe subirse a GitHub porque contiene credenciales reales.

El archivo `.gitignore` ya excluye:

- `config/database.local.php`
- `storage/install.lock`
- logs, respaldos y archivos generados.

## 5. Estructura tecnica

| Area | Carpeta |
|---|---|
| Pantallas internas | `modules/` |
| Pantallas publicas | `public/` |
| Reportes/exportaciones | `reports/` |
| Servicios | `services/` |
| Utilidades globales | `includes/` |
| Activos visuales | `assets/` |
| Archivos generados | `storage/` |
| SQL de instalacion | `install/` |

## 6. Seguridad

### 6.1 Sesiones

Las sesiones se inicializan con cookie segura, `httponly` y `SameSite=Strict` cuando el entorno lo permite.

### 6.2 CSRF

Los formularios POST utilizan token CSRF para evitar envios no autorizados.

### 6.3 Permisos

El sistema maneja tres roles principales:

- `admin`
- `administrativo`
- `maestro`

Cada modulo valida permisos antes de permitir operaciones sensibles.

### 6.4 Contraseñas

Las contraseñas se almacenan con `password_hash()` y se validan con `password_verify()`.

### 6.5 Directorios protegidos

Las carpetas internas incluyen `.htaccess` para bloquear acceso directo cuando el servidor usa Apache.

## 7. Respaldos

El sistema incluye respaldo manual desde panel y tareas programadas en `cron/`.

Ejemplo de cron diario:

```cron
0 2 * * * /usr/bin/php /var/www/html/SGCE/cron/backup_diario.php >/dev/null 2>&1
```

Ejemplo de cron semanal:

```cron
0 3 * * 0 /usr/bin/php /var/www/html/SGCE/cron/backup_semanal.php >/dev/null 2>&1
```

## 8. Mantenimiento

- Mantener `storage/backups/` con limpieza periodica.
- Revisar `storage/logs/` cuando ocurra un error.
- No editar directamente `config/database.local.php` salvo que cambien credenciales.
- Hacer respaldos antes de restaurar base de datos.
- Probar restauracion en ambiente de prueba antes de hacerlo en produccion.

## 9. Validacion tecnica realizada

- Sintaxis PHP validada.
- Sintaxis JavaScript validada.
- Wrappers principales revisados.
- Includes y requires locales revisados.
- Funciones PHP sin duplicados.
- Favicon centralizado en `assets/media/img/`.
- Documentacion actualizada para esta entrega.

## 10. Prueba final recomendada

Antes de entregar al cliente, realizar flujo completo en servidor real:

1. Instalacion limpia.
2. Inicio de sesion admin.
3. Alta de docente.
4. Alta de grupo.
5. Alta de alumno.
6. Asignacion de materia.
7. Pase de lista.
8. Captura de calificaciones.
9. Subida y revision de planeaciones.
10. Exportacion de reportes.
11. Respaldo y restauracion controlada.
