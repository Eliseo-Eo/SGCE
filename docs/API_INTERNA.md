# API interna AJAX — SGCE 1.0.140

Los endpoints internos viven en `api/` y requieren sesión válida, permisos y token CSRF cuando modifican información.

## Principios

- No exponer consultas sin validación de rol.
- No devolver trazas técnicas al cliente.
- Registrar errores técnicos en logs.
- Responder JSON con UTF-8.
