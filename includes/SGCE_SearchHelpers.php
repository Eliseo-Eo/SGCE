<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceTextoBusquedaNormalizado($Texto): string {
    $Texto = SgceNormalizarMayusculas((string)$Texto);
    $Texto = strtr($Texto, [
        'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
        'á'=>'A','é'=>'E','í'=>'I','ó'=>'O','ú'=>'U','ü'=>'U','ñ'=>'N'
    ]);
    $Texto = preg_replace('/[^A-Z0-9 ]/u', ' ', $Texto);
    $Texto = preg_replace('/\s+/u', ' ', trim((string)$Texto));
    return $Texto;
}

function SgceLikePrefijoBusqueda($Texto): string {
    return SgceTextoBusquedaNormalizado($Texto) . '%';
}

function SgceFullTextBusqueda($Texto): string {
    $Texto = SgceTextoBusquedaNormalizado($Texto);
    $Partes = preg_split('/\s+/', $Texto) ?: [];
    $Tokens = [];
    foreach ($Partes as $Parte) {
        $Parte = preg_replace('/[^A-Z0-9]/', '', (string)$Parte);
        if ($Parte === '' || strlen($Parte) < 2) { continue; }
        $Tokens[] = '+' . $Parte . '*';
    }
    return implode(' ', $Tokens);
}

function SgceBindLimitOffset(PDOStatement $Stmt, int $Inicio, int $Limit, int $Offset): void {
    $Stmt->bindValue($Inicio, max(1, min(200, $Limit)), PDO::PARAM_INT);
    $Stmt->bindValue($Inicio + 1, max(0, $Offset), PDO::PARAM_INT);
}

function SgceMaestroListarTodosUsuariosParaFiltro(PDO $Pdo): array {
    try {
        $Stmt = $Pdo->query("SELECT Id, NombreCompleto, Rol FROM Usuarios ORDER BY NombreCompleto ASC LIMIT 500");
        return $Stmt->fetchAll();
    } catch (Throwable $E) { return []; }
}

function SgceBitacoraAccionesDisponibles(PDO $Pdo): array {
    try {
        $Stmt = $Pdo->query("SELECT DISTINCT Accion FROM BitacoraMovimientos ORDER BY Accion ASC LIMIT 250");
        return array_values(array_filter($Stmt->fetchAll(PDO::FETCH_COLUMN) ?: []));
    } catch (Throwable $E) { return []; }
}

function SgceMateriaGrupoBasesFiltro(PDO $Pdo, int $CicloId): array {
    if ($CicloId <= 0) { return []; }
    try {
        $Stmt = $Pdo->prepare("SELECT DISTINCT MateriaNombre FROM MateriasGrupo WHERE CicloId = ? AND Activo = 1 ORDER BY MateriaNombre ASC LIMIT 500");
        $Stmt->execute([$CicloId]);
        $Opciones = [];
        foreach ($Stmt->fetchAll(PDO::FETCH_COLUMN) as $Nombre) {
            $Nombre = SgceNormalizarMayusculas((string)$Nombre);
            $Base = trim((string)preg_replace('/\s+\d+$/u', '', $Nombre));
            if ($Base === '') { $Base = $Nombre; }
            if ($Base !== '') { $Opciones[$Base] = ['Value' => $Base, 'Label' => $Base, 'Orden' => 9999]; }
        }
        uasort($Opciones, static fn($A, $B) => strnatcasecmp((string)$A['Label'], (string)$B['Label']));
        return array_values($Opciones);
    } catch (Throwable $E) { return []; }
}

function SgceReindexarBusquedasNormalizadas(PDO $Pdo, int $Lote = 500): array {
    $Lote = max(100, min(5000, $Lote));
    $Resultado = ['Usuarios'=>0, 'Alumnos'=>0, 'MateriasCatalogo'=>0, 'MateriasGrupo'=>0, 'Asignaciones'=>0, 'BitacoraMovimientos'=>0];
    $Mapas = [
        'Usuarios' => ['Id', 'NombreBusqueda', 'NombreCompleto'],
        'Alumnos' => ['Id', 'NombreBusqueda', 'NombreCompleto'],
        'MateriasCatalogo' => ['Id', 'NombreBusqueda', 'Nombre'],
        'MateriasGrupo' => ['Id', 'MateriaBusqueda', 'MateriaNombre'],
        'Asignaciones' => ['Id', 'MateriaBusqueda', 'MateriaNombre'],
    ];
    foreach ($Mapas as $Tabla => [$Pk, $CampoDestino, $CampoFuente]) {
        if (!SgceDbTablaExiste($Pdo, $Tabla)) { continue; }
        $UltimoId = 0;
        $StmtSelect = $Pdo->prepare("SELECT {$Pk}, {$CampoFuente} FROM {$Tabla} WHERE {$Pk} > ? ORDER BY {$Pk} ASC LIMIT {$Lote}");
        $StmtUpdate = $Pdo->prepare("UPDATE {$Tabla} SET {$CampoDestino} = ? WHERE {$Pk} = ?");
        while (true) {
            $StmtSelect->execute([$UltimoId]);
            $Filas = $StmtSelect->fetchAll();
            if (!$Filas) { break; }
            foreach ($Filas as $Fila) {
                $UltimoId = (int)$Fila[$Pk];
                $StmtUpdate->execute([SgceTextoBusquedaNormalizado((string)$Fila[$CampoFuente]), $UltimoId]);
                $Resultado[$Tabla]++;
            }
            if (count($Filas) < $Lote) { break; }
        }
    }
    if (SgceDbTablaExiste($Pdo, 'BitacoraMovimientos')) {
        $UltimoId = 0;
        $StmtSelect = $Pdo->prepare("SELECT Id, Accion, TablaAfectada, RegistroId, Detalle, Rol, Ip FROM BitacoraMovimientos WHERE Id > ? ORDER BY Id ASC LIMIT {$Lote}");
        $StmtUpdate = $Pdo->prepare('UPDATE BitacoraMovimientos SET BusquedaTexto = ? WHERE Id = ?');
        while (true) {
            $StmtSelect->execute([$UltimoId]);
            $Filas = $StmtSelect->fetchAll();
            if (!$Filas) { break; }
            foreach ($Filas as $Fila) {
                $UltimoId = (int)$Fila['Id'];
                $Texto = trim(implode(' ', [(string)$Fila['Accion'], (string)$Fila['TablaAfectada'], (string)$Fila['RegistroId'], (string)$Fila['Detalle'], (string)$Fila['Rol'], (string)$Fila['Ip']]));
                $StmtUpdate->execute([SgceTextoBusquedaNormalizado($Texto), $UltimoId]);
                $Resultado['BitacoraMovimientos']++;
            }
            if (count($Filas) < $Lote) { break; }
        }
    }
    return $Resultado;
}
