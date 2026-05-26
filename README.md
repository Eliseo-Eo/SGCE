# SGCE - Consulta de padres y asistencias agrupadas por fecha

Sistema Integral de Gestión Escolar.

## Cambios de esta versión

- Se conserva la consulta pública para padres sin login.
- Se conserva la base de datos optimizada con `FechaDia` e índices para asistencias.
- Los reportes de asistencia ahora se agrupan por fecha.
- En exportar todas las asistencias, el PDF y Excel separan los registros por día.
- Cada fecha inicia con un encabezado visible, por ejemplo: `FECHA: 26/05/2026`.
- Se mantiene el diseño visual, favicon, confirmación bonita para eliminar y botones homologados.

## Instalación

1. Borra la base de datos anterior si vas a empezar desde cero.
2. Copia y ejecuta `ControlEscolar.sql` en MySQL o phpMyAdmin.
3. Reemplaza los archivos del proyecto con los archivos de este paquete.
