<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceGuardarReporteImportacionFinal(string $Tipo, array $Resumen, array $Errores): void {
    SgceImportacionReporteGuardar($Tipo, $Resumen, $Errores);
}

