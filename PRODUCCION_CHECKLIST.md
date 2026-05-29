# Checklist de producción - SGCE 2026 FINAL

## Antes de instalar

- PHP 8.1 o superior.
- Extensiones PHP: PDO MySQL, Zip, SimpleXML, mbstring, fileinfo.
- Base de datos MySQL/MariaDB vacía y exclusiva.
- Carpeta `config/` con permisos de escritura durante la instalación.
- Carpetas `storage/`, `storage/backups/`, `storage/logs/` y `storage/planeaciones/` con permisos de escritura.
- En Plesk/cPanel, no agregar reglas manuales PHP/Nginx que dupliquen las del panel.

## Instalación

1. Subir todo el contenido de la carpeta `SGCE/` al dominio o subcarpeta.
2. Abrir `Instalar.php` desde el navegador.
3. Ejecutar `Verificar servidor`.
4. Capturar datos de MySQL, escuela, ciclo escolar y administrador.
5. Escribir la confirmación `INSTALAR SGCE`.
6. Entrar desde `index.php`.

## Después de instalar

- Confirmar que el login funcione.
- Confirmar que `storage/install.lock` exista.
- Si `Instalar.php` no se eliminó automáticamente, eliminarlo manualmente.
- Probar alta de maestro, grupo, alumno y asignación.
- Probar importación CSV/Excel con archivo pequeño.
- Probar exportación PDF/Excel.
- Probar respaldo de solo datos.

## Plesk y PageSpeed

El `.htaccess` ya incluye `ModPagespeed off`. No quitarlo. Si PageSpeed se activa, puede reescribir archivos CSS/JS y romper botones, colores o modales.

## Calificación final

SGCE 2026 FINAL queda en **9.4/10** para entrega. El margen restante depende de pruebas reales con datos del cliente, concurrencia del hosting contratado y configuración específica del servidor.
