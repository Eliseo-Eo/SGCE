# SGCE - Sistema de Gestión y Control Escolar

**Versión Final de Producción 1.0**

SGCE es un sistema web para administrar la operación escolar: ciclos y periodos, maestros, grupos, alumnos, asignaciones, avisos, expedientes, asistencia, calificaciones, reportes PDF/Excel, boletas, consulta para padres, usuarios, respaldos y bitácora.

Esta entrega está preparada para instalarse desde cero en una base de datos exclusiva. No incluye alumnos, maestros, grupos, asignaciones ni avisos precargados.

## Requisitos del servidor

- PHP 8.1 o superior recomendado.
- MySQL o MariaDB.
- Servidor web Apache o compatible con PHP.
- Extensiones PHP: `pdo`, `pdo_mysql`, `mbstring`, `zip`, `simplexml`, `fileinfo`, `iconv` y `json`.
- Permiso temporal de escritura para PHP en `config/` y `storage/` durante la instalación.
- HTTPS activo en producción.

## Instalación desde cero

1. Descomprime el sistema en el servidor.
2. Abre `Instalar.php` desde el navegador.
3. Captura los datos de conexión MySQL.
4. Pulsa **Verificar servidor** para revisar versión PHP, extensiones, permisos y conexión MySQL.
5. Captura los datos oficiales de la escuela, color institucional, ciclo escolar inicial y periodos.
6. Crea el administrador principal.
7. Escribe `INSTALAR SGCE` para confirmar.
8. Entra desde `index.php` con el administrador creado.

Al terminar, el instalador crea `config/database.local.php`, bloquea nuevas instalaciones con `storage/install.lock` y deja el sistema listo para operar. Si el servidor lo permite, elimina `Instalar.php` después de confirmar que puedes iniciar sesión.

## Flujo recomendado de primer uso

1. **Periodos**: revisar ciclo escolar y parciales.
2. **Maestros**: registrar docentes.
3. **Grupos**: crear grado, grupo y turno.
4. **Alumnos**: inscribir estudiantes.
5. **Asignaciones**: vincular materia, maestro y grupo.
6. **Avisos**: publicar comunicados.
7. **Expedientes**: consultar historial individual.
8. **Reportes**: generar asistencia, calificaciones y boletas.
9. **Padres**: validar consulta pública protegida.
10. **Usuarios**: administrar roles y cuentas del personal.
11. **Respaldos**: generar, descargar o restaurar copias.
12. **Configuración**: actualizar datos oficiales y color institucional.
13. **Bitácora**: revisar movimientos relevantes.

## Importaciones

SGCE permite importar catálogos desde **CSV** y **Excel .xlsx**:

- Maestros: `NOMBRE, USUARIO, CONTRASEÑA`
- Grupos: `GRADO, GRUPO, TURNO`
- Alumnos: `NOMBRE`

Para alumnos, selecciona primero el grupo destino. Para maestros, la contraseña debe cumplir la política de seguridad del sistema.

## Reportes PDF reales

Los reportes en formato PDF se generan desde el servidor como archivos PDF descargables:

- Boleta individual.
- Calificaciones por asignación.
- Calificaciones por grupo.
- Asistencias por asignación.
- Asistencias por grupo.

Los reportes Excel se descargan como archivos compatibles con hojas de cálculo.

## Respaldos

El sistema incluye respaldos manuales desde el panel y respaldos automáticos mediante cron.

Ejemplo de cron diario:

```bash
0 2 * * * /usr/bin/php /ruta/SGCE/cron/backup_diario.php
```

Ejemplo de cron semanal:

```bash
30 2 * * 0 /usr/bin/php /ruta/SGCE/cron/backup_semanal.php
```

Los respaldos se guardan en la carpeta configurada durante la instalación. Por seguridad, se recomienda ubicar esa carpeta fuera del directorio público cuando el hosting lo permita.

## Control de errores

El sistema muestra mensajes simples al usuario final y registra detalles técnicos en:

```text
storage/logs/
```

Los logs permiten revisar errores de PHP, conexión, instalador, respaldos automáticos y fallas no controladas sin mostrar información técnica al usuario final.

## Archivos importantes

- `Instalar.php`: instalación inicial.
- `index.php`: acceso principal.
- `Admin.php`: panel administrador.
- `Maestro.php`: portal docente.
- `ConsultaPadre.php`: consulta pública protegida.
- `cron/backup_diario.php`: respaldo automático diario.
- `cron/backup_semanal.php`: respaldo automático semanal.
- `docs/MANUAL_USUARIO_SGCE.pdf`: manual para usuarios finales.
- `docs/MANUAL_TECNICO_INSTALACION_SGCE.pdf`: manual técnico para instalación y soporte.

## Seguridad recomendada

- Usar HTTPS.
- Usar un usuario MySQL exclusivo para SGCE.
- Proteger `config/`, `includes/`, `modules/`, `reports/`, `storage/`, `install/` y `cron/`.
- Quitar permisos amplios después de instalar.
- Revisar periódicamente `storage/logs/`.
- Programar respaldos automáticos.
- Descargar respaldos antes de cambios importantes.
- Usar contraseñas fuertes para todos los usuarios.
