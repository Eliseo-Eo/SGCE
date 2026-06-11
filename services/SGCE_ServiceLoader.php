<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

$SgceRepositoryLoader = dirname(__DIR__) . '/repositories/SGCE_RepositoryLoader.php';
if (is_file($SgceRepositoryLoader)) { require_once $SgceRepositoryLoader; }

$SgceServiceFiles = [
    __DIR__ . '/AlumnoService.php',
    __DIR__ . '/MaestroService.php',
    __DIR__ . '/GrupoService.php',
    __DIR__ . '/AsistenciaService.php',
    __DIR__ . '/CalificacionService.php',
    __DIR__ . '/ReporteService.php',
    __DIR__ . '/migracion/MigracionService.php',
    __DIR__ . '/admin/AvisosAdminService.php',
    __DIR__ . '/admin/UsuariosAdminService.php',
    __DIR__ . '/admin/ConfiguracionAdminService.php',
];

foreach ($SgceServiceFiles as $SgceServiceFile) {
    if (is_file($SgceServiceFile)) {
        require_once $SgceServiceFile;
    }
}
