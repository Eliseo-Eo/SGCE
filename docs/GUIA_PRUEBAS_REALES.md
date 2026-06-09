# Guía de pruebas reales - SGCE 1.0.122

## Objetivo

Validar una instalación limpia antes de usar datos reales.

## Prueba 1: Instalación

1. Crear base vacía.
2. Ejecutar `Instalar.php`.
3. Crear administrador.
4. Entrar al sistema.
5. Confirmar que el instalador quede bloqueado.

## Prueba 2: Importaciones

Cargar en este orden:

1. Docentes.
2. Grupos.
3. Materias por grupo.
4. Alumnos.
5. Asignaciones.

Validar que no existan errores de formato requerido.

## Prueba 3: Captura docente

1. Entrar como maestro.
2. Capturar asistencia.
3. Actualizar asistencia.
4. Capturar calificación.
5. Revisar que el alumno muestre datos en expediente.

## Prueba 4: Migración

1. Crear ciclo nuevo.
2. Activarlo.
3. Confirmar que se copiaron periodos.
4. Abrir Migración.
5. Revisar diagnóstico.
6. Ejecutar simulación.
7. Ejecutar migración real.
8. Revisar:
   - Grupos del ciclo nuevo.
   - Materias por grupo del ciclo nuevo.
   - Alumnos promovidos.
   - Alumnos egresados.
   - Kardex congelado.
   - Asignaciones copiadas si se marcó la opción.

## Resultado esperado

- El ciclo anterior queda intacto.
- El ciclo nuevo tiene todos los grupos equivalentes.
- Los grupos de nuevo ingreso quedan vacíos y con materias.
- Los alumnos se promueven correctamente.
- No aparecen errores de periodo inválido.
