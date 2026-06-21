# SGCE 1.0.197 — Corrección puntual de seguridad: pruebas fuera de Producción

## Alcance
Corrección puntual sobre el hallazgo de `Produccion/tests/` en el ZIP instalable de cierre.
No se modificó Kardex, caché de ciclo/oferta activa, CSRF del instalador, lógica académica, asistencia, calificaciones ni conducta.

## Cambios aplicados
1. `Produccion/tests/` no existe en el paquete final.
2. Los harnesses ejecutables de verificación no se incluyen en Producción. La evidencia ya generada permanece como texto en `docs/historial/1.0.197/`.
3. Se agregó defensa preventiva en `.htaccess` para bloquear carpetas accidentales de desarrollo en cualquier nivel de ruta: `tests/`, `tools/`, `scripts/`, `dev/` y `__pycache__/`.
4. Se agregó bloqueo explícito de artefactos Python compilados `*.pyc` y `*.pyo`.
5. Se verificó que no existan `__pycache__/` ni `*.pyc` en el paquete de producción.

## Verificación ejecutada
```text
find Produccion -path '*/tests/*' -o -path '*/__pycache__/*' -o -name '*.pyc' -o -name '*.pyo'
# Sin resultados

find Produccion -maxdepth 1 -type d -name tests
# Sin resultados

find Produccion -type f -name '*.php' -print0 | xargs -0 -n1 php -l
# Sin errores de sintaxis
```

## Nota
La recomendación de usar `LocationMatch` no se aplicó literalmente porque `LocationMatch` no es una directiva válida para `.htaccess` en muchos entornos Apache/Plesk. Se usó `mod_rewrite` + `RedirectMatch`, que sí corresponde mejor al despliegue en Plesk.
