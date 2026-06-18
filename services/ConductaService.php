<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceConductaTipos(): array { return ['OBSERVACION','REPORTE','REPORTE_GRAVE','RECONOCIMIENTO','SEGUIMIENTO']; }
function SgceConductaSeveridades(): array { return ['LEVE','MEDIA','GRAVE']; }
function SgceConductaEstados(): array { return ['PENDIENTE','VALIDADO','EN_SEGUIMIENTO','CERRADO','CANCELADO']; }

function SgceConductaTipoSeguro($Valor): string {
    $Valor = SgceNormalizarMayusculas($Valor);
    return in_array($Valor, SgceConductaTipos(), true) ? $Valor : '';
}
function SgceConductaTipoSeguroDefault($Valor): string { $V = SgceConductaTipoSeguro($Valor); return $V !== '' ? $V : 'REPORTE'; }
function SgceConductaSeveridadSeguro($Valor): string {
    $Valor = SgceNormalizarMayusculas($Valor);
    return in_array($Valor, SgceConductaSeveridades(), true) ? $Valor : '';
}
function SgceConductaSeveridadSeguroDefault($Valor): string { $V = SgceConductaSeveridadSeguro($Valor); return $V !== '' ? $V : 'LEVE'; }
function SgceConductaEstadoSeguro($Valor): string {
    $Valor = SgceNormalizarMayusculas($Valor);
    return in_array($Valor, SgceConductaEstados(), true) ? $Valor : '';
}
function SgceConductaEstadoSeguroDefault($Valor): string { $V = SgceConductaEstadoSeguro($Valor); return $V !== '' ? $V : 'PENDIENTE'; }

function SgceConductaTextoTipo(string $Tipo): string {
    return [
        'OBSERVACION' => 'Observación',
        'REPORTE' => 'Reporte',
        'REPORTE_GRAVE' => 'Reporte grave',
        'RECONOCIMIENTO' => 'Reconocimiento',
        'SEGUIMIENTO' => 'Seguimiento',
    ][$Tipo] ?? 'Reporte';
}
function SgceConductaTextoSeveridad(string $Severidad): string {
    return ['LEVE' => 'Leve', 'MEDIA' => 'Media', 'GRAVE' => 'Grave'][$Severidad] ?? 'Leve';
}
function SgceConductaTextoEstado(string $Estado): string {
    return [
        'PENDIENTE' => 'Pendiente',
        'VALIDADO' => 'Validado',
        'EN_SEGUIMIENTO' => 'En seguimiento',
        'CERRADO' => 'Cerrado',
        'CANCELADO' => 'Cancelado',
    ][$Estado] ?? 'Pendiente';
}
function SgceConductaClaseSeveridad(string $Severidad): string {
    return ['LEVE' => 'success', 'MEDIA' => 'warning text-dark', 'GRAVE' => 'danger'][$Severidad] ?? 'secondary';
}
function SgceConductaClaseEstado(string $Estado): string {
    return ['PENDIENTE' => 'secondary', 'VALIDADO' => 'primary', 'EN_SEGUIMIENTO' => 'warning text-dark', 'CERRADO' => 'success', 'CANCELADO' => 'dark'][$Estado] ?? 'secondary';
}

function SgceConductaFechaSegura($Valor, string $Default): string {
    $Valor = trim((string)$Valor);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $Valor)) { return $Default; }
    $Dt = DateTime::createFromFormat('Y-m-d', $Valor);
    return ($Dt && $Dt->format('Y-m-d') === $Valor) ? $Valor : $Default;
}

function SgceConductaLimpiarTexto($Texto, int $Max = 500): string {
    $Texto = trim(preg_replace('/\s+/u', ' ', (string)$Texto));
    if ($Texto === '') { return ''; }
    if (function_exists('mb_strtoupper')) { $Texto = mb_strtoupper($Texto, 'UTF-8'); }
    else { $Texto = strtoupper($Texto); }
    if (function_exists('mb_substr')) { return mb_substr($Texto, 0, $Max, 'UTF-8'); }
    return substr($Texto, 0, $Max);
}

function SgceConductaPuedeAdministrar($UserSession): bool {
    return SgceTienePermiso($UserSession, 'conducta') && !SgceTieneRol($UserSession, ['maestro']);
}

function SgceConductaResumenHoy(PDO $Pdo, int $CicloId = 0): array {
    return function_exists('SgceConductaRepoResumenHoy') ? SgceConductaRepoResumenHoy($Pdo, $CicloId) : ['Total'=>0,'Pendientes'=>0,'Graves'=>0];
}
function SgceConductaResumenAlumno(PDO $Pdo, int $AlumnoId, int $CicloId): array {
    return function_exists('SgceConductaRepoResumenAlumno') ? SgceConductaRepoResumenAlumno($Pdo, $AlumnoId, $CicloId) : ['Total'=>0,'Leves'=>0,'Medias'=>0,'Graves'=>0,'Pendientes'=>0,'VisiblesPadre'=>0];
}
function SgceConductaHistorialAlumno(PDO $Pdo, int $AlumnoId, int $CicloId, int $Limite = 80, bool $SoloPadre = false): array {
    return function_exists('SgceConductaRepoHistorialAlumno') ? SgceConductaRepoHistorialAlumno($Pdo, $AlumnoId, $CicloId, $Limite, $SoloPadre) : [];
}
function SgceConductaPaseListaPorFecha(PDO $Pdo, int $CicloId, int $AsignacionId, string $Fecha): array {
    return function_exists('SgceConductaRepoPaseListaPorFecha') ? SgceConductaRepoPaseListaPorFecha($Pdo, $CicloId, $AsignacionId, $Fecha) : [];
}
function SgceConductaAlumnosActivos(PDO $Pdo, int $CicloId = 0): array {
    return function_exists('SgceConductaRepoAlumnosActivos') ? SgceConductaRepoAlumnosActivos($Pdo, $CicloId) : [];
}

function SgceConductaObtener(PDO $Pdo, int $Id): ?array {
    return function_exists('SgceConductaRepoObtener') ? SgceConductaRepoObtener($Pdo, $Id) : null;
}

function SgceConductaPaseListaMotivoFaltante(array $Alumnos, array $ConductaPost): bool {
    foreach ($Alumnos as $Alumno) {
        $AlumnoId = (int)($Alumno['Id'] ?? 0);
        if ($AlumnoId <= 0) { continue; }
        $Datos = $ConductaPost[$AlumnoId] ?? [];
        if (!is_array($Datos) || empty($Datos['registrar'])) { continue; }
        if (SgceConductaLimpiarTexto($Datos['motivo'] ?? '', 180) === '') { return true; }
    }
    return false;
}

function SgceConductaPaseListaKey(int $CicloId, int $AsignacionId, int $AlumnoId, string $Fecha): string {
    return $CicloId . ':' . $AsignacionId . ':' . $AlumnoId . ':' . $Fecha;
}

function SgceConductaGuardarDesdePaseLista(PDO $Pdo, int $CicloId, array $InfoClase, string $Fecha, array $Alumnos, array $ConductaPost, array $UserSession): int {
    if ($CicloId <= 0 || empty($InfoClase['Id']) || empty($InfoClase['GrupoId']) || !SgceDbTablaExiste($Pdo, 'ConductaRegistros')) { return 0; }
    $AsignacionId = (int)$InfoClase['Id'];
    $GrupoId = (int)$InfoClase['GrupoId'];
    $UsuarioId = (int)($UserSession['Id'] ?? 0);
    $Momento = $Fecha . ' ' . date('H:i:s');
    $Cambios = 0;

    $StmtBuscar = $Pdo->prepare('SELECT Id, Estado, ReportaUsuarioId FROM ConductaRegistros WHERE PaseListaKey = ? LIMIT 1');
    $StmtInsertar = $Pdo->prepare("\n        INSERT INTO ConductaRegistros\n        (CicloId, AlumnoId, GrupoId, AsignacionId, ReportaUsuarioId, FechaIncidente, Origen, Tipo, Categoria, Severidad, MotivoCorto, Detalle, AccionTomada, Estado, VisiblePadre, PaseListaKey)\n        VALUES (?, ?, ?, ?, ?, ?, 'PASE_LISTA', ?, ?, ?, ?, ?, ?, 'PENDIENTE', ?, ?)\n    ");
    $StmtActualizar = $Pdo->prepare("\n        UPDATE ConductaRegistros\n        SET Tipo = ?, Categoria = ?, Severidad = ?, MotivoCorto = ?, Detalle = ?, AccionTomada = ?, Estado = 'PENDIENTE', VisiblePadre = ?, FechaIncidente = ?, FechaActualizacion = CURRENT_TIMESTAMP\n        WHERE Id = ? AND Estado IN ('PENDIENTE','CANCELADO')\n    ");
    $StmtCancelar = $Pdo->prepare("UPDATE ConductaRegistros SET Estado = 'CANCELADO', VisiblePadre = 0, FechaActualizacion = CURRENT_TIMESTAMP WHERE Id = ? AND Estado = 'PENDIENTE' AND (ReportaUsuarioId = ? OR ? = 1)");
    $EsAdminConducta = SgceConductaPuedeAdministrar($UserSession) ? 1 : 0;

    foreach ($Alumnos as $Alumno) {
        $AlumnoId = (int)($Alumno['Id'] ?? 0);
        if ($AlumnoId <= 0) { continue; }
        $Datos = $ConductaPost[$AlumnoId] ?? [];
        if (!is_array($Datos)) { $Datos = []; }
        $Registrar = !empty($Datos['registrar']);
        $Key = SgceConductaPaseListaKey($CicloId, $AsignacionId, $AlumnoId, $Fecha);
        $StmtBuscar->execute([$Key]);
        $Existente = $StmtBuscar->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$Registrar) {
            if ($Existente) {
                $StmtCancelar->execute([(int)$Existente['Id'], $UsuarioId, $EsAdminConducta]);
                $Cambios += $StmtCancelar->rowCount() > 0 ? 1 : 0;
            }
            continue;
        }

        $Motivo = SgceConductaLimpiarTexto($Datos['motivo'] ?? '', 180);
        if ($Motivo === '') { continue; }
        $Tipo = SgceConductaTipoSeguroDefault($Datos['tipo'] ?? 'REPORTE');
        $Severidad = SgceConductaSeveridadSeguroDefault($Datos['severidad'] ?? 'LEVE');
        $Categoria = SgceConductaLimpiarTexto($Datos['categoria'] ?? '', 80);
        $Detalle = SgceConductaLimpiarTexto($Datos['detalle'] ?? '', 1200);
        $Accion = SgceConductaLimpiarTexto($Datos['accion'] ?? '', 800);
        $VisiblePadre = !empty($Datos['visible_padre']) ? 1 : 0;

        if ($Existente) {
            $StmtActualizar->execute([$Tipo, $Categoria, $Severidad, $Motivo, $Detalle, $Accion, $VisiblePadre, $Momento, (int)$Existente['Id']]);
            $Cambios += $StmtActualizar->rowCount() > 0 ? 1 : 0;
        } else {
            $StmtInsertar->execute([$CicloId, $AlumnoId, $GrupoId, $AsignacionId, $UsuarioId ?: null, $Momento, $Tipo, $Categoria, $Severidad, $Motivo, $Detalle, $Accion, $VisiblePadre, $Key]);
            $Cambios++;
        }
    }
    return $Cambios;
}

function SgceConductaGuardarManual(PDO $Pdo, array $Datos, array $UserSession): int {
    $AlumnoId = (int)($Datos['AlumnoId'] ?? 0);
    $GrupoId = (int)($Datos['GrupoId'] ?? 0);
    $CicloId = (int)($Datos['CicloId'] ?? 0);
    if ($AlumnoId <= 0 || $CicloId <= 0 || !SgceDbTablaExiste($Pdo, 'ConductaRegistros')) { return 0; }
    if ($GrupoId <= 0) {
        $StmtGrupo = $Pdo->prepare("SELECT GrupoId FROM AlumnoInscripciones WHERE AlumnoId = ? AND CicloId = ? AND Estado = 'INSCRITO' LIMIT 1");
        $StmtGrupo->execute([$AlumnoId, $CicloId]);
        $GrupoId = (int)$StmtGrupo->fetchColumn();
    }
    if ($GrupoId <= 0) { return 0; }
    $FechaBase = SgceConductaFechaSegura($Datos['FechaIncidente'] ?? date('Y-m-d'), date('Y-m-d'));
    $Momento = $FechaBase . ' ' . date('H:i:s');
    $Motivo = SgceConductaLimpiarTexto($Datos['MotivoCorto'] ?? '', 180);
    if ($Motivo === '') { return 0; }
    $Tipo = SgceConductaTipoSeguroDefault($Datos['Tipo'] ?? 'REPORTE');
    $Severidad = SgceConductaSeveridadSeguroDefault($Datos['Severidad'] ?? 'LEVE');
    $Estado = SgceConductaEstadoSeguroDefault($Datos['Estado'] ?? 'VALIDADO');
    if ($Estado === 'CANCELADO') { $Estado = 'PENDIENTE'; }
    $VisiblePadre = !empty($Datos['VisiblePadre']) ? 1 : 0;
    if (!in_array($Estado, ['VALIDADO','EN_SEGUIMIENTO','CERRADO'], true)) { $VisiblePadre = 0; }
    $Stmt = $Pdo->prepare("\n        INSERT INTO ConductaRegistros\n        (CicloId, AlumnoId, GrupoId, AsignacionId, ReportaUsuarioId, RevisaUsuarioId, FechaIncidente, Origen, Tipo, Categoria, Severidad, MotivoCorto, Detalle, AccionTomada, Estado, VisiblePadre)\n        VALUES (?, ?, ?, NULL, ?, ?, ?, 'ADMINISTRATIVO', ?, ?, ?, ?, ?, ?, ?, ?)\n    ");
    $UsuarioId = (int)($UserSession['Id'] ?? 0);
    $Stmt->execute([
        $CicloId, $AlumnoId, $GrupoId, $UsuarioId ?: null, $UsuarioId ?: null, $Momento,
        $Tipo, SgceConductaLimpiarTexto($Datos['Categoria'] ?? '', 80), $Severidad, $Motivo,
        SgceConductaLimpiarTexto($Datos['Detalle'] ?? '', 1200), SgceConductaLimpiarTexto($Datos['AccionTomada'] ?? '', 800), $Estado, $VisiblePadre
    ]);
    return (int)$Pdo->lastInsertId();
}

function SgceConductaActualizarRevision(PDO $Pdo, int $Id, array $Datos, array $UserSession): bool {
    if ($Id <= 0 || !SgceDbTablaExiste($Pdo, 'ConductaRegistros')) { return false; }
    $Estado = SgceConductaEstadoSeguroDefault($Datos['Estado'] ?? 'PENDIENTE');
    $VisiblePadre = !empty($Datos['VisiblePadre']) ? 1 : 0;
    if (!in_array($Estado, ['VALIDADO','EN_SEGUIMIENTO','CERRADO'], true)) { $VisiblePadre = 0; }
    $AccionTomada = SgceConductaLimpiarTexto($Datos['AccionTomada'] ?? '', 800);
    $Detalle = SgceConductaLimpiarTexto($Datos['Detalle'] ?? '', 1200);
    $RevisaUsuarioId = (int)($UserSession['Id'] ?? 0);
    $Stmt = $Pdo->prepare("\n        UPDATE ConductaRegistros\n        SET Estado = ?, VisiblePadre = ?, AccionTomada = ?, Detalle = ?, RevisaUsuarioId = ?, FechaActualizacion = CURRENT_TIMESTAMP\n        WHERE Id = ?\n    ");
    $Stmt->execute([$Estado, $VisiblePadre, $AccionTomada, $Detalle, $RevisaUsuarioId ?: null, $Id]);
    return $Stmt->rowCount() > 0;
}

function SgceConductaListar(PDO $Pdo, int $CicloId, array $Filtro, int $Limite, int $Offset): array {
    return function_exists('SgceConductaRepoListar') ? SgceConductaRepoListar($Pdo, $CicloId, $Filtro, $Limite, $Offset) : [];
}
function SgceConductaContar(PDO $Pdo, int $CicloId, array $Filtro): int {
    return function_exists('SgceConductaRepoContar') ? SgceConductaRepoContar($Pdo, $CicloId, $Filtro) : 0;
}
function SgceConductaFiltros(array $Origen): array {
    return function_exists('SgceConductaRepoFiltros') ? SgceConductaRepoFiltros($Origen) : [];
}
