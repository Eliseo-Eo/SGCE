# SGCE FIX30 - Versión final optimizada

Cambios principales:

- CSS y JavaScript extraídos a `assets/css` y `assets/js`.
- Helpers comunes en `includes/SGCE_Helpers.php`.
- Paginación real SQL en alumnos y asignaciones del panel Admin.
- Ciclos escolares y periodos de evaluación agregados, con panel `PeriodosAdmin.php`.
- Calificaciones separadas por periodo.
- Permisos de reportes homologados para admin, director y prefecto.
- Respaldos marcados con firma SGCE_FIX30 y advertencia porque las contraseñas siguen normales.

IMPORTANTE:

1. Sube los archivos y reemplaza los anteriores.
2. Entra como admin/director a `Migrar_FIX30.php` una vez para actualizar bases ya existentes.
3. Si instalas desde cero, usa `ControlEscolar.sql`.
4. No se encriptaron contraseñas, respetando la regla operativa solicitada.


## FIX31 - Usuarios y Roles

Se agrega el módulo `UsuariosAdmin.php` para que el administrador pueda crear, editar, desactivar y reactivar usuarios con rol:

- admin
- maestro
- director
- secretario
- coordinador
- prefecto

Las contraseñas se mantienen visibles y sin encriptar por decisión operativa del proyecto.

Después de copiar esta versión sobre una instalación existente, entra como administrador y ejecuta una vez:

`Migrar_FIX30.php`

Aunque el archivo conserve el nombre FIX30 por compatibilidad, ahora también actualiza el campo `Usuarios.Rol` para aceptar `secretario` y `coordinador`.
