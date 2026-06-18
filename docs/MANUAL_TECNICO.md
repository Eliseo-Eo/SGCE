# Manual técnico - SGCE 1.0.185

## Estructura

- `config/`: conexión, versión y configuración base.
- `includes/`: seguridad, layout, helpers, PDF, importación y mantenimiento.
- `modules/`: controladores internos.
- `views/`: vistas administrativas y docentes.
- `repositories/`: acceso a datos.
- `services/`: reglas de negocio.
- `reports/`: exportaciones PDF/Excel.
- `assets/`: CSS y JavaScript.
- `install/`: SQL e instalador.
- `storage/`: respaldos, logs, planeaciones y temporales.

## Seguridad

El sistema usa sesiones seguras, protección de carpetas, consultas preparadas, escape de salida y bloqueo de instalador mediante `storage/install.lock`.

## Mantenimiento

Configura respaldos programados, monitorea logs, conserva el directorio `storage/` fuera de acceso público directo y prueba restauraciones periódicamente.
