<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceAsistenciaResumenHoy(PDO $Pdo): array {
    $Ciclo = SgceCicloActivo($Pdo);
    $CicloId = (int)($Ciclo['Id'] ?? 0);
    if ($CicloId <= 0) { return ['Total' => 0, 'Faltas' => 0]; }
    $Stmt = $Pdo->prepare("\n        SELECT\n            COUNT(*) AS Total,\n            SUM(CASE WHEN Asi.Estado = 'F' THEN 1 ELSE 0 END) AS Faltas\n        FROM Asistencias Asi\n        INNER JOIN Asignaciones A ON A.Id = Asi.AsignacionId AND A.Activo = 1 AND A.CicloId = Asi.CicloId\n        INNER JOIN Usuarios U ON U.Id = A.MaestroId AND U.Activo = 1\n        INNER JOIN Grupos G ON G.Id = A.GrupoId AND G.Activo = 1 AND G.CicloId = Asi.CicloId\n        INNER JOIN AlumnoInscripciones AI ON AI.AlumnoId = Asi.AlumnoId AND AI.CicloId = Asi.CicloId AND AI.GrupoId = A.GrupoId AND AI.Estado = 'INSCRITO'\n        INNER JOIN Alumnos Al ON Al.Id = Asi.AlumnoId AND Al.Activo = 1\n        WHERE Asi.CicloId = ? AND Asi.FechaDia = CURDATE()\n    ");
    $Stmt->execute([$CicloId]);
    $Row = $Stmt->fetch() ?: [];
    return [
        'Total' => (int)($Row['Total'] ?? 0),
        'Faltas' => (int)($Row['Faltas'] ?? 0),
    ];
}


function SgceAsistenciaEstadosPermitidos(): array {
    return ['A', 'F', 'R', 'J'];
}

function SgceAsistenciaEstadoSeguro($Estado): string {
    $Estado = SgceNormalizarMayusculas((string)$Estado);
    return in_array($Estado, SgceAsistenciaEstadosPermitidos(), true) ? $Estado : 'A';
}

function SgceAsistenciaMensajeResultado(string $Tipo): string {
    $Tipo = strtolower(trim($Tipo));
    if ($Tipo === 'actualizada') {
        return 'Pase de lista actualizado correctamente. Los cambios quedaron guardados para la fecha seleccionada.';
    }
    if ($Tipo === 'registrada') {
        return 'Pase de lista registrado correctamente para la fecha seleccionada.';
    }
    return 'Pase de lista guardado correctamente.';
}

function SgceAsistenciaObtenerAsignacionActiva(PDO $Pdo, int $AsignacionId): ?array {
    return function_exists('SgceAsistenciaRepoObtenerAsignacionActiva')
        ? SgceAsistenciaRepoObtenerAsignacionActiva($Pdo, $AsignacionId)
        : null;
}


function SgceAsistenciaObtenerAsignacionContexto(PDO $Pdo, int $AsignacionId): ?array {
    return function_exists('SgceAsistenciaRepoObtenerAsignacionContexto')
        ? SgceAsistenciaRepoObtenerAsignacionContexto($Pdo, $AsignacionId)
        : null;
}

function SgceAsistenciaObtenerAlumnos(PDO $Pdo, int $GrupoId, int $CicloId): array {
    return function_exists('SgceAsistenciaRepoObtenerAlumnos')
        ? SgceAsistenciaRepoObtenerAlumnos($Pdo, $GrupoId, $CicloId)
        : [];
}

function SgceAsistenciaExisteFecha(PDO $Pdo, int $CicloId, int $AsignacionId, string $Fecha): bool {
    return function_exists('SgceAsistenciaRepoExisteFecha')
        ? SgceAsistenciaRepoExisteFecha($Pdo, $CicloId, $AsignacionId, $Fecha)
        : false;
}

function SgceAsistenciaGuardarPase(PDO $Pdo, int $CicloId, int $AsignacionId, string $Fecha, array $Alumnos, array $Estados): void {
    $Momento = $Fecha . ' ' . date('H:i:s');
    $StmtExiste = $Pdo->prepare("\n        SELECT Id\n        FROM Asistencias\n        WHERE CicloId = ?\n        AND AsignacionId = ?\n        AND AlumnoId = ?\n        AND FechaDia = ?\n        ORDER BY Id ASC\n        LIMIT 1\n    ");
    $StmtActualizar = $Pdo->prepare("\n        UPDATE Asistencias\n        SET Estado = ?, Fecha = ?\n        WHERE CicloId = ?\n        AND AsignacionId = ?\n        AND AlumnoId = ?\n        AND FechaDia = ?\n    ");
    $StmtInsertar = $Pdo->prepare("\n        INSERT INTO Asistencias (CicloId, AsignacionId, AlumnoId, Fecha, Estado)\n        VALUES (?, ?, ?, ?, ?)\n    ");
    foreach ($Alumnos as $Alumno) {
        $AlumnoId = (int)$Alumno['Id'];
        $Estado = SgceAsistenciaEstadoSeguro($Estados[$AlumnoId] ?? 'A');
        $StmtExiste->execute([$CicloId, $AsignacionId, $AlumnoId, $Fecha]);
        $AsistenciaId = (int)$StmtExiste->fetchColumn();
        if ($AsistenciaId > 0) {
            $StmtActualizar->execute([$Estado, $Momento, $CicloId, $AsignacionId, $AlumnoId, $Fecha]);
        } else {
            $StmtInsertar->execute([$CicloId, $AsignacionId, $AlumnoId, $Momento, $Estado]);
        }
    }
    $StmtLimpiarDuplicados = $Pdo->prepare("\n        DELETE AsiDuplicada\n        FROM Asistencias AsiDuplicada\n        INNER JOIN Asistencias AsiBase\n            ON AsiBase.CicloId = AsiDuplicada.CicloId\n            AND AsiBase.AsignacionId = AsiDuplicada.AsignacionId\n            AND AsiBase.AlumnoId = AsiDuplicada.AlumnoId\n            AND AsiBase.FechaDia = AsiDuplicada.FechaDia\n            AND AsiBase.Id < AsiDuplicada.Id\n        WHERE AsiDuplicada.CicloId = ?\n        AND AsiDuplicada.AsignacionId = ?\n        AND AsiDuplicada.FechaDia = ?\n    ");
    $StmtLimpiarDuplicados->execute([$CicloId, $AsignacionId, $Fecha]);
}

function SgceAsistenciaEstadosRegistrados(PDO $Pdo, int $CicloId, int $AsignacionId, string $Fecha): array {
    return function_exists('SgceAsistenciaRepoEstadosRegistrados')
        ? SgceAsistenciaRepoEstadosRegistrados($Pdo, $CicloId, $AsignacionId, $Fecha)
        : [];
}

function SgceAsistenciaResumenAlumnos(array $Alumnos, array $EstadosRegistrados): array {
    $Resumen = ['A' => 0, 'F' => 0, 'R' => 0, 'J' => 0];
    foreach ($Alumnos as $Alumno) {
        $Estado = $EstadosRegistrados[(int)$Alumno['Id']] ?? 'A';
        if (!array_key_exists($Estado, $Resumen)) { $Estado = 'A'; }
        $Resumen[$Estado]++;
    }
    return $Resumen;
}
