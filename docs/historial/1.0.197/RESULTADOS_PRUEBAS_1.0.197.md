# SGCE 1.0.197 - Resultados de pruebas ejecutadas

## Entorno

- PHP CLI: 8.4.16.
- Composer/PHPUnit: no incluido en el paquete recibido.
- PDO MySQL: no disponible en el contenedor.
- MySQL server/client: no disponible en el contenedor.
- SQLite: disponible por Python 3 (`sqlite3` 3.46.1), usado para prueba real de conteo de consultas del Kardex.

## Salida real: PHPUnit o fallback

```text
### PHP version
PHP 8.4.16 (cli) (built: Dec 18 2025 21:19:25) (NTS)
Copyright (c) The PHP Group
Built by Debian
Zend Engine v4.4.16, Copyright (c) Zend Technologies
    with Zend OPcache v8.4.16, Copyright (c), by Zend Technologies
### PHPUnit discovery
vendor/bin/phpunit no existe en el paquete 1.0.196; se ejecuta fallback sin Composer: tests/sgce_197_fallback.php
[OK] Version::current() devuelve 1.0.197.
[OK] SgceCicloActivoCacheLimpiar incrementa contador numérico.
[OK] SgceOfertaActivaCacheLimpiar incrementa contador numérico.
[OK] CSRF instalador persiste respaldo temporal en storage/locks.
[OK] CSRF instalador valida por archivo aunque sesión y cookie no existan.
[OK] CSRF restauró token en sesión después de validar archivo.
[OK] Función muerta SgceValidarXlsxImportacionSeguro eliminada.
RESULTADO: FALLBACK SGCE 1.0.197 OK
```

## Salida real: Kardex 3 ciclos sin N+1

```text
### Kardex SQLite harness
KARDEX_SQLITE_QUERY_COUNT=5
KARDEX_CICLOS=3
KARDEX_FILAS=6
Q1: SELECT Id, NombreCompleto FROM Alumnos WHERE Id = ? LIMIT 1
Q2: SELECT AI.CicloId, AI.GrupoId, AI.Estado, C.Nombre AS CicloNombre, C.FechaInicio, G.Grado, G.Grupo, G.Turno, G.OfertaId FROM AlumnoInscripciones AI JOIN CiclosEscolares C ON C.Id = AI.CicloId JOIN Grupos G ON G.Id = AI.GrupoId AND G.CicloId = AI.CicloId WHERE AI.AlumnoId = ? ORDER BY C.FechaInicio ASC, C.Id ASC
Q3: SELECT Id, Nombre, Orden, CicloId, OfertaId FROM PeriodosEvaluacion WHERE ((CicloId = ? AND OfertaId = ?) OR (CicloId = ? AND OfertaId = ?) OR (CicloId = ? AND OfertaId = ?)) AND Activo = 1 ORDER BY CicloId ASC, OfertaId ASC, Orden ASC, Id ASC
Q4: SELECT A.Id, A.CicloId, A.GrupoId, A.MateriaNombre, U.NombreCompleto AS Maestro FROM Asignaciones A LEFT JOIN Usuarios U ON U.Id = A.MaestroId WHERE (A.CicloId = ? AND A.GrupoId = ?) OR (A.CicloId = ? AND A.GrupoId = ?) OR (A.CicloId = ? AND A.GrupoId = ?) ORDER BY A.CicloId ASC, A.GrupoId ASC, A.MateriaNombre ASC
Q5: SELECT AsignacionId, PeriodoId, Calificacion FROM Calificaciones WHERE AlumnoId = ? AND AsignacionId IN (?,?,?,?,?,?) AND PeriodoId IN (?,?,?,?,?,?,?,?,?)
RESULTADO: KARDEX 3 CICLOS SIN N+1 OK
```

Conclusión: el Kardex completo de un alumno con 3 ciclos se armó con 5 consultas totales. Las consultas de periodos, asignaciones y calificaciones son agrupadas; no hay consulta por ciclo, por materia ni por periodo.

## Salida real: sintaxis PHP

`php -l` se ejecutó sobre todos los archivos PHP del paquete. Resultado: sin errores de sintaxis. La salida completa queda en el archivo de evidencia generado durante la entrega: `php_lint_197_raw.txt` en la carpeta de trabajo usada para la construcción.

## Limitación honesta

No se pudo ejecutar instalación real contra MySQL en este contenedor porque no hay driver `pdo_mysql`, cliente `mysql` ni servidor `mysqld`. La instalación debe validarse en el VPS/Plesk con el checklist de aceptación incluido.
