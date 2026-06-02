<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceRepoBitacoraFiltros(array $Entrada): array {
    return [
        'buscar' => trim((string)($Entrada['BuscarBitacora'] ?? '')),
        'accion' => trim((string)($Entrada['AccionFiltro'] ?? '')),
        'usuario_id' => max(0, (int)($Entrada['UsuarioIdFiltro'] ?? 0)),
        'desde' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($Entrada['DesdeFiltro'] ?? '')) ? (string)$Entrada['DesdeFiltro'] : '',
        'hasta' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($Entrada['HastaFiltro'] ?? '')) ? (string)$Entrada['HastaFiltro'] : '',
    ];
}

function SgceRepoBitacoraWhere(array $Filtros, array &$Params): string {
    $Where = ['1=1'];
    if (!empty($Filtros['buscar'])) {
        $Where[] = '(B.Accion LIKE ? OR B.TablaAfectada LIKE ? OR B.Detalle LIKE ? OR U.NombreCompleto LIKE ?)';
        for ($I = 0; $I < 4; $I++) { $Params[] = '%' . $Filtros['buscar'] . '%'; }
    }
    if (!empty($Filtros['accion'])) { $Where[] = 'B.Accion = ?'; $Params[] = $Filtros['accion']; }
    if (!empty($Filtros['usuario_id'])) { $Where[] = 'B.UsuarioId = ?'; $Params[] = (int)$Filtros['usuario_id']; }
    if (!empty($Filtros['desde'])) { $Where[] = 'B.FechaRegistro >= ?'; $Params[] = $Filtros['desde'] . ' 00:00:00'; }
    if (!empty($Filtros['hasta'])) { $Where[] = 'B.FechaRegistro <= ?'; $Params[] = $Filtros['hasta'] . ' 23:59:59'; }
    return implode(' AND ', $Where);
}

function SgceRepoBitacoraContar(PDO $Pdo, array $Filtros = []): int {
    if (function_exists('CrearTablaBitacoraSiNoExiste')) { CrearTablaBitacoraSiNoExiste($Pdo); }
    $Params = [];
    $Where = SgceRepoBitacoraWhere($Filtros, $Params);
    $Stmt = $Pdo->prepare("
        SELECT COUNT(*)
        FROM BitacoraMovimientos B
        LEFT JOIN Usuarios U ON U.Id = B.UsuarioId
        WHERE {$Where}
    ");
    $Stmt->execute($Params);
    return (int)$Stmt->fetchColumn();
}

function SgceRepoBitacoraListar(PDO $Pdo, array $Filtros, int $Limit, int $Offset): array {
    if (function_exists('CrearTablaBitacoraSiNoExiste')) { CrearTablaBitacoraSiNoExiste($Pdo); }
    $Params = [];
    $Where = SgceRepoBitacoraWhere($Filtros, $Params);
    $Stmt = $Pdo->prepare("
        SELECT B.Id, B.UsuarioId, B.Rol, B.Accion, B.TablaAfectada, B.RegistroId, B.Detalle, B.Ip, B.FechaRegistro, U.NombreCompleto
        FROM BitacoraMovimientos B
        LEFT JOIN Usuarios U ON U.Id = B.UsuarioId
        WHERE {$Where}
        ORDER BY B.FechaRegistro DESC, B.Id DESC
        LIMIT ? OFFSET ?
    ");
    foreach ($Params as $I => $Param) { $Stmt->bindValue($I + 1, $Param); }
    $Stmt->bindValue(count($Params) + 1, max(1, min(200, $Limit)), PDO::PARAM_INT);
    $Stmt->bindValue(count($Params) + 2, max(0, $Offset), PDO::PARAM_INT);
    $Stmt->execute();
    return $Stmt->fetchAll();
}
