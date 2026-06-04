# Manual de instalación SGCE

## Requisitos

- PHP 8.1 o superior.
- MySQL o MariaDB con motor InnoDB.
- Extensiones PHP: PDO, PDO MySQL, mbstring, json y fileinfo.
- Permisos de escritura en `storage/`.

## Base de datos

Crea una base vacía antes de instalar:

```sql
CREATE DATABASE Control_Escolar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

No uses una base con tablas previas. El instalador bloquea bases no vacías para evitar instalaciones mezcladas.

## Instalador

Abre:

```text
http://tu-servidor/SGCE/Instalar.php
```

Captura:

1. Conexión MySQL.
2. Carpeta de respaldos.
3. Datos oficiales de la institución.
4. Configuración académica inicial.
5. Ciclo inicial, periodos de evaluación y planeaciones.
6. Usuario administrador.

## Configuración académica inicial

El instalador separa dos conceptos:

- **Nivel educativo:** define la lógica académica general del sistema: Primaria, Secundaria, Bachillerato / Preparatoria, Universidad / Licenciatura, Maestría, Doctorado o Curso / Diplomado.
- **Nombre específico de la oferta educativa:** es opcional y sirve para nombres reales como Secundaria Técnica, Secundaria General, Bachillerato Tecnológico, Bachillerato General, Licenciatura, Posgrado o Curso de Redes. Si queda vacío, SGCE usa el nivel educativo como nombre de la oferta.

También debes indicar:

- Organización académica: Años/grados, semestres, cuatrimestres, trimestres o módulos.
- Cantidad de grados/etapas.
- Si la institución usa programas educativos, especialidades, carreras o posgrados.
- Programas educativos iniciales cuando aplique.

En primaria o secundaria puedes dejar desactivado el uso de programas. En universidad, maestría y doctorado SGCE solicita al menos un programa educativo real.

## Periodos y planeaciones

Los periodos de evaluación pertenecen al ciclo inicial y a la oferta educativa configurada. Puedes usar modo automático o nombres personalizados.

La opción **La institución usa planeaciones** controla si se habilitan los campos de tipo de planeación y cantidad de entregas. Si está desactivada, esos campos no se validan ni se guardan como obligatorios.

## Finalización

Al terminar, SGCE crea la configuración local y genera `storage/install.lock` para bloquear reinstalaciones accidentales.

## Instalación limpia recomendada

SGCE 1.0.91 está preparado para instalar desde cero. No mezcles tablas antiguas ni bases usadas por versiones previas. Para controlar cambios futuros de estructura existe la tabla `SchemaMigrations`, pero el flujo principal recomendado es instalación limpia y respaldo regular.

## Cron recomendado

Configura estos comandos según tu servidor:

```bash
php /ruta/SGCE/cron/backup_diario.php
php /ruta/SGCE/cron/backup_semanal.php
php /ruta/SGCE/cron/archivar_bitacora.php 365
```

Los scripts tienen bloqueo interno para evitar ejecuciones simultáneas.


## Extensión DOM

Los endpoints parciales del panel administrador generan directamente filas, paginación, contador y modales desde parciales PHP. La extensión DOM/XML sigue siendo recomendable para otras funciones PHP, pero no es requisito de los filtros AJAX.

## Paquete de producción

Para producción revisa `docs/PRODUCCION.md`. Las carpetas `tests/` y `tools/` son útiles para auditoría, pruebas y GitHub; no son necesarias para operar el sistema en servidor. Si se suben, permanecen protegidas con `.htaccess`.

Antes de liberar en servidor real ejecuta:

```bash
php tests/RunStaticChecks.php
php tests/RunIntegrationChecks.php
```

La prueba de integración requiere variables de entorno MySQL para crear una base temporal.
