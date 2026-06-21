<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

require_once dirname(__DIR__) . '/services/GrupoService.php';

// Funciones académicas de materias y cargas por grupo.

function SgceMateriaIdPorNombre(PDO $Pdo, string $Nombre): int {
    $Nombre = SgceNormalizarMayusculas($Nombre);
    if ($Nombre === '') { return 0; }
    if (!SgceDbTablaExiste($Pdo, 'MateriasCatalogo')) { return 0; }
    $Stmt = $Pdo->prepare('SELECT Id FROM MateriasCatalogo WHERE Nombre = ? LIMIT 1');
    $Stmt->execute([$Nombre]);
    $Id = (int)$Stmt->fetchColumn();
    if ($Id > 0) {
        $Pdo->prepare('UPDATE MateriasCatalogo SET Activo = 1, NombreBusqueda = ? WHERE Id = ?')->execute([function_exists('SgceTextoBusquedaNormalizado') ? SgceTextoBusquedaNormalizado($Nombre) : $Nombre, $Id]);
        return $Id;
    }
    $Stmt = $Pdo->prepare('INSERT INTO MateriasCatalogo (Nombre, NombreBusqueda, Activo) VALUES (?, ?, 1)');
    $Stmt->execute([$Nombre, function_exists('SgceTextoBusquedaNormalizado') ? SgceTextoBusquedaNormalizado($Nombre) : $Nombre]);
    return (int)$Pdo->lastInsertId();
}

function SgceHorasMateriaSeguro($Horas): int {
    $Horas = (int)$Horas;
    return ($Horas >= 1 && $Horas <= 40) ? $Horas : 0;
}

function SgceMateriaGrupoHorasActuales(PDO $Pdo, int $GrupoId, int $ExcluirMateriaGrupoId = 0): int {
    if ($GrupoId <= 0 || !SgceDbTablaExiste($Pdo, 'MateriasGrupo')) { return 0; }
    $Sql = 'SELECT COALESCE(SUM(HorasSemana),0) FROM MateriasGrupo WHERE GrupoId = ? AND Activo = 1';
    $Params = [$GrupoId];
    if ($ExcluirMateriaGrupoId > 0) { $Sql .= ' AND Id <> ?'; $Params[] = $ExcluirMateriaGrupoId; }
    $Stmt = $Pdo->prepare($Sql);
    $Stmt->execute($Params);
    return (int)$Stmt->fetchColumn();
}

function SgceMateriaGrupoObtener(PDO $Pdo, int $MateriaGrupoId) {
    if ($MateriaGrupoId <= 0 || !SgceDbTablaExiste($Pdo, 'MateriasGrupo')) { return null; }
    $Stmt = $Pdo->prepare("SELECT MG.*, G.Grado, G.Grupo, G.Turno, PE.Nombre AS ProgramaNombre, C.Nombre AS CicloNombre, C.Activo AS CicloActivo
        FROM MateriasGrupo MG
        INNER JOIN Grupos G ON G.Id = MG.GrupoId AND G.CicloId = MG.CicloId
        INNER JOIN ProgramasEducativos PE ON PE.Id = MG.ProgramaId
        INNER JOIN CiclosEscolares C ON C.Id = MG.CicloId
        WHERE MG.Id = ? LIMIT 1");
    $Stmt->execute([$MateriaGrupoId]);
    return $Stmt->fetch() ?: null;
}

function SgceMateriaGrupoTieneAsignacion(PDO $Pdo, int $MateriaGrupoId): bool {
    if ($MateriaGrupoId <= 0 || !SgceDbTablaExiste($Pdo, 'Asignaciones')) { return false; }
    $Stmt = $Pdo->prepare('SELECT COUNT(*) FROM Asignaciones WHERE MateriaGrupoId = ? AND Activo = 1');
    $Stmt->execute([$MateriaGrupoId]);
    return (int)$Stmt->fetchColumn() > 0;
}

function SgceMateriaGrupoTieneDatosAcademicos(PDO $Pdo, int $MateriaGrupoId): bool {
    if ($MateriaGrupoId <= 0 || !SgceDbTablaExiste($Pdo, 'Asignaciones')) { return false; }
    $Stmt = $Pdo->prepare('SELECT Id FROM Asignaciones WHERE MateriaGrupoId = ?');
    $Stmt->execute([$MateriaGrupoId]);
    foreach ($Stmt->fetchAll(PDO::FETCH_COLUMN) as $AsignacionId) {
        if (SgceAsignacionTieneDatosAcademicos($Pdo, (int)$AsignacionId)) { return true; }
    }
    return false;
}

function SgceMateriaGrupoCrearOReactivar(PDO $Pdo, int $GrupoId, string $MateriaNombre, int $HorasSemana, int $CicloId = 0): int {
    $MateriaNombre = SgceNormalizarMayusculas($MateriaNombre);
    $MateriaBusqueda = function_exists('SgceTextoBusquedaNormalizado') ? SgceTextoBusquedaNormalizado($MateriaNombre) : $MateriaNombre;
    $HorasSemana = SgceHorasMateriaSeguro($HorasSemana);
    if ($GrupoId <= 0 || $MateriaNombre === '' || $HorasSemana <= 0) { throw new RuntimeException('Datos de materia inválidos.'); }

    $TransaccionPropia = !$Pdo->inTransaction();
    if ($TransaccionPropia) { $Pdo->beginTransaction(); }
    try {
        // Bloqueo de grupo: evita que dos importaciones/usuarios sumen horas al mismo grupo al mismo tiempo y rebasen 40.
        $StmtLock = $Pdo->prepare('SELECT Id FROM Grupos WHERE Id = ? FOR UPDATE');
        $StmtLock->execute([$GrupoId]);
        if (!$StmtLock->fetchColumn()) { throw new RuntimeException('El grupo seleccionado no existe.'); }

        $Grupo = $CicloId > 0 ? SgceGrupoObtenerPorId($Pdo, $GrupoId) : SgceGrupoObtenerActivoPorId($Pdo, $GrupoId);
        if (!$Grupo) { throw new RuntimeException('El grupo seleccionado no existe o no pertenece al ciclo activo.'); }
        if ($CicloId <= 0) { $CicloId = (int)$Grupo['CicloId']; }
        if ((int)$Grupo['CicloId'] !== $CicloId) { throw new RuntimeException('La materia debe pertenecer al mismo ciclo del grupo.'); }
        $MateriaId = SgceMateriaIdPorNombre($Pdo, $MateriaNombre);
        if ($MateriaId <= 0) { throw new RuntimeException('No se pudo registrar la materia en catálogo.'); }

        $StmtExistente = $Pdo->prepare('SELECT Id, Activo FROM MateriasGrupo WHERE CicloId = ? AND GrupoId = ? AND MateriaId = ? LIMIT 1');
        $StmtExistente->execute([$CicloId, $GrupoId, $MateriaId]);
        $Existente = $StmtExistente->fetch();
        $ExcluirId = $Existente ? (int)$Existente['Id'] : 0;
        $HorasActuales = SgceMateriaGrupoHorasActuales($Pdo, $GrupoId, $ExcluirId);
        if ($HorasActuales + $HorasSemana > 40) {
            throw new RuntimeException('El grupo no puede superar 40 horas semanales. Actualmente tiene ' . $HorasActuales . ' horas registradas.');
        }

        if ($Existente) {
            $Id = (int)$Existente['Id'];
            if (SgceMateriaGrupoTieneDatosAcademicos($Pdo, $Id)) {
                $Pdo->prepare('UPDATE MateriasGrupo SET Activo = 1, MateriaBusqueda = ? WHERE Id = ?')->execute([$MateriaBusqueda, $Id]);
            } else {
                $Pdo->prepare('UPDATE MateriasGrupo SET MateriaNombre = ?, MateriaBusqueda = ?, HorasSemana = ?, Activo = 1 WHERE Id = ?')->execute([$MateriaNombre, $MateriaBusqueda, $HorasSemana, $Id]);
            }
            if ($TransaccionPropia) { $Pdo->commit(); }
            return $Id;
        }

        $Stmt = $Pdo->prepare('INSERT INTO MateriasGrupo (CicloId, OfertaId, ProgramaId, EtapaId, GrupoId, MateriaId, MateriaNombre, MateriaBusqueda, HorasSemana, Activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
        $Stmt->execute([$CicloId, (int)$Grupo['OfertaId'], (int)$Grupo['ProgramaId'], (int)$Grupo['EtapaId'], $GrupoId, $MateriaId, $MateriaNombre, $MateriaBusqueda, $HorasSemana]);
        $NuevoId = (int)$Pdo->lastInsertId();
        if ($TransaccionPropia) { $Pdo->commit(); }
        return $NuevoId;
    } catch (Throwable $E) {
        if ($TransaccionPropia && $Pdo->inTransaction()) { $Pdo->rollBack(); }
        throw $E;
    }
}

function SgceMateriaGrupoListar(PDO $Pdo, int $CicloId = 0, bool $SoloActivas = true): array {
    if (!SgceDbTablaExiste($Pdo, 'MateriasGrupo')) { return []; }
    if ($CicloId <= 0) { $Ciclo = SgceCicloActivo($Pdo); $CicloId = (int)($Ciclo['Id'] ?? 0); }
    if ($CicloId <= 0) { return []; }
    $Where = ['MG.CicloId = ?'];
    $Params = [$CicloId];
    if ($SoloActivas) { $Where[] = 'MG.Activo = 1'; }
    $Stmt = $Pdo->prepare("SELECT MG.*, G.Grado, G.Grupo, G.Turno, PE.Nombre AS ProgramaNombre,
        OE.TipoPeriodizacion, EA.Nombre AS EtapaNombre, EA.Orden AS EtapaOrden, EA.EsTerminal,
        (SELECT COUNT(*) FROM Asignaciones A WHERE A.MateriaGrupoId = MG.Id AND A.Activo = 1) AS TieneAsignacion
        FROM MateriasGrupo MG
        INNER JOIN Grupos G ON G.Id = MG.GrupoId AND G.CicloId = MG.CicloId
        INNER JOIN ProgramasEducativos PE ON PE.Id = MG.ProgramaId
        LEFT JOIN OfertasEducativas OE ON OE.Id = G.OfertaId
        LEFT JOIN EtapasAcademicas EA ON EA.Id = G.EtapaId
        WHERE " . implode(' AND ', $Where) . "
        ORDER BY PE.Nombre, G.Turno, COALESCE(EA.Orden, CAST(G.Grado AS UNSIGNED)), G.Grado, G.Grupo, MG.MateriaNombre");
    $Stmt->execute($Params);
    return $Stmt->fetchAll();
}

function SgceMateriaGrupoListarDisponiblesAsignacion(PDO $Pdo, int $CicloId = 0): array {
    if ($CicloId <= 0) { $Ciclo = SgceCicloActivo($Pdo); $CicloId = (int)($Ciclo['Id'] ?? 0); }
    if ($CicloId <= 0 || !SgceDbTablaExiste($Pdo, 'MateriasGrupo')) { return []; }
    $Stmt = $Pdo->prepare("SELECT MG.*, G.Grado, G.Grupo, G.Turno, PE.Nombre AS ProgramaNombre,
        OE.TipoPeriodizacion, EA.Nombre AS EtapaNombre, EA.Orden AS EtapaOrden, EA.EsTerminal
        FROM MateriasGrupo MG
        INNER JOIN Grupos G ON G.Id = MG.GrupoId AND G.CicloId = MG.CicloId AND G.Activo = 1
        INNER JOIN ProgramasEducativos PE ON PE.Id = MG.ProgramaId
        LEFT JOIN OfertasEducativas OE ON OE.Id = G.OfertaId
        LEFT JOIN EtapasAcademicas EA ON EA.Id = G.EtapaId
        LEFT JOIN Asignaciones A ON A.MateriaGrupoId = MG.Id AND A.Activo = 1
        WHERE MG.CicloId = ? AND MG.Activo = 1 AND A.Id IS NULL
        ORDER BY PE.Nombre, G.Turno, COALESCE(EA.Orden, CAST(G.Grado AS UNSIGNED)), G.Grado, G.Grupo, MG.MateriaNombre");
    $Stmt->execute([$CicloId]);
    return $Stmt->fetchAll();
}

function SgceMateriaGrupoActualizarSeguro(PDO $Pdo, int $MateriaGrupoId, int $GrupoId, string $MateriaNombre, int $HorasSemana, int $CicloId, array $UserSession = []): void {
    $MateriaNombre = SgceNormalizarMayusculas($MateriaNombre);
    $MateriaBusqueda = function_exists('SgceTextoBusquedaNormalizado') ? SgceTextoBusquedaNormalizado($MateriaNombre) : $MateriaNombre;
    $HorasSemana = SgceHorasMateriaSeguro($HorasSemana);
    if ($MateriaGrupoId <= 0 || $GrupoId <= 0 || $CicloId <= 0 || $MateriaNombre === '' || $HorasSemana <= 0) {
        throw new RuntimeException('Datos de materia inválidos.');
    }

    $TransaccionPropia = !$Pdo->inTransaction();
    if ($TransaccionPropia) { $Pdo->beginTransaction(); }
    try {
        $StmtActual = $Pdo->prepare('SELECT Id, GrupoId, CicloId, Activo FROM MateriasGrupo WHERE Id = ? AND CicloId = ? FOR UPDATE');
        $StmtActual->execute([$MateriaGrupoId, $CicloId]);
        $Actual = $StmtActual->fetch();
        if (!$Actual || (int)$Actual['Activo'] !== 1) { throw new RuntimeException('Solo puedes editar materias activas del ciclo actual.'); }
        if (SgceMateriaGrupoTieneAsignacion($Pdo, $MateriaGrupoId) || SgceMateriaGrupoTieneDatosAcademicos($Pdo, $MateriaGrupoId)) {
            throw new RuntimeException('Esta materia ya tiene asignación o historial académico. Por seguridad no puedes cambiarla; crea otra materia o realiza ajustes antes de asignarla.');
        }

        $IdsBloqueo = array_values(array_unique(array_filter([(int)$Actual['GrupoId'], $GrupoId])));
        sort($IdsBloqueo, SORT_NUMERIC);
        foreach ($IdsBloqueo as $IdBloqueo) {
            $StmtLock = $Pdo->prepare('SELECT Id FROM Grupos WHERE Id = ? AND CicloId = ? FOR UPDATE');
            $StmtLock->execute([$IdBloqueo, $CicloId]);
            if (!$StmtLock->fetchColumn()) { throw new RuntimeException('El grupo seleccionado no existe o no pertenece al ciclo activo.'); }
        }

        $Grupo = SgceGrupoObtenerActivoPorId($Pdo, $GrupoId);
        if (!$Grupo || (int)$Grupo['CicloId'] !== $CicloId) { throw new RuntimeException('El grupo debe pertenecer al ciclo activo.'); }
        $MateriaId = SgceMateriaIdPorNombre($Pdo, $MateriaNombre);
        if ($MateriaId <= 0) { throw new RuntimeException('No se pudo registrar la materia en catálogo.'); }

        $HorasActuales = SgceMateriaGrupoHorasActuales($Pdo, $GrupoId, $MateriaGrupoId);
        if ($HorasActuales + $HorasSemana > 40) {
            throw new RuntimeException('El grupo no puede superar 40 horas semanales. Actualmente tiene ' . $HorasActuales . ' horas registradas.');
        }

        $StmtUpdate = $Pdo->prepare('UPDATE MateriasGrupo SET OfertaId = ?, ProgramaId = ?, EtapaId = ?, GrupoId = ?, MateriaId = ?, MateriaNombre = ?, MateriaBusqueda = ?, HorasSemana = ? WHERE Id = ? AND CicloId = ? AND Activo = 1');
        $StmtUpdate->execute([(int)$Grupo['OfertaId'], (int)$Grupo['ProgramaId'], (int)$Grupo['EtapaId'], $GrupoId, $MateriaId, $MateriaNombre, $MateriaBusqueda, $HorasSemana, $MateriaGrupoId, $CicloId]);
        if ($StmtUpdate->rowCount() < 1) { throw new RuntimeException('No se pudo actualizar la materia.'); }

        if ($TransaccionPropia) { $Pdo->commit(); }
    } catch (Throwable $E) {
        if ($TransaccionPropia && $Pdo->inTransaction()) { $Pdo->rollBack(); }
        throw $E;
    }
}

