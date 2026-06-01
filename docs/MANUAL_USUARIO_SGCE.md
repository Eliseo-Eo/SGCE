# Manual de usuario - SGCE

## 1. Acceso al sistema

Abre la dirección del sistema en el navegador e inicia sesión con tu usuario y contraseña.

Roles principales:

- Administrador: controla todo el sistema.
- Administrativo: puede apoyar en operación escolar según permisos configurados.
- Maestro: captura asistencia, calificaciones y planeaciones.
- Padre de familia: consulta asistencia y calificaciones desde las pantallas públicas.

## 2. Panel de administrador

Al entrar como administrador se muestra el dashboard con tarjetas de acceso a módulos y datos principales del ciclo escolar.

### 2.1 Inicio

Muestra resumen de:

- Alumnos activos.
- Docentes activos.
- Grupos activos.
- Asistencias y faltas del día.
- Promedio general del ciclo.
- Alumnos en riesgo.

### 2.2 Maestros

Permite:

- Registrar docente.
- Editar nombre, usuario o contraseña.
- Desactivar docente.
- Reactivar docente cuando el usuario ya existe pero está inactivo.

Los nombres se normalizan en mayúsculas y se validan para evitar números en nombres.

### 2.3 Grupos

Permite registrar grupos por:

- Grado.
- Grupo.
- Turno.

El grado acepta números. El grupo acepta letras en mayúsculas.

### 2.4 Alumnos

Permite:

- Registrar alumno individual.
- Asociarlo a un grupo.
- Importar alumnos por CSV o Excel.
- Editar alumno.
- Desactivar alumno.

### 2.5 Asignaciones

Sirve para vincular:

- Docente.
- Grupo.
- Materia.

La asignación activa permite que el docente capture asistencia, calificaciones y planeaciones.

### 2.6 Expedientes

Permite consultar alumnos por grupo y entrar al historial del alumno.

### 2.7 Avisos y comunicados

Permite crear avisos para:

- Todos.
- Maestros.
- Padres.

También permite modificar, activar o desactivar avisos.

### 2.8 Planeaciones administrativas

Permite revisar las planeaciones subidas por docentes.

Estados disponibles:

- SUBIDA: el docente ya cargó archivo.
- APROBADA: la planeación fue validada.
- DEVUELTA: requiere corrección.

Cuando una planeación se devuelve, el docente puede volver a subir archivo. El sistema incrementa versión interna y conserva control del registro.

### 2.9 Reportes

Permite generar reportes de:

- Asistencia por grupo.
- Asistencia por asignación.
- Calificaciones.
- Boleta o historial por alumno.
- Exportación de datos.

### 2.10 Configuración

Permite actualizar datos institucionales, color institucional, ciclo activo, periodos y cantidad de planeaciones solicitadas.

### 2.11 Respaldos

Permite descargar respaldo SQL del sistema y restaurar información desde respaldos válidos.

### 2.12 Bitácora

Muestra movimientos relevantes del sistema, como altas, bajas, ediciones, capturas y restauraciones.

## 3. Portal docente

Al entrar como maestro se muestran sus materias asignadas y accesos a captura.

### 3.1 Asistencia

Pasos:

1. Selecciona la asignación.
2. Selecciona la fecha.
3. Marca cada alumno como asistencia, falta, retardo o justificante.
4. Guarda la lista.

Estados:

- A: asistencia.
- F: falta.
- R: retardo.
- J: justificante.

### 3.2 Calificaciones

Pasos:

1. Selecciona la materia/asignación.
2. Selecciona el periodo.
3. Captura calificación por alumno.
4. Guarda cambios.

La calificación debe estar dentro del rango permitido por el sistema.

### 3.3 Planeaciones

Pasos:

1. Entra a planeaciones.
2. Selecciona materia.
3. Elige el número de planeación solicitado.
4. Sube archivo.
5. Espera revisión administrativa.

Formatos aceptados:

- PDF.
- Word.
- Excel.
- PowerPoint.

## 4. Consulta para padres de familia

Desde la pantalla principal existen accesos a consulta pública.

### 4.1 Consulta de asistencia

El padre captura:

- Nombre completo del alumno.
- Grado.
- Grupo.
- Turno.
- Rango de fechas.

El sistema muestra resumen por materia y detalle de registros.

### 4.2 Consulta de calificaciones

El padre captura:

- Nombre completo del alumno.
- Grado.
- Grupo.
- Turno.

El sistema muestra calificaciones por materia y periodo, con opción de exportar boleta.

## 5. Recomendaciones de uso

- Captura nombres completos en mayúsculas.
- Revisa grupo y turno antes de guardar alumnos.
- Crea primero grupos y docentes antes de asignar materias.
- Realiza respaldos antes de restaurar información.
- No compartas usuarios entre varias personas.
- Cierra sesión al terminar.

## 6. Flujo recomendado para iniciar un ciclo

1. Configurar datos de la escuela.
2. Crear ciclo escolar y periodos.
3. Registrar grupos.
4. Registrar docentes.
5. Registrar o importar alumnos.
6. Crear asignaciones.
7. Probar login docente.
8. Capturar asistencia de prueba.
9. Capturar calificación de prueba.
10. Generar un respaldo inicial.
