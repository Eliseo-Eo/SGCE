# SGCE 1.0.198 — Corrección de seguridad: pruebas fuera de Producción

## Motivo

Se detectó que el paquete instalable SGCE 1.0.197 conservaba la carpeta `tests/` dentro de Producción con harnesses usados para generar evidencia real de pruebas, incluyendo `tests/sgce_197_fallback.php`, `tests/kardex_query_count_sqlite.py` y un artefacto de caché Python en `tests/__pycache__/`.

Aunque esos harnesses fueron útiles para generar evidencia real, no deben distribuirse dentro del paquete público de producción. En Apache/Plesk podían quedar accesibles si el sitio se instalaba completo en el document root.

El riesgo principal no era fuga de credenciales ni ejecución remota arbitraria, sino exposición de información técnica: versión exacta instalada y comportamiento interno del CSRF del instalador y del caché de ciclo/oferta activa.

## Alcance

Esta corrección es únicamente de housekeeping de versión, evidencia y documentación del hotfix. No cambia lógica académica, Kardex, caché de ciclo/oferta activa, CSRF del instalador, asistencia, calificaciones, migración, conducta ni base de datos.

No se agregó 2FA. No se agregó PIN ni token familiar.

## Corrección aplicada en el hotfix de seguridad

1. `Produccion/tests/` no existe en el paquete final.
2. Los harnesses ejecutables de verificación no se incluyen en Producción.
3. La evidencia ya generada permanece como texto en `docs/historial/1.0.197/`, porque son logs documentales y no scripts ejecutables.
4. Se eliminó cualquier `__pycache__/`, `*.pyc` y `*.pyo` del paquete de producción.
5. Se agregó defensa preventiva en `.htaccess` para bloquear carpetas accidentales de desarrollo en cualquier nivel de ruta: `tests/`, `tools/`, `scripts/`, `dev/` y `__pycache__/`.
6. Se agregó bloqueo explícito para artefactos Python compilados `*.pyc` y `*.pyo`.

## Nota técnica sobre `.htaccess`

La recomendación inicial de usar `LocationMatch` no se aplicó literalmente porque `LocationMatch` no es una directiva adecuada para `.htaccess` en muchos entornos Apache/Plesk; normalmente corresponde a configuración de servidor o virtual host. Para el despliegue real en Plesk se usó una defensa compatible con `.htaccess`, basada en `mod_rewrite` y `RedirectMatch`.

## Verificación esperada del paquete final

```bash
find Produccion -path '*/tests/*' -o -path '*/__pycache__/*' -o -name '*.pyc' -o -name '*.pyo'
# Sin resultados

find Produccion -maxdepth 1 -type d -name tests
# Sin resultados

unzip -l SGCE_1.0.198_Cierre_Real_Produccion_Hotfix_Seguridad_Tests.zip | grep -E '(^|/)(tests|__pycache__)(/|$)|\.py[co]$'
# Sin resultados
```

## Versionado

El hotfix se identifica como SGCE 1.0.198 para distinguirlo de la 1.0.197 vulnerable que todavía contenía `Produccion/tests/` en el paquete instalable inicial.

Las fuentes reales de versión del sistema son:

- `src/Foundation/Version.php`
- `VERSION.txt`
