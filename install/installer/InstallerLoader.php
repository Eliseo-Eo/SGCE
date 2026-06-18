<?php
if (!defined('SGCE_INSTALLER')) { http_response_code(403); exit('Acceso directo no permitido.'); }

$SgceInstallerFiles = [
    'InstallerCore.php',
    'InstallerSqlText.php',
    'InstallerDatabase.php',
    'InstallerValidation.php',
    'InstallerAcademic.php',
    'InstallerRuntime.php',
];
foreach ($SgceInstallerFiles as $SgceInstallerFile) {
    require_once __DIR__ . '/' . $SgceInstallerFile;
}
