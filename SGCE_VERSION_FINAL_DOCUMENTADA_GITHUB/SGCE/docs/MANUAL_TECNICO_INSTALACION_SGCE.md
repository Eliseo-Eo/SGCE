# Manual técnico e instalación - SGCE

## 1. Objetivo

Este manual describe la instalación, estructura, configuración, seguridad, mantenimiento y revisión técnica del Sistema Gestor de Control Escolar SGCE.

SGCE está diseñado para operar como una aplicación web PHP/MySQL con instalación inicial asistida, módulos protegidos y archivos de trabajo resguardados fuera del acceso público directo.

## 2. Requisitos del servidor

### 2.1 Software

- PHP 8.1 o superior.
- MySQL 8.x o MariaDB compatible.
- Apache con `.htaccess` habilitado, Plesk con Apache/Nginx proxy, o configuración Nginx equivalente.
- Navegador moderno para administración.

### 2.2 Extensiones PHP obligatorias

- `pdo`
- `pdo_mysql`
- `mbstring`
- `zip`
- `simplexml`
- `fileinfo`
- `iconv`
- `json`

El instalador valida estas extensiones antes de permitir la instalación.

## 3. Instalación desde cero

### 3.1 Preparar carpeta

Sube la carpeta `SGCE` completa al servidor. No mezcles esta versión con una carpeta antigua.

Ejemplo en Ubuntu:

```bash
cd /var/www/html
sudo mv SGCE SGCE_RESPALDO_ANTES_FINAL 2>/dev/null || true
sudo unzip SGCE_VERSION_FINAL_DOCUMENTADA_GITHUB.zip
```

### 3.2 Permisos mínimos

El instalador necesita escribir en `config/` y `storage/`.

```bash
sudo chown -R www-data:www-data /var/www/html/SGCE/config /var/www/html/SGCE/storage
sudo chmod 775 /var/www/html/SGCE/config
sudo find /var/www/html/SGCE/storage -type d -exec chmod 775 {} \;
```

En Plesk puede ser necesario ajustar el usuario del dominio, por ejemplo `usuario_psacln` o el usuario propio del sitio.

### 3.3 Base de datos

Crea una base vacía y asigna permisos al usuario MySQL. La base debe estar vacía porque el instalador evita mezclar instalaciones.

### 3.4 Ejecutar instalador

Abre:

```text
https://tu-dominio.com/SGCE/Instalar.php
```

El instalador solicita:

- Host, base de datos, usuario y contraseña MySQL.
- Nombre oficial de la escuela.
- CCT, director(a), municipio/estado, teléfono y correo opcionales.
- Color institucional.
- Ciclo escolar inicial.
- Tres periodos de evaluación.
- Cantidad de planeaciones por ciclo.
- Usuario administrador inicial.

Al finalizar correctamente:

- Crea tablas desde `install/SGCE.sql`.
- Inserta administrador inicial.
- Inserta configuración institucional.
- Inserta ciclo escolar activo y tres periodos.
- Crea `config/database.local.php`.
- Crea `storage/install.lock`.
- Intenta eliminar `install/` y `Instalar.php`.

## 4. Estructura técnica

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

## 5. Archivos de entrada pública

Los archivos PHP en la raíz son wrappers. Cada wrapper define `SGCE_APP` y carga el módulo real protegido.

Ejemplos:

- `Admin.php` carga `modules/Admin.php`.
- `Maestro.php` carga `modules/Maestro.php`.
- `ConsultaPadre.php` carga `public/ConsultaPadre.php`.
- `ExportarAlumno.php` carga `reports/ExportarAlumno.php`.

Esto permite URLs simples para el usuario y mantiene la lógica real en carpetas internas bloqueadas.

## 6. Seguridad técnica

### 6.1 Carpetas protegidas

Las carpetas `config/`, `includes/`, `modules/`, `reports/`, `cron/` y `storage/` tienen reglas `.htaccess` para evitar acceso directo.

### 6.2 Archivos sensibles

La raíz bloquea archivos como:

- `database.php`
- `database.local.php`
- `.sql`
- `.log`
- `.zip`
- `.bak`
- `.old`
- `.tmp`
- `.md`
- `.env`

Esto permite subir documentación a GitHub sin exponerla necesariamente desde producción.

### 6.3 Sesiones

El sistema inicializa sesiones seguras con:

- `HttpOnly`
- `SameSite=Strict`
- cookie segura si detecta HTTPS directo o por proxy.

### 6.4 Formularios

Los formularios POST usan token CSRF. El archivo `assets/js/sgce-shared.js` también ayuda a insertar el token en formularios dinámicos cuando corresponde.

### 6.5 Contraseñas

Las contraseñas se almacenan con `password_hash()` y se validan con `password_verify()`. El sistema permite rehash cuando PHP actualiza el algoritmo recomendado.

## 7. Módulos funcionales

### Administración

- Dashboard institucional.
- Alta, edición, desactivación y reactivación de docentes.
- Alta, edición, desactivación y reactivación de grupos.
- Alta, edición, desactivación y reactivación de alumnos.
- Asignación de materias a docentes y grupos.
- Expedientes por grupo.
- Acceso a reportes, avisos, periodos, configuración, respaldos y bitácora.

### Docente

- Portal docente.
- Registro de asistencia.
- Captura de calificaciones.
- Entrega de planeaciones por materia.

### Consulta pública

- Consulta de asistencia.
- Consulta de calificaciones.
- Exportación de boleta pública en PDF.

### Reportes

- Asistencia por grupo.
- Asistencia por asignación.
- Calificaciones por asignación o grupo.
- Historial/boleta por alumno.
- Exportación de base de datos.
- Respaldo SQL.

### Planeaciones

- Subida de PDF, Word, Excel o PowerPoint.
- Validación de extensión, MIME y firma de archivo.
- Versionado interno.
- Revisión administrativa: subida, aprobada o devuelta.

## 8. Base de datos

Tablas principales:

- `ConfiguracionSistema`
- `Usuarios`
- `Grupos`
- `Alumnos`
- `Asignaciones`
- `CiclosEscolares`
- `PeriodosEvaluacion`
- `Calificaciones`
- `Asistencias`
- `Avisos`
- `Planeaciones`
- `BitacoraMovimientos`
- `IntentosSeguridad`

Todas las tablas usan InnoDB y `utf8mb4_unicode_ci`.

## 9. Respaldos

El sistema permite respaldos manuales desde el panel y scripts de cron:

```bash
php /ruta/SGCE/cron/backup_diario.php
php /ruta/SGCE/cron/backup_semanal.php
```

Ejemplo de cron diario:

```cron
0 2 * * * php /var/www/html/SGCE/cron/backup_diario.php
```

## 10. Restauración

La restauración permite importar archivos SQL generados por SGCE. Existen modos de fusión o reemplazo, y el módulo protege contra sentencias no permitidas.

Antes de restaurar en producción:

1. Genera respaldo actual.
2. Verifica que el archivo SQL provenga de SGCE.
3. Prueba en ambiente local si es información crítica.

## 11. Publicación en GitHub

Antes de subir al repositorio:

- Conserva `README.md` y `docs/`.
- No subas `config/database.local.php`.
- No subas backups reales.
- No subas logs reales.
- No subas planeaciones reales de docentes si contienen datos sensibles.

La `.gitignore` incluida ya contempla estos puntos.

## 12. Checklist de entrega

- [ ] Carpeta nueva, no mezclada con versiones anteriores.
- [ ] Base de datos vacía.
- [ ] Permisos de escritura en `config/` y `storage/`.
- [ ] Instalador ejecutado correctamente.
- [ ] Login admin probado.
- [ ] Alta de grupo, docente y alumno probada.
- [ ] Asignación docente-grupo-materia probada.
- [ ] Login docente probado.
- [ ] Captura de asistencia probada.
- [ ] Captura de calificaciones probada.
- [ ] Consulta pública probada.
- [ ] Reportes PDF/Excel/HTML probados.
- [ ] Respaldo SQL probado.
