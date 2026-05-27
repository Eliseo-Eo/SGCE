# Checklist de entrega SGCE

## Servidor

- [ ] PHP 8.1 o superior.
- [ ] MySQL / MariaDB disponible.
- [ ] HTTPS activo.
- [ ] Extensiones PHP requeridas activas.
- [ ] Carpetas `config/` y `storage/` con permisos de escritura durante instalación.

## Instalación

- [ ] Base de datos exclusiva para SGCE.
- [ ] Instalador ejecutado correctamente.
- [ ] Administrador principal creado.
- [ ] Datos oficiales de escuela revisados.
- [ ] Ciclo escolar y periodos revisados.

## Después de instalar

- [ ] Iniciar sesión con el administrador.
- [ ] Revisar módulo Configuración.
- [ ] Crear usuarios reales.
- [ ] Crear grupos, alumnos, maestros y asignaciones.
- [ ] Probar asistencia, calificaciones y reportes.
- [ ] Generar respaldo inicial.

## Seguridad

- [ ] Retirar permisos 777 si se usaron durante instalación.
- [ ] Mantener `config/` y `storage/` protegidos.
- [ ] Probar que no se puedan descargar archivos `.sql`, `.log` o configuración.
- [ ] Usar contraseñas fuertes.
- [ ] Restringir acceso al servidor solo a personal autorizado.
