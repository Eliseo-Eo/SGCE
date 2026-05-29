# SGCE 2026 FINAL

Versión limpia basada únicamente en la última versión estable revisada.

## Calificación técnica estimada

**9.4 / 10** para entrega en producción en hosting compartido, Plesk, cPanel o instalación local.

La calificación considera: seguridad de acceso, control CSRF, sesiones por token, respaldo, restauración segura de solo datos, diseño responsive, compatibilidad con Plesk/cPanel, organización por módulos, validación de archivos, paginación y protección de carpetas internas.

## Correcciones aplicadas en esta edición

1. `.htaccess` reconstruido para producción, sin directivas `php_value` que puedan provocar error 500 en PHP-FPM.
2. PageSpeed/mod_pagespeed queda desactivado para evitar que Plesk reescriba CSS/JS y rompa estilos o botones.
3. Se bloquearon archivos sensibles: SQL, logs, configuración local, manifiestos, reportes internos y documentación técnica desde descarga directa.
4. Se restringió el acceso directo a `public/` para que todo pase por los accesos raíz oficiales.
5. Se agregaron guardas PHP a los archivos públicos internos para evitar ejecución directa fuera del flujo del sistema.
6. Se homologó la confirmación amber/metálica para importaciones, restauración de respaldos, borrado escolar y cierre de sesión.
7. Se corrigió y centralizó el cierre del aviso del docente sin materias asignadas.
8. Se reforzó la alineación general de tachitas en alertas Bootstrap.
9. Se actualizó el cache-busting a `sgce2026final`.
10. Se añadieron índices SQL extra para mejorar consultas de asistencia, riesgo y calificaciones en bases con muchos registros.
11. Se escapó el mensaje de error del login para evitar salida HTML no controlada.
12. Se retiraron archivos de cambios antiguos para entregar una versión limpia.

## Revisión de sintaxis

- PHP: revisado con `php -l` en todos los archivos `.php`.
- JavaScript: revisado con `node --check` en todos los archivos `.js`.
- Estructura ZIP: empaquetada desde carpeta limpia `SGCE/`.

## Recomendaciones para producción

- Usar PHP 8.1 o superior.
- Usar MySQL/MariaDB con InnoDB y `utf8mb4`.
- Crear una base vacía exclusiva para SGCE.
- En Plesk o cPanel, cargar el sistema completo y abrir `Instalar.php`.
- Después de instalar y probar acceso, confirmar que `storage/install.lock` exista.
- Mantener respaldos automáticos fuera de carpetas públicas cuando el hosting lo permita.
- No activar PageSpeed para este sistema.
