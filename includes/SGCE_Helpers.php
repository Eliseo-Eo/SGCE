<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

$SgceHelperFiles = [
    'SGCE_Texto.php',
    'SGCE_Seguridad.php',
    'SGCE_BaseDatos.php',
    'SGCE_Configuracion.php',
    'SGCE_UI.php',
    'SGCE_Layout.php',
    'SGCE_Archivos.php',
    'SGCE_Bitacora.php',
    'SGCE_PlaneacionesHelper.php',
    'SGCE_Academico.php',
    'SGCE_AcademicoMaterias.php',
    'SGCE_AcademicoAsignaciones.php',
    'SGCE_AcademicoKardex.php',
    'SGCE_AcademicoMigracion.php',
];
foreach ($SgceHelperFiles as $SgceHelperFile) { require_once __DIR__ . '/' . $SgceHelperFile; }
