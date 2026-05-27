# SGCE FIX46 - Botón Guardar Maestro

Corrección aplicada:

- Se corrigió el botón **Guardar Maestro** en `Admin.php?Tab=maestros`.
- El botón ahora usa la clase compartida `BtnPrimary`, igual que el resto del rediseño.
- Se agregó compatibilidad para botones heredados `.btn-guinda` y `.btn-success` en `assets/css/sgce-base.css`.
- Se actualizó la versión del CSS de Admin a `v=46` para evitar caché del navegador.
- No se modificaron contraseñas ni estructura de base de datos.
