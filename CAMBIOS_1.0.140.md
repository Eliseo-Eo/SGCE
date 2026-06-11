# SGCE 1.0.140 - Limpieza final, pruebas visuales reales y hardening de producción

## Seguridad y producción

- Proxy headers solo se aceptan desde proxies confiables configurados.
- Se agregó redirección real HTTP a HTTPS cuando `force_https` está activo.
- Las cookies usan path basado en `base_url`.
- Producción no crea carpeta `/tools` automáticamente.

## Instalador

- `base_url` ahora es visible y editable.
- Se conserva el prediagnóstico compacto.

## Visual y CSS

- Se movieron estilos inline restantes a CSS.
- Se homologaron textos con mayúsculas naturales en asistencia y calificaciones.
- Se redujeron reglas correctivas específicas sin cambiar el diseño visual.

## Pruebas y documentación

- Script visual actualizado a 1.0.140.
- Soporte de capturas con sesión autenticada real por token o credenciales.
- Nuevas pruebas para detectar versiones viejas, enlaces rotos, script visual obsoleto y rastros de desarrollo en Producción.
- Manuales y documentos actualizados a 1.0.140.
