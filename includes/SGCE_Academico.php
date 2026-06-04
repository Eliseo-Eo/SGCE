<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceContarAdminsActivos($Pdo) {
    $Stmt = $Pdo->query("SELECT COUNT(*) FROM Usuarios WHERE Rol = 'admin' AND Activo = 1");
    return (int)$Stmt->fetchColumn();
}

function SgcePeriodoActualId($Pdo, $PeriodoSolicitado = 0, int $OfertaId = 0) {
    $PeriodoSolicitado = (int)$PeriodoSolicitado;
    if ($OfertaId <= 0) { $Oferta = SgceOfertaActiva($Pdo); $OfertaId = (int)($Oferta['Id'] ?? 0); }
    if ($PeriodoSolicitado > 0) {
        $Stmt = $Pdo->prepare('SELECT P.Id FROM PeriodosEvaluacion P INNER JOIN CiclosEscolares C ON C.Id = P.CicloId WHERE P.Id = ? AND P.OfertaId = ? AND P.Activo = 1 AND C.Activo = 1 LIMIT 1');
        $Stmt->execute([$PeriodoSolicitado, $OfertaId]);
        $Id = (int)$Stmt->fetchColumn();
        if ($Id > 0) { return $Id; }
    }
    $Stmt = $Pdo->prepare('SELECT P.Id FROM PeriodosEvaluacion P INNER JOIN CiclosEscolares C ON C.Id = P.CicloId WHERE P.OfertaId = ? AND P.Activo = 1 AND C.Activo = 1 ORDER BY C.FechaInicio DESC, P.Orden ASC, P.Id ASC LIMIT 1');
    $Stmt->execute([$OfertaId]);
    return (int)$Stmt->fetchColumn();
}

function SgceActivarCicloUnico(PDO $Pdo, int $CicloId): void {
    if ($CicloId <= 0 || !SgceDbTablaExiste($Pdo, 'CiclosEscolares')) { return; }
    $Pdo->prepare('UPDATE CiclosEscolares SET Activo = 0 WHERE Id <> ?')->execute([$CicloId]);
    $Pdo->prepare('UPDATE CiclosEscolares SET Activo = 1 WHERE Id = ?')->execute([$CicloId]);
}

function SgceCicloActivo($Pdo) {
    SgceNormalizarCicloActivoUnico($Pdo);
    $Stmt = $Pdo->query("SELECT Id, Nombre, FechaInicio, FechaFin FROM CiclosEscolares WHERE Activo = 1 ORDER BY FechaInicio DESC, Id DESC LIMIT 1");
    return $Stmt->fetch() ?: ['Id' => 0, 'Nombre' => '', 'FechaInicio' => null, 'FechaFin' => null];
}

function SgcePeriodoInfo($Pdo, $PeriodoId, int $OfertaId = 0) {
    if ($OfertaId <= 0) { $Oferta = SgceOfertaActiva($Pdo); $OfertaId = (int)($Oferta['Id'] ?? 0); }
    $Stmt = $Pdo->prepare('SELECT P.Id, P.Nombre, P.Orden, P.CicloId, P.OfertaId, C.Nombre AS CicloNombre, C.FechaInicio, C.FechaFin FROM PeriodosEvaluacion P INNER JOIN CiclosEscolares C ON C.Id = P.CicloId WHERE P.Id = ? AND P.OfertaId = ? AND P.Activo = 1 AND C.Activo = 1 LIMIT 1');
    $Stmt->execute([(int)$PeriodoId, $OfertaId]);
    return $Stmt->fetch() ?: null;
}

function SgceValidarParcial($Orden) {
    $Orden = (int)$Orden;
    return $Orden >= 1 && $Orden <= 12;
}

function SgcePeriodosDisponibles($Pdo, int $OfertaId = 0) {
    if ($OfertaId <= 0) { $Oferta = SgceOfertaActiva($Pdo); $OfertaId = (int)($Oferta['Id'] ?? 0); }
    if ($OfertaId <= 0) { return []; }
    $Stmt = $Pdo->prepare('SELECT P.Id, P.Nombre, P.Orden, P.OfertaId, C.Nombre AS CicloNombre, C.Id AS CicloId FROM PeriodosEvaluacion P INNER JOIN CiclosEscolares C ON C.Id = P.CicloId WHERE P.OfertaId = ? AND P.Activo = 1 AND C.Activo = 1 ORDER BY C.FechaInicio DESC, P.Orden ASC, P.Id ASC');
    $Stmt->execute([$OfertaId]);
    return $Stmt->fetchAll();
}

function SgceOfertaActiva(PDO $Pdo) {
    $Stmt = $Pdo->query("SELECT Id, Nombre, NivelEducativo, TipoPeriodizacion, TotalEtapas, EtiquetaEtapa, UsaProgramas, Activo, FechaCreacion, FechaActualizacion FROM OfertasEducativas WHERE Activo = 1 ORDER BY Id ASC LIMIT 1");
    return $Stmt->fetch() ?: null;
}

function SgceEtapasAcademicasListar(PDO $Pdo, int $OfertaId = 0, bool $SoloActivas = true): array {
    if ($OfertaId <= 0) { $Oferta = SgceOfertaActiva($Pdo); $OfertaId = (int)($Oferta['Id'] ?? 0); }
    if ($OfertaId <= 0) { return []; }
    $Where = $SoloActivas ? ' AND Activo = 1' : '';
    $Stmt = $Pdo->prepare("SELECT Id, OfertaId, Nombre, Orden, EsTerminal, Activo FROM EtapasAcademicas WHERE OfertaId = ?{$Where} ORDER BY Orden ASC");
    $Stmt->execute([$OfertaId]);
    return $Stmt->fetchAll();
}

function SgceEtapaAcademicaPorId(PDO $Pdo, int $EtapaId) {
    if ($EtapaId <= 0) { return null; }
    $Stmt = $Pdo->prepare('SELECT Id, OfertaId, Nombre, Orden, EsTerminal, Activo FROM EtapasAcademicas WHERE Id = ? LIMIT 1');
    $Stmt->execute([$EtapaId]);
    return $Stmt->fetch() ?: null;
}

function SgceEtapaSiguiente(PDO $Pdo, int $EtapaId) {
    $Etapa = SgceEtapaAcademicaPorId($Pdo, $EtapaId);
    if (!$Etapa || (int)$Etapa['EsTerminal'] === 1) { return null; }
    $Stmt = $Pdo->prepare('SELECT Id, OfertaId, Nombre, Orden, EsTerminal, Activo FROM EtapasAcademicas WHERE OfertaId = ? AND Orden > ? AND Activo = 1 ORDER BY Orden ASC LIMIT 1');
    $Stmt->execute([(int)$Etapa['OfertaId'], (int)$Etapa['Orden']]);
    return $Stmt->fetch() ?: null;
}

function SgceProgramasEducativosListar(PDO $Pdo, bool $SoloActivas = true, int $OfertaId = 0): array {
    if ($OfertaId <= 0) { $Oferta = SgceOfertaActiva($Pdo); $OfertaId = (int)($Oferta['Id'] ?? 0); }
    if ($OfertaId <= 0) { return []; }
    $Where = ['OfertaId = ?'];
    $Params = [$OfertaId];
    if ($SoloActivas) { $Where[] = 'Activo = 1'; }
    $Stmt = $Pdo->prepare('SELECT Id, OfertaId, Nombre, Clave, Activo FROM ProgramasEducativos WHERE ' . implode(' AND ', $Where) . ' ORDER BY CASE WHEN Nombre = \'GENERAL\' THEN 0 ELSE 1 END, Nombre ASC');
    $Stmt->execute($Params);
    return $Stmt->fetchAll();
}

function SgceProgramaPorId(PDO $Pdo, int $ProgramaId) {
    if ($ProgramaId <= 0) { return null; }
    $Stmt = $Pdo->prepare('SELECT Id, OfertaId, Nombre, Clave, Activo FROM ProgramasEducativos WHERE Id = ? LIMIT 1');
    $Stmt->execute([$ProgramaId]);
    return $Stmt->fetch() ?: null;
}

function SgceProgramaCrearOReactivar(PDO $Pdo, string $Nombre, string $Clave = '', int $OfertaId = 0): int {
    if ($OfertaId <= 0) { $Oferta = SgceOfertaActiva($Pdo); $OfertaId = (int)($Oferta['Id'] ?? 0); }
    if ($OfertaId <= 0) { return 0; }
    $Nombre = SgceNormalizarPrograma($Nombre);
    $Clave = SgceNormalizarMayusculas($Clave);
    if ($Nombre === '') { return 0; }
    $Stmt = $Pdo->prepare('SELECT Id, Activo FROM ProgramasEducativos WHERE OfertaId = ? AND Nombre = ? LIMIT 1');
    $Stmt->execute([$OfertaId, $Nombre]);
    $Programa = $Stmt->fetch();
    if ($Programa) {
        $Pdo->prepare('UPDATE ProgramasEducativos SET Clave = NULLIF(?, \'\'), Activo = 1 WHERE Id = ?')->execute([$Clave, (int)$Programa['Id']]);
        return (int)$Programa['Id'];
    }
    $Pdo->prepare("INSERT INTO ProgramasEducativos (OfertaId, Nombre, Clave, Activo) VALUES (?, ?, NULLIF(?, ''), 1)")->execute([$OfertaId, $Nombre, $Clave]);
    return (int)$Pdo->lastInsertId();
}

function SgceProgramaGeneralId(PDO $Pdo, int $OfertaId = 0): int {
    if ($OfertaId <= 0) { $Oferta = SgceOfertaActiva($Pdo); $OfertaId = (int)($Oferta['Id'] ?? 0); }
    if ($OfertaId <= 0) { return 0; }
    return SgceProgramaCrearOReactivar($Pdo, 'GENERAL', 'GEN', $OfertaId);
}

function SgceCrearOfertaAcademica(PDO $Pdo, string $Nivel, string $Tipo, int $TotalEtapas, bool $UsaProgramas, string $NombreOferta = '', string $EtiquetaEtapa = ''): int {
    $Nivel = SgceNivelEducativoValido($Nivel);
    $Tipo = SgceTipoPeriodizacionValido($Tipo);
    $TotalEtapas = max(1, min(20, $TotalEtapas));
    $NombreOferta = SgceNormalizarMayusculas($NombreOferta !== '' ? $NombreOferta : $Nivel . ' ' . $Tipo);
    $EtiquetaEtapa = SgceNormalizarMayusculas($EtiquetaEtapa !== '' ? $EtiquetaEtapa : SgceEtiquetaEtapaPorTipo($Tipo));
    $Pdo->prepare('UPDATE OfertasEducativas SET Activo = 0')->execute();
    $StmtExiste = $Pdo->prepare('SELECT Id FROM OfertasEducativas WHERE Nombre = ? LIMIT 1');
    $StmtExiste->execute([$NombreOferta]);
    $OfertaId = (int)$StmtExiste->fetchColumn();
    if ($OfertaId > 0) {
        $Pdo->prepare('UPDATE OfertasEducativas SET NivelEducativo = ?, TipoPeriodizacion = ?, TotalEtapas = ?, EtiquetaEtapa = ?, UsaProgramas = ?, Activo = 1 WHERE Id = ?')->execute([$Nivel, $Tipo, $TotalEtapas, $EtiquetaEtapa, $UsaProgramas ? 1 : 0, $OfertaId]);
    } else {
        $Pdo->prepare('INSERT INTO OfertasEducativas (Nombre, NivelEducativo, TipoPeriodizacion, TotalEtapas, EtiquetaEtapa, UsaProgramas, Activo) VALUES (?, ?, ?, ?, ?, ?, 1)')->execute([$NombreOferta, $Nivel, $Tipo, $TotalEtapas, $EtiquetaEtapa, $UsaProgramas ? 1 : 0]);
        $OfertaId = (int)$Pdo->lastInsertId();
    }
    $Pdo->prepare('UPDATE EtapasAcademicas SET Activo = 0, EsTerminal = 0 WHERE OfertaId = ?')->execute([$OfertaId]);
    $StmtBuscar = $Pdo->prepare('SELECT Id FROM EtapasAcademicas WHERE OfertaId = ? AND Orden = ? LIMIT 1');
    $StmtActualizar = $Pdo->prepare('UPDATE EtapasAcademicas SET Nombre = ?, EsTerminal = ?, Activo = 1 WHERE Id = ?');
    $StmtInsertar = $Pdo->prepare('INSERT INTO EtapasAcademicas (OfertaId, Nombre, Orden, EsTerminal, Activo) VALUES (?, ?, ?, ?, 1)');
    for ($Orden = 1; $Orden <= $TotalEtapas; $Orden++) {
        $Nombre = SgceEtiquetaEtapaAcademica($Orden, $Tipo);
        $Terminal = $Orden === $TotalEtapas ? 1 : 0;
        $StmtBuscar->execute([$OfertaId, $Orden]);
        $EtapaId = (int)$StmtBuscar->fetchColumn();
        if ($EtapaId > 0) { $StmtActualizar->execute([$Nombre, $Terminal, $EtapaId]); }
        else { $StmtInsertar->execute([$OfertaId, $Nombre, $Orden, $Terminal]); }
    }
    SgceProgramaGeneralId($Pdo, $OfertaId);
    return $OfertaId;
}

function SgceConfigurarEstructuraAcademicaInicial(PDO $Pdo, string $Nivel, string $Tipo, int $TotalEtapas, bool $UsaProgramas, string $ProgramasTexto = '', string $NombreOferta = '', string $EtiquetaEtapa = '', int $CantidadPeriodos = 3, string $NombreBasePeriodo = 'PARCIAL', string $ModoPeriodos = 'AUTOMATICO', string $PeriodosPersonalizados = '', bool $UsaPlaneaciones = true, string $TipoPlaneacion = 'PERIODO', int $PlaneacionesCantidad = 3): int {
    $OfertaId = SgceCrearOfertaAcademica($Pdo, $Nivel, $Tipo, $TotalEtapas, $UsaProgramas, $NombreOferta, $EtiquetaEtapa);
    SgceGuardarConfiguracionAcademica($Pdo, $OfertaId, $CantidadPeriodos, $NombreBasePeriodo, $ModoPeriodos, $PeriodosPersonalizados, $UsaPlaneaciones, $TipoPlaneacion, $PlaneacionesCantidad);
    SgceProgramaGeneralId($Pdo, $OfertaId);
    foreach (preg_split('/[,;\n]+/u', (string)$ProgramasTexto) as $ProgramaNombre) {
        $ProgramaNombre = SgceNormalizarPrograma($ProgramaNombre);
        if ($ProgramaNombre !== '') { SgceProgramaCrearOReactivar($Pdo, $ProgramaNombre, '', $OfertaId); }
    }
    return $OfertaId;
}

function SgceGrupoObtenerPorCicloEstructura(PDO $Pdo, int $CicloId, int $OfertaId, int $ProgramaId, int $EtapaId, string $Grupo, string $Turno) {
    $Stmt = $Pdo->prepare('SELECT Id, CicloId, OfertaId, ProgramaId, EtapaId, Grado, Grupo, Turno, Activo FROM Grupos WHERE CicloId = ? AND OfertaId = ? AND ProgramaId = ? AND EtapaId = ? AND Grupo = ? AND Turno = ? LIMIT 1');
    $Stmt->execute([$CicloId, $OfertaId, $ProgramaId, $EtapaId, $Grupo, $Turno]);
    return $Stmt->fetch() ?: null;
}

function SgceCicloPorId(PDO $Pdo, int $CicloId) {
    $Stmt = $Pdo->prepare('SELECT Id, Nombre, FechaInicio, FechaFin, Activo FROM CiclosEscolares WHERE Id = ? LIMIT 1');
    $Stmt->execute([$CicloId]);
    return $Stmt->fetch() ?: null;
}

function SgceCiclosInactivosConGrupos(PDO $Pdo): array {
    if (!SgceDbTablaExiste($Pdo, 'Grupos') || !SgceDbColumnaExiste($Pdo, 'Grupos', 'CicloId')) { return []; }
    return $Pdo->query("SELECT C.Id, C.Nombre, C.FechaInicio, C.FechaFin, COUNT(G.Id) AS TotalGrupos
        FROM CiclosEscolares C
        INNER JOIN Grupos G ON G.CicloId = C.Id
        WHERE C.Activo = 0
        GROUP BY C.Id, C.Nombre, C.FechaInicio, C.FechaFin
        ORDER BY C.FechaInicio DESC, C.Id DESC")->fetchAll();
}

function SgceGruposListarPorCiclo(PDO $Pdo, int $CicloId, bool $SoloActivos = true): array {
    if ($CicloId <= 0) { return []; }
    $WhereActivo = $SoloActivos ? ' AND G.Activo = 1' : '';
    $Stmt = $Pdo->prepare("SELECT G.Id, G.CicloId, G.OfertaId, G.ProgramaId, G.EtapaId, G.Grado, G.Grupo, G.Turno,
        C.Nombre AS CicloNombre, C.Activo AS CicloActivo,
        OE.Nombre AS OfertaNombre, OE.NivelEducativo, OE.TipoPeriodizacion, OE.UsaProgramas,
        CA.Nombre AS ProgramaNombre, EA.Nombre AS EtapaNombre, EA.Orden AS EtapaOrden, EA.EsTerminal,
        (SELECT COUNT(*) FROM AlumnoInscripciones AI WHERE AI.GrupoId = G.Id AND AI.CicloId = G.CicloId AND AI.Estado IN ('INSCRITO','PROMOVIDO','EGRESADO')) AS TotalAlumnos
        FROM Grupos G
        INNER JOIN CiclosEscolares C ON C.Id = G.CicloId
        LEFT JOIN OfertasEducativas OE ON OE.Id = G.OfertaId
        LEFT JOIN ProgramasEducativos CA ON CA.Id = G.ProgramaId
        LEFT JOIN EtapasAcademicas EA ON EA.Id = G.EtapaId
        WHERE G.CicloId = ?{$WhereActivo}
        ORDER BY G.Turno, COALESCE(EA.Orden, CAST(G.Grado AS UNSIGNED)), G.Grado, CA.Nombre, G.Grupo, G.Id");
    $Stmt->execute([$CicloId]);
    return $Stmt->fetchAll();
}

function SgceGrupoObtenerPorId(PDO $Pdo, int $GrupoId) {
    $Stmt = $Pdo->prepare("SELECT G.Id, G.CicloId, G.OfertaId, G.ProgramaId, G.EtapaId, G.Grado, G.Grupo, G.Turno, G.Activo,
        C.Nombre AS CicloNombre, C.Activo AS CicloActivo, C.FechaInicio, C.FechaFin,
        OE.Nombre AS OfertaNombre, OE.NivelEducativo, OE.TipoPeriodizacion, OE.UsaProgramas,
        CA.Nombre AS ProgramaNombre, EA.Nombre AS EtapaNombre, EA.Orden AS EtapaOrden, EA.EsTerminal
        FROM Grupos G
        INNER JOIN CiclosEscolares C ON C.Id = G.CicloId
        LEFT JOIN OfertasEducativas OE ON OE.Id = G.OfertaId
        LEFT JOIN ProgramasEducativos CA ON CA.Id = G.ProgramaId
        LEFT JOIN EtapasAcademicas EA ON EA.Id = G.EtapaId
        WHERE G.Id = ? LIMIT 1");
    $Stmt->execute([$GrupoId]);
    return $Stmt->fetch() ?: null;
}

function SgceGrupoCrearOReactivar(PDO $Pdo, int $CicloId, string $Grado, string $Grupo, string $Turno, int $EtapaId = 0, int $ProgramaId = 0, int $OfertaId = 0): int {
    if ($EtapaId <= 0) { throw new RuntimeException('Selecciona una etapa académica válida para crear el grupo.'); }
    $Etapa = SgceEtapaAcademicaPorId($Pdo, $EtapaId);
    if (!$Etapa || (int)$Etapa['Activo'] !== 1) { throw new RuntimeException('La etapa académica seleccionada no existe o está inactiva.'); }
    $OfertaId = $OfertaId > 0 ? $OfertaId : (int)$Etapa['OfertaId'];
    $TipoOferta = 'ANUAL';
    if ($OfertaId > 0) {
        $StmtOferta = $Pdo->prepare('SELECT TipoPeriodizacion FROM OfertasEducativas WHERE Id = ? LIMIT 1');
        $StmtOferta->execute([$OfertaId]);
        $TipoOferta = (string)($StmtOferta->fetchColumn() ?: 'ANUAL');
    }
    $Grado = SgceEtapaNombreVisual($Etapa, $TipoOferta);
    $ProgramaId = $ProgramaId > 0 ? $ProgramaId : SgceProgramaGeneralId($Pdo, $OfertaId);
    $Existente = SgceGrupoObtenerPorCicloEstructura($Pdo, $CicloId, $OfertaId, $ProgramaId, $EtapaId, $Grupo, $Turno);
    if ($Existente) {
        if ((int)$Existente['Activo'] !== 1) {
            $Stmt = $Pdo->prepare('UPDATE Grupos SET Activo = 1 WHERE Id = ?');
            $Stmt->execute([(int)$Existente['Id']]);
        }
        return (int)$Existente['Id'];
    }
    $Stmt = $Pdo->prepare('INSERT INTO Grupos (CicloId, OfertaId, ProgramaId, EtapaId, Grado, Grupo, Turno, Activo) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
    $Stmt->execute([$CicloId, $OfertaId, $ProgramaId, $EtapaId, $Grado, $Grupo, $Turno]);
    return (int)$Pdo->lastInsertId();
}

function SgceAlumnoTieneInscripcion(PDO $Pdo, int $AlumnoId, int $CicloId): bool {
    $Stmt = $Pdo->prepare('SELECT COUNT(*) FROM AlumnoInscripciones WHERE AlumnoId = ? AND CicloId = ?');
    $Stmt->execute([$AlumnoId, $CicloId]);
    return (int)$Stmt->fetchColumn() > 0;
}

function SgceAlumnoInscribirEnCiclo(PDO $Pdo, int $AlumnoId, int $CicloId, int $GrupoId, string $Estado = 'INSCRITO'): bool {
    $Estado = in_array($Estado, ['INSCRITO','PROMOVIDO','EGRESADO','BAJA'], true) ? $Estado : 'INSCRITO';
    try {
        $Grupo = SgceGrupoObtenerPorId($Pdo, $GrupoId);
        $OfertaId = (int)($Grupo['OfertaId'] ?? 0);
        $ProgramaId = (int)($Grupo['ProgramaId'] ?? 0);
        $EtapaId = (int)($Grupo['EtapaId'] ?? 0);
        $Stmt = $Pdo->prepare('INSERT INTO AlumnoInscripciones (AlumnoId, CicloId, GrupoId, OfertaId, ProgramaId, EtapaId, Estado) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $Stmt->execute([$AlumnoId, $CicloId, $GrupoId, $OfertaId, $ProgramaId, $EtapaId, $Estado]);
        return true;
    } catch (PDOException $E) {
        return false;
    }
}

function SgceAlumnosPorGrupoCiclo(PDO $Pdo, int $GrupoId, int $CicloId, array $Estados = ['INSCRITO']): array {
    if ($GrupoId <= 0 || $CicloId <= 0) { return []; }
    $EstadosPermitidos = ['INSCRITO','PROMOVIDO','EGRESADO','BAJA'];
    $Estados = array_values(array_intersect($Estados, $EstadosPermitidos));
    if (!$Estados) { $Estados = ['INSCRITO']; }
    $Place = implode(',', array_fill(0, count($Estados), '?'));
    $Stmt = $Pdo->prepare("SELECT A.Id, A.NombreCompleto, AI.GrupoId, AI.OfertaId, AI.ProgramaId, AI.EtapaId, G.Grado, G.Grupo, G.Turno, AI.CicloId, AI.Estado
        FROM AlumnoInscripciones AI
        INNER JOIN Alumnos A ON A.Id = AI.AlumnoId AND A.Activo = 1
        INNER JOIN Grupos G ON G.Id = AI.GrupoId AND G.CicloId = AI.CicloId
        WHERE AI.GrupoId = ? AND AI.CicloId = ? AND AI.Estado IN ($Place)
        ORDER BY A.NombreCompleto, A.Id");
    $Stmt->execute(array_merge([$GrupoId, $CicloId], $Estados));
    return $Stmt->fetchAll();
}

