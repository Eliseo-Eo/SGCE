<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceCantidadPlaneaciones($Pdo) {
    try {
        $Oferta = SgceOfertaActiva($Pdo);
        $ConfigAcademica = SgceConfiguracionAcademicaPorOferta($Pdo, (int)($Oferta['Id'] ?? 0));
        if (isset($ConfigAcademica['UsaPlaneaciones']) && (int)$ConfigAcademica['UsaPlaneaciones'] !== 1) { return 0; }
        $Cantidad = (int)($ConfigAcademica['PlaneacionesCantidad'] ?? 0);
        if ($Cantidad > 0) { return max(1, min(12, $Cantidad)); }
    } catch (Throwable $E) {}
    $Config = SgceObtenerConfiguracion($Pdo);
    if (isset($Config['UsaPlaneaciones']) && (int)$Config['UsaPlaneaciones'] !== 1) { return 0; }
    $Cantidad = (int)($Config['PlaneacionesCantidad'] ?? 1);
    return max(1, min(12, $Cantidad));
}

function SgceEstadosPlaneacion() {
    return [
        'PENDIENTE' => 'PENDIENTE',
        'SUBIDA' => 'SUBIDA',
        'APROBADA' => 'APROBADA',
        'DEVUELTA' => 'DEVUELTA',
    ];
}

function SgceMateriasDocente($Pdo, $MaestroId) {
    $Ciclo = SgceCicloActivo($Pdo);
    $CicloId = (int)($Ciclo['Id'] ?? 0);
    if ($CicloId <= 0) { return []; }
    $Stmt = $Pdo->prepare("SELECT A.MateriaNombre, G.ProgramaId, PE.Nombre AS ProgramaNombre,
        GROUP_CONCAT(CONCAT(G.Grado, ' ', G.Grupo, ' - ', G.Turno) ORDER BY G.Turno, CAST(G.Grado AS UNSIGNED), G.Grado, G.Grupo SEPARATOR ', ') AS Grupos
        FROM Asignaciones A
        INNER JOIN Grupos G ON G.Id = A.GrupoId AND G.CicloId = A.CicloId
        INNER JOIN ProgramasEducativos PE ON PE.Id = G.ProgramaId
        WHERE A.MaestroId = ? AND A.CicloId = ? AND A.Activo = 1 AND G.Activo = 1
        GROUP BY A.MateriaNombre, G.ProgramaId, PE.Nombre
        ORDER BY PE.Nombre ASC, A.MateriaNombre ASC");
    $Stmt->execute([(int)$MaestroId, $CicloId]);
    return $Stmt->fetchAll(PDO::FETCH_ASSOC);
}

