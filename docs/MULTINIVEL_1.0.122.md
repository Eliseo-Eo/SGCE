# Configuración multinivel — SGCE 1.0.122

## Turnos configurables

En Configuración general se puede capturar un turno por línea:

- MATUTINO
- VESPERTINO
- NOCTURNO
- SABATINO
- DOMINICAL
- EN LÍNEA
- SIN TURNO

Los grupos nuevos usan esa lista. Los turnos existentes se conservan.

## Matrícula automática

Si se activa, cuando se registra o importa un alumno sin matrícula, SGCE genera una con este formato:

```text
SGCE-2026-000001
```

El prefijo se configura en Configuración general.

## Escala de calificaciones

Se configura:

- Calificación mínima.
- Calificación máxima.
- Calificación aprobatoria.
- Uso de decimales.

El panel docente valida con esa escala y ya no está fijo a 5-10.

## Instalación y configuración posterior

SGCE permite definir turnos, matrícula automática y escala de calificaciones desde dos lugares:

1. **Instalador:** Para dejar lista la institución desde el primer arranque.
2. **Configuración:** Para ajustar esos valores después de instalado el sistema.

La recomendación es capturar los valores reales desde el instalador cuando ya se conoce la operación de la escuela. Si todavía estás probando, puedes usar los valores por defecto y cambiarlos después.
