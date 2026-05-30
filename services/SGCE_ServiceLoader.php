<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

$SgceServiceFiles = [
    __DIR__ . '/AlumnoService.php',
    __DIR__ . '/MaestroService.php',
    __DIR__ . '/GrupoService.php',
    __DIR__ . '/AsistenciaService.php',
    __DIR__ . '/CalificacionService.php',
    __DIR__ . '/ReporteService.php',
    __DIR__ . '/UsuarioService.php',
];

foreach ($SgceServiceFiles as $SgceServiceFile) {
    if (is_file($SgceServiceFile)) {
        require_once $SgceServiceFile;
    }
}
