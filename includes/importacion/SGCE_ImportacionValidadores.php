<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

function EsFilaVacia($Data) {
    foreach ($Data as $Valor) {
        if (trim((string)$Valor) !== '') { return false; }
    }
    return true;
}

function SgceCeldasConDatoImportacion($Data) {
    return array_values(array_filter(array_map(static fn($Valor) => trim((string)$Valor), $Data), static fn($Valor) => $Valor !== ''));
}

function EsFilaTituloImportacion($Data) {
    $Valores = SgceCeldasConDatoImportacion($Data);
    if (!$Valores) { return true; }

    $Claves = array_map('SgceNormalizarClaveHojaImportacion', $Valores);
    $PrimerValor = $Claves[0] ?? '';

    if (count($Claves) === 1 && in_array($PrimerValor, [
        'DOCENTES', 'MAESTROS', 'GRUPOS', 'GRADO Y GRUPO', 'MATERIAS', 'ASIGNATURAS', 'ALUMNOS', 'PADRON DE ALUMNOS', 'RESUMEN', 'HORAS POR GRUPO'
    ], true)) {
        return true;
    }

    if (count($Claves) <= 2 && in_array($PrimerValor, ['FORMATO CSV O EXCEL', 'EJEMPLO', 'ARCHIVO CSV O EXCEL'], true)) {
        return true;
    }

    $ColumnasGenericas = 0;
    foreach ($Claves as $Clave) {
        if (preg_match('/^COLUMN[0-9]+$/', $Clave)) { $ColumnasGenericas++; }
    }
    if ($ColumnasGenericas > 0 && $ColumnasGenericas === count($Claves)) {
        return true;
    }

    return false;
}

function EsEncabezadoAlumno($Data) {
    if (EsFilaTituloImportacion($Data)) { return true; }
    $Primero = SgceNormalizarMayusculas($Data[0] ?? '');
    $Segundo = SgceNormalizarMayusculas($Data[1] ?? '');
    $Tercero = SgceNormalizarMayusculas($Data[2] ?? '');
    $Cuarto = SgceNormalizarMayusculas($Data[3] ?? '');
    return in_array($Primero, ['NOMBRE', 'NOMBRE COMPLETO', 'NOMBRE DEL ALUMNO', 'ALUMNO', 'ALUMNOS'], true)
        || in_array($Segundo, ['GRADO', 'AÑO', 'ETAPA', 'SEMESTRE'], true)
        || in_array($Tercero, ['GRUPO', 'GRUPOS'], true)
        || in_array($Cuarto, ['TURNO', 'TURNOS'], true);
}

function EsEncabezadoDocente($Data) {
    if (EsFilaTituloImportacion($Data)) { return true; }
    $Primero = SgceNormalizarMayusculas($Data[0] ?? '');
    $Segundo = SgceNormalizarMayusculas($Data[1] ?? '');
    return in_array($Primero, ['NOMBRE', 'NOMBRE COMPLETO', 'DOCENTE', 'MAESTRO'], true)
        || in_array($Segundo, ['USUARIO', 'USERNAME'], true);
}

function EsEncabezadoGrupo($Data) {
    if (EsFilaTituloImportacion($Data)) { return true; }
    $PrimerCampo = SgceNormalizarMayusculas($Data[0] ?? '');
    $SegundoCampo = SgceNormalizarMayusculas($Data[1] ?? '');
    $TercerCampo = SgceNormalizarMayusculas($Data[2] ?? '');
    return in_array($PrimerCampo, ['GRADO', 'GRADOS', 'AÑO', 'AÑOS', 'ETAPA'], true)
        || in_array($SegundoCampo, ['GRUPO', 'GRUPOS'], true)
        || in_array($TercerCampo, ['TURNO', 'TURNOS'], true);
}

function EsEncabezadoMateriaGrupo($Data) {
    if (EsFilaTituloImportacion($Data)) { return true; }
    $Primero = SgceNormalizarMayusculas($Data[0] ?? '');
    $Segundo = SgceNormalizarMayusculas($Data[1] ?? '');
    $Tercero = SgceNormalizarMayusculas($Data[2] ?? '');
    $Cuarto = SgceNormalizarMayusculas($Data[3] ?? '');
    $Quinto = SgceNormalizarMayusculas($Data[4] ?? '');
    return in_array($Primero, ['MATERIA', 'ASIGNATURA', 'NOMBRE MATERIA'], true)
        || in_array($Segundo, ['GRADO', 'GRADOS', 'ETAPA', 'ETAPAS', 'AÑO', 'AÑOS', 'SEMESTRE', 'SEMESTRES', 'CUATRIMESTRE', 'CUATRIMESTRES', 'MODULO', 'MÓDULO'], true)
        || in_array($Tercero, ['GRUPO', 'GRUPOS'], true)
        || in_array($Cuarto, ['TURNO', 'TURNOS'], true)
        || in_array($Quinto, ['HORAS', 'HORAS SEMANA', 'HORAS SEMANALES'], true);
}

function SgceMensajeFormatoMateriasGrupo(): string {
    return 'El archivo de materias no tiene el formato correcto. Para registrar materias por grupo se necesitan 5 columnas: MATERIA, AÑO, GRUPO, TURNO, HORAS. Ejemplo: ESPAÑOL, 1,C, MATUTINO, 5. Tu archivo parece traer solo MATERIA, AÑO, HORAS; falta capturar GRUPO y TURNO.';
}

function SgceResolverEtapaImportacionMateria(string $EtapaTxt, array $MapaEtapasPorOrden, array $MapaEtapasPorNombre): int {
    $EtapaTxt = SgceNormalizarEtapaAcademica($EtapaTxt);
    if ($EtapaTxt === '') { return 0; }
    if (ctype_digit($EtapaTxt) && isset($MapaEtapasPorOrden[(int)$EtapaTxt])) {
        return (int)$MapaEtapasPorOrden[(int)$EtapaTxt]['Id'];
    }
    $Clave = SgceNormalizarMayusculas($EtapaTxt);
    return isset($MapaEtapasPorNombre[$Clave]) ? (int)$MapaEtapasPorNombre[$Clave]['Id'] : 0;
}

function SgceFilaAlumnoTraeGrupo($Data) {
    return trim((string)($Data[1] ?? '')) !== ''
        || trim((string)($Data[2] ?? '')) !== ''
        || trim((string)($Data[3] ?? '')) !== '';
}

