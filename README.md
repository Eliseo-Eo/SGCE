# SGCE 1.0.185 - Versión final limpia instalable

Versión limpia del Sistema Gestor de Control Escolar preparada para instalación desde cero, pruebas reales y operación prolongada.

## Carpeta de instalación

Sube el contenido de `Produccion/` al servidor o a tu entorno local.

## Alcance funcional

- Administración escolar: Anuncios, ciclos/periodos, maestros, grupos, materias, alumnos, asignaciones, expedientes, usuarios, respaldos, migración, configuración y bitácora.
- Portal docente: Clases asignadas, pase de lista, conducta desde asistencia, calificaciones, planeaciones y exportaciones.
- Conducta y disciplina: Reporte manual, validación administrativa, visibilidad controlada para padres y seguimiento en expediente.
- Centro de reportes: Asistencia por grupo, asistencia por asignación, asistencia individual, calificaciones por periodo, boleta individual y kardex individual por ciclo o por todos los ciclos conservados.
- Consulta pública: Vista externa para padres/tutores con asistencia, conducta visible y calificaciones.

## Instalación desde cero

1. Copia el contenido de `Produccion/` en la raíz web del sistema.
2. Verifica permisos de `storage/`, `storage/backups/`, `storage/logs/`, `storage/planeaciones/`, `storage/tmp_uploads/` y `storage/locks/`.
3. Abre `Instalar.php`.
4. Captura conexión MySQL, datos de escuela, ciclo inicial, oferta educativa y usuario administrador.
5. Al finalizar, confirma que exista `storage/install.lock`.
6. Entra al sistema y prueba el flujo mínimo: maestros, grupos, alumnos, materias, asignaciones, asistencia, conducta, calificaciones, expediente, reportes y consulta pública.

## Notas de limpieza

- Producción no contiene carpetas `tests/` ni `tools/`.
- Los archivos de cambios de versiones intermedias fueron consolidados.
- La documentación y constantes internas quedaron homologadas a SGCE 1.0.185.
- La matrícula de alumnos se genera automáticamente; el formulario de alta ya no solicita matrícula manual.

## Seguridad operativa

- No expongas `config/`, `install/`, `storage/`, `includes/`, `repositories/`, `services/`, `cron/` ni `Desarrollo/` públicamente.
- Después de instalar, conserva `storage/install.lock`.
- En producción usa HTTPS, respaldos programados y credenciales MySQL exclusivas para el sistema.
