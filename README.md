# SGCE 1.0.140 - Versión final pulida

SGCE es un Sistema Gestor de Control Escolar para administración, docentes y consulta pública de asistencia/calificaciones.

## Instalación limpia

1. Sube el contenido de `Produccion/` al servidor.
2. Abre `Instalar.php`.
3. Revisa el prediagnóstico.
4. Configura MySQL, URL base, escuela, ciclo escolar y administrador.
5. Finaliza instalación. El instalador se bloquea al terminar.

## Enfoque de la versión 1.0.140

- Limpieza final de rastros de versiones previas.
- Hardening de sesión, HTTPS y proxies confiables.
- URL base editable desde el instalador.
- Cookie path calculado desde la URL base.
- Pruebas visuales móviles con sesión autenticada real.
- Documentación y manuales actualizados a 1.0.140.
- Producción sin carpetas de pruebas ni herramientas internas.

## Recomendación final

Instala desde cero en un entorno de prueba, importa tus archivos reales y ejecuta las pruebas visuales antes de entregarlo definitivamente.
