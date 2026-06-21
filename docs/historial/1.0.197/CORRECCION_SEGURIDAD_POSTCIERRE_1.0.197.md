# Corrección de seguridad post-cierre SGCE 1.0.197

## Motivo

Se detectó que el paquete instalable conservaba la carpeta `tests/` con el harness `tests/sgce_197_fallback.php` y un archivo de caché Python en `tests/__pycache__/`.

Aunque el harness fue útil para generar evidencia real de pruebas, no debe distribuirse dentro de la carpeta pública de producción. En Apache/Plesk podía quedar accesible si el sitio se instalaba completo en el document root.

## Corrección aplicada

- Se eliminó por completo la carpeta `tests/` del paquete instalable de producción.
- Se eliminó cualquier archivo `__pycache__/` y `*.pyc`.
- Se agregó una regla preventiva en `.htaccess` para bloquear `tests/` y `__pycache__/` si alguien los copiara accidentalmente en una instalación futura.
- Se conservaron los logs de evidencia en `docs/historial/1.0.197/` como archivos documentales, no como scripts ejecutables.

## Alcance

No se cambió lógica académica, calificaciones, asistencia, conducta, migración ni base de datos.

## Verificación

Criterios esperados:

```bash
find . -path '*/tests/*' -o -path '*/__pycache__/*' -o -name '*.pyc'
# Sin resultados

find . -type f -name '*.php' -print0 | xargs -0 -n1 php -l
# Sin errores de sintaxis
```
