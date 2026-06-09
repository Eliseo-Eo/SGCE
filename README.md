# SGCE 1.0.122 - Rendimiento histórico y consistencia multinivel

Versión limpia para instalación desde cero.

Esta entrega se enfoca en rendimiento, crecimiento histórico y consistencia multinivel. No incluye archivos de actualización desde versiones anteriores; para probarla, instala en una base nueva y vacía.

## Enfoque principal

- Calificaciones listas para escalas 0-100 con `DECIMAL(5,2)`.
- Turnos largos conservados en kardex histórico.
- Grupos más flexibles: letras, números, guion, diagonal, punto y guion bajo.
- Mantenimiento diario por cron para bitácora, sesiones, intentos de seguridad y respaldos temporales.
- Límites más claros para PDF grandes; Excel/CSV queda como salida recomendada para reportes masivos.
- Pruebas de mantenimiento y crecimiento histórico.
- Documentación de rendimiento a 10 años.

## Instalación

Sube únicamente el contenido de `Produccion/` y ejecuta `Instalar.php` en una base de datos vacía.

## Mantenimiento recomendado

Configura en cron una ejecución diaria:

```bash
php /ruta/SGCE/cron/mantenimiento_diario.php
```

Para verificar que el cron está bien ubicado:

```bash
php /ruta/SGCE/cron/mantenimiento_diario.php --self-check
```
