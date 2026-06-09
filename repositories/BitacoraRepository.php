<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceRepoBitacoraFiltros(array $Entrada): array {
    $Desde = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($Entrada['DesdeFiltro'] ?? '')) ? (string)$Entrada['DesdeFiltro'] : '';
    $Hasta = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($Entrada['HastaFiltro'] ?? '')) ? (string)$Entrada['HastaFiltro'] : '';
    if ($Desde === '' && $Hasta === '') {
        $Hasta = date('Y-m-d');
        $Desde = date('Y-m-d', strtotime('-30 days'));
    }
    if ($Desde !== '' && $Hasta !== '') {
        try {
            $D1 = new DateTime($Desde);
            $D2 = new DateTime($Hasta);
            if ($D2 < $D1) { $Hasta = $Desde; }
            elseif ((int)$D1->diff($D2)->format('%a') > 370) { $Desde = date('Y-m-d', strtotime($Hasta . ' -370 days')); }
        } catch (Throwable $E) {}
    }
    return [
        'buscar' => trim((string)($Entrada['BuscarBitacora'] ?? '')),
        'accion' => trim((string)($Entrada['AccionFiltro'] ?? '')),
        'usuario_id' => max(0, (int)($Entrada['UsuarioIdFiltro'] ?? 0)),
        'desde' => $Desde,
        'hasta' => $Hasta,
    ];
}

function SgceRepoBitacoraWhere(array $Filtros, array &$Params): string {
    $Where = ['1=1'];
    if (!empty($Filtros['buscar'])) {
        $FullText = SgceFullTextBusqueda($Filtros['buscar']);
        if ($FullText !== '') {
            $Where[] = '(MATCH(B.BusquedaTexto, B.Accion, B.TablaAfectada, B.Detalle) AGAINST (? IN BOOLEAN MODE) OR U.NombreBusqueda LIKE ? OR U.NombreCompleto LIKE ?)';
            $Params[] = $FullText;
            $Params[] = SgceLikePrefijoBusqueda($Filtros['buscar']);
            $Params[] = SgceLikePrefijoBusqueda($Filtros['buscar']);
        } else {
            $Where[] = '(B.BusquedaTexto LIKE ? OR U.NombreBusqueda LIKE ?)';
            $Params[] = SgceLikePrefijoBusqueda($Filtros['buscar']);
            $Params[] = SgceLikePrefijoBusqueda($Filtros['buscar']);
        }
    }
    if (!empty($Filtros['accion'])) { $Where[] = 'B.Accion = ?'; $Params[] = $Filtros['accion']; }
    if (!empty($Filtros['usuario_id'])) { $Where[] = 'B.UsuarioId = ?'; $Params[] = (int)$Filtros['usuario_id']; }
    if (!empty($Filtros['desde'])) { $Where[] = 'B.FechaRegistro >= ?'; $Params[] = $Filtros['desde'] . ' 00:00:00'; }
    if (!empty($Filtros['hasta'])) { $Where[] = 'B.FechaRegistro <= ?'; $Params[] = $Filtros['hasta'] . ' 23:59:59'; }
    return implode(' AND ', $Where);
}

function SgceRepoBitacoraContar(PDO $Pdo, array $Filtros = []): int {
    $Params = [];
    $Where = SgceRepoBitacoraWhere($Filtros, $Params);
    $Stmt = $Pdo->prepare("SELECT COUNT(*) FROM BitacoraMovimientos B LEFT JOIN Usuarios U ON U.Id = B.UsuarioId WHERE {$Where}");
    $Stmt->execute($Params);
    return (int)$Stmt->fetchColumn();
}

function SgceRepoBitacoraListar(PDO $Pdo, array $Filtros, int $Limit, int $Offset): array {
    $Params = [];
    $Where = SgceRepoBitacoraWhere($Filtros, $Params);
    $Stmt = $Pdo->prepare("\n        SELECT B.Id, B.UsuarioId, B.Rol, B.Accion, B.TablaAfectada, B.RegistroId, B.Detalle, B.Ip, B.FechaRegistro, U.NombreCompleto\n        FROM BitacoraMovimientos B\n        LEFT JOIN Usuarios U ON U.Id = B.UsuarioId\n        WHERE {$Where}\n        ORDER BY B.FechaRegistro DESC, B.Id DESC\n        LIMIT ? OFFSET ?\n    ");
    foreach ($Params as $I => $Param) { $Stmt->bindValue($I + 1, $Param); }
    SgceBindLimitOffset($Stmt, count($Params) + 1, $Limit, $Offset);
    $Stmt->execute();
    return $Stmt->fetchAll();
}

