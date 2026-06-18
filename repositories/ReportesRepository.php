<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }


function SgceRepoValidarRangoReporte(string $Inicio, string $Fin, int $MaxDias = 370): bool {
    try {
        $D1 = new DateTime($Inicio);
        $D2 = new DateTime($Fin);
        if ($D2 < $D1) { return false; }
        return ((int)$D1->diff($D2)->format('%a')) <= $MaxDias;
    } catch (Exception $E) { return false; }
}
