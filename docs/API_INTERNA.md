# API interna AJAX — SGCE 1.0.122

La carpeta `api/admin/` no es una API pública externa. Es una API interna usada por el panel administrativo para actualizar tablas sin recargar toda la página.

## Endpoints activos

- `api/admin/alumnos.php`
- `api/admin/materias.php`
- `api/admin/asignaciones.php`
- `api/admin/bitacora.php`

## Cómo funciona

1. `assets/js/Admin.js` detecta filtros, búsquedas o paginación.
2. Se envía una petición `fetch()` al endpoint correspondiente.
3. El endpoint valida sesión, rol y permisos.
4. Se carga `modules/admin/AdminDatos.php`.
5. Se renderizan parciales de `views/admin/partials/`.
6. Se devuelve JSON con `tbody`, `pager`, `modals`, `count` y `url`.

## Seguridad

- Requiere sesión válida.
- Requiere permiso de panel administrativo.
- No modifica datos; solo devuelve vistas parciales.
- No debe exponerse como API pública para integraciones externas.

## Nota de mantenimiento

No borrar `api/admin/` mientras `assets/js/Admin.js` utilice el mapa de endpoints. Si se elimina, filtros y paginación dinámica de Alumnos, Materias, Asignaciones y Bitácora dejarán de funcionar correctamente.
