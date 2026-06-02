# SGCE - Sistema Gestor de Control Escolar

Versión: **1.0.1**

SGCE es un sistema escolar en PHP/MySQL para control de alumnos, docentes, grupos, asignaciones, asistencias, calificaciones, planeaciones, reportes, consulta pública y administración de ciclos escolares.

## Novedades principales de la versión 1.0.1

- Control académico por ciclo escolar.
- Inscripciones históricas por alumno, ciclo y grupo.
- Migración segura de grupos/ciclos: 1° -> 2°, 2° -> 3°, 3° -> egresado.
- Kardex congelado por alumno/ciclo para proteger boletas históricas.
- Soporte para interinatos/relevos docentes sin romper la materia.
- Bloqueo de desactivación de docentes con asignaciones activas.
- Bloqueo de desactivación de asignaciones con asistencias o calificaciones.
- Historial académico PDF basado en kardex congelado cuando exista.

## Instalación

1. Copiar el proyecto al servidor.
2. Dar permisos de escritura a `storage/`.
3. Entrar a `Instalar.php`.
4. Configurar base de datos y usuario administrador.
5. El instalador crea `storage/install.lock` para bloquear reinstalaciones.

## Documentación

Revisar especialmente:

- `docs/SGCE_VERSION_1_0_1_CICLOS_KARDEX_INTERINATOS.md`
- `docs/MIGRACION_CICLOS_SGCE_V1.md`
- `docs/MANUAL_TECNICO_INSTALACION_SGCE.md`
- `docs/MANUAL_USUARIO_SGCE.md`
- `docs/CHANGELOG_SGCE.md`

## Flujo recomendado

1. Crear ciclo escolar activo.
2. Crear grupos del ciclo activo.
3. Importar alumnos y docentes.
4. Asignar materias a grupos y docentes.
5. Capturar asistencias y calificaciones.
6. Al cierre del ciclo, inactivar ciclo anterior y activar ciclo nuevo.
7. Usar Configuración -> Migración de ciclo escolar.
8. Revisar kardex e historial académico.
