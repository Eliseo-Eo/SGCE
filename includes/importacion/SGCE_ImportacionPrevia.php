<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceImportacionPrimeraFilaDatos(array $Filas, callable $EsEncabezado): array {
    foreach ($Filas as $NumeroFila => $Data) {
        $Data = array_map(static fn($Valor) => trim((string)$Valor), (array)$Data);
        if (EsFilaVacia($Data)) { continue; }
        if ($EsEncabezado($Data)) { continue; }
        return [(int)$NumeroFila, $Data];
    }
    return [0, []];
}

function SgceImportacionColumnasConDato(array $Data): int {
    return count(array_values(array_filter($Data, static fn($Valor) => trim((string)$Valor) !== '')));
}

function SgceValidarImportacionDocentesPrevia(array $Filas): string {
    [$Fila, $Data] = SgceImportacionPrimeraFilaDatos($Filas, 'EsEncabezadoDocente');
    if (!$Data) { return 'El archivo de docentes no contiene registros para importar. Usa el formato requerido: NOMBRE COMPLETO, USUARIO, CONTRASEÑA.'; }
    if (SgceImportacionColumnasConDato($Data) < 3) { return 'El archivo de docentes debe tener 3 columnas: NOMBRE COMPLETO, USUARIO, CONTRASEÑA. Revisa la fila ' . $Fila . '.'; }
    return '';
}

function SgceValidarImportacionGruposPrevia(array $Filas): string {
    [$Fila, $Data] = SgceImportacionPrimeraFilaDatos($Filas, 'EsEncabezadoGrupo');
    if (!$Data) { return 'El archivo de grupos no contiene registros para importar. Usa el formato requerido: AÑO, GRUPO, TURNO.'; }
    if (SgceImportacionColumnasConDato($Data) < 3) { return 'El archivo de grupos debe tener 3 columnas: AÑO, GRUPO, TURNO. Revisa la fila ' . $Fila . '.'; }
    return '';
}

function SgceValidarImportacionMateriasPrevia(array $Filas, bool $UsaProgramas = false): string {
    [$Fila, $Data] = SgceImportacionPrimeraFilaDatos($Filas, 'EsEncabezadoMateriaGrupo');
    if (!$Data) { return 'El archivo de materias no contiene registros para importar. Usa el formato requerido: MATERIA, AÑO, GRUPO, TURNO, HORAS.'; }
    $Minimo = $UsaProgramas ? 6 : 5;
    $Formato = $UsaProgramas ? 'MATERIA, AÑO, GRUPO, TURNO, HORAS, PROGRAMA' : 'MATERIA, AÑO, GRUPO, TURNO, HORAS';
    if (SgceImportacionColumnasConDato($Data) < $Minimo) { return 'El archivo de materias debe tener el formato: ' . $Formato . '. Revisa la fila ' . $Fila . '.'; }
    return '';
}

function SgceValidarImportacionAlumnosPrevia(array $Filas, bool $TieneGrupoDestino, bool $UsaProgramas = false): string {
    [$Fila, $Data] = SgceImportacionPrimeraFilaDatos($Filas, 'EsEncabezadoAlumno');
    if (!$Data) { return 'El archivo de alumnos no contiene registros para importar. Usa el formato requerido: NOMBRE COMPLETO, AÑO, GRUPO, TURNO.'; }
    if ($TieneGrupoDestino) {
        if (trim((string)($Data[0] ?? '')) === '') { return 'El archivo de alumnos debe traer el NOMBRE COMPLETO en la primera columna. Revisa la fila ' . $Fila . '.'; }
        return '';
    }
    $Minimo = $UsaProgramas ? 5 : 4;
    $Formato = $UsaProgramas ? 'NOMBRE COMPLETO, AÑO, GRUPO, TURNO, PROGRAMA' : 'NOMBRE COMPLETO, AÑO, GRUPO, TURNO';
    if (SgceImportacionColumnasConDato($Data) < $Minimo) { return 'Selecciona un grupo destino o usa un archivo de alumnos con formato: ' . $Formato . '. Revisa la fila ' . $Fila . '.'; }
    return '';
}
