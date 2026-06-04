<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

function DetectarDelimitadorCsv($RutaArchivo) {
    $Linea = (string)@file_get_contents($RutaArchivo, false, null, 0, 4096);
    $Conteos = [
        ',' => substr_count($Linea, ','),
        ';' => substr_count($Linea, ';'),
        "	" => substr_count($Linea, "	"),
    ];
    arsort($Conteos);
    $Delimitador = (string)array_key_first($Conteos);
    return ($Conteos[$Delimitador] ?? 0) > 0 ? $Delimitador : ',';
}

function LeerFilasCsv($RutaArchivo) {
    $Handle = fopen($RutaArchivo, 'r');
    if (!$Handle) {
        throw new RuntimeException('No se pudo leer el archivo CSV.');
    }

    $Delimitador = DetectarDelimitadorCsv($RutaArchivo);
    BomStrip($Handle);
    $Filas = [];
    while (($Data = fgetcsv($Handle, 8000, $Delimitador)) !== false) {
        $Filas[] = $Data;
    }
    fclose($Handle);
    return $Filas;
}

