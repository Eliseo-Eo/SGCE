# SGCE - Versión final limpia para instalación desde cero

Sistema de Gestión y Control Escolar preparado para iniciar con base de datos nueva.

## Instalación recomendada

1. Copia la carpeta del sistema en tu servidor local o hosting PHP.
2. Importa `ControlEscolar.sql` en MySQL/MariaDB. Este archivo crea la base `ControlEscolar` desde cero.
3. Revisa en `Conexion.php` los datos de conexión:
   - host
   - base de datos
   - usuario
   - contraseña
4. Entra al sistema desde `index.php`.
5. Usuario inicial:
   - Usuario: `Admin`
   - Contraseña: `Admin123`
6. Después de entrar, crea tus usuarios reales desde **Usuarios y Roles**.

## Roles disponibles

- Administrador
- Maestro
- Director
- Secretario
- Coordinador
- Prefecto

## Diseño y estructura

- CSS centralizado en `assets/css/sgce-base.css`.
- JavaScript por módulo en `assets/js/`.
- Helpers compartidos en `includes/SGCE_Helpers.php`.
- `Conexion.php` solo conserva conexión, sesión, seguridad base, CSRF, rate limit y bitácora.
- Las migraciones ya no se ejecutan automáticamente en cada carga.

## Base de datos desde cero

`ControlEscolar.sql` ya incluye:

- Usuarios y roles
- Grupos
- Alumnos
- Asignaciones
- Ciclos escolares
- Periodos de evaluación
- Calificaciones por periodo
- Asistencias
- Avisos
- Bitácora
- Rate limit
- Expiración de sesión

## Reportes

El centro de reportes ya no carga miles de alumnos de golpe. Para boletas individuales primero se filtra por grupo o por búsqueda de nombre.

## Seguridad operativa

Las contraseñas se conservan visibles en la base de datos por decisión operativa del proyecto. Por eso los respaldos SQL deben tratarse como información sensible.

Antes de usar en producción real:

- Cambia la contraseña del usuario `Admin`.
- Elimina o protege `Instalar.php` si no lo usarás.
- No publiques respaldos SQL.
- Usa HTTPS si el sistema se sube a internet.

## Archivo opcional

`Migrar.php` queda como validador técnico para instalaciones existentes, pero si instalas desde cero con `ControlEscolar.sql`, normalmente no necesitas ejecutarlo.
