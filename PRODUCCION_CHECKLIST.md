# Checklist de producción SGCE

## Antes de instalar

- [ ] Confirmar PHP 8.1 o superior recomendado.
- [ ] Confirmar extensiones: pdo, pdo_mysql, mbstring, zip, simplexml, fileinfo, iconv y json.
- [ ] Confirmar HTTPS en el dominio o subdominio.
- [ ] Crear base de datos exclusiva para SGCE.
- [ ] Crear usuario MySQL exclusivo con permisos sobre esa base.
- [ ] Dar permiso temporal de escritura a `config/` y `storage/`.

## Instalación

- [ ] Abrir `Instalar.php`.
- [ ] Capturar conexión MySQL.
- [ ] Pulsar **Verificar servidor**.
- [ ] Corregir cualquier error de permisos, extensión o conexión.
- [ ] Capturar datos oficiales de la escuela.
- [ ] Capturar ciclo escolar y tres periodos.
- [ ] Crear administrador principal.
- [ ] Confirmar con `INSTALAR SGCE`.
- [ ] Entrar al sistema desde `index.php`.

## Después de instalar

- [ ] Confirmar que se creó `config/database.local.php`.
- [ ] Confirmar que existe `storage/install.lock`.
- [ ] Eliminar `Instalar.php` si el servidor lo permite.
- [ ] Quitar permisos amplios en `config/` y `storage/`.
- [ ] Programar cron de respaldo diario o semanal.
- [ ] Probar creación de maestro, grupo, alumno y asignación.
- [ ] Probar asistencia, calificaciones, consulta de padres y reportes PDF.
- [ ] Crear un respaldo manual desde el panel.
- [ ] Revisar que no existan errores en `storage/logs/`.

## Cron recomendado

Diario:

```bash
0 2 * * * /usr/bin/php /ruta/SGCE/cron/backup_diario.php
```

Semanal:

```bash
30 2 * * 0 /usr/bin/php /ruta/SGCE/cron/backup_semanal.php
```
