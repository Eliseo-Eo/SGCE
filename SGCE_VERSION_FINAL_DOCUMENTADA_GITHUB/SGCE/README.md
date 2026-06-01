# SGCE - Sistema Gestor de Control Escolar

SGCE es un sistema web en PHP y MySQL para administrar control escolar: docentes, alumnos, grupos, asignaciones, calificaciones, asistencia, planeaciones, avisos, reportes, respaldos y consulta pública para padres de familia.

Esta versión está organizada para instalación desde cero y para control de versiones en GitHub.

## Estado de esta entrega

- Favicon e imágenes centralizados en `assets/media/img/`.
- Módulos internos protegidos con `.htaccess` y acceso mediante wrappers en raíz.
- Instalador inicial en `Instalar.php` con verificación de servidor, creación de base de datos, configuración local, ciclo escolar y administrador inicial.
- Manuales técnicos y de usuario regenerados desde cero dentro de `docs/`.
- Archivos de configuración local, logs, backups y subidas protegidos para no exponerse públicamente.

## Requisitos recomendados

- PHP 8.1 o superior.
- MySQL 8.x o MariaDB compatible con InnoDB, claves foráneas y columnas generadas.
- Extensiones PHP: `pdo`, `pdo_mysql`, `mbstring`, `zip`, `simplexml`, `fileinfo`, `iconv` y `json`.
- Servidor Apache con soporte `.htaccess` o reglas equivalentes en Nginx/Plesk.
- Permisos de escritura en `config/` y `storage/` durante la instalación.

## Instalación rápida desde cero

1. Sube la carpeta `SGCE` al servidor.
2. Crea una base de datos vacía para el sistema.
3. Asegura permisos de escritura:

```bash
sudo chown -R www-data:www-data SGCE/config SGCE/storage
sudo find SGCE/storage -type d -exec chmod 775 {} \;
sudo chmod 775 SGCE/config
```

4. Abre en el navegador:

```text
https://tu-dominio.com/SGCE/Instalar.php
```

5. Captura conexión MySQL, datos de escuela, ciclo escolar, periodos y administrador inicial.
6. Al finalizar, el instalador crea `config/database.local.php`, `storage/install.lock`, elimina `install/` y se programa para eliminar `Instalar.php`.
7. Entra desde:

```text
https://tu-dominio.com/SGCE/index.php
```

## Estructura del proyecto

```text
SGCE/
├── assets/
│   ├── css/                  Estilos minificados y estilos por módulo.
│   ├── js/                   JavaScript del sistema y validaciones visuales.
│   └── media/img/            Favicon e imágenes institucionales del sistema.
├── config/                   Conexión y configuración local del servidor.
├── cron/                     Respaldos automáticos diario/semanal.
├── docs/                     Manuales y documentación para GitHub.
├── includes/                 Helpers, seguridad, PDF y consultas públicas.
├── install/                  SQL base para instalación inicial.
├── modules/                  Módulos internos protegidos del sistema.
│   └── admin/                Lógica, datos y vista del panel administrativo.
├── public/                   Inicio, login y consultas públicas.
├── reports/                  Exportaciones, reportes y respaldos.
├── services/                 Funciones de acceso a datos por módulo.
└── storage/                  Respaldos, logs, planeaciones y temporales protegidos.
```

## Documentación

- [`docs/MANUAL_TECNICO_INSTALACION_SGCE.md`](docs/MANUAL_TECNICO_INSTALACION_SGCE.md): instalación, estructura, seguridad, respaldos y despliegue.
- [`docs/MANUAL_USUARIO_SGCE.md`](docs/MANUAL_USUARIO_SGCE.md): uso por administradores, docentes y padres de familia.
- [`docs/REVISION_FUNCIONES_SGCE.md`](docs/REVISION_FUNCIONES_SGCE.md): revisión técnica de funciones, módulos y validaciones.
- PDFs equivalentes incluidos en `docs/` para entrega al cliente.

## Archivos que no deben subirse con datos reales

El repositorio incluye una `.gitignore` para evitar subir datos sensibles o generados en producción. Revisa especialmente:

- `config/database.local.php`
- `storage/backups/*`
- `storage/logs/*`
- `storage/planeaciones/*`
- `storage/tmp_uploads/*`
- `storage/install.lock`

## Seguridad incluida

- Sesiones con cookies `HttpOnly`, `SameSite=Strict` y `secure` cuando el sitio usa HTTPS.
- Tokens CSRF en formularios POST.
- Rate limit para login y consultas públicas.
- Bitácora de movimientos administrativos.
- Bloqueo de acceso directo a carpetas internas.
- Contraseñas almacenadas mediante hash seguro de PHP.
- Protección para archivos SQL, ZIP, logs, temporales y configuración local.

## Flujo principal

1. El administrador configura escuela, ciclos, periodos, grupos, alumnos, docentes y asignaciones.
2. El docente registra asistencia, calificaciones y sube planeaciones.
3. El administrador revisa planeaciones, genera reportes, consulta bitácora y respalda/restaura información.
4. Padres de familia consultan asistencia y calificaciones mediante datos del alumno.

## Nota de despliegue

Para reemplazar una instalación vieja no descomprimas encima de la carpeta anterior. Renombra o respalda la carpeta vieja y sube la versión nueva limpia. Esto evita que queden archivos obsoletos como favicons duplicados, ZIPs anteriores o respaldos mezclados.
