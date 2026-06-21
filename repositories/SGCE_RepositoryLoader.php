<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

$SgceRepositoryFiles = [
    __DIR__ . '/AlumnosRepository.php',
    __DIR__ . '/MateriasRepository.php',
    __DIR__ . '/AsignacionesRepository.php',
    __DIR__ . '/BitacoraRepository.php',
    __DIR__ . '/ReportesRepository.php',
    __DIR__ . '/AsistenciaRepository.php',
    __DIR__ . '/CalificacionRepository.php',
    __DIR__ . '/ConductaRepository.php',
];

foreach ($SgceRepositoryFiles as $SgceRepositoryFile) {
    if (is_file($SgceRepositoryFile)) { require_once $SgceRepositoryFile; }
}
