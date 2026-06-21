# SGCE 1.0.196 — Resultados de verificación ejecutada

## Pruebas automáticas/locales ejecutadas

| Prueba | Resultado |
|---|---|
| Inspección ZIP contra rutas peligrosas | PASA. No se detectaron rutas absolutas ni `../`. |
| Conteo de archivos PHP | 189 archivos. |
| `php -l` sobre todos los PHP | PASA. 189/189 sin errores de sintaxis. |
| `php -l install/installer/InstallerCore.php` | PASA. |
| `php -l reports/ExportarKardexAlumno.php` | PASA. |
| Búsqueda `sourceMappingURL` en Bootstrap local | PASA. No quedan referencias. |
| Búsqueda `.map` en Bootstrap local | PASA. No quedan source maps. |
| Render manual técnico DOCX/PDF | PASA. PDF renderizado en 5 páginas. |
| Render manual instalación DOCX/PDF | PASA. PDF renderizado en 3 páginas. |
| Render manual usuario DOCX/PDF | PASA. PDF renderizado en 4 páginas. |

## Pruebas que NO se pudieron ejecutar aquí

- Instalación real contra MySQL con navegador.
- Login real en dominio final.
- Captura real de asistencia/calificaciones con datos del cliente.
- Exportación PDF/Excel con base productiva.
- Subida/descarga de archivos desde navegador real.
- Restauración de respaldo en servidor real.
- Pruebas de carga concurrente.
- Medición Core Web Vitals en dominio público.

## Interpretación

La versión queda lista para pruebas finales de aceptación. Las pruebas automáticas verifican integridad de sintaxis, consistencia básica del paquete y documentación renderizable, pero no sustituyen el checklist manual.
