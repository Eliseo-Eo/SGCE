# Manual tecnico e instalacion - SGCE

## 1. Objetivo

Este manual explica como instalar, configurar, respaldar y mantener SGCE en un servidor PHP/MySQL.

## 2. Requisitos del servidor

- PHP 8.1 o superior recomendado.
- MySQL 5.7/8.0 o MariaDB compatible.
- Apache con soporte para `.htaccess`.
- Extension `pdo_mysql` habilitada.
- Permisos de escritura en `config/` y `storage/` durante la instalacion.
- Navegador moderno para administracion.

## 3. Instalacion desde cero

### 3.1 Descomprimir

```bash
cd /var/www/html
sudo unzip SGCE_FINAL_DESDE_0.zip
```

### 3.2 Permisos

```bash
sudo chown -R www-data:www-data SGCE/storage SGCE/config
sudo find SGCE -type d -exec chmod 755 {} \;
sudo find SGCE -type f -exec chmod 644 {} \;
```

### 3.3 Crear base de datos

```sql
CREATE DATABASE sgce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sgce_user'@'localhost' IDENTIFIED BY 'Cambia_Esta_Clave_2026!';
GRANT ALL PRIVILEGES ON sgce.* TO 'sgce_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3.4 Ejecutar instalador

Abrir en el navegador:

```text
http://tu-servidor/SGCE/Instalar.php
```

Capturar datos de conexion, informacion institucional, ciclo activo, periodos y usuario administrador.

### 3.5 Bloqueo posterior

Al terminar la instalacion debe conservarse `storage/install.lock`. Ese archivo evita reinstalaciones accidentales.

## 4. Rendimiento

SGCE incluye paginacion y limites de consulta en secciones de alto crecimiento. Para escuelas con cientos de alumnos y registros diarios se recomienda:

- Mantener indices SQL creados por el instalador.
- Evitar borrar manualmente llaves e indices.
- Usar filtros por ciclo, grupo, periodo o fecha.
- Realizar respaldos periodicos.

## 5. Seguridad operativa

- Usar HTTPS en produccion.
- Cambiar claves predeterminadas.
- Mantener permisos restrictivos en `config/` y `storage/`.
- No publicar respaldos dentro de carpetas publicas.
- Revisar bitacora y respaldos periodicamente.

## 6. Validacion final

Ejecutar:

```bash
cd /var/www/html/SGCE
php tests/RunStaticChecks.php
```

Luego probar flujo completo con datos reales: docentes, alumnos, grupos, asignaciones, asistencia, calificaciones, planeaciones, reportes, respaldos y restauracion.
