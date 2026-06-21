# SGCE 1.0.197 - Checklist de aceptación en servidor real

## Instalación desde cero con MySQL

- [ ] Crear base de datos vacía con `utf8mb4_unicode_ci`.
- [ ] Subir contenido de `Produccion/` al dominio.
- [ ] Confirmar permisos de escritura en `storage/`, `storage/backups/`, `storage/logs/`, `storage/locks/`, `storage/planeaciones/` y `storage/tmp_uploads/`.
- [ ] Abrir `Instalar.php`.
- [ ] Confirmar que la pantalla muestra requisitos del servidor sin errores críticos.
- [ ] Capturar datos MySQL.
- [ ] Capturar datos de escuela.
- [ ] Capturar oferta académica, ciclo inicial, fechas, periodos, planeaciones y admin.
- [ ] Instalar.
- [ ] Confirmar creación de `config/database.local.php`.
- [ ] Confirmar creación de `storage/install.lock`.
- [ ] Confirmar que el instalador queda bloqueado/eliminado después de instalar.
- [ ] Entrar con usuario administrador.

## Validación funcional mínima

- [ ] Dashboard carga sin errores.
- [ ] Periodos muestra el ciclo activo correcto.
- [ ] Crear/activar nuevo ciclo y confirmar que Periodos/Dashboard reflejan el cambio en la misma petición posterior.
- [ ] Configuración guarda oferta académica y periodos sin duplicar activos.
- [ ] Importar o crear maestro, grupo, materia, alumno y asignación.
- [ ] Registrar calificaciones.
- [ ] Generar Kardex individual de un alumno con más de un ciclo.
- [ ] Generar respaldo SQL.
- [ ] Restaurar en entorno de prueba, no en producción directa.

## Seguridad y storage

- [ ] `storage/` no es navegable desde HTTP.
- [ ] `storage/locks/` no permite descarga de archivos `.token`.
- [ ] Consultas públicas no solicitan PIN ni token familiar.
- [ ] No existe 2FA en login, por alcance explícito.

## Evidencia solicitada al correr en VPS

- [ ] Captura de requisitos del instalador.
- [ ] Captura de instalación exitosa.
- [ ] Captura de Dashboard posterior al login.
- [ ] Captura de `VERSION.txt` con `1.0.197`.
- [ ] Exportación de Kardex individual.
- [ ] Respaldo SQL generado y firmado.
