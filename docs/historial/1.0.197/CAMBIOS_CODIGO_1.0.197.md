# SGCE 1.0.197 - Cambios de código

## Gatillo de caché de ciclo/oferta activa

### `includes/SGCE_Academico.php`

Líneas 23-36:

```php
function SgceCicloActivoCacheLimpiar(): void {
    $GLOBALS['SGCE_CICLO_ACTIVO_CACHE_RESET'] = ($GLOBALS['SGCE_CICLO_ACTIVO_CACHE_RESET'] ?? 0) + 1;
}

function SgceOfertaActivaCacheLimpiar(): void {
    $GLOBALS['SGCE_OFERTA_ACTIVA_CACHE_RESET'] = ($GLOBALS['SGCE_OFERTA_ACTIVA_CACHE_RESET'] ?? 0) + 1;
}

function SgceActivarCicloUnico(PDO $Pdo, int $CicloId): void {
    if ($CicloId <= 0 || !SgceDbTablaExiste($Pdo, 'CiclosEscolares')) { return; }
    $Pdo->prepare('UPDATE CiclosEscolares SET Activo = 0 WHERE Id <> ?')->execute([$CicloId]);
    SgceCicloActivoCacheLimpiar();
    $Pdo->prepare('UPDATE CiclosEscolares SET Activo = 1 WHERE Id = ?')->execute([$CicloId]);
    SgceCicloActivoCacheLimpiar();
}
```

Líneas 174-185:

```php
$Pdo->prepare('UPDATE OfertasEducativas SET Activo = 0')->execute();
SgceOfertaActivaCacheLimpiar();
...
$Pdo->prepare('UPDATE OfertasEducativas SET NivelEducativo = ?, TipoPeriodizacion = ?, TotalEtapas = ?, EtiquetaEtapa = ?, UsaProgramas = ?, Activo = 1 WHERE Id = ?')->execute([...]);
SgceOfertaActivaCacheLimpiar();
...
$Pdo->prepare('INSERT INTO OfertasEducativas ... Activo) VALUES (..., 1)')->execute([...]);
$OfertaId = (int)$Pdo->lastInsertId();
SgceOfertaActivaCacheLimpiar();
```

### `includes/SGCE_BaseDatos.php`

Líneas 13-14:

```php
$Pdo->prepare('UPDATE CiclosEscolares SET Activo = 0 WHERE Activo = 1 AND Id <> ?')->execute([$IdActivo]);
$GLOBALS['SGCE_CICLO_ACTIVO_CACHE_RESET'] = ($GLOBALS['SGCE_CICLO_ACTIVO_CACHE_RESET'] ?? 0) + 1;
```

### `Instalar.php`

Líneas 351-353:

```php
$StmtOferta->execute([$NombreOfertaAcademica, $NivelEducativo, $TipoPeriodizacion, $TotalEtapas, $EtiquetaEtapa, $UsaProgramas ? 1 : 0]);
$GLOBALS['SGCE_OFERTA_ACTIVA_CACHE_RESET'] = ($GLOBALS['SGCE_OFERTA_ACTIVA_CACHE_RESET'] ?? 0) + 1;
$OfertaId = (int)$PdoDb->lastInsertId();
```

Líneas 368-372:

```php
$PdoDb->prepare('UPDATE CiclosEscolares SET Activo = 0')->execute();
$GLOBALS['SGCE_CICLO_ACTIVO_CACHE_RESET'] = ($GLOBALS['SGCE_CICLO_ACTIVO_CACHE_RESET'] ?? 0) + 1;
$StmtCiclo = $PdoDb->prepare('INSERT INTO CiclosEscolares (Nombre, FechaInicio, FechaFin, Activo) VALUES (?, ?, ?, 1)');
$StmtCiclo->execute([$CicloNombre, $FechaInicio, $FechaFin]);
$GLOBALS['SGCE_CICLO_ACTIVO_CACHE_RESET'] = ($GLOBALS['SGCE_CICLO_ACTIVO_CACHE_RESET'] ?? 0) + 1;
```

## Kardex individual

- Se agregó `SgceKardexAlumnoReporteDatos()` a `includes/SGCE_AcademicoKardex.php`.
- `reports/ExportarKardexAlumno.php` ahora delega el armado de datos a esa función y conserva la misma salida PDF/Excel.
- La función permite prueba directa del patrón de consultas agrupadas.

## Código muerto eliminado

- `SgceValidarXlsxImportacionSeguro()` eliminado de `includes/importacion/SGCE_ImportacionXlsx.php`.

## Versión

- `src/Foundation/Version.php`: `1.0.197`.
- `VERSION.txt`: `1.0.197`.
