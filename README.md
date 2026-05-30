# SGCE - Sistema Gestor de Control Escolar

SGCE es un sistema web para control escolar con administración de alumnos, docentes, grupos, asignaciones, asistencias, calificaciones, planeaciones, reportes, respaldos, bitácora y consulta pública para padres/alumnos.

## Requisitos

- PHP 8.1 o superior.
- MySQL 5.7+ o MariaDB 10.4+.
- Apache con `.htaccess` habilitado.
- Extensiones PHP recomendadas: `pdo_mysql`, `zip`, `simplexml`, `mbstring`, `fileinfo`, `json` y `session`.
- Permisos de escritura en `storage/` y, durante la instalación, en `config/`.

## Instalación rápida desde cero

1. Subir la carpeta `SGCE` completa al servidor.
2. Crear una base de datos vacía y un usuario MySQL con permisos completos sobre esa base.
3. Abrir `Instalar.php` en el navegador.
4. Ejecutar la verificación del servidor.
5. Capturar datos de escuela, ciclo escolar inicial y administrador.
6. Confirmar la instalación escribiendo `INSTALAR SGCE`.
7. Entrar desde `index.php`.

## Módulos principales

- Login con color institucional configurable.
- Dashboard administrador.
- Maestros, grupos, alumnos y asignaciones.
- Importación CSV/Excel.
- Asistencias con contadores.
- Calificaciones por periodo.
- Planeaciones docentes.
- Avisos para todos, maestros o padres.
- Reportes PDF/Excel.
- Respaldos y restauración de datos SGCE.
- Consulta pública individual de asistencias y calificaciones.
- Bitácora de movimientos.

## Estructura general

```text
SGCE/
├── assets/       # CSS, JS e imágenes
├── config/       # Configuración de conexión
├── docs/         # Manual técnico y manual de usuario
├── includes/     # Funciones comunes, seguridad y PDF
├── install/      # SQL base para instalación
├── modules/      # Módulos internos
├── public/       # Login y consulta pública
├── reports/      # Exportaciones y respaldos
├── services/     # Servicios por entidad
├── storage/      # Backups, logs, locks y planeaciones
└── tests/        # Pruebas estáticas por CLI
```

## Seguridad incluida

- Protección por sesión y roles.
- CSRF en formularios internos y públicos.
- Rate limit en consulta pública.
- `.htaccess` para bloquear SQL, ZIP, logs, README, MD, DM, MANIFEST y archivos sensibles.
- Respaldos SGCE con validación antes de restaurar.

## Pruebas técnicas

Desde terminal, en la raíz del proyecto:

```bash
php tests/RunStaticChecks.php
```

La prueba revisa sintaxis PHP/JS, rutas críticas, protección de archivos, formularios, consultas públicas, respaldos, restauración e indicadores de limpieza.

## Documentación

- `docs/MANUAL_TECNICO_INSTALACION_SGCE.pdf`
- `docs/MANUAL_TECNICO_INSTALACION_SGCE.docx`
- `docs/MANUAL_USUARIO_SGCE.pdf`
- `docs/MANUAL_USUARIO_SGCE.docx`

## Recomendación antes de producción

Instalar en una base vacía, cargar datos reales de prueba, validar importaciones, asistencia, calificaciones, planeaciones, reportes, consulta pública, respaldos y funcionamiento en celular/tablet/escritorio antes de habilitarlo oficialmente.
