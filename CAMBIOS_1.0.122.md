# SGCE 1.0.122 — Rendimiento histórico y consistencia multinivel

## Cambios principales

1. `Calificaciones.Calificacion` cambió a `DECIMAL(5,2)` para permitir 100.00.
2. `KardexAlumno.TurnoSnapshot` cambió a `VARCHAR(40)`.
3. La validación de grupo ahora acepta letras, números, guion, diagonal, punto y guion bajo.
4. Se agregó `cron/mantenimiento_diario.php`.
5. Se agregaron funciones de limpieza diaria en `SGCE_Mantenimiento.php`.
6. Se limpian sesiones vencidas, intentos antiguos, bitácora vieja y respaldos temporales.
7. Se reforzaron límites de PDF en reportes de calificaciones y boletas individuales.
8. Se agregó documentación de rendimiento a 10 años.
9. Se agregaron pruebas de mantenimiento y crecimiento histórico.
10. Se retiraron archivos SQL de actualización de versiones anteriores para mantener el paquete limpio desde cero.
