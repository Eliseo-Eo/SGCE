<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }


function SgceRepoValidarRangoReporte(string $Inicio, string $Fin, int $MaxDias = 370): bool {
    return function_exists('SgceValidarRangoFechaYmd')
        ? SgceValidarRangoFechaYmd($Inicio, $Fin, $MaxDias)
        : false;
}
