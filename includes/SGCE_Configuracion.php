<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceConfiguracionDefault() {
    return [
        'NombreEscuela' => 'ESCUELA SIN CONFIGURAR',
        'ClaveCentroTrabajo' => '',
        'DirectorNombre' => '',
        'MunicipioEstado' => '',
        'TelefonoEscuela' => '',
        'CorreoEscuela' => '',
        'LemaInstitucional' => '',
        'ColorInstitucional' => '#97051E',
        'SistemaNombre' => 'SGCE',
        'NombreOfertaAcademica' => '',
        'NivelEducativo' => 'SECUNDARIA',
        'TipoPeriodizacion' => 'ANUAL',
        'TotalEtapas' => '3',
        'UsaProgramas' => '0',
        'PeriodosCantidad' => '3',
        'PeriodosNombreBase' => 'PARCIAL',
        'PeriodosModo' => 'AUTOMATICO',
        'PeriodosPersonalizados' => '',
        'UsaPlaneaciones' => '1',
        'TipoPlaneacion' => 'CICLO',
        'PlaneacionesCantidad' => '1',
        'TurnosDisponibles' => 'MATUTINO\nVESPERTINO',
        'CalificacionMinima' => '5',
        'CalificacionMaxima' => '10',
        'CalificacionAprobatoria' => '6',
        'CalificacionDecimales' => '1',
        'MatriculaAutomatica' => '1',
        'MatriculaPrefijo' => 'SGCE',
        'MatriculaIncluirCiclo' => '1',
    ];
}

function SgceObtenerConfiguracion($Pdo, $ForzarRecarga = false) {
    if (!$ForzarRecarga && isset($GLOBALS['SGCE_CONFIG_CACHE']) && is_array($GLOBALS['SGCE_CONFIG_CACHE'])) {
        return $GLOBALS['SGCE_CONFIG_CACHE'];
    }

    $Config = SgceConfiguracionDefault();
    try {
        $Stmt = $Pdo->query('SELECT Clave, Valor FROM ConfiguracionSistema');
        foreach ($Stmt->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $Clave = (string)($Row['Clave'] ?? '');
            if ($Clave !== '' && array_key_exists($Clave, $Config)) {
                $Config[$Clave] = (string)($Row['Valor'] ?? '');
            }
        }
    } catch (Exception $E) {}

    $GLOBALS['SGCE_CONFIG_CACHE'] = $Config;
    return $Config;
}

function SgceGuardarConfiguracion($Pdo, $Datos) {
    unset($GLOBALS['SGCE_CONFIG_CACHE']);
    $Permitidas = array_keys(SgceConfiguracionDefault());
    $Stmt = $Pdo->prepare('INSERT INTO ConfiguracionSistema (Clave, Valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE Valor = VALUES(Valor), FechaActualizacion = CURRENT_TIMESTAMP');
    foreach ($Permitidas as $Clave) {
        if (array_key_exists($Clave, $Datos)) {
            $Stmt->execute([$Clave, (string)$Datos[$Clave]]);
        }
    }
}



function SgceTurnosDisponibles(PDO $Pdo = null): array {
    $Texto = '';
    if ($Pdo instanceof PDO) {
        $Config = SgceObtenerConfiguracion($Pdo);
        $Texto = (string)($Config['TurnosDisponibles'] ?? '');
    }
    if ($Texto === '') { $Texto = "MATUTINO
VESPERTINO"; }
    $Turnos = [];
    foreach (preg_split('/[,;
]+/u', $Texto) as $Turno) {
        $Turno = SgceNormalizarMayusculas($Turno);
        if ($Turno !== '' && preg_match('/^[0-9A-ZÁÉÍÓÚÜÑ ._\-\/]{1,40}$/u', $Turno) && !in_array($Turno, $Turnos, true)) {
            $Turnos[] = $Turno;
        }
    }
    return $Turnos ?: ['MATUTINO','VESPERTINO'];
}

function SgceTurnosTextoSeguro(string $Texto): string {
    $Turnos = [];
    foreach (preg_split('/[,;
]+/u', $Texto) as $Turno) {
        $Turno = SgceNormalizarMayusculas($Turno);
        if ($Turno !== '' && preg_match('/^[0-9A-ZÁÉÍÓÚÜÑ ._\-\/]{1,40}$/u', $Turno) && !in_array($Turno, $Turnos, true)) {
            $Turnos[] = $Turno;
        }
    }
    return implode(PHP_EOL, $Turnos ?: ['MATUTINO','VESPERTINO']);
}

function SgceCalificacionConfig(PDO $Pdo): array {
    $Config = SgceObtenerConfiguracion($Pdo);
    $Min = is_numeric($Config['CalificacionMinima'] ?? null) ? (float)$Config['CalificacionMinima'] : 5.0;
    $Max = is_numeric($Config['CalificacionMaxima'] ?? null) ? (float)$Config['CalificacionMaxima'] : 10.0;
    if ($Min < 0) { $Min = 0.0; }
    if ($Max > 100) { $Max = 100.0; }
    if ($Min >= $Max) { $Min = 5.0; $Max = 10.0; }
    $Aprobatoria = is_numeric($Config['CalificacionAprobatoria'] ?? null) ? (float)$Config['CalificacionAprobatoria'] : 6.0;
    $Aprobatoria = max($Min, min($Max, $Aprobatoria));
    $Decimales = !empty($Config['CalificacionDecimales']) ? 1 : 0;
    return ['Minima'=>$Min,'Maxima'=>$Max,'Aprobatoria'=>$Aprobatoria,'Decimales'=>$Decimales];
}

function SgceCalificacionNormalizar(PDO $Pdo, $Valor): ?float {
    $Texto = trim((string)$Valor);
    if ($Texto === '') { return null; }
    if (!is_numeric($Texto)) { return null; }
    $Cfg = SgceCalificacionConfig($Pdo);
    $Cal = (float)$Texto;
    if ($Cal < $Cfg['Minima'] || $Cal > $Cfg['Maxima']) { return null; }
    return $Cfg['Decimales'] ? round($Cal, 2) : round($Cal, 0);
}

function SgceCalificacionTextoRango(PDO $Pdo): string {
    $Cfg = SgceCalificacionConfig($Pdo);
    return rtrim(rtrim(number_format($Cfg['Minima'], 2, '.', ''), '0'), '.') . ' a ' . rtrim(rtrim(number_format($Cfg['Maxima'], 2, '.', ''), '0'), '.');
}

function SgceMatriculaAutomaticaActiva(PDO $Pdo): bool {
    $Config = SgceObtenerConfiguracion($Pdo);
    return (string)($Config['MatriculaAutomatica'] ?? '1') === '1';
}

function SgceMatriculaPrefijo(PDO $Pdo): string {
    $Config = SgceObtenerConfiguracion($Pdo);
    $Prefijo = SgceNormalizarMayusculas((string)($Config['MatriculaPrefijo'] ?? 'SGCE'));
    return preg_match('/^[A-Z0-9]{2,12}$/', $Prefijo) ? $Prefijo : 'SGCE';
}

function SgceGenerarMatricula(PDO $Pdo, int $CicloId = 0): string {
    $Anio = date('Y');
    if ($CicloId > 0) {
        try {
            $Stmt = $Pdo->prepare('SELECT FechaInicio FROM CiclosEscolares WHERE Id = ? LIMIT 1');
            $Stmt->execute([$CicloId]);
            $Fecha = (string)$Stmt->fetchColumn();
            if (preg_match('/^(\d{4})-/', $Fecha, $M)) { $Anio = $M[1]; }
        } catch (Throwable $E) {}
    }
    $Prefijo = SgceMatriculaPrefijo($Pdo);
    $Base = $Prefijo . '-' . $Anio . '-';
    $Stmt = $Pdo->prepare('SELECT Matricula FROM Alumnos WHERE Matricula LIKE ? ORDER BY Matricula DESC LIMIT 1');
    $Stmt->execute([$Base . '%']);
    $Ultima = (string)$Stmt->fetchColumn();
    $Consecutivo = 1;
    if (preg_match('/-(\d{6})$/', $Ultima, $M)) { $Consecutivo = ((int)$M[1]) + 1; }
    return $Base . str_pad((string)$Consecutivo, 6, '0', STR_PAD_LEFT);
}

function SgceAsignarMatriculaSiAplica(PDO $Pdo, int $AlumnoId, int $CicloId = 0): ?string {
    if ($AlumnoId <= 0 || !SgceMatriculaAutomaticaActiva($Pdo)) { return null; }
    $Stmt = $Pdo->prepare('SELECT Matricula FROM Alumnos WHERE Id = ? LIMIT 1');
    $Stmt->execute([$AlumnoId]);
    $Actual = trim((string)$Stmt->fetchColumn());
    if ($Actual !== '') { return $Actual; }
    for ($Intento=0; $Intento<5; $Intento++) {
        $Matricula = SgceGenerarMatricula($Pdo, $CicloId);
        try {
            $Anio = (int)date('Y');
            if (preg_match('/-(\d{4})-/', $Matricula, $M)) { $Anio = (int)$M[1]; }
            $Upd = $Pdo->prepare("UPDATE Alumnos SET Matricula = ?, AnioIngreso = COALESCE(AnioIngreso, ?) WHERE Id = ? AND (Matricula IS NULL OR Matricula = '')");
            $Upd->execute([$Matricula, $Anio, $AlumnoId]);
            if ($Upd->rowCount() > 0) { return $Matricula; }
        } catch (PDOException $E) {
            if ($E->getCode() !== '23000') { throw $E; }
        }
    }
    return null;
}

function SgceNivelEducativoOpciones(): array {
    return [
        'PRIMARIA' => 'Primaria',
        'SECUNDARIA' => 'Secundaria',
        'BACHILLERATO' => 'Bachillerato / Preparatoria',
        'UNIVERSIDAD' => 'Universidad / Licenciatura',
        'MAESTRIA' => 'Maestría',
        'DOCTORADO' => 'Doctorado',
        'CURSO' => 'Curso, diplomado o capacitación',
    ];
}

function SgceTipoPeriodizacionOpciones(): array {
    return [
        'ANUAL' => 'Años / grados',
        'SEMESTRAL' => 'Semestres',
        'CUATRIMESTRAL' => 'Cuatrimestres',
        'TRIMESTRAL' => 'Trimestres',
        'MODULAR' => 'Módulos / niveles',
    ];
}

function SgceNivelEducativoValido(string $Nivel): string {
    $Nivel = SgceNormalizarMayusculas($Nivel);
    return array_key_exists($Nivel, SgceNivelEducativoOpciones()) ? $Nivel : 'SECUNDARIA';
}

function SgceTipoPeriodizacionValido(string $Tipo): string {
    $Tipo = SgceNormalizarMayusculas($Tipo);
    return array_key_exists($Tipo, SgceTipoPeriodizacionOpciones()) ? $Tipo : 'ANUAL';
}

function SgceRequiereProgramasEducativosPorDefecto(string $Nivel, string $Tipo): bool {
    $Nivel = SgceNivelEducativoValido($Nivel);
    return in_array($Nivel, ['UNIVERSIDAD','MAESTRIA','DOCTORADO'], true);
}

function SgceModoPeriodosValido(string $Modo): string {
    $Modo = SgceNormalizarMayusculas($Modo);
    return in_array($Modo, ['AUTOMATICO','PERSONALIZADO'], true) ? $Modo : 'AUTOMATICO';
}

function SgceTipoPlaneacionValido(string $Tipo): string {
    $Tipo = SgceNormalizarMayusculas($Tipo);
    return in_array($Tipo, ['CICLO','PERIODO','UNIDAD','SEMANA'], true) ? $Tipo : 'CICLO';
}

function SgceNombreBasePeriodoValido(string $Nombre): string {
    $Nombre = SgceNormalizarMayusculas($Nombre);
    if ($Nombre === '' || !preg_match('/^[0-9A-ZÁÉÍÓÚÜÑ .º°_\-\/]{1,60}$/u', $Nombre)) { return 'PARCIAL'; }
    return $Nombre;
}

function SgceGenerarNombresPeriodos(int $Cantidad, string $NombreBase = 'PARCIAL', string $Modo = 'AUTOMATICO', string $Personalizados = ''): array {
    $Cantidad = max(1, min(12, $Cantidad));
    $Modo = SgceModoPeriodosValido($Modo);
    $NombreBase = SgceNombreBasePeriodoValido($NombreBase);
    $Periodos = [];
    if ($Modo === 'PERSONALIZADO') {
        foreach (preg_split('/[,;\n]+/u', (string)$Personalizados) as $P) {
            $P = SgceNormalizarMayusculas($P);
            if ($P !== '' && mb_strlen($P, 'UTF-8') <= 80 && !in_array($P, $Periodos, true)) { $Periodos[] = $P; }
            if (count($Periodos) >= $Cantidad) { break; }
        }
    }
    for ($I = count($Periodos) + 1; $I <= $Cantidad; $I++) { $Periodos[] = $NombreBase . ' ' . $I; }
    return $Periodos;
}

function SgceConfiguracionAcademicaPorOferta(PDO $Pdo, int $OfertaId = 0): array {
    if ($OfertaId <= 0) { $Oferta = SgceOfertaActiva($Pdo); $OfertaId = (int)($Oferta['Id'] ?? 0); }
    $Default = [
        'OfertaId' => $OfertaId,
        'CantidadPeriodosEvaluacion' => 3,
        'NombreBasePeriodo' => 'PARCIAL',
        'ModoPeriodos' => 'AUTOMATICO',
        'PeriodosPersonalizados' => '',
        'UsaPlaneaciones' => 1,
        'TipoPlaneacion' => 'CICLO',
        'PlaneacionesCantidad' => 1,
    ];
    if ($OfertaId <= 0 || !SgceDbTablaExiste($Pdo, 'ConfiguracionesAcademicas')) { return $Default; }
    $Stmt = $Pdo->prepare('SELECT Id, OfertaId, CantidadPeriodosEvaluacion, NombreBasePeriodo, ModoPeriodos, PeriodosPersonalizados, UsaPlaneaciones, TipoPlaneacion, PlaneacionesCantidad, Activo, FechaCreacion, FechaActualizacion FROM ConfiguracionesAcademicas WHERE OfertaId = ? LIMIT 1');
    $Stmt->execute([$OfertaId]);
    $Row = $Stmt->fetch();
    return $Row ? array_merge($Default, $Row) : $Default;
}

function SgceGuardarConfiguracionAcademica(PDO $Pdo, int $OfertaId, int $CantidadPeriodos, string $NombreBasePeriodo, string $ModoPeriodos, string $PeriodosPersonalizados, bool $UsaPlaneaciones, string $TipoPlaneacion, int $PlaneacionesCantidad): void {
    $CantidadPeriodos = max(1, min(12, $CantidadPeriodos));
    $NombreBasePeriodo = SgceNombreBasePeriodoValido($NombreBasePeriodo);
    $ModoPeriodos = SgceModoPeriodosValido($ModoPeriodos);
    $TipoPlaneacion = SgceTipoPlaneacionValido($TipoPlaneacion);
    $PlaneacionesCantidad = $UsaPlaneaciones ? max(1, min(12, $PlaneacionesCantidad)) : 0;
    if ($UsaPlaneaciones && $TipoPlaneacion === 'CICLO') { $PlaneacionesCantidad = 1; }
    $Stmt = $Pdo->prepare("INSERT INTO ConfiguracionesAcademicas (OfertaId, CantidadPeriodosEvaluacion, NombreBasePeriodo, ModoPeriodos, PeriodosPersonalizados, UsaPlaneaciones, TipoPlaneacion, PlaneacionesCantidad, Activo)
        VALUES (?, ?, ?, ?, NULLIF(?, ''), ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE CantidadPeriodosEvaluacion = VALUES(CantidadPeriodosEvaluacion), NombreBasePeriodo = VALUES(NombreBasePeriodo), ModoPeriodos = VALUES(ModoPeriodos), PeriodosPersonalizados = VALUES(PeriodosPersonalizados), UsaPlaneaciones = VALUES(UsaPlaneaciones), TipoPlaneacion = VALUES(TipoPlaneacion), PlaneacionesCantidad = VALUES(PlaneacionesCantidad), Activo = 1");
    $Stmt->execute([$OfertaId, $CantidadPeriodos, $NombreBasePeriodo, $ModoPeriodos, $PeriodosPersonalizados, $UsaPlaneaciones ? 1 : 0, $TipoPlaneacion, $PlaneacionesCantidad]);
}

function SgceSincronizarPeriodosCicloOferta(PDO $Pdo, int $CicloId, int $OfertaId, array $NombresPeriodos): void {
    if ($CicloId <= 0 || $OfertaId <= 0) { return; }
    $NombresPeriodos = array_values(array_filter(array_map(static fn($P) => SgceNormalizarMayusculas((string)$P), $NombresPeriodos), static fn($P) => $P !== ''));
    if (!$NombresPeriodos) { $NombresPeriodos = ['PARCIAL 1','PARCIAL 2','PARCIAL 3']; }
    $StmtTemp = $Pdo->prepare('UPDATE PeriodosEvaluacion SET Nombre = ? WHERE CicloId = ? AND OfertaId = ? AND Orden = ?');
    foreach ($NombresPeriodos as $Idx => $_) { $StmtTemp->execute(['__TEMP_PERIODO_' . ($Idx + 1) . '_' . time(), $CicloId, $OfertaId, $Idx + 1]); }
    $StmtBuscar = $Pdo->prepare('SELECT Id FROM PeriodosEvaluacion WHERE CicloId = ? AND OfertaId = ? AND Orden = ? LIMIT 1');
    $StmtInsertar = $Pdo->prepare('INSERT INTO PeriodosEvaluacion (CicloId, OfertaId, Nombre, Orden, Activo) VALUES (?, ?, ?, ?, 1)');
    $StmtActualizar = $Pdo->prepare('UPDATE PeriodosEvaluacion SET Nombre = ?, Activo = 1 WHERE Id = ?');
    foreach ($NombresPeriodos as $Idx => $Nombre) {
        $Orden = $Idx + 1;
        $StmtBuscar->execute([$CicloId, $OfertaId, $Orden]);
        $Id = (int)$StmtBuscar->fetchColumn();
        if ($Id > 0) { $StmtActualizar->execute([$Nombre, $Id]); }
        else { $StmtInsertar->execute([$CicloId, $OfertaId, $Nombre, $Orden]); }
    }
    $Pdo->prepare('UPDATE PeriodosEvaluacion SET Activo = 0 WHERE CicloId = ? AND OfertaId = ? AND Orden > ?')->execute([$CicloId, $OfertaId, count($NombresPeriodos)]);
}

