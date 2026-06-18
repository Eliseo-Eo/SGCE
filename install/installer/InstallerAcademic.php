<?php
if (!defined('SGCE_INSTALLER')) { http_response_code(403); exit('Acceso directo no permitido.'); }

function InstalarNivelValido($Nivel) {
    $Nivel = InstalarMayusculas(trim((string)$Nivel));
    return in_array($Nivel, ['PRIMARIA','SECUNDARIA','BACHILLERATO','UNIVERSIDAD','MAESTRIA','DOCTORADO','CURSO'], true) ? $Nivel : 'SECUNDARIA';
}

function InstalarTipoPeriodizacionValido($Tipo) {
    $Tipo = InstalarMayusculas(trim((string)$Tipo));
    return in_array($Tipo, ['ANUAL','SEMESTRAL','CUATRIMESTRAL','TRIMESTRAL','MODULAR'], true) ? $Tipo : 'ANUAL';
}

function InstalarRequiereProgramasEducativos($Nivel) {
    return in_array(InstalarNivelValido($Nivel), ['UNIVERSIDAD','MAESTRIA','DOCTORADO'], true);
}

function InstalarNombreOfertaPorNivel($Nivel) {
    $Nivel = InstalarNivelValido($Nivel);
    $Nombres = [
        'PRIMARIA' => 'PRIMARIA',
        'SECUNDARIA' => 'SECUNDARIA',
        'BACHILLERATO' => 'BACHILLERATO',
        'UNIVERSIDAD' => 'LICENCIATURA',
        'MAESTRIA' => 'MAESTRÍA',
        'DOCTORADO' => 'DOCTORADO',
        'CURSO' => 'CURSO / DIPLOMADO',
    ];
    return $Nombres[$Nivel] ?? 'SECUNDARIA';
}

function InstalarNombreOfertaFinal($NombreCapturado, $Nivel) {
    $Nombre = InstalarNormalizarTexto($NombreCapturado, true);
    if ($Nombre === '') {
        return InstalarNombreOfertaPorNivel($Nivel);
    }
    if (InstalarLongitud($Nombre) > 140 || !preg_match('/^[0-9A-ZÁÉÍÓÚÜÑ .º°_\-\/]+$/u', $Nombre)) {
        throw new Exception('El nombre específico de la oferta educativa solo debe usar letras, números, espacios, guion o diagonal, máximo 140 caracteres.');
    }
    return $Nombre;
}

function InstalarEtiquetaEtapa($Orden, $Tipo) {
    $Orden = max(1, (int)$Orden);
    $Tipo = InstalarTipoPeriodizacionValido($Tipo);
    if ($Tipo === 'SEMESTRAL') { return $Orden . ' SEMESTRE'; }
    if ($Tipo === 'CUATRIMESTRAL') { return $Orden . ' CUATRIMESTRE'; }
    if ($Tipo === 'TRIMESTRAL') { return $Orden . ' TRIMESTRE'; }
    if ($Tipo === 'MODULAR') { return 'MÓDULO ' . $Orden; }
    return (string)$Orden;
}

function InstalarEtiquetaEtapaPorTipo($Tipo) {
    $Tipo = InstalarTipoPeriodizacionValido($Tipo);
    if ($Tipo === 'SEMESTRAL') { return 'SEMESTRE'; }
    if ($Tipo === 'CUATRIMESTRAL') { return 'CUATRIMESTRE'; }
    if ($Tipo === 'TRIMESTRAL') { return 'TRIMESTRE'; }
    if ($Tipo === 'MODULAR') { return 'MÓDULO'; }
    return 'GRADO';
}

function InstalarModoPeriodosValido($Modo) {
    $Modo = InstalarMayusculas(trim((string)$Modo));
    return in_array($Modo, ['AUTOMATICO','PERSONALIZADO'], true) ? $Modo : 'AUTOMATICO';
}

function InstalarTipoPlaneacionValido($Tipo) {
    $Tipo = InstalarMayusculas(trim((string)$Tipo));
    return in_array($Tipo, ['CICLO','PERIODO','UNIDAD','SEMANA'], true) ? $Tipo : 'PERIODO';
}

function InstalarNombreBasePeriodoValido($Nombre) {
    $Nombre = InstalarNormalizarTexto($Nombre, true);
    if ($Nombre === '' || InstalarLongitud($Nombre) > 60 || !preg_match('/^[0-9A-ZÁÉÍÓÚÜÑ .º°_\-\/]+$/u', $Nombre)) { return 'PARCIAL'; }
    return $Nombre;
}

function InstalarGenerarNombresPeriodos($Cantidad, $NombreBase, $Modo, $Personalizados) {
    $Cantidad = max(1, min(12, (int)$Cantidad));
    $NombreBase = InstalarNombreBasePeriodoValido($NombreBase);
    $Modo = InstalarModoPeriodosValido($Modo);
    $Periodos = [];
    if ($Modo === 'PERSONALIZADO') {
        foreach (preg_split('/[,;\n]+/u', (string)$Personalizados) as $P) {
            $P = InstalarNormalizarTexto($P, true);
            if ($P !== '' && InstalarLongitud($P) <= 80 && !in_array($P, $Periodos, true)) { $Periodos[] = $P; }
            if (count($Periodos) >= $Cantidad) { break; }
        }
    }
    for ($I = count($Periodos) + 1; $I <= $Cantidad; $I++) { $Periodos[] = $NombreBase . ' ' . $I; }
    return $Periodos;
}
