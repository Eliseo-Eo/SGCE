# SGCE - Sistema Gestor de Control Escolar

SGCE es un sistema escolar en PHP y MySQL para administración de alumnos, docentes, grupos, asignaciones, asistencias, calificaciones, planeaciones, reportes, respaldos y consulta pública para madres, padres o tutores.

## Características principales

- Administración de docentes, alumnos, grupos y asignaciones.
- Captura de asistencia y calificaciones por docente.
- Consulta pública de asistencia y calificaciones con coincidencia exacta.
- Reportes PDF y exportaciones Excel/CSV.
- Planeaciones por ciclo escolar.
- Respaldos, restauración controlada y bitácora de movimientos.
- Instalador web con bloqueo posterior por `storage/install.lock`.
- Paginación real desde MySQL en módulos de alto crecimiento.
- Validación CSRF, sesiones seguras y rate limit de login.

## Requisitos

- PHP 8.1 o superior.
- MySQL 8 o MariaDB compatible.
- Extensiones PHP: PDO MySQL, mbstring, zip, simplexml, fileinfo.
- Servidor Apache/Nginx con permisos de escritura en `storage/` y `config/`.

## Instalación limpia

```bash
cd /var/www/html
sudo unzip SGCE_FINAL_DESDE_0.zip
sudo chown -R www-data:www-data SGCE/storage SGCE/config
```

Después abre `http://tu-servidor/SGCE/Instalar.php` y completa el asistente.

Al finalizar, el instalador crea `storage/install.lock`. Mientras ese archivo exista, el instalador queda bloqueado por seguridad.

## Verificación estática

```bash
cd /var/www/html/SGCE
php tests/RunStaticChecks.php
```

## Estructura principal

```text
assets/        CSS, JavaScript e imágenes públicas.
config/        Configuración de conexión.
includes/      Utilidades, seguridad, PDF y consultas públicas.
modules/       Módulos internos del sistema.
public/        Acceso público y consultas para padres.
reports/       Exportaciones y reportes.
repositories/  Consultas SQL optimizadas y paginadas.
services/      Lógica de negocio reutilizable.
storage/       Archivos privados, respaldos, logs y planeaciones.
tests/         Pruebas estáticas del paquete.
```

## Nota de producción

No instales encima de una carpeta vieja. Para probar limpio, renombra la carpeta anterior y descomprime nuevamente el paquete final.
