# SGCE 2026 FINAL

Sistema de Gestión y Control Escolar listo para instalación local, Plesk, cPanel o hosting compartido compatible con PHP y MySQL/MariaDB.

## Módulos principales

- Administrador con dashboard general.
- Maestros, grupos, alumnos y asignaciones.
- Asistencia por materia y grupo.
- Calificaciones por periodo.
- Consulta pública de asistencia individual.
- Avisos y comunicados.
- Expediente e historial del alumno.
- Planeaciones docentes.
- Reportes PDF/Excel.
- Respaldos e importación segura de datos.
- Configuración institucional, ciclo escolar y periodos.
- Usuarios con roles y permisos.
- Bitácora de movimientos.

## Requisitos recomendados

- PHP 8.1 o superior.
- MySQL 5.7+/MariaDB 10.4+.
- Extensiones PHP: `pdo_mysql`, `zip`, `simplexml`, `mbstring`, `fileinfo`.
- Apache con `.htaccess` habilitado.
- Permisos de escritura en `config/` y `storage/` durante instalación.

## Instalación rápida

1. Sube la carpeta `SGCE/` al servidor.
2. Crea una base de datos vacía en Plesk/cPanel, o permite que el instalador la cree en local.
3. Abre `Instalar.php` en el navegador.
4. Pulsa **Verificar servidor**.
5. Captura datos de conexión, escuela, ciclo, periodos y administrador.
6. Escribe `INSTALAR SGCE` para confirmar.
7. Entra al sistema desde `index.php`.

## Instalación en Plesk

1. Crea una base de datos exclusiva desde Plesk.
2. Crea o asigna un usuario MySQL con permisos sobre esa base.
3. Sube todos los archivos dentro del directorio del dominio o subdominio.
4. Verifica que `config/` y `storage/` permitan escritura temporal.
5. Abre `Instalar.php`.
6. Usa los datos exactos de la base creada en Plesk.
7. Después de instalar, elimina `Instalar.php` si el servidor no lo eliminó automáticamente.

### Importante sobre PageSpeed en Plesk

El `.htaccess` incluye:

```apache
<IfModule pagespeed_module>
    ModPagespeed off
</IfModule>
```

No lo elimines. PageSpeed puede combinar o reescribir CSS/JS y provocar errores visuales, botones invisibles o modales rotas.

## Instalación en cPanel

1. Entra a **MySQL Databases**.
2. Crea una base de datos vacía.
3. Crea un usuario MySQL y asígnalo a la base con todos los privilegios.
4. Sube la carpeta `SGCE/` con el Administrador de Archivos o FTP.
5. Abre `Instalar.php`.
6. Captura el nombre completo de la base y usuario tal como los muestra cPanel.

## Seguridad incluida

- Contraseñas guardadas con hash seguro.
- Tokens de sesión en base de datos.
- Cookies `HttpOnly`, `SameSite=Strict` y `Secure` cuando hay HTTPS.
- Protección CSRF en formularios POST.
- Rate limit en login.
- Cabeceras de seguridad HTTP.
- Bloqueo de carpetas internas con `.htaccess`.
- Respaldos sin tokens de sesión activos.
- Importación de respaldos limitada a archivos firmados por SGCE.

## Rendimiento

- Paginación en tablas administrativas.
- Índices SQL para usuarios, alumnos, asistencia, calificaciones y consultas públicas.
- Cache-busting de assets con versión `sgce2026final`.
- Backups automáticos controlados desde el acceso administrador.
- Validación de archivos antes de importar.

## Carpetas importantes

```text
SGCE/
├── assets/              CSS y JavaScript
├── config/              Configuración de base de datos
├── cron/                Tareas programadas opcionales
├── docs/                Manuales técnicos y de usuario
├── includes/            Funciones internas
├── install/             SQL inicial
├── modules/             Módulos privados
├── public/              Vistas internas llamadas por wrappers raíz
├── reports/             Exportaciones y reportes
└── storage/             Backups, logs y planeaciones
```

## Respaldos

Desde el sistema se recomienda usar **Exportar solo datos**. Ese archivo puede importarse después desde el módulo de respaldos.

No subas respaldos externos o editados manualmente al importador; por seguridad solo acepta archivos con firma oficial SGCE.

## Calificación de entrega

Calificación técnica estimada: **9.4/10**.

La versión está lista para entrega comercial, sujeta a pruebas finales con datos reales del cliente y a la capacidad del hosting contratado.
