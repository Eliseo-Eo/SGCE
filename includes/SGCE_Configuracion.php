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
        'TipoPlaneacion' => 'PERIODO',
        'PlaneacionesCantidad' => '3',
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
    return in_array($Tipo, ['CICLO','PERIODO','UNIDAD','SEMANA'], true) ? $Tipo : 'PERIODO';
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
        'TipoPlaneacion' => 'PERIODO',
        'PlaneacionesCantidad' => 3,
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

