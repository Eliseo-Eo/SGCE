# Pruebas visuales móviles SGCE 1.0.140

Esta guía valida el sistema con capturas reales en celular, tablet y escritorio.

## Ejecución pública básica

```bash
SGCE_VISUAL_BASE_URL="https://tu-dominio-sgce.com" bash tools/visual-mobile-smoke.sh
```

## Ejecución con sesión autenticada real

Opción recomendada con usuario de prueba:

```bash
SGCE_VISUAL_BASE_URL="https://tu-dominio-sgce.com" \
SGCE_VISUAL_LOGIN_USER="admin" \
SGCE_VISUAL_LOGIN_PASSWORD="tu_password" \
bash tools/visual-mobile-smoke.sh
```

Opción con token de sesión:

```bash
SGCE_VISUAL_BASE_URL="https://tu-dominio-sgce.com" \
SGCE_VISUAL_AUTH_TOKEN="valor_de_la_cookie_AuthToken" \
bash tools/visual-mobile-smoke.sh
```

## Salida

El reporte queda en:

```txt
tests/visual-mobile-captures/reporte-visual.html
```

## Qué revisar

- Sin scroll horizontal.
- Botones móviles a ancho completo cuando corresponda.
- Hero público en orden correcto: icono, escuela, título, descripción y botón abajo.
- Tablas administrativas sin desbordar.
- Paginadores con primera, anterior, números, siguiente y última.
- Páginas autenticadas capturadas realmente como usuario, no como login.
