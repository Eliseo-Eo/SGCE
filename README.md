# SGCE - Sistema Gestor de Control Escolar

SGCE es un sistema web en PHP y MySQL para administrar control escolar en una institucion educativa. Incluye administracion de docentes, grupos, alumnos, asignaciones, asistencia, calificaciones, avisos, planeaciones, consultas publicas, reportes, respaldos y restauracion de base de datos.

## Estado de esta entrega

Esta version queda preparada como base final para subir a GitHub, montar en servidor y probar instalacion limpia desde cero.

Puntos revisados:

- Estructura de carpetas ordenada por responsabilidad.
- Entradas publicas compatibles en raiz mediante wrappers PHP.
- Modulos internos protegidos contra acceso directo.
- Favicon centralizado en `assets/media/img/`.
- Sin favicon duplicado en la raiz del proyecto.
- Sin ZIP interno, archivos temporales o residuos de versiones anteriores.
- README y manuales regenerados para esta entrega.
- Modal de importar, modificar y eliminar homologadas: el boton cancelar conserva fondo blanco y en hover solo cambia texto/icono a color institucional.
- Transiciones suaves al cargar pantallas, sin crecimiento agresivo de botones.
- Campo de planeaciones por ciclo sin valor impuesto en el instalador; la institucion escribe la cantidad deseada.

## Requisitos

- PHP 8.0 o superior recomendado.
- MySQL 5.7/8.0 o MariaDB compatible.
- Apache con `.htaccess` habilitado.
- Extension PDO MySQL activa.
- Permisos de escritura en `storage/` y `config/` durante la instalacion.

## Instalacion limpia

1. Subir o descomprimir la carpeta `SGCE` en el servidor.
2. Entrar a `http://tu-dominio/SGCE/Instalar.php`.
3. Capturar datos de conexion MySQL, datos institucionales, ciclo activo, periodos y usuario administrador.
4. Finalizar la instalacion.
5. Iniciar sesion desde `index.php`.
6. Eliminar o bloquear `Instalar.php` despues de confirmar que el sistema quedo instalado.

En Ubuntu local:

```bash
cd /var/www/html
sudo unzip SGCE.zip
sudo chown -R www-data:www-data SGCE/storage SGCE/config
```

Para reemplazar una instalacion anterior, no descomprimas encima de la carpeta vieja. Primero respalda o renombra la carpeta anterior y despues monta esta version limpia.

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

- Contraseñas almacenadas con hash de PHP.
- Tokens CSRF en formularios POST.
- Validacion de roles y permisos.
- Encabezados de seguridad HTTP.
- Directorios internos protegidos con `.htaccess`.
- Archivos generados en `storage/` protegidos.
- Rate limit para consultas publicas sensibles.
- Uso de consultas preparadas PDO.

## Documentacion

Consulta estos archivos:

- `docs/MANUAL_TECNICO_INSTALACION_SGCE.md`
- `docs/MANUAL_USUARIO_SGCE.md`
- `docs/REVISION_FUNCIONES_SGCE.md`
- `docs/ESTRUCTURA_PROYECTO_SGCE.md`
- `docs/AUDITORIA_FINAL_SGCE.txt`

## Recomendacion antes de entregar al cliente

Realizar prueba funcional real con MySQL y navegador:

1. Instalar desde cero.
2. Crear usuario administrador.
3. Registrar docente, grupo, alumno y asignacion.
4. Capturar asistencia y calificaciones.
5. Subir planeacion.
6. Generar reportes PDF/CSV.
7. Crear respaldo y probar restauracion en ambiente de prueba.

La revision estatica de esta entrega no sustituye la prueba final en el servidor real.
