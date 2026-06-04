# Migraciones SGCE 1.0.91

SGCE distingue dos conceptos:

## 1. Instalación limpia

La fuente oficial de la base de datos es:

```text
install/SGCE.sql
```

Para una instalación desde cero no se requiere aplicar scripts adicionales.

## 2. Migración técnica futura

La carpeta `install/migrations/` queda protegida y preparada para cambios futuros de esquema en instalaciones ya existentes. En esta versión no incluye migraciones heredadas, porque el objetivo es partir de una base limpia.

Las migraciones técnicas se aplicarían por CLI con:

```bash
php tools/AplicarMigraciones.php
```

## 3. Migración académica

La migración académica es diferente a la migración técnica. Sirve para pasar alumnos de un ciclo escolar cerrado al ciclo activo:

- 1° a 2°
- 2° a 3°
- 3° a egresados

Esta operación se realiza desde `MigracionAdmin.php` y trabaja por grupo con transacción para evitar estados incompletos.
