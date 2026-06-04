<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

$TipoPeriodizacionAdmin = (string)($OfertaActiva['TipoPeriodizacion'] ?? 'ANUAL');
$EtiquetaEtapaAdmin = SgceEtiquetaEtapaActual($Pdo);
$EtiquetaEtapaAdminMayus = SgceNormalizarMayusculas($EtiquetaEtapaAdmin);
$EjemploEtapaImportacion = !empty($EtapasAcademicas[0]) ? SgceEtapaNombreVisual($EtapasAcademicas[0], $TipoPeriodizacionAdmin) : SgceEtiquetaEtapaAcademica(1, $TipoPeriodizacionAdmin);

$SgceFiltroOrdenNatural = static function (array &$Opciones): void {
    uasort($Opciones, static function ($A, $B) {
        $OrdenA = is_numeric($A['Orden'] ?? null) ? (int)$A['Orden'] : 9999;
        $OrdenB = is_numeric($B['Orden'] ?? null) ? (int)$B['Orden'] : 9999;
        if ($OrdenA !== $OrdenB) { return $OrdenA <=> $OrdenB; }
        return strnatcasecmp((string)($A['Label'] ?? ''), (string)($B['Label'] ?? ''));
    });
};

$SgceFiltroEtapas = static function (array $Registros) use ($TipoPeriodizacionAdmin, $SgceFiltroOrdenNatural): array {
    $Opciones = [];
    foreach ($Registros as $R) {
        $Valor = trim((string)($R['Grado'] ?? $R['EtapaOrden'] ?? ''));
        if ($Valor === '') { continue; }
        $Etiqueta = SgceEtapaNombreVisual($R, $TipoPeriodizacionAdmin);
        $Orden = is_numeric($R['EtapaOrden'] ?? null) ? (int)$R['EtapaOrden'] : (is_numeric($Valor) ? (int)$Valor : 9999);
        $Opciones[$Valor] = ['Value' => $Valor, 'Label' => $Etiqueta, 'Orden' => $Orden];
    }
    $SgceFiltroOrdenNatural($Opciones);
    return array_values($Opciones);
};

$SgceFiltroValores = static function (array $Registros, string $Campo, bool $OrdenNatural = true): array {
    $Opciones = [];
    foreach ($Registros as $R) {
        $Valor = trim((string)($R[$Campo] ?? ''));
        if ($Valor === '') { continue; }
        $ValorMayus = SgceNormalizarMayusculas($Valor);
        $Opciones[$ValorMayus] = ['Value' => $ValorMayus, 'Label' => $ValorMayus, 'Orden' => is_numeric($ValorMayus) ? (int)$ValorMayus : 9999];
    }
    if ($OrdenNatural) {
        uasort($Opciones, static function ($A, $B) {
            return strnatcasecmp((string)$A['Label'], (string)$B['Label']);
        });
    }
    return array_values($Opciones);
};

$SgceFiltroMateriasBase = static function (array $Registros): array {
    $Opciones = [];
    foreach ($Registros as $R) {
        $Nombre = SgceNormalizarMayusculas((string)($R['MateriaNombre'] ?? ''));
        $Base = trim((string)preg_replace('/\s+\d+$/u', '', $Nombre));
        if ($Base === '') { $Base = $Nombre; }
        if ($Base === '') { continue; }
        $Opciones[$Base] = ['Value' => $Base, 'Label' => $Base, 'Orden' => 9999];
    }
    uasort($Opciones, static function ($A, $B) {
        return strnatcasecmp((string)$A['Label'], (string)$B['Label']);
    });
    return array_values($Opciones);
};

$FiltroGruposEtapas = $SgceFiltroEtapas($GruposTabla ?: $Grupos);
$FiltroGruposLetras = $SgceFiltroValores($GruposTabla ?: $Grupos, 'Grupo');
$FiltroGruposTurnos = $SgceFiltroValores($GruposTabla ?: $Grupos, 'Turno');
$FiltroAlumnosEtapas = $SgceFiltroEtapas($Grupos ?: $Alumnos);
$FiltroAlumnosLetras = $SgceFiltroValores($Grupos ?: $Alumnos, 'Grupo');
$FiltroAlumnosTurnos = $SgceFiltroValores($Grupos ?: $Alumnos, 'Turno');
$FiltroMateriasBase = function_exists('SgceMateriaGrupoBasesFiltro') ? SgceMateriaGrupoBasesFiltro($Pdo, $CicloActivoId) : $SgceFiltroMateriasBase($MateriasGrupo);
$FiltroMateriasEtapas = $SgceFiltroEtapas($Grupos ?: $MateriasGrupo);
$FiltroMateriasLetras = $SgceFiltroValores($Grupos ?: $MateriasGrupo, 'Grupo');
$FiltroMateriasTurnos = $SgceFiltroValores($Grupos ?: $MateriasGrupo, 'Turno');
$SgceSelected = static function($Actual, $Esperado): string { return (string)$Actual === (string)$Esperado ? ' selected' : ''; };
