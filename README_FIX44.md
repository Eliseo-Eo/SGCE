SGCE FIX44 - Rediseño visual limpio

Cambios principales:
- Rediseño de Admin.php sin barra superior heredada.
- Admin.php ahora usa encabezado tipo tarjeta guinda independiente.
- Logout solo aparece en el inicio de Admin.php.
- En las pestañas internas de Admin.php solo aparece Regresar al inicio.
- Se eliminaron los CSS repetidos de los archivos por página.
- Los estilos comunes quedaron centralizados en assets/css/sgce-base.css.
- assets/css/sgce-shared.css y los CSS por módulo quedan como archivos de compatibilidad, sin reglas duplicadas.
- El dashboard conserva el menú 40/60 con botones tipo 70x50.
- Las páginas con Top, TopBar y TopHeader toman el mismo diseño institucional guinda.
- Las barras navbar antiguas quedan ocultas para evitar menús duplicados.

Notas:
- No se modificó la política de contraseñas: siguen guardándose en texto normal como fue solicitado.
- Se conservan los CSS internos propios de reportes/exportaciones cuando son necesarios para impresión/PDF/Excel.
- Todos los archivos PHP fueron validados con php -l sin errores de sintaxis.
