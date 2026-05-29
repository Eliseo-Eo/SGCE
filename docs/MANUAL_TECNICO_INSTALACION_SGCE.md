# Manual Técnico de Instalación SGCE 2026 FINAL

## 1. Objetivo
SGCE 2026 FINAL es una plataforma escolar para control de alumnos, docentes, grupos, asistencia, calificaciones, reportes, planeaciones, avisos, respaldos y consulta pública.

## 2. Requisitos
- PHP 8.1 o superior.
- MySQL 5.7+ o MariaDB 10.4+.
- Extensiones PHP: pdo_mysql, zip, simplexml, mbstring y fileinfo.
- Apache con .htaccess habilitado.
- Permiso de escritura temporal en config/ y permanente en storage/.

## 3. Instalación local
1. Copiar la carpeta SGCE en el directorio web local.
2. Crear una base vacía o usar un usuario MySQL con permiso para crearla.
3. Abrir Instalar.php.
4. Pulsar Verificar servidor.
5. Capturar datos de escuela, ciclo escolar y administrador.
6. Escribir INSTALAR SGCE.
7. Entrar desde index.php.

## 4. Instalación en Plesk
1. Crear una base de datos exclusiva y vacía desde Plesk.
2. Asignar usuario MySQL con permisos completos sobre esa base.
3. Subir todos los archivos de SGCE al dominio o subdominio.
4. Confirmar permisos de config/ y storage/.
5. Abrir Instalar.php y usar los datos exactos creados en Plesk.
6. No retirar la regla ModPagespeed off del .htaccess.

## 5. Instalación en cPanel
1. Crear base y usuario desde MySQL Databases.
2. Asignar todos los privilegios al usuario.
3. Subir SGCE por File Manager o FTP.
4. Ejecutar Instalar.php.
5. Usar el nombre completo de base y usuario como lo muestra cPanel.

## 6. PageSpeed y cache
El .htaccess desactiva mod_pagespeed porque puede reescribir CSS/JS y romper colores, botones o modales. El sistema usa cache-busting sgce2026final para obligar al navegador a cargar assets actualizados.

## 7. Seguridad
El sistema incluye CSRF, sesiones por token, cookies HttpOnly/SameSite, hash de contraseñas, rate limit en login, bloqueo de carpetas internas y restauración de respaldos firmados.

## 8. Respaldo y restauración
Para restaurar desde el sistema usa Exportar solo datos. El importador rechaza SQL externo, estructura DROP/CREATE y respaldos sin firma SGCE.

## 9. Revisión final
Antes de entregar al cliente: probar login, alta de maestro, grupo, alumno, asignación, asistencia, calificación, importación, exportación, respaldo y consulta pública.
