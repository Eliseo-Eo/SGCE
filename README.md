# SGCE - Sistema Gestor de Control Escolar

SGCE es un sistema web en PHP y MySQL para administrar control escolar en una institucion educativa. Incluye administracion de docentes, grupos, alumnos, asignaciones, asistencia, calificaciones, avisos, planeaciones, consultas publicas, reportes, respaldos y restauracion de base de datos.

## Estado de la entrega

Esta entrega queda preparada para GitHub, instalacion limpia y prueba final en servidor. El paquete esta depurado para no incluir ZIP internos, archivos temporales, favicon duplicado en raiz ni residuos de trabajo.

Puntos incluidos:

- Estructura de carpetas ordenada por responsabilidad.
- Entradas publicas en raiz mediante wrappers PHP.
- Modulos internos protegidos contra acceso directo.
- Favicon centralizado en `assets/media/img/`.
- Transiciones suaves y consistentes mediante `assets/css/sgce-soft-motion.css`.
- Panel administrativo optimizado para cargar solamente la pestana activa.
- Modales de importar, modificar y eliminar homologadas.
- Boton cancelar en modales con fondo blanco; en hover/focus/active solo cambia el texto/icono al color institucional.
- Campo de planeaciones por ciclo sin valor impuesto en el instalador; la institucion escribe la cantidad requerida.
- Documentacion tecnica y de usuario actualizada.

## Requisitos

- PHP 8.1 o superior recomendado.
- MySQL 5.7/8.0 o MariaDB compatible.
- Apache con `.htaccess` habilitado.
- Extension PDO MySQL activa.
- Permisos de escritura en `storage/` y `config/` durante la instalacion.

## Instalacion limpia

1. Subir o descomprimir la carpeta `SGCE` en el servidor.
2. Entrar a `http://tu-dominio/SGCE/Instalar.php`.
3. Capturar conexion MySQL, datos institucionales, ciclo activo, periodos y usuario administrador.
4. Finalizar la instalacion.
5. Iniciar sesion desde `index.php`.
6. Conservar `storage/install.lock` para bloquear reinstalaciones accidentales.

En Ubuntu local:

```bash
cd /var/www/html
sudo unzip SGCE_FINAL_DESDE_0.zip
sudo chown -R www-data:www-data SGCE/storage SGCE/config
```

Para reemplazar una instalacion anterior, no descomprimas encima de la carpeta vieja. Primero respalda o renombra la carpeta anterior y despues monta esta entrega limpia.

## Carpetas principales

| Carpeta | Uso |
|---|---|
| `assets/` | CSS, JavaScript e imagenes del sistema. |
| `config/` | Conexion y configuracion local generada por instalador. |
| `cron/` | Tareas programadas de respaldo. |
| `docs/` | Manuales, estructura, revision y auditoria. |
| `includes/` | Helpers, seguridad, PDF y consultas publicas. |
| `install/` | Script SQL base. |
| `modules/` | Pantallas internas del sistema. |
| `public/` | Pantallas publicas y salida de sesion. |
| `reports/` | Exportaciones, reportes y respaldos. |
| `services/` | Capa de servicios por entidad. |
| `storage/` | Respaldos, logs, planeaciones y temporales. |

## Seguridad incluida

- Contrasenas almacenadas con `password_hash()`.
- Tokens CSRF en formularios POST.
- Validacion de roles y permisos.
- Encabezados de seguridad HTTP.
- Directorios internos protegidos con `.htaccess`.
- Archivos generados en `storage/` protegidos.
- Rate limit para consultas publicas sensibles.
- Uso de consultas preparadas PDO.

## Documentacion

- `docs/MANUAL_TECNICO_INSTALACION_SGCE.md`
- `docs/MANUAL_USUARIO_SGCE.md`
- `docs/REVISION_FUNCIONES_SGCE.md`
- `docs/ESTRUCTURA_PROYECTO_SGCE.md`
- `docs/AUDITORIA_FINAL_SGCE.txt`

## Prueba recomendada antes de entrega formal

1. Instalar desde cero.
2. Crear usuario administrador.
3. Registrar docente, grupo, alumno y asignacion.
4. Capturar asistencia y calificaciones.
5. Subir y revisar planeacion.
6. Generar reportes PDF/CSV.
7. Crear respaldo y probar restauracion en ambiente controlado.

La revision estatica no sustituye la prueba funcional con MySQL y navegador en el servidor real.
