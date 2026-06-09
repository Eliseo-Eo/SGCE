<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

$SgceImportacionFiles = [
    'importacion/SGCE_ImportacionCsv.php',
    'importacion/SGCE_ImportacionXlsx.php',
    'importacion/SGCE_ImportacionArchivos.php',
    'importacion/SGCE_ImportacionValidadores.php',
    'importacion/SGCE_ImportacionPrevia.php',
    'importacion/SGCE_ImportacionDocentes.php',
    'importacion/SGCE_ImportacionReportesFinales.php',
];
foreach ($SgceImportacionFiles as $ArchivoImportacion) { require_once __DIR__ . '/' . $ArchivoImportacion; }
