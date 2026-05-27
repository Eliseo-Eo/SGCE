# SGCE - Sistema de Gestión y Control Escolar

SGCE es un sistema web para administrar alumnos, docentes, grupos, asignaciones, asistencia, calificaciones, avisos, reportes, boletas, usuarios, respaldos y bitácora de movimientos.

## Requisitos

- PHP 8.1 o superior recomendado.
- MySQL / MariaDB.
- Servidor web Apache o compatible con PHP.
- Extensiones PHP: PDO, PDO MySQL, mbstring y session.
- Acceso de escritura para PHP en las carpetas `config/` y `storage/` durante la instalación.

## Instalación inicial

1. Descomprime el sistema en el servidor.
2. Abre `Instalar.php` desde el navegador.
3. Captura la conexión MySQL.
4. Captura los datos oficiales de la escuela.
5. Captura el ciclo escolar inicial y sus tres periodos de evaluación.
6. Crea el administrador principal.
7. Escribe `INSTALAR SGCE` y presiona **Instalar sistema**.

La base seleccionada se prepara desde cero para SGCE. Usa una base exclusiva para el sistema.

## Primer acceso

Después de instalar, entra a `index.php` e inicia sesión con el usuario administrador creado durante la instalación.

## Configuración general

Desde el panel administrador puedes entrar a **Configuración** para actualizar:

- Nombre oficial de la escuela.
- CCT / clave.
- Director(a).
- Municipio y estado.
- Teléfono y correo.
- Ciclo escolar activo.
- Periodos de evaluación.

Estos datos se usan en reportes, boletas y vistas del sistema.

## Seguridad recomendada

- Usar HTTPS en producción.
- Usar un usuario MySQL exclusivo para SGCE.
- Mantener protegidas las carpetas `config/`, `includes/`, `modules/`, `reports/`, `storage/`, `install/` y `cron/`.
- Mover respaldos fuera del directorio público cuando el hosting lo permita.
- Cambiar contraseñas periódicamente.
- Hacer respaldos antes de cambios importantes.

## Manual de usuario

El paquete incluye el manual en la carpeta `docs/`.
