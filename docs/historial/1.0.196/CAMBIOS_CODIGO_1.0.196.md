# SGCE 1.0.196 — Cambios de código con antes/después

## C-01 — Instalador: respaldo real de CSRF en `storage/locks`

Archivo modificado: `install/installer/InstallerCore.php`.

Problema: el diagnóstico del instalador decía que existía token temporal en `storage/locks`, pero la validación real solo dependía de sesión/cookie.

### Antes

```php
function InstalarPersistirCsrfCookie($Token) {
    if (!InstalarCsrfTokenValidoFormato($Token) || headers_sent()) { return; }

    setcookie('SGCE_INSTALL_CSRF', $Token, [
        'expires' => 0,
        'path' => InstalarCsrfCookiePath(),
        'secure' => InstalarCsrfCookieSecure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function InstalarValidarCsrf($Token) {
    if (!InstalarCsrfTokenValidoFormato($Token)) { return false; }

    if (!empty($_SESSION['InstalarCsrfToken']) && InstalarCsrfTokenValidoFormato($_SESSION['InstalarCsrfToken'])) {
        if (hash_equals((string)$_SESSION['InstalarCsrfToken'], (string)$Token)) { return true; }
    }

    if (!empty($_COOKIE['SGCE_INSTALL_CSRF']) && InstalarCsrfTokenValidoFormato($_COOKIE['SGCE_INSTALL_CSRF'])) {
        return hash_equals((string)$_COOKIE['SGCE_INSTALL_CSRF'], (string)$Token);
    }

    return false;
}
```

### Después

```php
function InstalarCsrfStorageDir() {
    return dirname(__DIR__, 2) . '/storage/locks';
}

function InstalarCsrfArchivoPath($Token) {
    return InstalarCsrfStorageDir() . '/installer_csrf_' . hash('sha256', strtolower((string)$Token)) . '.token';
}

function InstalarPersistirCsrfArchivo($Token) {
    if (!InstalarCsrfTokenValidoFormato($Token) || !InstalarAsegurarCsrfStorage()) { return; }
    InstalarLimpiarCsrfArchivos();
    $Ruta = InstalarCsrfArchivoPath($Token);
    $Temporal = $Ruta . '.' . getmypid() . '.tmp';
    $Contenido = json_encode([
        'created_at' => time(),
        'token_hash' => hash('sha256', strtolower((string)$Token)),
    ], JSON_UNESCAPED_SLASHES);
    if ($Contenido === false) { return; }
    if (@file_put_contents($Temporal, $Contenido, LOCK_EX) !== false) {
        @chmod($Temporal, 0600);
        @rename($Temporal, $Ruta);
        @chmod($Ruta, 0600);
    } else {
        @unlink($Temporal);
    }
}

function InstalarPersistirCsrfCookie($Token) {
    if (!InstalarCsrfTokenValidoFormato($Token)) { return; }
    InstalarPersistirCsrfArchivo($Token);
    if (headers_sent()) { return; }

    setcookie('SGCE_INSTALL_CSRF', $Token, [
        'expires' => 0,
        'path' => InstalarCsrfCookiePath(),
        'secure' => InstalarCsrfCookieSecure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function InstalarValidarCsrf($Token) {
    if (!InstalarCsrfTokenValidoFormato($Token)) { return false; }

    if (!empty($_SESSION['InstalarCsrfToken']) && InstalarCsrfTokenValidoFormato($_SESSION['InstalarCsrfToken'])) {
        if (hash_equals((string)$_SESSION['InstalarCsrfToken'], (string)$Token)) { return true; }
    }

    if (!empty($_COOKIE['SGCE_INSTALL_CSRF']) && InstalarCsrfTokenValidoFormato($_COOKIE['SGCE_INSTALL_CSRF'])) {
        if (hash_equals((string)$_COOKIE['SGCE_INSTALL_CSRF'], (string)$Token)) { return true; }
    }

    return InstalarValidarCsrfArchivo((string)$Token);
}
```

Qué resuelve: alinea el diagnóstico con el comportamiento real y evita falsos rechazos de instalación cuando PHP pierde sesión/cookie durante el POST.

## C-02 — Kardex individual: eliminación de patrón N+1

Archivo modificado: `reports/ExportarKardexAlumno.php`.

Problema: por cada ciclo del alumno se consultaban periodos, asignaciones y calificaciones.

### Antes

```php
foreach ($Ciclos as $Ciclo) {
    $StmtPeriodos = $Pdo->prepare('SELECT Id, Nombre, Orden FROM PeriodosEvaluacion WHERE CicloId = ? AND OfertaId = ? AND Activo = 1 ORDER BY Orden ASC, Id ASC');
    $StmtPeriodos->execute([(int)$Ciclo['CicloId'], (int)$Ciclo['OfertaId']]);
    $Periodos = $StmtPeriodos->fetchAll();

    $StmtAsignaciones = $Pdo->prepare('SELECT A.Id, A.MateriaNombre, U.NombreCompleto AS Maestro FROM Asignaciones A LEFT JOIN Usuarios U ON U.Id = A.MaestroId WHERE A.CicloId = ? AND A.GrupoId = ? ORDER BY A.MateriaNombre ASC');
    $StmtAsignaciones->execute([(int)$Ciclo['CicloId'], (int)$Ciclo['GrupoId']]);
    $Asignaciones = $StmtAsignaciones->fetchAll();

    if ($PeriodoIds && $Asignaciones) {
        $StmtCal = $Pdo->prepare("SELECT AsignacionId, PeriodoId, Calificacion FROM Calificaciones WHERE AlumnoId = ? AND AsignacionId IN ($MarcadoresAsignaciones) AND PeriodoId IN ($MarcadoresPeriodos)");
        $StmtCal->execute(array_merge([$AlumnoId], $AsignacionIds, $PeriodoIds));
    }
}
```

### Después

```php
$PeriodosPorCicloOferta = [];
$AsignacionesPorCicloGrupo = [];
$Calificaciones = [];

// Periodos por lote para todos los ciclos/ofertas del alumno.
$SqlPeriodos = 'SELECT Id, Nombre, Orden, CicloId, OfertaId FROM PeriodosEvaluacion WHERE (' . implode(' OR ', $ClausulasPeriodos) . ') AND Activo = 1 ORDER BY CicloId ASC, OfertaId ASC, Orden ASC, Id ASC';

// Asignaciones por lote para todos los ciclos/grupos del alumno.
$SqlAsignaciones = 'SELECT A.Id, A.CicloId, A.GrupoId, A.MateriaNombre, U.NombreCompleto AS Maestro FROM Asignaciones A LEFT JOIN Usuarios U ON U.Id = A.MaestroId WHERE ' . implode(' OR ', $ClausulasAsignaciones) . ' ORDER BY A.CicloId ASC, A.GrupoId ASC, A.MateriaNombre ASC';

// Calificaciones por lote para el alumno, las asignaciones y los periodos encontrados.
$StmtCal = $Pdo->prepare("SELECT AsignacionId, PeriodoId, Calificacion FROM Calificaciones WHERE AlumnoId = ? AND AsignacionId IN ($MarcadoresAsignaciones) AND PeriodoId IN ($MarcadoresPeriodos)");

foreach ($Ciclos as $Ciclo) {
    $ClavePeriodo = (int)$Ciclo['CicloId'] . ':' . (int)$Ciclo['OfertaId'];
    $ClaveAsignacion = (int)$Ciclo['CicloId'] . ':' . (int)$Ciclo['GrupoId'];
    $Periodos = $PeriodosPorCicloOferta[$ClavePeriodo] ?? [];
    $Asignaciones = $AsignacionesPorCicloGrupo[$ClaveAsignacion] ?? [];
}
```

Qué resuelve: reduce consultas aproximadas de `2 + 3N` a `5` en el caso general, preservando el armado de filas, promedios y salida PDF/Excel.

## C-03 — Limpieza de source maps de Bootstrap en producción

Archivos modificados/eliminados:

- `assets/vendor/bootstrap/5.3.3/css/bootstrap.min.css`
- `assets/vendor/bootstrap/5.3.3/js/bootstrap.bundle.min.js`
- `assets/vendor/bootstrap/5.3.3/css/bootstrap.min.css.map`
- `assets/vendor/bootstrap/5.3.3/js/bootstrap.bundle.min.js.map`

### Antes

```css
/*# sourceMappingURL=bootstrap.min.css.map */
```

```js
//# sourceMappingURL=bootstrap.bundle.min.js.map
```

### Después

Los comentarios `sourceMappingURL` fueron eliminados y los `.map` no se incluyen en el paquete final.

Qué resuelve: evita referencias a archivos de desarrollo y reduce el paquete sin tocar el diseño visual.

## C-04 — Documentación y versión final

Archivos actualizados:

- `VERSION.txt`: `1.0.196`.
- `src/Foundation/Version.php`: `SGCE 1.0.196 - Cierre de producción verificado`.
- `README.md`: instrucciones alineadas a cierre 1.0.196.
- Documentos 1.0.195 eliminados del paquete final y sustituidos por documentación 1.0.196.

Qué resuelve: evita confusión entre versión auditada anterior y entrega final.
