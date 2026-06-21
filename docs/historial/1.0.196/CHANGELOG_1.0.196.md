# SGCE 1.0.196 — Cierre de producción verificado

## Cambios realizados

- Se agregó respaldo real de token CSRF del instalador en `storage/locks`.
- Se mantuvo validación por sesión y cookie del instalador, sin relajar seguridad.
- Se optimizó `reports/ExportarKardexAlumno.php` para cargar periodos, asignaciones y calificaciones por lote.
- Se redujeron consultas del Kardex individual de aproximadamente `2 + 3N` a `5` en el caso general.
- Se eliminaron source maps de Bootstrap del paquete de producción.
- Se removieron comentarios `sourceMappingURL` de Bootstrap minificado.
- Se actualizó versión a `1.0.196`.
- Se reemplazó documentación 1.0.195 por documentación final 1.0.196.

## Evaluado y decidido NO cambiar

- No se agregó 2FA: fuera de alcance y no solicitado para esta entrega.
- No se cambió Bootstrap/FontAwesome a CDN ni se agregaron CDNs nuevos: cambiar fuente de assets en cierre final puede alterar apariencia o disponibilidad.
- No se generaron subsets de FontAwesome: riesgo visual mayor que beneficio sin prueba exhaustiva por módulo.
- No se quitaron fuentes fallback `.ttf`/`.woff`: podrían servir para compatibilidad.
- No se agregaron índices nuevos: el esquema ya contiene índices relevantes y no se contó con datos reales para justificar más índices.
- No se reescribieron módulos: el objetivo fue cierre de calidad, no rediseño arquitectónico.

## Fuera de alcance

- Pruebas de carga con usuarios concurrentes reales.
- Medición de Core Web Vitals en dominio final.
- Pruebas con red inestable o caídas durante restauración/importación.
- Validación completa con datos reales del cliente.
- Auditoría pentest dinámica con herramientas externas sobre servidor publicado.

## Resultado

Versión lista para pruebas finales de aceptación. No se declara libre de bugs; se entrega con hallazgos corregidos y limitaciones documentadas.
